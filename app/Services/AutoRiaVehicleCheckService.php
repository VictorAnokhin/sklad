<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AutoRiaVehicleCheckService
{
    private const CYRILLIC_MAP = [
        'А' => 'A',
        'В' => 'B',
        'Е' => 'E',
        'І' => 'I',
        'К' => 'K',
        'М' => 'M',
        'Н' => 'H',
        'О' => 'O',
        'Р' => 'P',
        'С' => 'C',
        'Т' => 'T',
        'Х' => 'X',
    ];

    public function normalizeVehicleInfo(string $value): string
    {
        $normalized = Str::upper(trim($value));
        $normalized = strtr($normalized, self::CYRILLIC_MAP);

        return preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';
    }

    public function detectInputType(string $normalized): ?string
    {
        if (preg_match('/^[0-9A-HJ-NPR-Z]{17}$/', $normalized) === 1) {
            return 'vin';
        }

        if (strlen($normalized) === 8) {
            return 'plate';
        }

        return null;
    }

    public function check(string $vehicleInfo, int $langId = 4): array
    {
        $normalized = $this->normalizeVehicleInfo($vehicleInfo);
        $type = $this->detectInputType($normalized);

        if ($normalized === '' || $type === null) {
            throw new InvalidArgumentException('Вкажіть коректний номер авто або VIN.');
        }

        $cacheTtl = max(0, (int) config('services.autoria.cache_ttl', 1800));
        $cacheKey = sprintf('autoria_vehicle_check:%d:%s', $langId, $normalized);

        $request = function () use ($normalized, $langId): array {
            $path = str_replace(
                '{vehicleInfo}',
                rawurlencode($normalized),
                (string) config('services.autoria.vehicle_check_path', '/cars-verifyings/api/vin-verifyings/{vehicleInfo}')
            );
            $baseUrl = rtrim((string) config('services.autoria.base_url', 'https://auto.ria.com'), '/');
            $timeout = max(1, (int) config('services.autoria.timeout', 15));

            $response = Http::acceptJson()
                ->timeout($timeout)
                ->connectTimeout(5)
                ->get($baseUrl . '/' . ltrim($path, '/'), [
                    'langId' => $langId,
                ]);

            return $this->formatResponse($response);
        };

        $result = $cacheTtl > 0 ? Cache::get($cacheKey) : null;

        if (!is_array($result)) {
            $result = $request();

            if ($cacheTtl > 0 && ($result['success'] ?? false) === true) {
                Cache::put($cacheKey, $result, $cacheTtl);
            }
        }

        return [
            ...$result,
            'vehicle_info' => $normalized,
            'input_type' => $type,
        ];
    }

    public function extractSnapshot(array $payload, string $normalized, string $inputType): array
    {
        $auto = data_get($payload, 'auto', []);
        $infotech = data_get($payload, 'verifyings.infotech', []);
        $title = $this->firstString([
            data_get($auto, 'title'),
            data_get($auto, 'name'),
            data_get($auto, 'modelName'),
            data_get($payload, 'title'),
            $normalized,
        ]);

        $vin = $inputType === 'vin'
            ? $normalized
            : $this->firstString([
                data_get($auto, 'vin'),
                data_get($infotech, 'vin'),
                data_get($payload, 'vin'),
            ]);

        $plate = $inputType === 'plate'
            ? $normalized
            : $this->firstString([
                data_get($auto, 'plate'),
                data_get($auto, 'number'),
                data_get($auto, 'stateNumber'),
                data_get($infotech, 'plate'),
            ]);

        return [
            'vehicle_number' => $plate,
            'vin' => $vin,
            'title' => $title,
            'photo_url' => $this->firstString([
                data_get($auto, 'mainPhoto'),
                data_get($auto, 'photo'),
                data_get($auto, 'photoData.seoLinkF'),
            ]),
            'adv_link' => $this->firstString([
                data_get($payload, 'advLink'),
                data_get($auto, 'advLink'),
            ]),
            'characteristics' => [
                'auto' => $auto,
                'infotech' => $infotech,
                'verifyings' => data_get($payload, 'verifyings', []),
                'paid_checks' => data_get($payload, 'paidChecks', []),
            ],
        ];
    }

    private function formatResponse(Response $response): array
    {
        $payload = $response->json();

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'rate_limit' => [
                'limit' => $response->header('x-ratelimit-limit'),
                'remaining' => $response->header('x-ratelimit-remaining'),
                'reset' => $response->header('x-ratelimit-reset'),
            ],
            'data' => $payload ?? $response->body(),
        ];
    }

    private function firstString(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
