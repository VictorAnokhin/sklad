<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SuiDefiProtocolService
{
    private const CACHE_TTL_SECONDS = 300;

    public function loadProtocols(string $address, bool $refresh = false): array
    {
        $normalizedAddress = $this->normalizeSuiAddress($address);
        if ($normalizedAddress === '') {
            throw new RuntimeException('Sui wallet address is required.');
        }

        $cacheKey = 'sui:defi-protocols:' . $normalizedAddress;
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($normalizedAddress) {
            try {
                return $this->mapOwnedObjects($this->fetchOwnedObjects($normalizedAddress));
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'sui_defi' => [
                        'name' => 'Sui DeFi',
                        'url' => null,
                        'icon' => null,
                        'available' => false,
                        'error' => $exception->getMessage() ?: 'Failed to load Sui DeFi positions.',
                        'tokens' => [],
                        'loans' => [],
                        'pools' => [],
                    ],
                ];
            }
        });
    }

    private function fetchOwnedObjects(string $address): array
    {
        $objects = [];
        $cursor = null;

        do {
            $result = $this->rpc('suix_getOwnedObjects', [
                $address,
                [
                    'options' => [
                        'showType' => true,
                        'showContent' => true,
                        'showDisplay' => true,
                        'showOwner' => true,
                    ],
                ],
                $cursor,
                50,
            ]);

            foreach ((array) data_get($result, 'data', []) as $item) {
                $data = (array) data_get($item, 'data', []);
                if ($data !== []) {
                    $objects[] = $data;
                }
            }

            $cursor = data_get($result, 'nextCursor');
        } while ((bool) data_get($result, 'hasNextPage', false) && $cursor);

        return $objects;
    }

    private function mapOwnedObjects(array $objects): array
    {
        $protocols = [
            'suilend' => $this->emptyProtocol('Suilend', 'https://suilend.fi'),
            'navi' => $this->emptyProtocol('NAVI Protocol', 'https://app.naviprotocol.io'),
        ];

        foreach ($objects as $object) {
            $type = (string) data_get($object, 'type', '');
            $protocolKey = $this->protocolKeyForType($type);
            if ($protocolKey === null) {
                continue;
            }

            $mapped = $this->mapObjectPosition($object, $protocolKey);
            $protocols[$protocolKey][$mapped['bucket']][] = $mapped['item'];
        }

        return collect($protocols)
            ->filter(fn (array $protocol) => $protocol['tokens'] !== [] || $protocol['loans'] !== [] || $protocol['pools'] !== [])
            ->all();
    }

    private function emptyProtocol(string $name, string $url): array
    {
        return [
            'name' => $name,
            'url' => $url,
            'icon' => null,
            'available' => true,
            'error' => null,
            'tokens' => [],
            'loans' => [],
            'pools' => [],
        ];
    }

    private function mapObjectPosition(array $object, string $protocolKey): array
    {
        $type = (string) data_get($object, 'type', '');
        $typeLower = strtolower($type);
        $objectId = (string) data_get($object, 'objectId', '');
        $fields = (array) data_get($object, 'content.fields', []);
        $coinType = $this->coinTypeFromObjectType($type);
        $symbol = $this->symbolFromType($coinType ?: $type);
        $name = $this->positionName($protocolKey, $type, $symbol);
        $balance = $this->numericField($fields, ['balance', 'value', 'amount', 'deposited_amount', 'supply_amount']);

        $baseMeta = [
            'chain' => 'sui',
            'position_type' => str_contains($typeLower, 'coin::coin<') ? 'deposit' : 'owned_object',
            'protocol_module' => $this->moduleFromType($type),
            'object_id' => $objectId,
            'object_type' => $type,
            'link' => $protocolKey === 'suilend' ? 'https://suilend.fi' : 'https://app.naviprotocol.io',
        ];

        $bucket = str_contains($typeLower, 'accountcap')
            || str_contains($typeLower, 'obligation')
            || str_contains($typeLower, 'strategyownercap')
            ? 'pools'
            : 'tokens';

        return [
            'bucket' => $bucket,
            'item' => [
                'name' => $name,
                'symbol' => $symbol,
                'balance' => $balance,
                'usd_value' => 0,
                'apy' => null,
                'collateral' => $bucket === 'tokens',
                'tvl_usd' => 0,
                'total_liquidity' => $bucket === 'pools' ? $balance : null,
                'total_borrowed' => null,
                ...$baseMeta,
            ],
        ];
    }

    private function protocolKeyForType(string $type): ?string
    {
        $typeLower = strtolower($type);

        foreach (['suilend', 'navi'] as $protocol) {
            foreach ($this->typeMarkers($protocol) as $marker) {
                if ($marker !== '' && str_contains($typeLower, $marker)) {
                    return $protocol;
                }
            }
        }

        return null;
    }

    private function typeMarkers(string $protocol): array
    {
        $raw = (string) config("services.sui.defi_protocols.$protocol.type_markers", '');

        return array_values(array_filter(array_map(
            fn (string $value) => strtolower(trim($value)),
            explode(',', $raw)
        )));
    }

    private function coinTypeFromObjectType(string $type): ?string
    {
        if (! preg_match('/^0x2::coin::Coin<(.+)>$/', $type, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function symbolFromType(string $type): string
    {
        if (preg_match('/::([A-Za-z_][A-Za-z0-9_]*)>?$/', $type, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'POSITION';
    }

    private function moduleFromType(string $type): string
    {
        if (preg_match('/^0x[a-fA-F0-9]+::([^:]+)::/', $type, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function positionName(string $protocolKey, string $type, string $symbol): string
    {
        $protocol = $protocolKey === 'suilend' ? 'Suilend' : 'NAVI';

        if (str_contains(strtolower($type), 'accountcap')) {
            return $protocol . ' Account Cap';
        }

        if (str_contains(strtolower($type), 'obligation')) {
            return $protocol . ' Obligation';
        }

        if (str_contains(strtolower($type), 'strategyownercap')) {
            return $protocol . ' StrategyOwnerCap';
        }

        return trim($protocol . ' ' . $symbol);
    }

    private function numericField(array $fields, array $keys): float
    {
        foreach ($keys as $key) {
            $value = data_get($fields, $key);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 1.0;
    }

    private function rpc(string $method, array $params): mixed
    {
        $rpc = trim((string) config('services.sui.rpc_url', ''))
            ?: trim((string) config('services.sui.mainnet_rpc_url', 'https://fullnode.mainnet.sui.io:443'));

        $response = Http::timeout(15)->post($rpc, [
            'jsonrpc' => '2.0',
            'id' => Str::uuid()->toString(),
            'method' => $method,
            'params' => $params,
        ]);

        $payload = $response->throw()->json();
        if (data_get($payload, 'error.message')) {
            throw new RuntimeException((string) data_get($payload, 'error.message'));
        }

        return data_get($payload, 'result');
    }

    private function normalizeSuiAddress(string $address): string
    {
        $normalized = strtolower(trim($address));

        return preg_match('/^0x[a-f0-9]{1,64}$/', $normalized) ? $normalized : '';
    }
}
