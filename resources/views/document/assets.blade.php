@extends('home')

@section('title', 'Активы')

@section('content')
@php
    $operationOptions = \App\Models\AssetOperation::operationOptions();
    $assetKindOptions = \App\Models\BusinessAsset::typeOptions();
@endphp

<div class="container py-4 asset-doc-page" data-bs-theme="dark">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="asset-stat">
                <div class="asset-stat__label">Активных активов</div>
                <div class="asset-stat__value">{{ (int) $summary['active_assets'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="asset-stat">
                <div class="asset-stat__label">Остаточная стоимость</div>
                <div class="asset-stat__value">{{ number_format((float) $summary['current_value'], 2, '.', ' ') }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="asset-stat">
                <div class="asset-stat__label">Проведенных операций</div>
                <div class="asset-stat__value">{{ (int) $summary['posted_operations'] }}</div>
            </div>
        </div>
    </div>

    <div class="asset-panel mb-4">
        <h2 class="h5 mb-3 text-light">Новая операция</h2>
        <form method="POST" action="{{ route('document.assets.operations.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Тип операции</label>
                <select name="operation_type" class="form-select">
                    @foreach($operationOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('operation_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Существующий актив</label>
                <select name="business_asset_id" class="form-select">
                    <option value="">Создать новый / не выбран</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected((string) old('business_asset_id') === (string) $asset->id)>
                            {{ $asset->name }} · {{ number_format((float) $asset->current_value, 2, '.', ' ') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Группа актива</label>
                <select name="asset_kind" class="form-select">
                    @foreach($assetKindOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('asset_kind', 'equipment') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Тип актива</label>
                <select name="asset_type_id" class="form-select">
                    <option value="">Без типа</option>
                    @foreach($assetTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) old('asset_type_id') === (string) $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Название актива</label>
                <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name') }}" placeholder="Например: Станок, сервер, ETH, R&D MVP">
            </div>
            <div class="col-md-2">
                <label class="form-label">Дата</label>
                <input type="date" name="operation_date" class="form-control" value="{{ old('operation_date', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Сумма</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Балансовая стоимость</label>
                <input type="number" step="0.01" min="0" name="carrying_amount" class="form-control" value="{{ old('carrying_amount') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Провести сразу</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="post_after_save" value="1" id="post-after-save" @checked(old('post_after_save'))>
                    <label class="form-check-label" for="post-after-save">Да</label>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Денежный счет</label>
                <select name="cash_account_id" class="form-select">
                    <option value="">Без денежного потока</option>
                    @foreach($cashAccounts as $cash)
                        <option value="{{ $cash->id }}" @selected((string) old('cash_account_id') === (string) $cash->id)>{{ $cash->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Вид платежа</label>
                <select name="payment_type_id" class="form-select">
                    <option value="">Без вида платежа</option>
                    @foreach($paymentTypes as $paymentType)
                        <option value="{{ $paymentType->id }}" @selected((string) old('payment_type_id') === (string) $paymentType->id)>{{ $paymentType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Примечание</label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-warning px-4">Сохранить операцию</button>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="asset-panel h-100">
                <h2 class="h5 mb-3 text-light">Список активов</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-dark table-hover align-middle asset-table">
                        <thead>
                            <tr>
                                <th>Актив</th>
                                <th>Тип</th>
                                <th class="text-end">Стоимость</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $asset->name }}</div>
                                        <div class="text-muted small">{{ \App\Models\BusinessAsset::typeLabel($asset->type) }}</div>
                                    </td>
                                    <td>{{ $asset->asset_type_name ?: 'Без типа' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $asset->current_value, 2, '.', ' ') }}</td>
                                    <td><span class="badge bg-secondary">{{ $asset->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">Активов пока нет.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="asset-panel h-100">
                <h2 class="h5 mb-3 text-light">Операции</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-dark table-hover align-middle asset-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Операция</th>
                                <th>Актив</th>
                                <th class="text-end">Сумма</th>
                                <th>Платеж</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operations as $operation)
                                <tr>
                                    <td>{{ $operation->operation_date ? \Carbon\Carbon::parse($operation->operation_date)->format('d.m.Y') : '' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ \App\Models\AssetOperation::operationLabel($operation->operation_type) }}</div>
                                        <div class="small {{ $operation->provodka ? 'text-success' : 'text-muted' }}">
                                            {{ $operation->provodka ? 'Проведено' : 'Черновик' }}
                                        </div>
                                    </td>
                                    <td>{{ $operation->asset_name ?: 'Без актива' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $operation->amount, 2, '.', ' ') }}</td>
                                    <td>
                                        <div>{{ $operation->payment_type_name ?: 'Без вида' }}</div>
                                        <div class="text-muted small">{{ $operation->cash_account_name ?: 'Без счета' }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if($operation->provodka)
                                            <form method="POST" action="{{ route('document.assets.operations.reverse', $operation->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning btn-sm">Снять</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('document.assets.operations.post', $operation->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm">Провести</button>
                                            </form>
                                            <form method="POST" action="{{ route('document.assets.operations.destroy', $operation->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">Операций пока нет.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .asset-doc-page {
        color: #f6efe6;
    }
    .asset-panel,
    .asset-stat {
        background: rgba(30, 22, 16, 0.82);
        border: 1px solid rgba(229, 177, 84, 0.22);
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22);
    }
    .asset-stat__label {
        color: rgba(246, 239, 230, 0.68);
        font-size: 13px;
        margin-bottom: 6px;
    }
    .asset-stat__value {
        color: #e5b154;
        font-size: 26px;
        font-weight: 700;
    }
    .asset-table th {
        color: #e5b154;
        border-color: rgba(229, 177, 84, 0.24);
    }
    .asset-table td {
        border-color: rgba(255, 255, 255, 0.1);
    }
    .asset-doc-page .form-control,
    .asset-doc-page .form-select {
        background-color: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.18);
        color: #fff;
    }
</style>
@endsection
