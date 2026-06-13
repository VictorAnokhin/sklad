@extends('home')

@section('title')
{{ $client ? __('client.edit_title') : __('client.create_title') }}
@endsection

@section('content')
<style>
    .client-kyc-photos {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .client-kyc-photo {
        border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.04);
        padding: 12px;
        min-width: 0;
        position: relative;
    }

    .client-kyc-photo__delete {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 50%;
        background: rgba(220, 38, 38, 0.85);
        color: #fff;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: background 0.2s, transform 0.15s;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    .client-kyc-photo__delete:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    .client-kyc-photo__delete:active {
        transform: scale(0.95);
    }

    .client-kyc-photo__delete--loading {
        pointer-events: none;
        opacity: 0.6;
    }

    .client-kyc-photo__label {
        color: var(--muted-foreground, #9ca3af);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .client-kyc-photo__image {
        display: block;
        width: 100%;
        aspect-ratio: 4 / 3;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .client-kyc-photo__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .client-kyc-photo__empty {
        display: flex;
        width: 100%;
        aspect-ratio: 4 / 3;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 1px dashed rgba(255, 255, 255, 0.16);
        color: var(--muted-foreground, #9ca3af);
        background: rgba(0, 0, 0, 0.16);
        font-size: 0.9rem;
    }

    .client-kyc-photo__meta {
        margin-top: 8px;
        color: var(--muted-foreground, #9ca3af);
        font-size: 0.82rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    @media (max-width: 767px) {
        .client-kyc-photos {
            grid-template-columns: 1fr;
        }
    }

    .client-garage-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 14px;
    }

    .client-garage-thumb {
        width: 72px;
        height: 54px;
        border-radius: 8px;
        object-fit: cover;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .client-garage-empty-thumb {
        display: inline-flex;
        width: 72px;
        height: 54px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: var(--muted-foreground, #9ca3af);
        background: rgba(255, 255, 255, 0.06);
        border: 1px dashed rgba(255, 255, 255, 0.16);
        font-size: 0.78rem;
    }

    .client-garage-meta {
        color: var(--muted-foreground, #9ca3af);
        font-size: 0.84rem;
        line-height: 1.35;
    }

    .client-garage-photo-field {
        display: grid;
        grid-template-columns: 96px minmax(0, 1fr);
        gap: 8px;
        align-items: center;
    }

    .client-garage-photo-preview {
        width: 96px;
        height: 72px;
        border-radius: 8px;
        object-fit: cover;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .client-garage-photo-upload-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    @media (max-width: 767px) {
        .client-garage-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .client-garage-photo-upload-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container mt-4">
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    @if($client)
        <ul class="nav nav-tabs mb-3" id="client-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="client-data-tab" data-bs-toggle="tab" data-bs-target="#client-data-pane" type="button" role="tab" aria-controls="client-data-pane" aria-selected="true">
                    Данные
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="client-garage-tab" data-bs-toggle="tab" data-bs-target="#client-garage-pane" type="button" role="tab" aria-controls="client-garage-pane" aria-selected="false">
                    Гараж
                </button>
            </li>
        </ul>
        <div class="tab-content" id="client-tabs-content">
            <div class="tab-pane fade show active" id="client-data-pane" role="tabpanel" aria-labelledby="client-data-tab" tabindex="0">
    @endif

    <div class="glass-card" style="max-width: 900px;">
        <form method="POST" action="{{ route('client.save') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $client->id ?? '0' }}">

            @if($client)
                @php
                    $formatKycSize = static function ($bytes): string {
                        $bytes = (int) ($bytes ?? 0);
                        if ($bytes <= 0) {
                            return '';
                        }
                        return $bytes >= 1048576
                            ? number_format($bytes / 1048576, 1, '.', ' ') . ' MB'
                            : max(1, (int) round($bytes / 1024)) . ' KB';
                    };
                @endphp
                <div class="mb-3">
                    <div class="form-label">Фото клиента</div>
                    <div class="client-kyc-photos">
                        @foreach($kycPhotos as $photo)
                            <div class="client-kyc-photo" data-photo-type="{{ $photo['type'] }}">
                                <div class="client-kyc-photo__label">{{ $photo['column'] }} · {{ $photo['label'] }}</div>
                                @if($photo['url'])
                                    <button
                                        type="button"
                                        class="client-kyc-photo__delete js-delete-kyc-photo"
                                        title="Видалити фото"
                                        data-photo-type="{{ $photo['type'] }}"
                                        data-client-id="{{ $client->id ?? 0 }}"
                                    >✕</button>
                                    <a href="{{ $photo['url'] }}" target="_blank" rel="noopener" class="client-kyc-photo__image">
                                        <img src="{{ $photo['url'] }}" alt="{{ $photo['label'] }}">
                                    </a>
                                    <div class="client-kyc-photo__meta">
                                        <div>{{ $photo['file_name'] ?: basename($photo['path']) }}</div>
                                        @if($formatKycSize($photo['file_size']) !== '')
                                            <div>{{ $formatKycSize($photo['file_size']) }}</div>
                                        @endif
                                        @if($photo['uploaded_at'])
                                            <div>{{ $photo['uploaded_at'] }}</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="client-kyc-photo__empty">Фото не загружено</div>
                                    <div class="client-kyc-photo__meta">{{ $photo['column'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('client.field_organization') }}</label>
                    <input type="text" name="orgname" class="form-control" value="{{ $client->orgname ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_edrpou') }}</label>
                    <input type="text" name="kod1" class="form-control" value="{{ $client->kod1 ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_contact') }}</label>
                    <input type="text" name="name2" class="form-control" value="{{ $client->name2 ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_lastname') }}</label>
                    <input type="text" name="secondname" class="form-control" value="{{ $client->secondname ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_firstname') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ $client->name ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_middlename') }}</label>
                    <input type="text" name="fathername" class="form-control" value="{{ $client->fathername ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('client.field_phone') }}</label>
                    <input type="tel" name="phone" id="phone-input" class="form-control phone-input" value="{{ $client->phone ?? '' }}" placeholder="+38 (0XX) XXX-XX-XX" maxlength="19">
                    <div class="invalid-feedback" id="phone-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('client.field_phone2') }}</label>
                    <input type="tel" name="phone1" id="phone1-input" class="form-control phone-input" value="{{ $client->phone1 ?? '' }}" placeholder="+38 (0XX) XXX-XX-XX" maxlength="19">
                    <div class="invalid-feedback" id="phone1-error"></div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_region') }}</label>
                    <input type="text" name="region" class="form-control" value="{{ $client->region ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_city') }}</label>
                    <input type="text" name="city" class="form-control" value="{{ $client->city ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_nova_poshta') }}</label>
                    <input type="text" name="poshta" class="form-control" value="{{ $client->poshta ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_client_type') }}</label>
                    <select name="tgroup" class="form-select">
                        <option value="">{{ __('client.select_type') }}</option>
                        @foreach($clientTypes ?? [] as $type)
                            <option value="{{ $type->id }}" {{ (string)($client->tgroup ?? '') === (string)$type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_email') }}</label>
                    <input type="email" name="email" id="email-input" class="form-control" value="{{ $client->email ?? '' }}" required>
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_password') }}</label>
                    <input type="password" name="pass" class="form-control" value="" placeholder="{{ $client ? __('client.field_password_hint') : '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Группа</label>
                    <div class="input-group">
                        <select name="usergroup" id="client-group-select" class="form-select">
                            <option value="">Не выбрана</option>
                            @foreach($userGroups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ (string)($client->usergroup ?? '') === (string)$group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#clientGroupModal">
                            <i class="fas fa-users-cog me-1"></i> Группы
                        </button>
                    </div>
                </div>
                <div class="col-md-8"></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_status') }}</label>
                    <select name="idstatus" class="form-select">
                        @foreach($statuses as $s)
                            <option value="{{ $s->id }}" {{ (string)($client->idstatus ?? $client->ustype ?? '') === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_bonus') }}</label>
                    <input type="number" step="0.01" name="bonus" class="form-control" value="{{ $client->bonus ?? 0 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_birthday') }}</label>
                    <input type="text" name="hbd" class="form-control" value="{{ $client->hbd ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Проект</label>
                    <select name="project_id" class="form-select">
                        <option value="">Не выбран</option>
                        @php
                            $selectedProjectId = old(
                                'project_id',
                                $client->project_id ?? (is_numeric($fid ?? null) ? (int) $fid : '')
                            );
                        @endphp
                        @foreach($projects ?? [] as $project)
                            <option value="{{ $project->id }}" {{ (string) $selectedProjectId === (string) $project->id ? 'selected' : '' }}>
                                {{ $project->name }} #{{ $project->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">KYC верификация</label>
                    <select name="kyc_status" class="form-select">
                        <option value="not_started" {{ ($client->kyc_status ?? 'not_started') === 'not_started' ? 'selected' : '' }}>Не начат</option>
                        <option value="pending" {{ ($client->kyc_status ?? '') === 'pending' ? 'selected' : '' }}>На рассмотрении</option>
                        <option value="in_review" {{ ($client->kyc_status ?? '') === 'in_review' ? 'selected' : '' }}>На проверке</option>
                        <option value="approved" {{ ($client->kyc_status ?? '') === 'approved' ? 'selected' : '' }}>Проверка пройдена</option>
                        <option value="rejected" {{ ($client->kyc_status ?? '') === 'rejected' ? 'selected' : '' }}>Требуется повторная проверка</option>
                        <option value="frozen" {{ ($client->kyc_status ?? '') === 'frozen' ? 'selected' : '' }}>Заблокирован</option>
                    </select>
                </div>
                <div class="col-md-4"></div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-success" style="min-width: 120px;">
                    <i class="fas fa-save me-1"></i> {{ __('client.btn_save') }}
                </button>
                <a href="{{ route('client.index') }}" class="btn btn-outline-secondary" style="min-width: 120px;">
                    ← {{ __('client.btn_back') }}
                </a>
                @if($client && !empty($client->id))
                <button
                    type="submit"
                    class="btn btn-outline-danger ms-auto"
                    formaction="{{ route('client.destroy') }}"
                    formmethod="POST"
                    formnovalidate
                    onclick="return confirm('{{ __('client.confirm_delete') }}');"
                >
                    🗑 {{ __('client.btn_delete') }}
                </button>
                @endif
            </div>
        </form>
    </div>

    <div class="modal fade" id="clientGroupModal" tabindex="-1" aria-labelledby="clientGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientGroupModalLabel">Группы клиентов</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-none" id="client-group-alert"></div>

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <label class="form-label">Поиск группы</label>
                            <div class="input-group mb-3">
                                <input type="search" class="form-control" id="client-group-search" placeholder="Название группы">
                                <button type="button" class="btn btn-outline-primary" id="client-group-search-button">Найти</button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="client-group-table">
                                    <thead>
                                        <tr>
                                            <th>Группа</th>
                                            <th>Тип</th>
                                            <th class="text-end">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Загрузка...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0" id="client-group-form-title">Новая группа</h6>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="client-group-new-button">Новая</button>
                                </div>
                                <input type="hidden" id="client-group-id">
                                <div class="mb-3">
                                    <label class="form-label">Название</label>
                                    <input type="text" class="form-control" id="client-group-name" maxlength="255">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Тип группы</label>
                                    <select class="form-select" id="client-group-status">
                                        <option value="0">Доп. группа</option>
                                        <option value="1">Розничная</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" id="client-group-save-button">Сохранить</button>
                                    <button type="button" class="btn btn-outline-danger d-none" id="client-group-delete-button">Удалить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    @if($client)
            </div>
            <div class="tab-pane fade" id="client-garage-pane" role="tabpanel" aria-labelledby="client-garage-tab" tabindex="0">
                <div class="glass-card" style="max-width: 1100px;">
                    <div class="client-garage-toolbar">
                        <div>
                            <h5 class="mb-1">Гараж клиента</h5>
                            <div class="client-garage-meta">Авто привязаны к текущему клиенту по ID и email.</div>
                        </div>
                        <button type="button" class="btn btn-success" id="garage-add-button">
                            <i class="fas fa-plus me-1"></i> Добавить
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="garage-table">
                            <thead>
                                <tr>
                                    <th style="width: 92px;">Фото</th>
                                    <th>Авто</th>
                                    <th>Номер / VIN</th>
                                    <th>Цена</th>
                                    <th>Проверено</th>
                                    <th class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($garageVehicles as $vehicle)
                                    @php
                                        $characteristics = is_array($vehicle->characteristics ?? null) ? $vehicle->characteristics : [];
                                        $vehicleName = trim(implode(' ', array_filter([
                                            $characteristics['brand'] ?? '',
                                            $characteristics['model'] ?? '',
                                        ]))) ?: ($vehicle->title ?: 'Без названия');
                                        $garagePhotos = [
                                            $vehicle->garage_photo_1 ?? null,
                                            $vehicle->garage_photo_2 ?? null,
                                            $vehicle->garage_photo_3 ?? null,
                                            $vehicle->garage_photo_4 ?? null,
                                            $vehicle->garage_photo_5 ?? null,
                                        ];
                                        $vehiclePayload = [
                                            'id' => $vehicle->id,
                                            'email' => $vehicle->email,
                                            'vehicle_number' => $vehicle->vehicle_number,
                                            'vin' => $vehicle->vin,
                                            'input_value' => $vehicle->input_value,
                                            'input_type' => $vehicle->input_type,
                                            'title' => $vehicle->title,
                                            'photo_url' => $vehicle->photo_url,
                                            'garage_photos' => $garagePhotos,
                                            'vehicle_price' => $vehicle->vehicle_price !== null ? (float) $vehicle->vehicle_price : null,
                                            'adv_link' => $vehicle->adv_link,
                                            'characteristics' => $characteristics,
                                            'brand' => $characteristics['brand'] ?? null,
                                            'model' => $characteristics['model'] ?? null,
                                            'color' => $characteristics['color'] ?? null,
                                            'year' => $characteristics['year'] ?? null,
                                            'description' => $characteristics['description'] ?? null,
                                            'autoria_status' => $vehicle->autoria_status,
                                            'checked_at' => optional($vehicle->checked_at)->toDateTimeString(),
                                        ];
                                        $mainPhoto = $vehicle->photo_url ?: collect($garagePhotos)->filter()->first();
                                    @endphp
                                    <tr data-garage-row="{{ $vehicle->id }}">
                                        <td data-garage-photo-cell>
                                            @if($mainPhoto)
                                                <img src="{{ $mainPhoto }}" alt="{{ $vehicle->title ?: $vehicle->vehicle_number }}" class="client-garage-thumb">
                                            @else
                                                <span class="client-garage-empty-thumb">Нет фото</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold" data-garage-title-cell>{{ $vehicleName }}</div>
                                            <div class="client-garage-meta">{{ $vehicle->input_type }}: {{ $vehicle->input_value }}</div>
                                        </td>
                                        <td>
                                            <div data-garage-number-cell>{{ $vehicle->vehicle_number ?: '-' }}</div>
                                            <div class="client-garage-meta" data-garage-vin-cell>{{ $vehicle->vin ?: '-' }}</div>
                                        </td>
                                        <td data-garage-price-cell>
                                            {{ $vehicle->vehicle_price !== null ? number_format((float) $vehicle->vehicle_price, 2, '.', ' ') : '-' }}
                                        </td>
                                        <td data-garage-checked-cell>{{ optional($vehicle->checked_at)->format('d.m.Y H:i') ?: '-' }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm js-garage-view" data-vehicle='@json($vehiclePayload)'>
                                                Просмотр
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="garage-empty-row">
                                        <td colspan="6" class="text-center text-muted py-4">В гараже пока нет авто</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="garageVehicleModal" tabindex="-1" aria-labelledby="garageVehicleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="garageVehicleModalLabel">Авто в гараже</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert d-none" id="garage-modal-alert"></div>

                        <div class="mb-4">
                            <label class="form-label">Поиск по номеру или VIN</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="garage-search-input" placeholder="Например AA1234AA или VIN">
                                <button type="button" class="btn btn-outline-primary" id="garage-search-button">Найти и добавить</button>
                            </div>
                            <div class="form-text">Поиск получает данные из Auto.RIA и сохраняет авто в гараж текущего клиента.</div>
                        </div>

                        <form id="garage-edit-form">
                            <input type="hidden" id="garage-vehicle-id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Марка</label>
                                    <input type="text" class="form-control" id="garage-brand-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Модель</label>
                                    <input type="text" class="form-control" id="garage-model-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Цвет</label>
                                    <input type="text" class="form-control" id="garage-color-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Год выпуска</label>
                                    <input type="number" min="1900" max="2100" class="form-control" id="garage-year-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">VIN</label>
                                    <input type="text" class="form-control" id="garage-vin-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Цена</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="garage-price-input">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Описание</label>
                                    <textarea class="form-control" id="garage-description-input" rows="4"></textarea>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label">Фото гаража</label>
                                <div class="client-garage-photo-upload-grid">
                                    @for($i = 0; $i < 5; $i++)
                                        <div class="client-garage-photo-field">
                                            <img src="" alt="Фото {{ $i + 1 }}" class="client-garage-photo-preview d-none" data-photo-preview="{{ $i }}">
                                            <span class="client-garage-empty-thumb" data-photo-empty="{{ $i }}" style="width: 96px; height: 72px;">Фото {{ $i + 1 }}</span>
                                            <div>
                                                <input type="url" class="form-control garage-photo-input mb-2" placeholder="URL фото {{ $i + 1 }}" data-photo-index="{{ $i }}">
                                                <input type="file" class="form-control garage-photo-file-input" accept="image/*" data-photo-index="{{ $i }}">
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-success" id="garage-save-button" disabled>Сохранить авто</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function() {
    const checkEmailUrl = "{{ route('client.checkEmail') }}";
    const clientId = "{{ $client->id ?? '0' }}";

    // Phone formatting: +38 (0XX) XXX-XX-XX
    function formatPhone(value) {
        let digits = value.replace(/\D/g, '');

        if (digits.startsWith('380') && digits.length > 3) {
            digits = digits.slice(0, 12);
        } else if (digits.startsWith('0') && digits.length > 0) {
            digits = `38${digits}`.slice(0, 12);
        } else if (digits.startsWith('38') && digits.length > 2) {
            digits = digits.slice(0, 12);
        } else if (digits.length === 0) {
            digits = '38';
        } else {
            digits = `38${digits}`.slice(0, 12);
        }

        const local = digits.slice(2);
        let formatted = '+38';
        if (local.length > 0) {
            formatted += ` (${local.slice(0, 3)}`;
            if (local.length >= 3) formatted += ')';
            if (local.length > 3) formatted += ` ${local.slice(3, 6)}`;
            if (local.length > 6) formatted += `-${local.slice(6, 8)}`;
            if (local.length > 8) formatted += `-${local.slice(8, 10)}`;
        }

        return formatted;
    }

    // Normalize phone to +380XXXXXXXXX
    function normalizePhone(value) {
        const digits = value.replace(/\D/g, '');
        if (digits.startsWith('38') && digits.length === 12) {
            return `+${digits}`;
        }
        const padded = digits.startsWith('38') ? digits.slice(0, 12) : `38${digits}`.slice(0, 12);
        return `+${padded.padEnd(12, '0')}`;
    }

    // Validate Ukrainian phone: +38XXXXXXXXXX (12 digits after +)
    function isValidPhone(value) {
        if (!value || value === '+38' || value === '+38 ()') return true; // empty is ok
        const normalized = normalizePhone(value);
        return /^\+38\d{10}$/.test(normalized);
    }

    // Validate email format
    function isValidEmail(value) {
        if (!value.trim()) return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    // Async email uniqueness check
    let emailCheckTimeout = null;
    let emailCheckPromise = null;

    function checkEmailUniqueness(email) {
        if (emailCheckTimeout) {
            clearTimeout(emailCheckTimeout);
        }

        return new Promise(function(resolve) {
            emailCheckTimeout = setTimeout(function() {
                emailCheckPromise = fetch(checkEmailUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                       document.querySelector('input[name="_token"]')?.value || ''
                    },
                    body: JSON.stringify({ email: email, client_id: clientId })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    resolve(data);
                })
                .catch(function(err) {
                    console.error('Email check error:', err);
                    resolve({ valid: true, message: '' }); // allow on error
                });
            }, 500); // debounce 500ms
        });
    }

    // Apply formatting to phone inputs
    document.querySelectorAll('.phone-input').forEach(function(input) {
        // Format existing value on page load
        if (input.value) {
            input.value = formatPhone(input.value);
        }

        input.addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            const oldLength = this.value.length;
            this.value = formatPhone(this.value);
            const newLength = this.value.length;
            // Try to maintain cursor position
            if (cursorPos !== null) {
                const newPos = cursorPos + (newLength - oldLength);
                this.setSelectionRange(newPos, newPos);
            }
            // Clear error on input
            this.classList.remove('is-invalid');
            const errorEl = document.getElementById(this.id.replace('-input', '-error'));
            if (errorEl) errorEl.textContent = '';
        });
    });

    // Email async validation
    const emailInput = document.getElementById('email-input');
    const emailError = document.getElementById('email-error');
    let emailIsValid = false;

    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();

            if (!email) {
                this.classList.remove('is-invalid', 'is-valid');
                if (emailError) emailError.textContent = '';
                emailIsValid = false;
                return;
            }

            if (!isValidEmail(email)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                if (emailError) emailError.textContent = 'Введіть коректну email адресу';
                emailIsValid = false;
                return;
            }

            // Check uniqueness async
            this.classList.remove('is-invalid', 'is-valid');
            if (emailError) emailError.textContent = 'Перевірка...';

            checkEmailUniqueness(email).then(function(result) {
                if (result.valid) {
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    if (emailError) emailError.textContent = '';
                    emailIsValid = true;
                } else {
                    emailInput.classList.add('is-invalid');
                    emailInput.classList.remove('is-valid');
                    if (emailError) emailError.textContent = result.message;
                    emailIsValid = false;
                }
            });
        });

        emailInput.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
            if (emailError) emailError.textContent = '';
            emailIsValid = false;
        });
    }

    // Form validation on submit
    const form = document.querySelector('form[action*="client.save"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;

            // Validate phone
            const phoneInput = document.getElementById('phone-input');
            if (phoneInput && phoneInput.value && !isValidPhone(phoneInput.value)) {
                phoneInput.classList.add('is-invalid');
                const errorEl = document.getElementById('phone-error');
                if (errorEl) errorEl.textContent = 'Введіть коректний номер телефону (наприклад: +380671234567)';
                hasErrors = true;
            }

            // Validate phone2
            const phone1Input = document.getElementById('phone1-input');
            if (phone1Input && phone1Input.value && !isValidPhone(phone1Input.value)) {
                phone1Input.classList.add('is-invalid');
                const errorEl = document.getElementById('phone1-error');
                if (errorEl) errorEl.textContent = 'Введіть коректний номер телефону';
                hasErrors = true;
            }

            // Validate email format
            const emailInput = document.getElementById('email-input');
            if (emailInput && !isValidEmail(emailInput.value)) {
                emailInput.classList.add('is-invalid');
                const errorEl = document.getElementById('email-error');
                if (errorEl) errorEl.textContent = 'Введіть коректну email адресу';
                hasErrors = true;
            }

            // Check if email was validated async
            if (emailInput && emailInput.value && !emailIsValid) {
                // Trigger async check and prevent submit
                emailInput.dispatchEvent(new Event('blur'));
                e.preventDefault();
                return false;
            }

            if (hasErrors) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ── Delete KYC photo ──────────────────────────────────────────────────────
    const deleteButtons = document.querySelectorAll('.js-delete-kyc-photo');
    const deleteKycUrl = "{{ route('client.deleteKycPhoto') }}";
    const csrfToken = "{{ csrf_token() }}";

    // ── Client groups modal ───────────────────────────────────────────────────
    const clientGroupModalEl = document.getElementById('clientGroupModal');
    const clientGroupSelect = document.getElementById('client-group-select');

    if (clientGroupModalEl && clientGroupSelect) {
        const clientGroupsIndexUrl = @json(route('client.groups.index'));
        const clientGroupsStoreUrl = @json(route('client.groups.store'));
        const clientGroupsBaseUrl = @json(url('/client/groups'));
        const clientGroupAlert = document.getElementById('client-group-alert');
        const clientGroupSearch = document.getElementById('client-group-search');
        const clientGroupSearchButton = document.getElementById('client-group-search-button');
        const clientGroupTableBody = document.querySelector('#client-group-table tbody');
        const clientGroupFormTitle = document.getElementById('client-group-form-title');
        const clientGroupId = document.getElementById('client-group-id');
        const clientGroupName = document.getElementById('client-group-name');
        const clientGroupStatus = document.getElementById('client-group-status');
        const clientGroupNewButton = document.getElementById('client-group-new-button');
        const clientGroupSaveButton = document.getElementById('client-group-save-button');
        const clientGroupDeleteButton = document.getElementById('client-group-delete-button');

        function escapeClientGroupHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showClientGroupAlert(type, message) {
            if (!clientGroupAlert) return;
            clientGroupAlert.className = 'alert alert-' + type;
            clientGroupAlert.textContent = message;
            clientGroupAlert.classList.remove('d-none');
        }

        function clearClientGroupAlert() {
            if (!clientGroupAlert) return;
            clientGroupAlert.className = 'alert d-none';
            clientGroupAlert.textContent = '';
        }

        function resetClientGroupForm() {
            clientGroupId.value = '';
            clientGroupName.value = '';
            clientGroupStatus.value = '0';
            clientGroupFormTitle.textContent = 'Новая группа';
            clientGroupDeleteButton.classList.add('d-none');
            clearClientGroupAlert();
            clientGroupName.focus();
        }

        function fillClientGroupForm(group) {
            clientGroupId.value = group.id;
            clientGroupName.value = group.name || '';
            clientGroupStatus.value = String(group.status ?? '0');
            clientGroupFormTitle.textContent = 'Редактирование группы';
            clientGroupDeleteButton.classList.remove('d-none');
            clearClientGroupAlert();
            clientGroupName.focus();
        }

        function upsertClientGroupOption(group, selected) {
            let option = clientGroupSelect.querySelector('option[value="' + group.id + '"]');

            if (!option) {
                option = document.createElement('option');
                option.value = group.id;
                clientGroupSelect.appendChild(option);
            }

            option.textContent = group.name;

            if (selected) {
                clientGroupSelect.value = String(group.id);
            }
        }

        function removeClientGroupOption(groupId) {
            const option = clientGroupSelect.querySelector('option[value="' + groupId + '"]');

            if (option) {
                option.remove();
            }

            if (clientGroupSelect.value === String(groupId)) {
                clientGroupSelect.value = '';
            }
        }

        function renderClientGroups(groups) {
            if (!clientGroupTableBody) return;

            if (!groups.length) {
                clientGroupTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Группы не найдены</td></tr>';
                return;
            }

            clientGroupTableBody.innerHTML = groups.map(function(group) {
                return [
                    '<tr data-group-id="' + escapeClientGroupHtml(group.id) + '">',
                    '<td class="fw-semibold">' + escapeClientGroupHtml(group.name) + '</td>',
                    '<td>' + escapeClientGroupHtml(group.status_label || '') + '</td>',
                    '<td class="text-end">',
                    '<button type="button" class="btn btn-outline-primary btn-sm me-1 js-client-group-select">Выбрать</button>',
                    '<button type="button" class="btn btn-outline-secondary btn-sm js-client-group-edit">Изменить</button>',
                    '</td>',
                    '</tr>',
                ].join('');
            }).join('');

            groups.forEach(function(group) {
                const row = clientGroupTableBody.querySelector('[data-group-id="' + group.id + '"]');
                if (row) {
                    row.dataset.group = JSON.stringify(group);
                }
            });

        }

        async function loadClientGroups() {
            if (clientGroupTableBody) {
                clientGroupTableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Загрузка...</td></tr>';
            }

            const url = new URL(clientGroupsIndexUrl, window.location.origin);
            const query = clientGroupSearch.value.trim();
            if (query) {
                url.searchParams.set('q', query);
            }

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json().catch(function() { return {}; });

                if (!response.ok) {
                    showClientGroupAlert('danger', payload.message || 'Не удалось загрузить группы.');
                    renderClientGroups([]);
                    return [];
                }

                const groups = payload.items || [];
                renderClientGroups(groups);
                return groups;
            } catch (error) {
                showClientGroupAlert('danger', error.message || 'Ошибка загрузки групп.');
                renderClientGroups([]);
                return [];
            }
        }

        async function saveClientGroup() {
            const groupId = clientGroupId.value;
            const name = clientGroupName.value.trim();

            if (!name) {
                showClientGroupAlert('warning', 'Введите название группы.');
                clientGroupName.focus();
                return;
            }

            clientGroupSaveButton.disabled = true;
            clearClientGroupAlert();

            try {
                const response = await fetch(groupId ? clientGroupsBaseUrl + '/' + groupId : clientGroupsStoreUrl, {
                    method: groupId ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        name: name,
                        status: clientGroupStatus.value,
                    }),
                });
                const payload = await response.json().catch(function() { return {}; });

                if (!response.ok || payload.success === false) {
                    showClientGroupAlert('danger', payload.message || 'Не удалось сохранить группу.');
                    return;
                }

                upsertClientGroupOption(payload.item, true);
                await loadClientGroups();
                fillClientGroupForm(payload.item);
                showClientGroupAlert('success', 'Группа сохранена.');
            } catch (error) {
                showClientGroupAlert('danger', error.message || 'Ошибка сохранения группы.');
            } finally {
                clientGroupSaveButton.disabled = false;
            }
        }

        async function deleteClientGroup() {
            const groupId = clientGroupId.value;
            if (!groupId) return;

            if (!confirm('Удалить эту группу?')) {
                return;
            }

            clientGroupDeleteButton.disabled = true;
            clearClientGroupAlert();

            try {
                const response = await fetch(clientGroupsBaseUrl + '/' + groupId, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json().catch(function() { return {}; });

                if (!response.ok || payload.success === false) {
                    showClientGroupAlert('danger', payload.message || 'Не удалось удалить группу.');
                    return;
                }

                removeClientGroupOption(groupId);
                await loadClientGroups();
                resetClientGroupForm();
                showClientGroupAlert('success', 'Группа удалена.');
            } catch (error) {
                showClientGroupAlert('danger', error.message || 'Ошибка удаления группы.');
            } finally {
                clientGroupDeleteButton.disabled = false;
            }
        }

        clientGroupModalEl.addEventListener('shown.bs.modal', function() {
            loadClientGroups();
            clientGroupSearch.focus();
        });

        clientGroupSearchButton.addEventListener('click', loadClientGroups);
        clientGroupSearch.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                loadClientGroups();
            }
        });

        clientGroupNewButton.addEventListener('click', resetClientGroupForm);
        clientGroupSaveButton.addEventListener('click', saveClientGroup);
        clientGroupDeleteButton.addEventListener('click', deleteClientGroup);

        clientGroupTableBody.addEventListener('click', function(event) {
            const row = event.target.closest('tr[data-group]');
            if (!row) return;

            const group = JSON.parse(row.dataset.group || '{}');

            if (event.target.closest('.js-client-group-select')) {
                clientGroupSelect.value = group.id;
                fillClientGroupForm(group);
                showClientGroupAlert('success', 'Группа выбрана для клиента. Сохраните карточку клиента.');
                return;
            }

            if (event.target.closest('.js-client-group-edit')) {
                fillClientGroupForm(group);
            }
        });
    }

    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            if (!confirm('Видалити це фото?')) {
                return;
            }

            const clientId = this.dataset.clientId;
            const photoType = this.dataset.photoType;
            const card = this.closest('.client-kyc-photo');
            const self = this;

            self.classList.add('client-kyc-photo__delete--loading');

            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('id', clientId);
            formData.append('type', photoType);

            fetch(deleteKycUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(text) {
                        throw new Error('HTTP ' + r.status + ': ' + text.slice(0, 200));
                    });
                }
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Replace photo content with empty state
                    const label = card.querySelector('.client-kyc-photo__label');
                    const labelText = label ? label.textContent : '';
                    const column = labelText.split('·')[0]?.trim() || '';

                    card.innerHTML = [
                        '<div class="client-kyc-photo__label">' + labelText.replace(/</g, '<') + '</div>',
                        '<div class="client-kyc-photo__empty">Фото не загружено</div>',
                        '<div class="client-kyc-photo__meta">' + column.replace(/</g, '<') + '</div>'
                    ].join('');
                } else {
                    alert('Помилка: ' + (data.message || 'Не вдалося видалити фото'));
                }
            })
            .catch(function(err) {
                console.error('Delete KYC photo error:', err);
                alert('Помилка при видаленні фото: ' + err.message);
            })
            .finally(function() {
                self.classList.remove('client-kyc-photo__delete--loading');
            });
        });
    });

    // ── Client garage ────────────────────────────────────────────────────────
    const garageModalEl = document.getElementById('garageVehicleModal');
    if (garageModalEl) {
        const garageModal = new bootstrap.Modal(garageModalEl);
        const garageLookupUrl = @json(route('client.garage.lookup'));
        const garageUpdateUrl = @json(route('client.garage.update'));
        const garageClientId = @json((string) ($client->id ?? '0'));
        const garageAlert = document.getElementById('garage-modal-alert');
        const garageSearchInput = document.getElementById('garage-search-input');
        const garageSearchButton = document.getElementById('garage-search-button');
        const garageSaveButton = document.getElementById('garage-save-button');
        const garageVehicleIdInput = document.getElementById('garage-vehicle-id');
        const garageBrandInput = document.getElementById('garage-brand-input');
        const garageModelInput = document.getElementById('garage-model-input');
        const garageColorInput = document.getElementById('garage-color-input');
        const garageYearInput = document.getElementById('garage-year-input');
        const garageVinInput = document.getElementById('garage-vin-input');
        const garagePriceInput = document.getElementById('garage-price-input');
        const garageDescriptionInput = document.getElementById('garage-description-input');
        const garagePhotoInputs = Array.from(document.querySelectorAll('.garage-photo-input'));
        const garagePhotoFileInputs = Array.from(document.querySelectorAll('.garage-photo-file-input'));
        const garagePhotoPreviews = Array.from(document.querySelectorAll('[data-photo-preview]'));
        const garagePhotoEmptyPreviews = Array.from(document.querySelectorAll('[data-photo-empty]'));

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showGarageAlert(type, message) {
            garageAlert.className = 'alert alert-' + type;
            garageAlert.textContent = message;
            garageAlert.classList.remove('d-none');
        }

        function hideGarageAlert() {
            garageAlert.className = 'alert d-none';
            garageAlert.textContent = '';
        }

        function normalizeGarageSearch(value) {
            return String(value || '').trim().toUpperCase().replace(/[^A-ZА-ЯІЇЄҐ0-9]/g, '');
        }

        function firstPhoto(vehicle) {
            const garagePhoto = (vehicle.garage_photos || []).find(function(photo) {
                return photo;
            });

            return vehicle.photo_url || garagePhoto || '';
        }

        function setPhotoPreview(index, photo) {
            const preview = garagePhotoPreviews[index];
            const empty = garagePhotoEmptyPreviews[index];
            if (!preview || !empty) {
                return;
            }

            if (photo) {
                preview.src = photo;
                preview.classList.remove('d-none');
                empty.classList.add('d-none');
            } else {
                preview.removeAttribute('src');
                preview.classList.add('d-none');
                empty.classList.remove('d-none');
            }
        }

        function setGaragePhotoPreviews(vehicle) {
            const photos = vehicle?.garage_photos || [];
            for (let index = 0; index < 5; index++) {
                setPhotoPreview(index, photos[index] || '');
            }
        }

        function fillGarageForm(vehicle) {
            hideGarageAlert();
            const characteristics = vehicle?.characteristics || {};
            garageVehicleIdInput.value = vehicle?.id || '';
            garageBrandInput.value = vehicle?.brand || characteristics.brand || '';
            garageModelInput.value = vehicle?.model || characteristics.model || '';
            garageColorInput.value = vehicle?.color || characteristics.color || '';
            garageYearInput.value = vehicle?.year || characteristics.year || '';
            garageVinInput.value = vehicle?.vin || '';
            garagePriceInput.value = vehicle?.vehicle_price ?? '';
            garageDescriptionInput.value = vehicle?.description || characteristics.description || '';
            garagePhotoInputs.forEach(function(input, index) {
                input.value = vehicle?.garage_photos?.[index] || '';
            });
            garagePhotoFileInputs.forEach(function(input) {
                input.value = '';
            });
            setGaragePhotoPreviews(vehicle || {});
            garageSaveButton.disabled = !vehicle?.id;
        }

        function openGarageModal(vehicle) {
            garageSearchInput.value = '';
            fillGarageForm(vehicle || {});
            garageModal.show();
            setTimeout(function() {
                if (!vehicle?.id) {
                    garageSearchInput.focus();
                }
            }, 200);
        }

        function garageRowHtml(vehicle) {
            const photo = firstPhoto(vehicle);
            const characteristics = vehicle.characteristics || {};
            const title = [vehicle.brand || characteristics.brand, vehicle.model || characteristics.model].filter(Boolean).join(' ') || vehicle.title || 'Без названия';
            const number = vehicle.vehicle_number || '-';
            const vin = vehicle.vin || '-';
            const price = vehicle.vehicle_price !== null && vehicle.vehicle_price !== undefined && vehicle.vehicle_price !== ''
                ? Number(vehicle.vehicle_price).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '-';
            const checkedAt = vehicle.checked_at || '-';
            const photoHtml = photo
                ? '<img src="' + escapeHtml(photo) + '" alt="' + escapeHtml(title) + '" class="client-garage-thumb">'
                : '<span class="client-garage-empty-thumb">Нет фото</span>';

            return [
                '<td data-garage-photo-cell>' + photoHtml + '</td>',
                '<td><div class="fw-semibold" data-garage-title-cell>' + escapeHtml(title) + '</div><div class="client-garage-meta">' + escapeHtml(vehicle.input_type || '') + ': ' + escapeHtml(vehicle.input_value || '') + '</div></td>',
                '<td><div data-garage-number-cell>' + escapeHtml(number) + '</div><div class="client-garage-meta" data-garage-vin-cell>' + escapeHtml(vin) + '</div></td>',
                '<td data-garage-price-cell>' + escapeHtml(price) + '</td>',
                '<td data-garage-checked-cell>' + escapeHtml(checkedAt) + '</td>',
                '<td class="text-end"><button type="button" class="btn btn-outline-primary btn-sm js-garage-view">Просмотр</button></td>'
            ].join('');
        }

        function upsertGarageRow(vehicle) {
            const tableBody = document.querySelector('#garage-table tbody');
            const emptyRow = document.getElementById('garage-empty-row');
            if (emptyRow) {
                emptyRow.remove();
            }

            let row = tableBody.querySelector('[data-garage-row="' + vehicle.id + '"]');
            if (!row) {
                row = document.createElement('tr');
                row.dataset.garageRow = vehicle.id;
                tableBody.prepend(row);
            }

            row.innerHTML = garageRowHtml(vehicle);
            row.querySelector('.js-garage-view').dataset.vehicle = JSON.stringify(vehicle);
        }

        document.getElementById('garage-add-button')?.addEventListener('click', function() {
            openGarageModal({});
        });

        document.querySelector('#garage-table')?.addEventListener('click', function(event) {
            const button = event.target.closest('.js-garage-view');
            if (!button) {
                return;
            }

            const vehicle = JSON.parse(button.dataset.vehicle || '{}');
            openGarageModal(vehicle);
        });

        garagePhotoInputs.forEach(function(input, index) {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    setPhotoPreview(index, this.value.trim());
                }
            });
        });

        garagePhotoFileInputs.forEach(function(input, index) {
            input.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (!file) {
                    setPhotoPreview(index, garagePhotoInputs[index]?.value || '');
                    return;
                }

                setPhotoPreview(index, URL.createObjectURL(file));
            });
        });

        garageSearchButton.addEventListener('click', async function() {
            const vehicleInfo = normalizeGarageSearch(garageSearchInput.value);
            garageSearchInput.value = vehicleInfo;

            if (!vehicleInfo) {
                showGarageAlert('warning', 'Введите номер или VIN.');
                return;
            }

            garageSearchButton.disabled = true;
            garageSaveButton.disabled = true;
            showGarageAlert('info', 'Ищем авто...');

            try {
                const response = await fetch(garageLookupUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        client_id: garageClientId,
                        vehicle_info: vehicleInfo,
                    }),
                });
                const payload = await response.json().catch(function() { return {}; });

                if (!response.ok || payload.success === false) {
                    showGarageAlert('danger', payload.message || 'Не удалось найти авто.');
                    return;
                }

                fillGarageForm(payload.item);
                upsertGarageRow(payload.item);
                showGarageAlert('success', payload.message || 'Авто добавлено.');
            } catch (error) {
                showGarageAlert('danger', error.message || 'Ошибка поиска авто.');
            } finally {
                garageSearchButton.disabled = false;
            }
        });

        garageSaveButton.addEventListener('click', async function() {
            const vehicleId = garageVehicleIdInput.value;
            if (!vehicleId) {
                showGarageAlert('warning', 'Сначала найдите или выберите авто.');
                return;
            }

            garageSaveButton.disabled = true;
            showGarageAlert('info', 'Сохраняем авто...');

            try {
                const formData = new FormData();
                formData.append('client_id', garageClientId);
                formData.append('vehicle_id', vehicleId);
                formData.append('brand', garageBrandInput.value);
                formData.append('model', garageModelInput.value);
                formData.append('color', garageColorInput.value);
                formData.append('year', garageYearInput.value || '');
                formData.append('vin', garageVinInput.value);
                formData.append('vehicle_price', garagePriceInput.value || '');
                formData.append('description', garageDescriptionInput.value);
                garagePhotoInputs.forEach(function(input, index) {
                    formData.append('garage_photos[' + index + ']', input.value);
                });
                garagePhotoFileInputs.forEach(function(input, index) {
                    if (input.files && input.files[0]) {
                        formData.append('garage_photo_files[' + index + ']', input.files[0]);
                    }
                });

                const response = await fetch(garageUpdateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const payload = await response.json().catch(function() { return {}; });

                if (!response.ok || payload.success === false) {
                    showGarageAlert('danger', payload.message || 'Не удалось сохранить авто.');
                    garageSaveButton.disabled = false;
                    return;
                }

                fillGarageForm(payload.item);
                upsertGarageRow(payload.item);
                showGarageAlert('success', payload.message || 'Авто сохранено.');
            } catch (error) {
                showGarageAlert('danger', error.message || 'Ошибка сохранения авто.');
                garageSaveButton.disabled = false;
            }
        });
    }
})();
</script>
@endpush
@endsection
