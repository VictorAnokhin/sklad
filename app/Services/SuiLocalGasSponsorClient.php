<?php

namespace App\Services;

use Inodrahq\Bcs\Decoder;
use Inodrahq\Bcs\U64;
use Inodrahq\SuiSdk\Bcs\GasData;
use Inodrahq\SuiSdk\Bcs\ObjectRef;
use Inodrahq\SuiSdk\Bcs\SuiAddress;
use Inodrahq\SuiSdk\Bcs\TransactionData;
use Inodrahq\SuiSdk\Bcs\TransactionDataV1;
use Inodrahq\SuiSdk\Bcs\TransactionExpiration;
use Inodrahq\SuiSdk\Bcs\TransactionKind;
use Inodrahq\SuiSdk\Crypto\Base58;
use Inodrahq\SuiSdk\SuiClient;
use Inodrahq\SuiSdk\Transport\JsonRpcTransport;
use Inodrahq\SuiSdk\Types\CoinStruct;
use Inodrahq\SuiSdk\Wallet;
use Illuminate\Support\Facades\Http;

/**
 * Sponsors a gasless Sui TransactionKind using a hot wallet on the server (no Node.js).
 *
 * Requires: composer package inodrahq/sui-sdk, PHP 8.2+, ext-sodium, ext-bcmath, ext-openssl.
 */
class SuiLocalGasSponsorClient
{
    private const SUI_TYPE = '0x2::sui::SUI';

    private const DRY_RUN_MAX_BUDGET = '50000000';

    public static function isConfigured(): bool
    {
        if (! self::sdkAvailable()) {
            return false;
        }

        $key = trim((string) config('services.sui.gas_sponsor_private_key', ''));

        return $key !== '';
    }

    public static function sdkAvailable(): bool
    {
        return class_exists(SuiClient::class);
    }

    /**
     * @return array{txBytes: string, signature: string, txDigest: string, expireAtTime?: int}
     */
    public static function sponsorTransactionBlock(
        string $transactionKindBase64,
        string $sender,
        ?string $gasBudget = null,
        ?string $gasPrice = null,
    ): array {
        if (! self::sdkAvailable()) {
            throw new \RuntimeException(
                'Local gas sponsor requires inodrahq/sui-sdk. Run: composer require inodrahq/sui-sdk (PHP 8.2+).'
            );
        }

        $rpc = trim((string) config('services.sui.rpc_url', ''));
        if ($rpc === '') {
            throw new \RuntimeException('SUI RPC URL is not configured (SUI_RPC_URL).');
        }

        $key = trim((string) config('services.sui.gas_sponsor_private_key', ''));
        if ($key === '') {
            throw new \RuntimeException('Local gas sponsor key is not configured (SUI_GAS_SPONSOR_PRIVATE_KEY).');
        }

        $kindBytes = base64_decode($transactionKindBase64, true);
        if ($kindBytes === false || $kindBytes === '') {
            throw new \RuntimeException('Invalid base64 transactionKind.');
        }

        $kind = TransactionKind::decode(new Decoder($kindBytes));

        $transport = new JsonRpcTransport($rpc, [], null, 90);
        $client = new SuiClient($transport);

        $sponsorWallet = new Wallet($key);
        $sponsorOwner = $sponsorWallet->getAddress();

        $gasPriceStr = ($gasPrice !== null && $gasPrice !== '')
            ? (string) $gasPrice
            : self::referenceGasPriceString($client);

        $gasPayment = self::pickGasPayment($client, $sponsorOwner);

        $budgetStr = ($gasBudget !== null && $gasBudget !== '')
            ? (string) $gasBudget
            : self::estimateGasBudget($client, $kind, $sender, $sponsorOwner, $gasPayment, $gasPriceStr);

        $senderAddr = self::suiAddressFromHex($sender);
        $sponsorAddr = self::suiAddressFromHex($sponsorOwner);

        $txData = new TransactionData(new TransactionDataV1(
            $kind,
            $senderAddr,
            new GasData(
                [$gasPayment],
                $sponsorAddr,
                new U64($gasPriceStr),
                new U64($budgetStr),
            ),
            TransactionExpiration::none(),
        ));

        $verifyDry = $client->dryRunTransactionBlock(base64_encode($txData->toBytes()));
        if ($verifyDry->effects->status->status !== 'success') {
            $err = $verifyDry->effects->status->error ?? 'unknown error';
            throw new \RuntimeException('Sponsored transaction dry run failed: '.$err);
        }

        $txDigest = $verifyDry->effects->transactionDigest;
        $signature = $sponsorWallet->sign($txData);

        return [
            'txBytes' => base64_encode($txData->toBytes()),
            'signature' => $signature,
            'txDigest' => $txDigest,
            'expireAtTime' => 0,
        ];
    }

