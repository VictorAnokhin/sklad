<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SmsClubService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function sendOtp(string $phone, string $message): array
    {
        $token = $this->apiToken();
        $sender = $this->sender();
        $endpoint = trim((string) config('services.smsclub.endpoint', '')) ?: 'https://im.smsclub.mobi/sms/send';

        if ($token === '') {
            throw new RuntimeException('SMS Club token is not configured.');
        }

        if ($sender === '') {
            throw new RuntimeException('SMS Club sender is not configured.');
        }

        $response = $this->http
            ->timeout(10)
            ->withToken($token)
            ->acceptJson()
            ->post($endpoint, [
                'phone' => [$phone],
                'message' => $message,
                'src_addr' => $sender,
            ]);

        return $this->parseResponse($response);
    }

    public function balance(?string $token = null): array
    {
        $token = trim((string) ($token ?? $this->apiToken()));
        $endpoint = trim((string) config('services.smsclub.balance_endpoint', '')) ?: 'https://im.smsclub.mobi/sms/balance';

        if ($token === '') {
            throw new RuntimeException('SMS Club token is not configured.');
        }

        $response = $this->http
            ->timeout(10)
            ->withToken($token)
            ->acceptJson()
            ->get($endpoint);

        return $this->parseBalanceResponse($response);
    }

    private function apiToken(): string
    {
        return $this->settingValue('api_key', trim((string) config('services.smsclub.token', '')));
    }

    private function sender(): string
    {
        return $this->settingValue('sender', trim((string) config('services.smsclub.sender', '')) ?: 'av8fund');
    }

    private function settingValue(string $name, string $fallback): string
    {
        if (!Schema::hasTable('conf') || !Schema::hasColumn('conf', 'constanta')) {
            return $fallback;
        }

        $fid = (string) session('fid', '');
        $query = DB::table('conf')
            ->where('type', 'smsclub')
            ->where('name', $name);

        $row = (clone $query)
            ->where('firma', $fid !== '' ? $fid : '0')
            ->first();

        if (!$row) {
            $row = (clone $query)->where('firma', 0)->first();
        }

        $token = trim((string) ($row->constanta ?? ''));

        return $token !== '' ? $token : $fallback;
    }

    private function parseResponse(Response $response): array
    {
        $payload = $response->json();

        if (!$response->successful()) {
            $message = $this->errorMessageFromPayload($payload);
            Log::warning('SMS Club send request failed.', [
                'status' => $response->status(),
                'payload' => is_array($payload) ? $payload : null,
                'body' => is_array($payload) ? null : mb_substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException($message !== '' ? $message : 'SMS Club request failed. HTTP '.$response->status());
        }

        if (!is_array($payload)) {
            throw new RuntimeException('SMS Club returned an invalid payload.');
        }

        if (!isset($payload['success_request'])) {
            $message = $this->errorMessageFromPayload($payload);
            Log::warning('SMS Club send request was not confirmed.', [
                'status' => $response->status(),
                'payload' => $payload,
            ]);

            throw new RuntimeException($message !== '' ? $message : 'SMS Club did not confirm the request.');
        }

        return $payload;
    }

    private function parseBalanceResponse(Response $response): array
    {
        $payload = $response->json();

        if (!$response->successful()) {
            $message = $this->errorMessageFromPayload($payload);

            throw new RuntimeException($message !== '' ? $message : 'SMS Club balance request failed. HTTP '.$response->status());
        }

        if (!is_array($payload)) {
            throw new RuntimeException('SMS Club returned an invalid balance payload.');
        }

        if (isset($payload['error_request'])) {
            $message = $this->errorMessageFromPayload($payload);
            throw new RuntimeException($message !== '' ? $message : 'SMS Club did not return balance.');
        }

        return $payload;
    }

    private function errorMessageFromPayload(mixed $payload): string
    {
        if (!is_array($payload)) {
            return '';
        }

        $candidates = [
            $payload['error_request']['message'] ?? null,
            $payload['error_request']['error'] ?? null,
            $payload['error_request'] ?? null,
            $payload['message'] ?? null,
            $payload['error'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate)) {
                $message = trim((string) $candidate);
                if ($message !== '') {
                    return $message;
                }
            }
        }

        return '';
    }
}
