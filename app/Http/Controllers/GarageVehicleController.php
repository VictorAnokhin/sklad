<?php

namespace App\Http\Controllers;

use App\Models\GarageVehicle;
use App\Models\User;
use App\Services\AutoRiaVehicleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

    public function owner(Request $request, string $owner)
    {
        $owner = trim(urldecode($owner));

        if ($owner === '') {
            return response()->json([
                'items' => [],
                'message' => 'Email или телефон не указан.',
            ], 422);
        }

        $userIds = collect();
        $emails = collect();
        $ownerEmail = mb_strtolower($owner);
        $ownerDigits = preg_replace('/\D+/', '', $owner) ?? '';
        $isEmail = filter_var($owner, FILTER_VALIDATE_EMAIL);
        $fid = trim((string) $request->query('fid', $request->input('fid', '')));

        if (!$isEmail && $ownerDigits === '') {
            return response()->json([
                'items' => [],
                'owner' => $owner,
                'matched_users_count' => 0,
                'message' => 'Клиент не найден.',
            ], 404);
        }

        if ($isEmail) {
            $emails->push($ownerEmail);
        }

        if (Schema::hasTable('users')) {
            $users = User::query()
                ->select(['id', 'email'])
                ->when($fid !== '' && Schema::hasColumn('users', 'firma'), fn ($query) => $query->where('firma', $fid))
                ->where(function ($query) use ($ownerEmail, $ownerDigits, $isEmail) {
                    if (Schema::hasColumn('users', 'email') && $isEmail) {
                        $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$ownerEmail]);
                    }

                    if ($ownerDigits !== '') {
                        foreach (['phone', 'phone1'] as $column) {
                            if (Schema::hasColumn('users', $column)) {
                                $query->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '') LIKE ?", ['%' . $ownerDigits]);
                            }
                        }
                    }
                })
                ->get();

            $userIds = $users->pluck('id')->filter();
            $emails = $emails
                ->merge($users->pluck('email')->map(fn ($email) => mb_strtolower(trim((string) $email))))
                ->filter()
                ->unique()
                ->values();
        }

        if ($emails->isEmpty() && $userIds->isEmpty()) {
            return response()->json([
                'items' => [],
                'owner' => $owner,
                'matched_users_count' => 0,
                'message' => 'Клиент не найден.',
            ], 404);
        }

        $items = GarageVehicle::query()
            ->where(function ($query) use ($emails, $userIds) {
                foreach ($emails as $email) {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                }

                if ($userIds->isNotEmpty()) {
                    $query->orWhereIn('user_id', $userIds->all());
                }
            })
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (GarageVehicle $vehicle) => $this->formatVehicle($vehicle))
            ->values();

        return response()->json([
            'items' => $items,
            'owner' => $owner,
            'matched_users_count' => $userIds->count(),
        ]);
    }

    public function update(Request $request, GarageVehicle $vehicle)
    {
        if (!$this->canEditVehicle($request, $vehicle)) {
            return response()->json([
                'message' => 'Нет доступа к редактированию этого авто.',
            ], 403);
        }

        $validated = $request->validate([
            'garage_photos' => ['nullable', 'array', 'max:5'],
            'garage_photos.*' => ['nullable', 'string', 'max:500'],
            'vehicle_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $photos = array_values($validated['garage_photos'] ?? []);
        $photos = array_pad(array_slice($photos, 0, 5), 5, null);

        $vehicle->fill([
            'garage_photo_1' => $this->cleanNullableString($photos[0]),
            'garage_photo_2' => $this->cleanNullableString($photos[1]),
            'garage_photo_3' => $this->cleanNullableString($photos[2]),
            'garage_photo_4' => $this->cleanNullableString($photos[3]),
            'garage_photo_5' => $this->cleanNullableString($photos[4]),
            'vehicle_price' => array_key_exists('vehicle_price', $validated) && $validated['vehicle_price'] !== null
                ? round((float) $validated['vehicle_price'], 2)
                : null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Данные авто обновлены.',
            'item' => $this->formatVehicle($vehicle->refresh()),
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
            'garage_photos' => [
                $vehicle->garage_photo_1,
                $vehicle->garage_photo_2,
                $vehicle->garage_photo_3,
                $vehicle->garage_photo_4,
                $vehicle->garage_photo_5,
            ],
            'vehicle_price' => $vehicle->vehicle_price !== null ? (float) $vehicle->vehicle_price : null,
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

    private function canEditVehicle(Request $request, GarageVehicle $vehicle): bool
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        if ($vehicle->user_id !== null && (int) $vehicle->user_id === (int) $user->id) {
            return true;
        }

        $userEmail = $this->resolveEmail($user);

        return $userEmail !== null && mb_strtolower(trim($vehicle->email)) === mb_strtolower($userEmail);
    }

    private function cleanNullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function resolveFid(Request $request, $user): ?int
    {
        $fid = $request->query('fid', $user?->firma ?? null);

        return is_numeric($fid) ? (int) $fid : null;
    }
}
