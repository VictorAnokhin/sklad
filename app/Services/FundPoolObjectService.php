<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FundPoolObjectService
{
    /**
     * @return array<string, mixed>
     */
    public function fetchObject(string $rpcUrl, string $objectId): array
    {
        $normalizedId = $this->normalizeObjectId($objectId);
        if ($normalizedId === '') {
            throw new \InvalidArgumentException('Object id is empty.');
        }

        $result = $this->rpc($rpcUrl, 'sui_getObject', [
            $normalizedId,
            ['showType' => true, 'showContent' => true],
        ]);

        $data = is_array($result['data'] ?? null) ? $result['data'] : null;
        if ($data === null) {
            throw new \RuntimeException("Sui object not found: {$normalizedId}");
        }

        return $data;
    }

    public function fetchObjectType(string $rpcUrl, string $objectId): ?string
    {
        try {
            $data = $this->fetchObject($rpcUrl, $objectId);
        } catch (\Throwable) {
            return null;
        }

        $type = $data['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : null;
    }

    public function fetchObjectPackageId(string $rpcUrl, string $objectId): ?string
    {
        $type = $this->fetchObjectType($rpcUrl, $objectId);

        return $type ? $this->extractPackageIdFromStructType($type) : null;
    }

    public function extractPackageIdFromStructType(string $structType): ?string
    {
        $structType = trim($structType);
        if ($structType === '') {
            return null;
        }

        if (! preg_match('/^(0x[a-f0-9]+)::/i', $structType, $matches)) {
            return null;
        }

        return $this->normalizeObjectId($matches[1]);
    }

    /**
     * @param  object  $row
     * @return array{
     *     id:int,
     *     name:string,
     *     network:string,
     *     db_package_id:string,
     *     expected_package_id:string,
     *     pool_object_id:string,
     *     pool_type:?string,
     *     pool_package_id:?string,
     *     pool_accounting_id:string,
     *     accounting_type:?string,
     *     accounting_package_id:?string,
     *     status:string,
     *     issues:array<int, string>
     * }
     */
    public function auditPoolRow(string $rpcUrl, object $row, string $expectedPackageId): array
    {
        $expectedPackageId = $this->normalizeObjectId($expectedPackageId);
        $poolObjectId = $this->normalizeObjectId((string) ($row->pool_object_id ?? ''));
        $accountingObjectId = $this->normalizeObjectId((string) ($row->pool_accounting_id ?? ''));
        $issues = [];

        $poolType = $poolObjectId !== '' ? $this->fetchObjectType($rpcUrl, $poolObjectId) : null;
        $poolPackageId = $poolType ? $this->extractPackageIdFromStructType($poolType) : null;
        if ($poolObjectId === '') {
            $issues[] = 'pool_object_id is empty';
        } elseif ($poolPackageId === null) {
            $issues[] = 'could not resolve on-chain package for pool object';
        } elseif ($poolPackageId !== $expectedPackageId) {
            $issues[] = 'pool object package mismatch';
        }

        $accountingType = null;
        $accountingPackageId = null;
        if ($accountingObjectId !== '') {
            $accountingType = $this->fetchObjectType($rpcUrl, $accountingObjectId);
            $accountingPackageId = $accountingType ? $this->extractPackageIdFromStructType($accountingType) : null;
            if ($accountingPackageId === null) {
                $issues[] = 'could not resolve on-chain package for PoolAccounting';
            } elseif ($accountingPackageId !== $expectedPackageId) {
                $issues[] = 'PoolAccounting package mismatch';
            }
        } else {
            $issues[] = 'pool_accounting_id is empty';
        }

        $dbPackageId = $this->normalizeObjectId((string) ($row->package_id ?? ''));
        if ($dbPackageId !== '' && $dbPackageId !== $expectedPackageId) {
            $issues[] = 'database package_id differs from expected package';
        }

        return [
            'id' => (int) ($row->id ?? 0),
            'name' => (string) ($row->name ?? ''),
            'network' => (string) ($row->network ?? ''),
            'db_package_id' => $dbPackageId,
            'expected_package_id' => $expectedPackageId,
            'pool_object_id' => $poolObjectId,
            'pool_type' => $poolType,
            'pool_package_id' => $poolPackageId,
            'pool_accounting_id' => $accountingObjectId,
            'accounting_type' => $accountingType,
            'accounting_package_id' => $accountingPackageId,
            'status' => $issues === [] ? 'ok' : 'mismatch',
            'issues' => $issues,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<int, string>
     */
    public function validateMigrationEntry(string $rpcUrl, array $entry, string $expectedPackageId): array
    {
        $issues = [];
        $poolObjectId = $this->normalizeObjectId((string) ($entry['pool_object_id'] ?? ''));
        $accountingObjectId = $this->normalizeObjectId((string) ($entry['pool_accounting_id'] ?? ''));
        $expectedPackageId = $this->normalizeObjectId($expectedPackageId);

        if ($poolObjectId === '') {
            $issues[] = 'pool_object_id is required';
        } else {
            $poolPackageId = $this->fetchObjectPackageId($rpcUrl, $poolObjectId);
            if ($poolPackageId === null) {
                $issues[] = "pool object {$poolObjectId} is missing or has no type";
            } elseif ($poolPackageId !== $expectedPackageId) {
                $issues[] = "pool object {$poolObjectId} is on package {$poolPackageId}, expected {$expectedPackageId}";
            }
        }

        if ($accountingObjectId === '') {
            $issues[] = 'pool_accounting_id is required';
        } else {
            $accountingPackageId = $this->fetchObjectPackageId($rpcUrl, $accountingObjectId);
            if ($accountingPackageId === null) {
                $issues[] = "PoolAccounting {$accountingObjectId} is missing or has no type";
            } elseif ($accountingPackageId !== $expectedPackageId) {
                $issues[] = "PoolAccounting {$accountingObjectId} is on package {$accountingPackageId}, expected {$expectedPackageId}";
            }
        }

        return $issues;
    }

    public function normalizeObjectId(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        if (preg_match('/^0x([a-f0-9]{1,64})$/', $value, $matches)) {
            return '0x'.str_pad($matches[1], 64, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    public function shortObjectId(string $value): string
    {
        $normalized = $this->normalizeObjectId($value);
        if (strlen($normalized) <= 14) {
            return $normalized;
        }

        return substr($normalized, 0, 8).'…'.substr($normalized, -6);
    }

    public function resolveRpcUrl(?string $override = null): string
    {
        $fromOption = trim((string) $override);
        if ($fromOption !== '') {
            return $fromOption;
        }

        $configured = trim((string) config('services.sui.rpc_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return 'https://fullnode.testnet.sui.io:443';
    }

    public function resolveExpectedPackageId(?string $override = null): string
    {
        $fromOption = trim((string) $override);
        if ($fromOption !== '') {
            return $this->normalizeObjectId($fromOption);
        }

        $configured = trim((string) config('services.av8_capital.package_id', ''));
        if ($configured !== '') {
            return $this->normalizeObjectId($configured);
        }

        return '';
    }

    /**
     * @param  array<int, mixed>  $params
     * @return array<string, mixed>
     */
    private function rpc(string $rpcUrl, string $method, array $params): array
    {
        $response = Http::acceptJson()
            ->timeout(90)
            ->connectTimeout(15)
            ->post($rpcUrl, [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => 1,
            ]);

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new \RuntimeException('Sui fullnode returned non-JSON (HTTP '.$response->status().').');
        }

        if (isset($payload['error']) && is_array($payload['error'])) {
            throw new \RuntimeException((string) ($payload['error']['message'] ?? 'Sui JSON-RPC error'));
        }

        $result = $payload['result'] ?? [];

        return is_array($result) ? $result : [];
    }
}
