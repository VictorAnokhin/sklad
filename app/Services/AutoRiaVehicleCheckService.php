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
        $rawTitle = $this->firstString([
            data_get($auto, 'title'),
            data_get($auto, 'name'),
            data_get($payload, 'title'),
            data_get($payload, 'name'),
            ...$this->valuesForKeys($payload, ['title', 'name', 'autoTitle', 'autoName']),
        ]);
        $brand = $this->firstString([
            data_get($auto, 'markName'),
            data_get($auto, 'brand'),
            data_get($auto, 'make'),
            data_get($auto, 'mark.name'),
            data_get($auto, 'autoData.markName'),
            data_get($auto, 'autoData.brand'),
            data_get($auto, 'autoData.make'),
            data_get($infotech, 'markName'),
            data_get($infotech, 'brand'),
            data_get($infotech, 'make'),
            data_get($payload, 'markName'),
            data_get($payload, 'brand'),
            data_get($payload, 'make'),
            ...$this->valuesForKeys($payload, [
                'markName',
                'mark_name',
                'marka',
                'mark',
                'brandName',
                'brand_name',
                'brand',
                'makeName',
                'make_name',
                'make',
                'manufacturer',
            ]),
            ...$this->valuesForNamedObjects($payload, ['mark', 'brand', 'make', 'manufacturer']),
        ]);
        $model = $this->firstString([
            data_get($auto, 'modelName'),
            data_get($auto, 'model'),
            data_get($auto, 'model.name'),
            data_get($auto, 'autoData.modelName'),
            data_get($auto, 'autoData.model'),
            data_get($infotech, 'modelName'),
            data_get($infotech, 'model'),
            data_get($payload, 'modelName'),
            data_get($payload, 'model'),
            ...$this->valuesForKeys($payload, [
                'modelName',
                'model_name',
                'model',
                'modelTitle',
                'model_title',
            ]),
            ...$this->valuesForNamedObjects($payload, ['model']),
        ]);

        if (($brand === null || $model === null) && $rawTitle !== null) {
            [$parsedBrand, $parsedModel] = $this->parseBrandModelFromTitle($rawTitle);
            $brand = $brand ?: $parsedBrand;
            $model = $model ?: $parsedModel;
        }

        $color = $this->firstString([
            data_get($auto, 'color.name'),
            data_get($auto, 'colorName'),
            data_get($auto, 'color'),
            data_get($infotech, 'color.name'),
            data_get($infotech, 'colorName'),
            data_get($infotech, 'color'),
            data_get($payload, 'colorName'),
            data_get($payload, 'color'),
        ]);
        $year = $this->firstInt([
            data_get($auto, 'year'),
            data_get($auto, 'autoData.year'),
            data_get($auto, 'yearOfProduction'),
            data_get($infotech, 'year'),
            data_get($infotech, 'yearOfProduction'),
            data_get($payload, 'year'),
        ]);
        $price = $this->firstNumber([
            data_get($auto, 'USD'),
            data_get($auto, 'price'),
            data_get($auto, 'priceData.USD'),
            data_get($auto, 'priceData.price'),
            data_get($payload, 'USD'),
            data_get($payload, 'price'),
        ]);
        $description = $this->firstString([
            data_get($auto, 'description'),
            data_get($auto, 'descriptionAuto'),
            data_get($payload, 'description'),
        ]);
        $title = $this->firstString([
            trim(implode(' ', array_filter([$brand, $model]))),
            $rawTitle,
            data_get($auto, 'modelName'),
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
            'vehicle_price' => $price,
            'characteristics' => [
                'brand' => $brand,
                'model' => $model,
                'color' => $color,
                'year' => $year,
                'description' => $description,
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
            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function firstInt(array $values): ?int
    {
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $int = (int) $value;
                if ($int > 0) {
                    return $int;
                }
            }
        }

        return null;
    }

    private function firstNumber(array $values): ?float
    {
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $number = (float) $value;
                if ($number >= 0) {
                    return $number;
                }
            }
        }

        return null;
    }

    private function valuesForKeys(array $payload, array $keys): array
    {
        $matches = [];
        $wanted = array_map(fn (string $key): string => $this->normalizeKey($key), $keys);

        $walk = function (mixed $value) use (&$walk, &$matches, $wanted): void {
            if (!is_array($value)) {
                return;
            }

            foreach ($value as $key => $item) {
                if (is_string($key) && in_array($this->normalizeKey($key), $wanted, true) && is_string($item)) {
                    $matches[] = $item;
                }

                if (is_array($item)) {
                    $walk($item);
                }
            }
        };

        $walk($payload);

        return $matches;
    }

    private function valuesForNamedObjects(array $payload, array $keys): array
    {
        $matches = [];
        $wanted = array_map(fn (string $key): string => $this->normalizeKey($key), $keys);
        $nameKeys = ['name', 'title', 'value'];

        $walk = function (mixed $value) use (&$walk, &$matches, $wanted, $nameKeys): void {
            if (!is_array($value)) {
                return;
            }

            foreach ($value as $key => $item) {
                if (is_string($key) && in_array($this->normalizeKey($key), $wanted, true) && is_array($item)) {
                    foreach ($nameKeys as $nameKey) {
                        if (isset($item[$nameKey]) && is_string($item[$nameKey]) && trim($item[$nameKey]) !== '') {
                            $matches[] = trim($item[$nameKey]);
                            break;
                        }
                    }
                }

                if (is_array($item)) {
                    $walk($item);
                }
            }
        };

        $walk($payload);

        return $matches;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $key) ?? '');
    }

    private function parseBrandModelFromTitle(string $title): array
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
        if ($title === '') {
            return [null, null];
        }

        $tokens = array_values(array_filter(explode(' ', $title), function (string $token): bool {
            return !preg_match('/^(19|20)\d{2}$/', $token);
        }));

        return [
            $tokens[0] ?? null,
            $tokens[1] ?? null,
        ];
    }
}
