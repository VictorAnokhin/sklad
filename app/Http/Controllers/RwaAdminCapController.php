<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RwaAdminCapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('rwa_admin_caps')) {
            return response()->json(['data' => []]);
        }

        $network = trim((string) $request->query('network', 'testnet'));
        $packageId = strtolower(trim((string) $request->query('package_id', '')));

        $query = DB::table('rwa_admin_caps')
            ->when($network !== '', fn ($q) => $q->where('network', $network))
            ->when($packageId !== '', fn ($q) => $q->where('package_id', $packageId))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return response()->json([
            'data' => $query->get()->map(fn ($row) => $this->mapRow($row))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('rwa_admin_caps')) {
            throw ValidationException::withMessages([
                'rwa_admin_caps' => 'Run migrations before saving RWA admin caps.',
            ]);
        }

        $validated = $request->validate([
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'admin_cap_id' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'owner_address' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'label' => ['nullable', 'string', 'max:120'],
            'tx_digest' => ['nullable', 'string', 'max:120'],
        ]);

        $adminCapId = strtolower($validated['admin_cap_id']);
        $now = now();

        DB::table('rwa_admin_caps')->updateOrInsert(
            ['admin_cap_id' => $adminCapId],
            [
                'network' => trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet',
                'package_id' => strtolower($validated['package_id']),
                'owner_address' => strtolower($validated['owner_address']),
                'label' => trim((string) ($validated['label'] ?? '')),
                'tx_digest' => trim((string) ($validated['tx_digest'] ?? '')),
                'created_by' => Auth::id(),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $row = DB::table('rwa_admin_caps')->where('admin_cap_id', $adminCapId)->first();

        return response()->json(['data' => $this->mapRow($row)], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        if (! Schema::hasTable('rwa_admin_caps')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $deleted = DB::table('rwa_admin_caps')->where('id', $id)->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    private function mapRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) $row->package_id,
            'admin_cap_id' => (string) $row->admin_cap_id,
            'owner_address' => (string) $row->owner_address,
            'label' => (string) ($row->label ?? ''),
            'tx_digest' => (string) ($row->tx_digest ?? ''),
            'created_at' => $row->created_at ? (string) $row->created_at : null,
        ];
    }
}
