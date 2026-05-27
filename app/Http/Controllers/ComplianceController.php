<?php

namespace App\Http\Controllers;

use App\Models\CryptoAmlScreening;
use App\Services\ChainalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function __construct(
        private readonly ChainalysisService $chainalysis,
    ) {}

    public function screenIncomingCrypto(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'address' => ['required', 'string', 'max:128'],
            'asset' => ['required', 'string', 'max:16'],
            'network' => ['required', 'string', 'max:32'],
            'amount' => ['nullable', 'string', 'max:64'],
            'direction' => ['nullable', 'string', 'in:incoming'],
        ]);

        $userId = $request->user()?->id;
        $result = $this->chainalysis->screenIncomingCrypto(
            $payload['address'],
            $payload['asset'],
            $payload['network'],
            $payload['amount'] ?? null,
            is_int($userId) ? $userId : null,
        );

        CryptoAmlScreening::query()->create([
            'user_id' => $userId,
            'wallet_address' => $result['address'],
            'asset' => $result['asset'],
            'network' => strtolower($payload['network']),
            'amount' => $payload['amount'] ?? null,
            'direction' => $payload['direction'] ?? 'incoming',
            'risk_level' => $result['risk_level'],
            'allowed' => $result['allowed'],
            'reason' => $result['reason'],
            'provider' => $result['provider'],
            'transfer_reference' => $result['transfer_reference'] ?? null,
            'raw_response' => $result['raw'] ?? null,
        ]);

        $status = $result['allowed'] ? 200 : 403;

        return response()->json([
            'allowed' => $result['allowed'],
            'riskLevel' => $result['risk_level'],
            'asset' => $result['asset'],
            'address' => $result['address'],
            'reason' => $result['reason'],
            'provider' => $result['provider'],
            'transferReference' => $result['transfer_reference'] ?? null,
            'message' => $result['allowed']
                ? 'Incoming crypto passed AML screening.'
                : 'Incoming USDT from this wallet is blocked due to elevated AML risk.',
        ], $status);
    }
}