    private static function referenceGasPriceString(SuiClient $client): string
    {
        $ref = $client->getReferenceGasPrice();
        if (is_object($ref) && isset($ref->referenceGasPrice)) {
            return (string) $ref->referenceGasPrice;
        }

        return (string) $ref;
    }

    /**
     * Largest SUI coin owned by the gas sponsor.
     */
    private static function pickGasPayment(SuiClient $client, string $owner): ObjectRef
    {
        /** @var CoinStruct[] $coins */
        $coins = [];
        $cursor = null;
        $rpc = trim((string) config('services.sui.rpc_url', ''));

        do {
            $page = self::rpcRequest($rpc, 'suix_getCoins', [$owner, self::SUI_TYPE, $cursor, 100]);
            $pageData = isset($page->data) && is_array($page->data) ? $page->data : [];
            foreach ($pageData as $coin) {
                $coins[] = CoinStruct::fromResponse($coin);
            }
            $cursor = ! empty($page->hasNextPage) ? ($page->nextCursor ?? null) : null;
        } while ($cursor !== null);

        if ($coins === []) {
            throw new \RuntimeException('Gas sponsor has no SUI coins for gas payment.');
        }

        usort($coins, function (CoinStruct $a, CoinStruct $b): int {
            return bccomp($b->balance, $a->balance);
        });

        $best = $coins[0];

        return new ObjectRef(
            self::suiAddressFromHex($best->coinObjectId),
            new U64($best->version),
            Base58::decode($best->digest),
        );
    }

    /**
     * Dry-run with max gas budget and derive a safe budget (same idea as Mysten / inodrahq Transaction).
     */
    private static function estimateGasBudget(
        SuiClient $client,
        TransactionKind $kind,
        string $sender,
        string $sponsorOwner,
        ObjectRef $gasPayment,
        string $gasPriceStr,
    ): string {
        $senderAddr = self::suiAddressFromHex($sender);
        $sponsorAddr = self::suiAddressFromHex($sponsorOwner);

        $tempTx = new TransactionData(new TransactionDataV1(
            $kind,
            $senderAddr,
            new GasData(
                [$gasPayment],
                $sponsorAddr,
                new U64($gasPriceStr),
                new U64(self::DRY_RUN_MAX_BUDGET),
            ),
            TransactionExpiration::none(),
        ));

        $dry = $client->dryRunTransactionBlock(base64_encode($tempTx->toBytes()));
        if ($dry->effects->status->status !== 'success') {
            $err = $dry->effects->status->error ?? 'unknown error';
            throw new \RuntimeException('Gas estimation dry run failed: '.$err);
        }

        $g = $dry->effects->gasUsed;
        $safeOverhead = bcmul($gasPriceStr, '1000');
        $budget = bcadd(
            bcsub(
                bcadd($g->computationCost, $g->storageCost),
                $g->storageRebate,
            ),
            $safeOverhead,
        );

        if (bccomp($budget, $safeOverhead) < 0) {
            $budget = $safeOverhead;
        }

        return $budget;
    }

    /**
     * The current inodrahq JSON-RPC getCoins wrapper omits a null cursor when a limit is passed,
     * which shifts `limit` into the cursor position and Sui fullnode returns Invalid params.
     *
     * @param  array<int, mixed>  $params
     */
    private static function rpcRequest(string $rpc, string $method, array $params): mixed
    {
        $response = Http::acceptJson()
            ->timeout(90)
            ->connectTimeout(15)
            ->post($rpc, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => $method,
                'params' => $params,
            ]);

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException('Sui fullnode returned non-JSON (HTTP '.$response->status().').');
        }

        if (isset($payload['error']) && is_array($payload['error'])) {
            $message = (string) ($payload['error']['message'] ?? 'Sui JSON-RPC error');
            $code = $payload['error']['code'] ?? null;
            throw new \RuntimeException($message.' (code: '.json_encode($code).')');
        }

        return json_decode(json_encode($payload['result'] ?? null, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }

    private static function suiAddressFromHex(string $address): SuiAddress
    {
        $hex = strtolower(trim($address));
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }
        $hex = str_pad($hex, 64, '0', STR_PAD_LEFT);
        if (! preg_match('/^[0-9a-f]{64}$/', $hex)) {
            throw new \RuntimeException('Invalid Sui address: expected 32-byte hex.');
        }

        return new SuiAddress('0x'.$hex);
    }
}
