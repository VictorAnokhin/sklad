<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Chainalysis KYT screening for incoming crypto (USDT).
 *
 * @see https://docs.chainalysis.com/api/kyt/
 */
class ChainalysisService
{
    /** @var list<string> */
    private array $blockedLevels;

    public function __construct()
    {
        $configured = config('services.chainalysis.blocked_risk_levels', ['HIGH', 'SEVERE', 'CRITICAL']);
        $this->blockedLevels = array_values(array_map(
            static fn (string $level): string => strtoupper(trim($level)),
            is_array($configured) ? $configured : explode(',', (string) $configured),
        ));
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.chainalysis.enabled', true);
    }

    /**
     * @return array{
     *   allowed: bool,
     *   risk_level: string,
     *   asset: string,
     *   address: string,
     *   reason: string,
     *   transfer_reference?: string|null,
     *   provider: string,
     *   raw?: array<string, mixed>|null
     * }
     */
    public function screenIncomingCrypto(
        string $address,
        string $asset,
        string $network,
        ?string $amount = null,
        ?int $userId = null,
    ): array {
        $asset = strtoupper(trim($asset));
        $address = trim($address);
        $network = strtolower(trim($network));

        if ($asset !== 'USDT') {
            return $this->buildResult(true, 'LOW', $asset, $address, 'non_usdt_skipped');
        }

        if ($address === '') {
            return $this->buildResult(false, 'HIGH', $asset, $address, 'missing_address');
        }

        if (! $this->isEnabled()) {
            return $this->buildResult(true, 'LOW', $asset, $address, 'screening_disabled');
        }

        $cacheKey = 'chainalysis:'.hash('sha256', strtolower($address).'|'.$asset.'|'.$network);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $apiKey = trim((string) config('services.chainalysis.api_key', ''));
        if ($apiKey === '') {
            $result = $this->screenInMockMode($address, $asset, $network);
            Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes()));

