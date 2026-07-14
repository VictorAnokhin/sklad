<?php

namespace App\Http\Controllers;

use App\Models\UserWalletSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserWalletSecretController extends Controller
{
    private const ALLOWED_KINDS = ['zklogin-session'];

    public function show(Request $request, string $kind): JsonResponse
    {
        $this->assertKindAllowed($kind);
        $this->assertGoogleIdentity($request);

        $network = $request->validate([
            'network' => ['required', 'string', 'max:32'],
        ])['network'];

        $secret = UserWalletSecret::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('provider', 'google')
            ->where('kind', $kind)
            ->where('network', strtolower($network))
            ->first();

        if (! $secret || ($secret->expires_at && $secret->expires_at->isPast())) {
            if ($secret) {
                $secret->delete();
            }

            return response()->json(['message' => 'Wallet session not found.'], 404)
                ->header('Cache-Control', 'no-store, private');
        }

        $secret->forceFill(['last_used_at' => now()])->save();

        return response()->json([
            'kind' => $secret->kind,
            'network' => $secret->network,
            'payload' => $secret->encrypted_payload,
            'expiresAt' => $secret->expires_at?->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, string $kind): JsonResponse
    {
        $this->assertKindAllowed($kind);
        $this->assertGoogleIdentity($request);

        $validated = $request->validate([
            'network' => ['required', 'string', 'max:32'],
            'payload' => ['required', 'array'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $payload = $validated['payload'];
        if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 131072) {
            return response()->json(['message' => 'Wallet session payload is too large.'], 422);
        }

        $secret = UserWalletSecret::query()->updateOrCreate([
            'user_id' => $request->user()->getAuthIdentifier(),
            'provider' => 'google',
            'kind' => $kind,
            'network' => strtolower($validated['network']),
        ], [
            'encrypted_payload' => $payload,
            'expires_at' => $validated['expires_at'] ?? null,
            'last_used_at' => now(),
        ]);

        return response()->json([
            'stored' => true,
            'kind' => $secret->kind,
            'network' => $secret->network,
            'expiresAt' => $secret->expires_at?->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function destroy(Request $request, string $kind): JsonResponse
    {
        $this->assertKindAllowed($kind);
        $this->assertGoogleIdentity($request);

        $validated = $request->validate([
            'network' => ['required', 'string', 'max:32'],
        ]);

        UserWalletSecret::query()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('provider', 'google')
            ->where('kind', $kind)
            ->where('network', strtolower($validated['network']))
            ->delete();

        return response()->json(['deleted' => true])
            ->header('Cache-Control', 'no-store, private');
    }

    private function assertKindAllowed(string $kind): void
    {
        validator(['kind' => $kind], [
            'kind' => ['required', 'string', Rule::in(self::ALLOWED_KINDS)],
        ])->validate();
    }

    private function assertGoogleIdentity(Request $request): void
    {
        abort_unless(Schema::hasTable('zklogin_identities'), 503, 'Google wallet identity storage is unavailable.');

        $hasGoogleIdentity = DB::table('zklogin_identities')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('provider', 'google')
            ->exists();

        abort_unless($hasGoogleIdentity, 403, 'A verified Google zkLogin identity is required.');
    }
}
