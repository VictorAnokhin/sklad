<?php

namespace App\Http\Controllers;

use App\Models\GarageVehicle;
use App\Services\AutoRiaVehicleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class GarageVehicleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $email = $this->resolveEmail($user);

        if ($email === null) {
            return response()->json([
                'items' => [],
            ]);
        }

        $items = GarageVehicle::query()
            ->where('email', $email)
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (GarageVehicle $vehicle) => $this->formatVehicle($vehicle))
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function lookup(Request $request, AutoRiaVehicleCheckService $autoRia)
    {
        $validated = $request->validate([
            'vehicle_info' => ['required', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $email = $this->resolveEmail($user);

        if ($email === null) {
            return response()->json([
                'success' => false,
                'message' => 'Для сохранения авто нужен email активного клиента.',
            ], 422);
        }

        try {
            $langId = in_array((string) $request->query('lang', ''), ['ua', 'uk'], true) ? 4 : 2;
            $check = $autoRia->check((string) $validated['vehicle_info'], $langId);
            $payload = is_array($check['data']) ? $check['data'] : ['raw' => $check['data']];
            $snapshot = $autoRia->extractSnapshot($payload, $check['vehicle_info'], $check['input_type']);

            $vehicle = GarageVehicle::query()->updateOrCreate(
                [
                    'email' => $email,
                    'input_value' => $check['vehicle_info'],
                ],
                [
                    'user_id' => $user?->id,
                    'fid' => $this->resolveFid($request, $user),
                    'vehicle_number' => $snapshot['vehicle_number'],
                    'vin' => $snapshot['vin'],
                    'input_type' => $check['input_type'],
                    'title' => $snapshot['title'],
                    'photo_url' => $snapshot['photo_url'],
                    'adv_link' => $snapshot['adv_link'],
                    'characteristics' => $snapshot['characteristics'],
                    'autoria_payload' => $payload,
                    'autoria_status' => $check['status'],
                    'checked_at' => now(),
                ]
            );

            return response()->json([
                'success' => $check['success'],
                'message' => $check['success'] ? 'Авто сохранено в гараже.' : 'Auto.RIA вернул ответ с ошибкой.',
                'item' => $this->formatVehicle($vehicle),
                'lookup' => [
                    'vehicle_info' => $check['vehicle_info'],
                    'input_type' => $check['input_type'],
                    'status' => $check['status'],
                    'rate_limit' => $check['rate_limit'],
                ],
            ], $check['success'] ? 200 : 502);
        } catch (InvalidArgumentException $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
            ], 422);
        } catch (Throwable $error) {
            Log::warning('Auto.RIA garage lookup failed', [
                'vehicle_info' => $validated['vehicle_info'],
                'email' => $email,
                'message' => $error->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить данные Auto.RIA.',
                'error' => $error->getMessage(),
            ], 502);
        }
    }

    private function formatVehicle(GarageVehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'email' => $vehicle->email,
            'vehicle_number' => $vehicle->vehicle_number,
            'vin' => $vehicle->vin,
            'input_value' => $vehicle->input_value,
            'input_type' => $vehicle->input_type,
            'title' => $vehicle->title,
            'photo_url' => $vehicle->photo_url,
            'adv_link' => $vehicle->adv_link,
            'characteristics' => $vehicle->characteristics ?? [],
            'autoria_status' => $vehicle->autoria_status,
            'checked_at' => optional($vehicle->checked_at)->toIso8601String(),
        ];
    }

    private function resolveEmail($user): ?string
    {
        $email = trim((string) ($user?->email ?? ''));

        if ($email === '' && filter_var($user?->login ?? '', FILTER_VALIDATE_EMAIL)) {
            $email = trim((string) $user->login);
        }

        return $email !== '' ? $email : null;
    }

    private function resolveFid(Request $request, $user): ?int
    {
        $fid = $request->query('fid', $user?->firma ?? null);

        return is_numeric($fid) ? (int) $fid : null;
    }
}