            return $result;
        }

        try {
            $result = $this->screenViaKyt($apiKey, $address, $asset, $network, $amount, $userId);
            Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes()));

            return $result;
        } catch (Throwable $exception) {
            Log::error('Chainalysis screening failed.', [
                'address' => $address,
                'asset' => $asset,
                'network' => $network,
                'error' => $exception->getMessage(),
            ]);

            if ((bool) config('services.chainalysis.fail_open', false)) {
                return $this->buildResult(true, 'LOW', $asset, $address, 'fail_open_error');
            }

            return $this->buildResult(false, 'HIGH', $asset, $address, 'screening_error');
        }
    }

    /**
     * @return array{
     *   allowed: bool,
     *   risk_level: string,
     *   asset: string,
     *   address: string,
     *   reason: string,
     *   transfer_reference?: string|null,
     *   provider: string,
     *   raw?: array<string, mixed>|null
     * }
     */
    private function screenViaKyt(
        string $apiKey,
        string $address,
        string $asset,
        string $network,
        ?string $amount,
        ?int $userId,
    ): array {
        $baseUrl = rtrim((string) config('services.chainalysis.base_url', 'https://api.chainalysis.com/api/kyt/v2'), '/');
        $externalUserId = $userId !== null
            ? 'user_'.$userId
            : 'wallet_'.substr(hash('sha256', strtolower($address)), 0, 20);
        $transferReference = (string) Str::uuid();

        Http::withHeaders([
            'Token' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/users/{$externalUserId}", [])->throw();

        $payload = [
            'network' => $this->mapNetwork($network),
            'asset' => $asset,
            'direction' => 'received',
            'transferReference' => $transferReference,
            'assetAmount' => max(0.01, (float) ($amount ?? '0.01')),
            'outputAddress' => $this->platformDepositAddress($address),
            'inputAddresses' => [$address],
        ];

        $registerResponse = Http::withHeaders([
            'Token' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/users/{$externalUserId}/transfers", $payload);

        if (! $registerResponse->successful()) {
            throw new \RuntimeException('Chainalysis transfer registration failed: '.$registerResponse->body());
        }

        $registerJson = $registerResponse->json();
        $riskLevel = $this->extractRiskLevel(is_array($registerJson) ? $registerJson : []);

        $alertsResponse = Http::withHeaders([
            'Token' => $apiKey,
            'Accept' => 'application/json',
        ])->get("{$baseUrl}/transfers/{$transferReference}/alerts");

        if ($alertsResponse->successful()) {
            $alertsJson = $alertsResponse->json();
            $riskLevel = $this->maxRiskLevel($riskLevel, $this->extractRiskFromAlerts(is_array($alertsJson) ? $alertsJson : []));
        }

        $allowed = ! in_array(strtoupper($riskLevel), $this->blockedLevels, true);

        return $this->buildResult(
            $allowed,
            strtoupper($riskLevel),
            $asset,
            $address,
            $allowed ? 'approved' : 'high_risk_blocked',
            $transferReference,
            [
                'register' => is_array($registerJson) ? $registerJson : null,
                'alerts' => $alertsResponse->successful() ? $alertsResponse->json() : null,
            ],
        );
    }

    /**
     * @return array{
     *   allowed: bool,
     *   risk_level: string,
     *   asset: string,
     *   address: string,
     *   reason: string,
     *   transfer_reference?: string|null,
     *   provider: string,
     *   raw?: array<string, mixed>|null
     * }
     */
    private function screenInMockMode(string $address, string $asset, string $network): array
    {
        if (! (bool) config('services.chainalysis.mock_mode', true)) {
            if ((bool) config('services.chainalysis.fail_open', false)) {
                return $this->buildResult(true, 'LOW', $asset, $address, 'fail_open_no_key');
            }

            return $this->buildResult(false, 'HIGH', $asset, $address, 'chainalysis_not_configured');
        }

        $blocklist = array_map(
            static fn (string $value): string => strtolower(trim($value)),
            array_filter(explode(',', (string) config('services.chainalysis.mock_blocklist', ''))),
        );

        if (in_array(strtolower($address), $blocklist, true)) {
            return $this->buildResult(false, 'HIGH', $asset, $address, 'mock_blocklist', null, [
                'mock' => true,
                'network' => $network,
            ]);
        }

        if (str_contains(strtolower($address), 'highrisk')) {
            return $this->buildResult(false, 'SEVERE', $asset, $address, 'mock_high_risk_pattern', null, [
                'mock' => true,
                'network' => $network,
            ]);
        }

        return $this->buildResult(true, 'LOW', $asset, $address, 'mock_approved', null, [
            'mock' => true,
            'network' => $network,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractRiskLevel(array $payload): string
    {
        foreach (['riskLevel', 'risk_level', 'risk', 'rating'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $payload[$key] !== '') {
                return strtoupper($payload[$key]);
            }
        }

        if (isset($payload['updatedAt']) || isset($payload['externalId'])) {
            return 'LOW';
        }

        return 'MEDIUM';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractRiskFromAlerts(array $payload): string
    {
        $alerts = $payload['alerts'] ?? $payload['data'] ?? $payload;
        if (! is_array($alerts)) {
            return 'LOW';
        }

        $max = 'LOW';
        foreach ($alerts as $alert) {
            if (! is_array($alert)) {
                continue;
            }

            $level = null;
            foreach (['alertLevel', 'riskLevel', 'level', 'severity'] as $key) {
                if (isset($alert[$key]) && is_string($alert[$key])) {
                    $level = strtoupper($alert[$key]);
                    break;
                }
            }

            if ($level !== null) {
                $max = $this->maxRiskLevel($max, $level);
            }
        }

        return $max;
    }

    private function maxRiskLevel(string $left, string $right): string
    {
        $order = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'SEVERE' => 4, 'CRITICAL' => 5];

        return ($order[strtoupper($right)] ?? 0) >= ($order[strtoupper($left)] ?? 0)
            ? strtoupper($right)
            : strtoupper($left);
    }

    private function mapNetwork(string $network): string
    {
        return match ($network) {
            'sui' => 'Sui',
            'ethereum', 'evm' => 'Ethereum',
            'solana' => 'Solana',
            'tron' => 'Tron',
            'polygon' => 'Polygon',
            'bsc', 'binance' => 'Binance_Smart_Chain',
            default => ucfirst($network),
        };
    }

    private function platformDepositAddress(string $fallback): string
    {
        $configured = trim((string) config('services.chainalysis.platform_deposit_address', ''));

        return $configured !== '' ? $configured : $fallback;
    }

    private function cacheMinutes(): int
    {
        return max(1, (int) config('services.chainalysis.cache_minutes', 15));
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{
     *   allowed: bool,
     *   risk_level: string,
     *   asset: string,
     *   address: string,
     *   reason: string,
     *   transfer_reference?: string|null,
     *   provider: string,
     *   raw?: array<string, mixed>|null
     * }
     */
    private function buildResult(
        bool $allowed,
        string $riskLevel,
        string $asset,
        string $address,
        string $reason,
        ?string $transferReference = null,
        ?array $raw = null,
    ): array {
        return [
            'allowed' => $allowed,
            'risk_level' => strtoupper($riskLevel),
            'asset' => strtoupper($asset),
            'address' => $address,
            'reason' => $reason,
            'transfer_reference' => $transferReference,
            'provider' => 'chainalysis',
            'raw' => $raw,
        ];
    }
}
