<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BlockchainAssetAdapterService
{
    public function adapterFor(string $blockchain, string $assetType): string
    {
        $chain = strtolower(trim($blockchain));
        $type = strtolower(trim($assetType));

        if ($chain === 'solana' && $type === 'nft') {
            return 'solana_nft_das';
        }

        return 'manual';
    }

    public function availableFields(string $adapter): array
    {
        return match ($adapter) {
            'solana_nft_das' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'string'],
                ['key' => 'symbol', 'label' => 'Symbol', 'type' => 'string'],
                ['key' => 'description', 'label' => 'Description', 'type' => 'string'],
                ['key' => 'image_url', 'label' => 'Image', 'type' => 'url'],
                ['key' => 'external_url', 'label' => 'External URL', 'type' => 'url'],
                ['key' => 'collection', 'label' => 'Collection', 'type' => 'string'],
                ['key' => 'owner', 'label' => 'Owner', 'type' => 'address'],
                ['key' => 'royalty_bps', 'label' => 'Royalty BPS', 'type' => 'number'],
                ['key' => 'token_standard', 'label' => 'Token standard', 'type' => 'string'],
                ['key' => 'compressed', 'label' => 'Compressed', 'type' => 'boolean'],
                ['key' => 'attributes', 'label' => 'Attributes', 'type' => 'json'],
                ['key' => 'creators', 'label' => 'Creators', 'type' => 'json'],
            ],
            default => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'string'],
                ['key' => 'symbol', 'label' => 'Symbol', 'type' => 'string'],
                ['key' => 'last_balance', 'label' => 'Balance', 'type' => 'number'],
                ['key' => 'last_value_usd', 'label' => 'Value USD', 'type' => 'number'],
            ],
        };
    }

    public function defaultSelectedFields(string $adapter): array
    {
        return match ($adapter) {
            'solana_nft_das' => ['name', 'image_url', 'collection', 'owner', 'token_standard'],
            default => ['name', 'symbol', 'last_balance', 'last_value_usd'],
        };
    }

    public function refresh(array $asset): array
    {
        $adapter = $asset['adapter'] ?? $this->adapterFor((string) ($asset['blockchain'] ?? ''), (string) ($asset['asset_type'] ?? ''));

        if ($adapter === 'solana_nft_das') {
            return $this->refreshSolanaNftDas($asset);
        }

        return [
            'adapter' => $adapter,
            'available_fields' => $this->availableFields($adapter),
            'selected_fields' => $asset['selected_fields'] ?? $this->defaultSelectedFields($adapter),
            'sync_status' => 'adapter_ready',
            'sync_error' => null,
            'last_payload' => null,
        ];
    }

    private function refreshSolanaNftDas(array $asset): array
    {
        $rpcUrl = rtrim((string) env('SOLANA_RPC_URL', 'https://solana-rpc.publicnode.com'), '/');
        $assetAddress = trim((string) ($asset['asset_address'] ?? ''));
        $availableFields = $this->availableFields('solana_nft_das');
        $selectedFields = $asset['selected_fields'] ?? $this->defaultSelectedFields('solana_nft_das');

        if ($assetAddress === '') {
            return [
                'adapter' => 'solana_nft_das',
                'available_fields' => $availableFields,
                'selected_fields' => $selectedFields,
                'sync_status' => 'error',
                'sync_error' => 'Asset address is required.',
            ];
        }

        $response = Http::timeout(20)->post($rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 'bank-solana-nft-'.$assetAddress,
            'method' => 'getAsset',
            'params' => ['id' => $assetAddress],
        ]);

        if (! $response->ok()) {
            return [
                'adapter' => 'solana_nft_das',
                'available_fields' => $availableFields,
                'selected_fields' => $selectedFields,
                'sync_status' => 'error',
                'sync_error' => 'Solana RPC request failed: HTTP '.$response->status(),
            ];
        }

        $payload = $response->json();
        if (isset($payload['error'])) {
            return [
                'adapter' => 'solana_nft_das',
                'available_fields' => $availableFields,
                'selected_fields' => $selectedFields,
                'sync_status' => 'error',
                'sync_error' => (string) ($payload['error']['message'] ?? 'Solana DAS getAsset error.'),
                'last_payload' => $payload,
            ];
        }

        $result = is_array($payload['result'] ?? null) ? $payload['result'] : [];
        $content = is_array($result['content'] ?? null) ? $result['content'] : [];
        $metadata = is_array($content['metadata'] ?? null) ? $content['metadata'] : [];
        $links = is_array($content['links'] ?? null) ? $content['links'] : [];
        $ownership = is_array($result['ownership'] ?? null) ? $result['ownership'] : [];
        $royalty = is_array($result['royalty'] ?? null) ? $result['royalty'] : [];

        return [
            'adapter' => 'solana_nft_das',
            'available_fields' => $availableFields,
            'selected_fields' => $selectedFields,
            'sync_status' => 'synced',
            'sync_error' => null,
            'name' => (string) ($metadata['name'] ?? $asset['name'] ?? ''),
            'symbol' => (string) ($metadata['symbol'] ?? $asset['symbol'] ?? ''),
            'owner_address' => (string) ($ownership['owner'] ?? $asset['owner_address'] ?? ''),
            'image_url' => (string) ($links['image'] ?? ''),
            'external_url' => (string) ($links['external_url'] ?? $metadata['external_url'] ?? ''),
            'last_payload' => [
                'name' => $metadata['name'] ?? null,
                'symbol' => $metadata['symbol'] ?? null,
                'description' => $metadata['description'] ?? null,
                'image_url' => $links['image'] ?? null,
                'external_url' => $links['external_url'] ?? $metadata['external_url'] ?? null,
                'collection' => $this->readGroupingValue($result, 'collection'),
                'owner' => $ownership['owner'] ?? null,
                'royalty_bps' => $royalty['basis_points'] ?? null,
                'token_standard' => $result['interface'] ?? null,
                'compressed' => $result['compression']['compressed'] ?? null,
                'attributes' => $metadata['attributes'] ?? [],
                'creators' => $result['creators'] ?? [],
                'raw' => $result,
            ],
        ];
    }

    private function readGroupingValue(array $asset, string $groupKey): ?string
    {
        foreach ((array) ($asset['grouping'] ?? []) as $group) {
            if (is_array($group) && ($group['group_key'] ?? null) === $groupKey) {
                return isset($group['group_value']) ? (string) $group['group_value'] : null;
            }
        }

        return null;
    }
}
