@extends('home')

@section('title', 'Налаштування')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container mt-4">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="reteil" data-title="📁 Проекти">
                <div class="card-body text-center">
                    <h5 class="card-title">📁 Проекти</h5>
                    <p class="card-text text-muted">Управління проектами</p>
                    <span class="badge bg-primary" id="badge-reteil">{{ count($projects ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="status" data-title="📊 Статуси">
                <div class="card-body text-center">
                    <h5 class="card-title">📊 Статуси</h5>
                    <p class="card-text text-muted">Статуси документів</p>
                    <span class="badge bg-success" id="badge-status">{{ count($statuses ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="reestr" data-title="💳 Види платежів">
                <div class="card-body text-center">
                    <h5 class="card-title">💳 Види платежів</h5>
                    <p class="card-text text-muted">Реєстр платежів</p>
                    <span class="badge bg-info" id="badge-reestr">{{ count($reestrs ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="tgroup" data-title="👥 Тип клієнта">
                <div class="card-body text-center">
                    <h5 class="card-title">👥 Тип клієнта</h5>
                    <p class="card-text text-muted">Типи клієнтів і цінові групи</p>
                    <span class="badge bg-secondary" id="badge-tgroup">{{ count($tgroups ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="tclient" data-title="🏷 Тип контрагента">
                <div class="card-body text-center">
                    <h5 class="card-title">🏷 Тип контрагента</h5>
                    <p class="card-text text-muted">Ролі контрагентів і підрозділів</p>
                    <span class="badge bg-dark" id="badge-tclient">{{ count($tclients ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="oplata" data-title="💰 Каса">
                <div class="card-body text-center">
                    <h5 class="card-title">💰 Каса</h5>
                    <p class="card-text text-muted">Види оплати</p>
                    <span class="badge bg-warning text-dark" id="badge-oplata">{{ count($oplatas ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="sklads" data-title="🏢 Офіси">
                <div class="card-body text-center">
                    <h5 class="card-title">🏢 Офіси</h5>
                    <p class="card-text text-muted">Список офісів</p>
                    <span class="badge bg-secondary" id="badge-sklads">{{ count($sklads ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="deposit" data-title="🏦 Депозити">
                <div class="card-body text-center">
                    <h5 class="card-title">🏦 Депозити</h5>
                    <p class="card-text text-muted">Депозитні рахунки</p>
                    <span class="badge bg-dark" id="badge-deposit">{{ count($deposits ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-info setting-card" data-bs-toggle="modal" data-bs-target="#modalCatalog">
                <div class="card-body text-center">
                    <h5 class="card-title">🗂 Категорії товарів</h5>
                    <p class="card-text text-muted">Категорії та підкатегорії каталогу</p>
                    <span class="badge bg-info text-dark" id="badge-catalog">{{ $catalogTopCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-primary setting-card" data-bs-toggle="modal" data-bs-target="#modalProfile">
                <div class="card-body text-center">
                    <h5 class="card-title">👤 Профіль</h5>
                    <p class="card-text text-muted">{{ session('login', '') }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-warning setting-card" data-bs-toggle="modal" data-bs-target="#modalFirms">
                <div class="card-body text-center">
                    <h5 class="card-title">🏛 Мої компанії</h5>
                    <p class="card-text text-muted">Редагування, додавання та видалення компаній</p>
                    <span class="badge bg-warning text-dark" id="badge-firms">{{ count($myCompanies ?? []) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCatalog" tabindex="-1" aria-labelledby="modalCatalogLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="modal-title" id="modalCatalogLabel">🗂 Категорії товарів</h5>
                    <div class="small text-muted" id="catalog-current-level">Корінь каталогу</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-md-3" id="btn-catalog-back" style="display:none;">← Назад</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-catalog-add">+ Додати категорію</button>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="catalog-form-area" style="display:none">
                <form id="catalog-form">
                    <input type="hidden" id="catalog-id" value="">
                    <input type="hidden" id="catalog-parent-id" value="0">

                    <div class="alert alert-secondary py-2 mb-3">
                        <strong>Рівень:</strong> <span id="catalog-parent-label">Корінь каталогу</span>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Назва RU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="catalog-name-ru" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Назва UA</label>
                            <input type="text" class="form-control" id="catalog-name-ua">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Назва EN</label>
                            <input type="text" class="form-control" id="catalog-name-en">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Порядок (num)</label>
                            <input type="number" class="form-control" id="catalog-num" min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="catalog-visible" checked>
                                <label class="form-check-label" for="catalog-visible">Показывать</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="catalog-firstpage">
                                <label class="form-check-label" for="catalog-firstpage">На первой странице</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Опис RU</label>
                            <textarea class="form-control" id="catalog-description-ru" rows="4"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Опис UA</label>
                            <textarea class="form-control" id="catalog-description-ua" rows="4"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Опис EN</label>
                            <textarea class="form-control" id="catalog-description-en" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" id="btn-catalog-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="catalog-list-area">
                <div class="mb-3" id="catalog-breadcrumb"></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>num</th>
                                <th>Назви</th>
                                <th>Прапори</th>
                                <th>Опис</th>
                                <th>Підкатегорії</th>
                                <th class="text-end">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="catalog-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="catalog-empty-msg" style="display:none">Категорій на цьому рівні ще немає</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrud" tabindex="-1" aria-labelledby="modalCrudLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalCrudLabel"></h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-add">+ Додати</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="conf-form-area" style="display:none">
                <form id="crud-form">
                    <input type="hidden" id="form-id" value="">
                    <div class="mb-3">
                        <label for="form-name" class="form-label">Назва <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="form-name" required>
                    </div>
                    <div class="mb-3">
                        <label for="form-color" class="form-label">Колір</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="form-color-picker" value="#ffffff">
                            <input type="text" class="form-control" id="form-color" placeholder="#hex">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="form-status" class="form-label" id="form-status-label">Статус</label>
                        <select class="form-select" id="form-status">
                            <option value="1">Активний</option>
                            <option value="0">Неактивний</option>
                        </select>
                        <div class="form-text" id="form-status-help" style="display:none;"></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" id="btn-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="conf-list-area">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Назва</th>
                            <th>Колір</th>
                            <th>Статус</th>
                            <th class="text-end">Дії</th>
                        </tr>
                    </thead>
                    <tbody id="crud-tbody"></tbody>
                </table>
                <p class="text-center text-muted" id="empty-msg" style="display:none">Немає записів</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFirms" tabindex="-1" aria-labelledby="modalFirmsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalFirmsLabel">🏛 Мої компанії</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-firm-add">+ Додати компанію</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="firm-form-area" style="display:none">
                <form id="firm-form">
                    <input type="hidden" id="firm-id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Назва компанії <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firm-name" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ЄДРПОУ / RegNum</label>
                            <input type="text" class="form-control" id="firm-regnum">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ІПН / INN</label>
                            <input type="text" class="form-control" id="firm-inn">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Рахунок</label>
                            <input type="text" class="form-control" id="firm-schet">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Банк</label>
                            <input type="text" class="form-control" id="firm-bank">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">МФО</label>
                            <input type="text" class="form-control" id="firm-mfo">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Місто</label>
                            <input type="text" class="form-control" id="firm-town">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" class="form-control" id="firm-phone">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Тип / View</label>
                            <input type="text" class="form-control" id="firm-view">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Адреса</label>
                        <input type="text" class="form-control" id="firm-address">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Карта / Посилання</label>
                        <input type="text" class="form-control" id="firm-map">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Директор</label>
                            <input type="text" class="form-control" id="firm-direktor">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Підписант</label>
                            <input type="text" class="form-control" id="firm-pidpys">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Печатка</label>
                            <input type="text" class="form-control" id="firm-pechat">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" id="btn-firm-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="firm-list-area">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Назва</th>
                                <th>Телефон</th>
                                <th>Місто</th>
                                <th>Адреса</th>
                                <th>Директор</th>
                                <th class="text-end">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="firms-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="firms-empty-msg" style="display:none">Компаній ще немає</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProfile" tabindex="-1" aria-labelledby="modalProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProfileLabel">👤 Дані зареєстрованого відвідувача</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <strong>Логін:</strong> {{ session('login', '') }} <span class="text-muted">(не можна змінити)</span>
                </div>

                <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profileTab" type="button" role="tab">📝 Дані</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#passwordTab" type="button" role="tab">🔑 Пароль</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profileTab">
                        <form action="{{ route('settings.profileUpdate') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Ім'я</label>
                                    <input type="text" name="name" class="form-control" value="{{ $user->name ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Прізвище</label>
                                    <input type="text" name="secondname" class="form-control" value="{{ $user->secondname ?? '' }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>По батькові</label>
                                    <input type="text" name="fathername" class="form-control" value="{{ $user->fathername ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ $user->email ?? '' }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Телефон</label>
                                    <input type="text" name="phone" class="form-control" value="{{ $user->phone ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Місто</label>
                                    <input type="text" name="city" class="form-control" value="{{ $user->city ?? '' }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Дата народження</label>
                                    <input type="date" name="hbd" class="form-control" value="{{ $user->hbd ?? '' }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Роль контрагента</label>
                                    <input type="text" class="form-control" value="{{ $currentCounterpartyType->name ?? 'Не вказано' }}" readonly>
                                </div>
                            </div>
                            <div class="wallet-link-card mb-4">
                                <div class="wallet-link-header">
                                    <div>
                                        <div class="wallet-link-eyebrow">WEB3</div>
                                        <h6 class="wallet-link-title mb-1">Прив'язка адреси гаманця</h6>
                                        <p class="wallet-link-text mb-0">Після прив'язки цей гаманець зможе входити через Web3 і буде асоційований з вашим контрагентом.</p>
                                    </div>
                                </div>
                                <div class="wallet-link-state">
                                    <div class="wallet-link-meta">
                                        <span class="wallet-link-label">Поточний статус</span>
                                        <strong id="wallet-status-badge" class="wallet-status-badge {{ ($userWallets->count() ?? 0) > 0 ? 'is-linked' : 'is-empty' }}">
                                            {{ ($userWallets->count() ?? 0) > 0 ? 'Гаманці прив’язані' : 'Гаманці не прив’язані' }}
                                        </strong>
                                    </div>
                                    <div class="wallet-link-meta">
                                        <span class="wallet-link-label">Кількість</span>
                                        <span id="wallet-count">{{ $userWallets->count() }}</span>
                                    </div>
                                    <div class="wallet-link-meta">
                                        <span class="wallet-link-label">Остання мережа</span>
                                        <span id="wallet-linked-network">{{ optional($userWallets->first())->network ?? ($user->wallet_network ?? '—') }}</span>
                                    </div>
                                </div>
                                <div id="wallet-list" class="wallet-list">
                                    @forelse($userWallets as $wallet)
                                    <div class="wallet-list-item" data-wallet-address="{{ $wallet->address }}">
                                        <div class="wallet-list-main">
                                            <code title="{{ $wallet->address }}">{{ $wallet->address }}</code>
                                            <span class="wallet-list-network">{{ $wallet->network ?: 'Мережа не вказана' }}</span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger wallet-remove-btn" data-address="{{ $wallet->address }}">Відв’язати</button>
                                    </div>
                                    @empty
                                    <p id="wallet-list-empty" class="wallet-list-empty mb-0">Ще не прив’язано жодного гаманця.</p>
                                    @endforelse
                                </div>
                                <div class="wallet-link-actions">
                                    <button type="button" class="btn btn-warning" id="wallet-connect-btn">Додати гаманець</button>
                                </div>
                                <p id="wallet-link-feedback" class="wallet-link-feedback" style="display:none;"></p>
                            </div>
                            <button type="submit" class="btn btn-primary">💾 Зберегти дані</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="passwordTab">
                        <form action="{{ route('settings.passwordChange') }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label>Поточний пароль</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Новий пароль</label>
                                <input type="password" name="new_password" class="form-control" required minlength="4">
                            </div>
                            <div class="mb-3">
                                <label>Підтвердження пароля</label>
                                <input type="password" name="new_password_confirmation" class="form-control" required minlength="4">
                            </div>
                            <button type="submit" class="btn btn-warning">🔑 Змінити пароль</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .setting-card {
        cursor: pointer;
    }

    .form-control-color {
        width: 48px;
        height: 38px;
        padding: 3px;
    }

    .action-btn {
        padding: 2px 8px;
        font-size: 0.85rem;
    }

    .company-meta {
        font-size: 0.85rem;
        color: #aab4c8;
    }

    .catalog-meta {
        font-size: 0.85rem;
        color: #6b7280;
    }

    .catalog-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .catalog-breadcrumb button {
        border: 0;
        background: transparent;
        color: #2563eb;
        padding: 0;
        text-decoration: underline;
        cursor: pointer;
    }

    .wallet-link-card {
        margin-top: 1rem;
        padding: 1rem 1.1rem;
        border: 1px solid rgba(251, 191, 36, 0.22);
        border-radius: 1rem;
        background:
            radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 36%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03));
    }

    .wallet-link-eyebrow {
        color: #fbbf24;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        margin-bottom: 0.3rem;
    }

    .wallet-link-title {
        color: #fff;
    }

    .wallet-link-text {
        color: #b8c0d4;
        max-width: 700px;
    }

    .wallet-link-state {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.9rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    .wallet-link-meta {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .wallet-link-label {
        color: #9ca3af;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .wallet-status-badge {
        display: inline-flex;
        align-items: center;
        min-height: 36px;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.9rem;
        width: fit-content;
    }

    .wallet-status-badge.is-linked {
        background: rgba(34, 197, 94, 0.15);
        color: #86efac;
        border: 1px solid rgba(34, 197, 94, 0.35);
    }

    .wallet-status-badge.is-empty {
        background: rgba(156, 163, 175, 0.12);
        color: #d1d5db;
        border: 1px solid rgba(156, 163, 175, 0.26);
    }

    .wallet-link-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .wallet-list {
        display: grid;
        gap: 0.65rem;
        margin-bottom: 1rem;
    }

    .wallet-list-item {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: center;
        padding: 0.8rem 0.9rem;
        border-radius: 0.85rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .wallet-list-main {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        min-width: 0;
    }

    .wallet-list-main code {
        color: #f3f4f6;
        word-break: break-all;
    }

    .wallet-list-network {
        color: #9ca3af;
        font-size: 0.88rem;
    }

    .wallet-list-empty {
        color: #9ca3af;
    }

    .wallet-link-feedback {
        margin-top: 0.85rem;
        margin-bottom: 0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    initConfCrud(csrf);
    initCatalogCrud(csrf);
    initFirmsCrud(csrf);
    initWalletLink(csrf);

    function initConfCrud(csrfToken) {
        const modal = document.getElementById('modalCrud');
        const tbody = document.getElementById('crud-tbody');
        const formArea = document.getElementById('conf-form-area');
        const listArea = document.getElementById('conf-list-area');
        const form = document.getElementById('crud-form');
        const emptyMsg = document.getElementById('empty-msg');
        const addBtn = document.getElementById('btn-add');
        const cancelBtn = document.getElementById('btn-cancel');
        const colorPicker = document.getElementById('form-color-picker');
        const colorInput = document.getElementById('form-color');
        const statusLabel = document.getElementById('form-status-label');
        const statusSelect = document.getElementById('form-status');
        const statusHelp = document.getElementById('form-status-help');

        let currentType = '';

        modal.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            currentType = btn.dataset.type;
            document.getElementById('modalCrudLabel').textContent = btn.dataset.title;
            configureStatusField();
            hideForm();
            loadData();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideForm();
            currentType = '';
            configureStatusField();
        });

        addBtn.addEventListener('click', () => {
            document.getElementById('form-id').value = '';
            document.getElementById('form-name').value = '';
            colorInput.value = '';
            colorPicker.value = '#ffffff';
            document.getElementById('form-status').value = currentType === 'tclient' ? '0' : '1';
            showForm();
        });

        cancelBtn.addEventListener('click', hideForm);

        colorPicker.addEventListener('input', (e) => {
            colorInput.value = e.target.value;
        });

        colorInput.addEventListener('input', (e) => {
            if (/^#[0-9a-fA-F]{6}$/.test(e.target.value)) {
                colorPicker.value = e.target.value;
            }
        });

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editItem(id);
            }
            if (btn.dataset.action === 'delete') {
                deleteItem(id, btn);
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = document.getElementById('form-id').value;
            const payload = {
                type: currentType,
                name: document.getElementById('form-name').value.trim(),
                color: colorInput.value.trim(),
                status: document.getElementById('form-status').value,
            };

            if (!payload.name) return;

            fetch(id ? `/settings/api/${id}` : '/settings/api', {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    alert(data.message || 'Помилка');
                    return;
                }
                hideForm();
                loadData();
                updateBadge();
            })
            .catch(() => alert('Помилка мережі'));
        });

        function loadData() {
            fetch(`/settings/api/${currentType}`)
                .then((r) => r.json())
                .then(renderTable)
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Помилка завантаження</td></tr>';
                });
        }

        function renderTable(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                const colorHtml = item.color
                    ? `<span style="display:inline-block;width:16px;height:16px;background:${item.color};border-radius:3px;vertical-align:middle"></span> ${escapeHtml(item.color)}`
                    : '—';
                let statusLabel = '<span class="badge bg-secondary">Неактивний</span>';
                if (currentType === 'tgroup') {
                    statusLabel = String(item.status) === '1'
                        ? '<span class="badge bg-warning text-dark">Роздріб</span>'
                        : '<span class="badge bg-secondary">Додаткова група</span>';
                } else if (currentType === 'tclient') {
                    statusLabel = ({
                        '1': '<span class="badge bg-primary">Відділ продаж</span>',
                        '2': '<span class="badge bg-warning text-dark">Виробництво</span>',
                        '3': '<span class="badge bg-danger">Адміністрація</span>',
                        '0': '<span class="badge bg-secondary">Прочие</span>',
                    }[String(item.status)] || '<span class="badge bg-secondary">Прочие</span>');
                } else if (String(item.status) === '1') {
                    statusLabel = '<span class="badge bg-success">Активний</span>';
                }

                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>${escapeHtml(item.name || '')}</td>
                    <td>${colorHtml}</td>
                    <td>${statusLabel}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function editItem(id) {
            fetch(`/settings/api/${currentType}/${id}`)
                .then((r) => r.json())
                .then((item) => {
                    document.getElementById('form-id').value = item.id;
                    document.getElementById('form-name').value = item.name || '';
                    colorInput.value = item.color || '';
                    colorPicker.value = item.color || '#ffffff';
                    document.getElementById('form-status').value = item.status ?? '1';
                    showForm();
                })
                .catch(() => alert('Помилка завантаження'));
        }

        function deleteItem(id, btn) {
            if (!confirm('Видалити цей запис?')) return;

            fetch(`/settings/api/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    alert(data.message || 'Помилка');
                    return;
                }
                btn.closest('tr').remove();
                updateBadge();
                if (!tbody.children.length) {
                    emptyMsg.style.display = 'block';
                }
            })
            .catch(() => alert('Помилка мережі'));
        }

        function updateBadge() {
            fetch(`/settings/api/${currentType}`)
                .then((r) => r.json())
                .then((items) => {
                    const badge = document.getElementById(`badge-${currentType}`);
                    if (badge) {
                        badge.textContent = items.length;
                    }
                });
        }

        function showForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }

        function configureStatusField() {
            if (currentType === 'tgroup') {
                statusLabel.textContent = 'Тип групи';
                statusSelect.innerHTML = `
                    <option value="1">Роздрібна група</option>
                    <option value="0">Додаткова група</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = 'Для виділення роздрібної групи цін використовуй status = 1.';
            } else if (currentType === 'tclient') {
                statusLabel.textContent = 'Підрозділ';
                statusSelect.innerHTML = `
                    <option value="0">Прочие</option>
                    <option value="1">Відділ продаж</option>
                    <option value="2">Виробництво</option>
                    <option value="3">Адміністрація</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = 'status: 1 = відділ продаж, 2 = виробництво, 3 = адміністрація, 0 = прочие.';
            } else {
                statusLabel.textContent = 'Статус';
                statusSelect.innerHTML = `
                    <option value="1">Активний</option>
                    <option value="0">Неактивний</option>
                `;
                statusHelp.style.display = 'none';
                statusHelp.textContent = '';
            }
        }
    }

    function initWalletLink(csrfToken) {
        const connectBtn = document.getElementById('wallet-connect-btn');
        const feedback = document.getElementById('wallet-link-feedback');
        const statusBadge = document.getElementById('wallet-status-badge');
        const countNode = document.getElementById('wallet-count');
        const networkNode = document.getElementById('wallet-linked-network');
        const walletList = document.getElementById('wallet-list');

        if (!connectBtn || !feedback || !statusBadge || !countNode || !networkNode || !walletList) {
            return;
        }

        const setFeedback = (message, isError = false) => {
            feedback.style.display = 'block';
            feedback.textContent = message;
            feedback.style.color = isError ? '#fca5a5' : '#86efac';
        };

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderWalletList = (wallets) => {
            if (!wallets.length) {
                walletList.innerHTML = '<p id="wallet-list-empty" class="wallet-list-empty mb-0">Ще не прив’язано жодного гаманця.</p>';
                return;
            }

            walletList.innerHTML = wallets.map((wallet) => `
                <div class="wallet-list-item" data-wallet-address="${escapeHtml(wallet.address)}">
                    <div class="wallet-list-main">
                        <code title="${escapeHtml(wallet.address)}">${escapeHtml(wallet.address)}</code>
                        <span class="wallet-list-network">${escapeHtml(wallet.network || 'Мережа не вказана')}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger wallet-remove-btn" data-address="${escapeHtml(wallet.address)}">Відв’язати</button>
                </div>
            `).join('');
        };

        const updateWalletState = (wallets) => {
            const linked = wallets.length > 0;
            const latestWallet = wallets[0] || null;

            statusBadge.textContent = linked ? 'Гаманці прив’язані' : 'Гаманці не прив’язані';
            statusBadge.classList.toggle('is-linked', linked);
            statusBadge.classList.toggle('is-empty', !linked);
            countNode.textContent = String(wallets.length);
            networkNode.textContent = latestWallet && latestWallet.network ? latestWallet.network : '—';
            renderWalletList(wallets);
        };

        const postJson = async (url, payload = {}) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Помилка Web3-запиту.');
            }

            return data;
        };

        connectBtn.addEventListener('click', async () => {
            if (!window.ethereum) {
                setFeedback('Ethereum-гаманець не знайдено. Відкрийте сторінку в браузері з MetaMask.', true);
                return;
            }

            connectBtn.disabled = true;
            connectBtn.textContent = 'Підключаємо...';
            setFeedback('Запитуємо доступ до гаманця...');

            try {
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                const address = accounts && accounts[0];

                if (!address) {
                    throw new Error('Гаманець не повернув адресу.');
                }

                const chainId = await window.ethereum.request({ method: 'eth_chainId' }).catch(() => null);
                const network = chainId ? `EVM ${chainId}` : null;
                const challenge = await postJson('{{ route('wallet.challenge') }}', { address });
                const signature = await window.ethereum.request({
                    method: 'personal_sign',
                    params: [challenge.message, address],
                });
                const result = await postJson('{{ route('wallet.link') }}', { address, signature, network });
                const user = result.user || {};

                updateWalletState(user.wallets || []);
                setFeedback('Гаманець успішно додано до вашого контрагента.');
            } catch (error) {
                setFeedback(error.message || 'Не вдалося прив’язати гаманець.', true);
            } finally {
                connectBtn.disabled = false;
                connectBtn.textContent = 'Додати гаманець';
            }
        });

        walletList.addEventListener('click', async (event) => {
            const button = event.target.closest('.wallet-remove-btn');
            if (!button) {
                return;
            }

            const address = button.dataset.address;
            if (!address || !confirm('Відв’язати цей гаманець від контрагента?')) {
                return;
            }

            button.disabled = true;
            setFeedback('Відв’язуємо гаманець...');

            try {
                const result = await postJson('{{ route('wallet.unlink') }}', { address });
                updateWalletState((result.user && result.user.wallets) || []);
                setFeedback('Гаманець відв’язано.');
            } catch (error) {
                setFeedback(error.message || 'Не вдалося відв’язати гаманець.', true);
                button.disabled = false;
            }
        });
    }

    function initCatalogCrud(csrfToken) {
        const modal = document.getElementById('modalCatalog');
        const listArea = document.getElementById('catalog-list-area');
        const formArea = document.getElementById('catalog-form-area');
        const tbody = document.getElementById('catalog-tbody');
        const emptyMsg = document.getElementById('catalog-empty-msg');
        const form = document.getElementById('catalog-form');
        const addBtn = document.getElementById('btn-catalog-add');
        const cancelBtn = document.getElementById('btn-catalog-cancel');
        const backBtn = document.getElementById('btn-catalog-back');
        const breadcrumbBox = document.getElementById('catalog-breadcrumb');
        const parentLabel = document.getElementById('catalog-parent-label');
        const currentLevel = document.getElementById('catalog-current-level');
        const badge = document.getElementById('badge-catalog');

        let currentParentId = '0';
        let breadcrumb = [{ id: 0, name: 'Категорії' }];

        modal.addEventListener('show.bs.modal', () => {
            currentParentId = '0';
            breadcrumb = [{ id: 0, name: 'Категорії' }];
            hideCatalogForm();
            resetCatalogForm();
            loadCatalog('0');
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideCatalogForm();
            resetCatalogForm();
            currentParentId = '0';
            breadcrumb = [{ id: 0, name: 'Категорії' }];
        });

        addBtn.addEventListener('click', () => {
            resetCatalogForm();
            document.getElementById('catalog-parent-id').value = currentParentId;
            parentLabel.textContent = getCurrentParentName();
            showCatalogForm();
        });

        cancelBtn.addEventListener('click', () => {
            hideCatalogForm();
            resetCatalogForm();
        });

        backBtn.addEventListener('click', () => {
            if (breadcrumb.length > 1) {
                const target = breadcrumb[breadcrumb.length - 2];
                loadCatalog(String(target.id));
            }
        });

        breadcrumbBox.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-parent-id]');
            if (!btn) return;
            loadCatalog(String(btn.dataset.parentId || '0'));
        });

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            if (btn.dataset.action === 'open') {
                loadCatalog(String(id));
            }
            if (btn.dataset.action === 'edit') {
                editCategory(id);
            }
            if (btn.dataset.action === 'delete') {
                deleteCategory(id);
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = document.getElementById('catalog-id').value;
            const payload = {
                parent_id: document.getElementById('catalog-parent-id').value || currentParentId,
                name_ru: document.getElementById('catalog-name-ru').value.trim(),
                name_ua: document.getElementById('catalog-name-ua').value.trim(),
                name_en: document.getElementById('catalog-name-en').value.trim(),
                num: document.getElementById('catalog-num').value,
                visible: document.getElementById('catalog-visible').checked,
                firstpage: document.getElementById('catalog-firstpage').checked,
                description_ru: document.getElementById('catalog-description-ru').value.trim(),
                description_ua: document.getElementById('catalog-description-ua').value.trim(),
                description_en: document.getElementById('catalog-description-en').value.trim(),
            };

            if (!payload.name_ru) {
                alert('Вкажіть назву RU');
                return;
            }

            fetch(id ? `/settings/catalog/${id}` : '/settings/catalog', {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(async (r) => {
                const data = await r.json();
                return { ok: r.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(data.message || 'Помилка збереження');
                    return;
                }

                hideCatalogForm();
                resetCatalogForm();
                loadCatalog(payload.parent_id || currentParentId);
                updateCatalogBadge();
            })
            .catch(() => alert('Помилка мережі'));
        });

        function loadCatalog(parentId) {
            fetch(`/settings/catalog?parent_id=${encodeURIComponent(parentId)}`)
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || 'Помилка завантаження каталогу');
                        return;
                    }

                    currentParentId = String(data.currentParentId ?? '0');
                    breadcrumb = Array.isArray(data.breadcrumb) && data.breadcrumb.length
                        ? data.breadcrumb
                        : [{ id: 0, name: 'Категорії' }];

                    renderBreadcrumb(breadcrumb);
                    renderCatalog(data.items || []);

                    const currentParentName = data.currentParent?.name_ru || 'Корінь каталогу';
                    currentLevel.textContent = currentParentId === '0'
                        ? 'Корінь каталогу'
                        : `Підкатегорії: ${currentParentName}`;
                    parentLabel.textContent = currentParentId === '0'
                        ? 'Корінь каталогу'
                        : currentParentName;
                    backBtn.style.display = currentParentId === '0' ? 'none' : 'inline-block';
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Помилка завантаження каталогу</td></tr>';
                });
        }

        function renderCatalog(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                const childLabel = item.children_count > 0
                    ? `<span class="badge bg-info text-dark">${item.children_count}</span>`
                    : '<span class="text-muted">0</span>';
                const flags = `
                    <div>${item.visible === '1' ? '<span class="badge bg-success">Показувати</span>' : '<span class="badge bg-secondary">Приховано</span>'}</div>
                    <div class="mt-1">${item.firstpage === '1' ? '<span class="badge bg-warning text-dark">Перша сторінка</span>' : '<span class="badge bg-light text-dark">Звичайна</span>'}</div>
                `;
                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>${item.num ?? 0}</td>
                    <td>
                        <div><strong>RU:</strong> ${escapeHtml(item.name_ru || '')}</div>
                        <div class="catalog-meta"><strong>UA:</strong> ${escapeHtml(item.name_ua || '—')}</div>
                        <div class="catalog-meta"><strong>EN:</strong> ${escapeHtml(item.name_en || '—')}</div>
                    </td>
                    <td>${flags}</td>
                    <td>
                        <div>${escapeHtml(shortText(item.description_ru || '—'))}</div>
                        <div class="catalog-meta">UA: ${escapeHtml(shortText(item.description_ua || '—'))}</div>
                        <div class="catalog-meta">EN: ${escapeHtml(shortText(item.description_en || '—'))}</div>
                    </td>
                    <td>${childLabel}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary action-btn" data-action="open" data-id="${item.id}">📂</button>
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderBreadcrumb(items) {
            breadcrumbBox.innerHTML = '';
            const wrapper = document.createElement('div');
            wrapper.className = 'catalog-breadcrumb';

            items.forEach((item, index) => {
                const node = document.createElement(index === items.length - 1 ? 'span' : 'button');
                node.textContent = item.name;
                if (index !== items.length - 1) {
                    node.type = 'button';
                    node.dataset.parentId = item.id;
                } else {
                    node.className = 'fw-semibold';
                }
                wrapper.appendChild(node);

                if (index !== items.length - 1) {
                    const separator = document.createElement('span');
                    separator.textContent = '›';
                    separator.className = 'text-muted';
                    wrapper.appendChild(separator);
                }
            });

            breadcrumbBox.appendChild(wrapper);
        }

        function editCategory(id) {
            fetch(`/settings/catalog/${id}`)
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || 'Категорію не знайдено');
                        return;
                    }

                    document.getElementById('catalog-id').value = data.id || '';
                    document.getElementById('catalog-parent-id').value = data.parent_id || '0';
                    document.getElementById('catalog-name-ru').value = data.name_ru || '';
                    document.getElementById('catalog-name-ua').value = data.name_ua || '';
                    document.getElementById('catalog-name-en').value = data.name_en || '';
                    document.getElementById('catalog-num').value = data.num ?? 0;
                    document.getElementById('catalog-visible').checked = String(data.visible ?? '1') === '1';
                    document.getElementById('catalog-firstpage').checked = String(data.firstpage ?? '0') === '1';
                    document.getElementById('catalog-description-ru').value = data.description_ru || '';
                    document.getElementById('catalog-description-ua').value = data.description_ua || '';
                    document.getElementById('catalog-description-en').value = data.description_en || '';

                    parentLabel.textContent = data.parent_id && data.parent_id !== '0'
                        ? getCurrentParentName(data.parent_id)
                        : 'Корінь каталогу';

                    showCatalogForm();
                })
                .catch(() => alert('Помилка завантаження категорії'));
        }

        function deleteCategory(id) {
            if (!confirm('Видалити категорію?')) return;

            fetch(`/settings/catalog/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(async (r) => {
                const data = await r.json();
                return { ok: r.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(data.message || 'Помилка видалення');
                    return;
                }

                loadCatalog(currentParentId);
                updateCatalogBadge();
            })
            .catch(() => alert('Помилка мережі'));
        }

        function updateCatalogBadge() {
            fetch('/settings/catalog?parent_id=0')
                .then((r) => r.json())
                .then((data) => {
                    if (badge) {
                        badge.textContent = (data.items || []).length;
                    }
                });
        }

        function getCurrentParentName(parentId = currentParentId) {
            const found = breadcrumb.find((item) => String(item.id) === String(parentId));
            return found ? found.name : 'Корінь каталогу';
        }

        function resetCatalogForm() {
            form.reset();
            document.getElementById('catalog-id').value = '';
            document.getElementById('catalog-parent-id').value = currentParentId;
            document.getElementById('catalog-num').value = '0';
            document.getElementById('catalog-visible').checked = true;
            document.getElementById('catalog-firstpage').checked = false;
            parentLabel.textContent = getCurrentParentName();
        }

        function showCatalogForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideCatalogForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }

        function shortText(value) {
            return value.length > 90 ? `${value.slice(0, 87)}...` : value;
        }
    }

    function initFirmsCrud(csrfToken) {
        const modal = document.getElementById('modalFirms');
        const listArea = document.getElementById('firm-list-area');
        const formArea = document.getElementById('firm-form-area');
        const tbody = document.getElementById('firms-tbody');
        const emptyMsg = document.getElementById('firms-empty-msg');
        const form = document.getElementById('firm-form');
        const addBtn = document.getElementById('btn-firm-add');
        const cancelBtn = document.getElementById('btn-firm-cancel');

        modal.addEventListener('show.bs.modal', () => {
            hideFirmForm();
            loadFirms();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideFirmForm();
            resetFirmForm();
        });

        addBtn.addEventListener('click', () => {
            resetFirmForm();
            showFirmForm();
        });

        cancelBtn.addEventListener('click', () => {
            hideFirmForm();
            resetFirmForm();
        });

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editFirm(id);
            }
            if (btn.dataset.action === 'delete') {
                deleteFirm(id, btn);
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = document.getElementById('firm-id').value;
            const payload = collectFirmPayload();

            if (!payload.name) {
                alert('Вкажіть назву компанії');
                return;
            }

            fetch(id ? `/settings/firms/${id}` : '/settings/firms', {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(async (r) => {
                const data = await r.json();
                return { ok: r.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(data.message || 'Помилка збереження');
                    return;
                }
                hideFirmForm();
                resetFirmForm();
                loadFirms();
            })
            .catch(() => alert('Помилка мережі'));
        });

        function collectFirmPayload() {
            return {
                name: document.getElementById('firm-name').value.trim(),
                regnum: document.getElementById('firm-regnum').value.trim(),
                inn: document.getElementById('firm-inn').value.trim(),
                schet: document.getElementById('firm-schet').value.trim(),
                bank: document.getElementById('firm-bank').value.trim(),
                mfo: document.getElementById('firm-mfo').value.trim(),
                town: document.getElementById('firm-town').value.trim(),
                address: document.getElementById('firm-address').value.trim(),
                map: document.getElementById('firm-map').value.trim(),
                view: document.getElementById('firm-view').value.trim(),
                phone: document.getElementById('firm-phone').value.trim(),
                direktor: document.getElementById('firm-direktor').value.trim(),
                pidpys: document.getElementById('firm-pidpys').value.trim(),
                pechat: document.getElementById('firm-pechat').value.trim(),
            };
        }

        function loadFirms() {
            fetch('/settings/firms')
                .then((r) => r.json())
                .then((items) => {
                    renderFirms(items);
                    const badge = document.getElementById('badge-firms');
                    if (badge) {
                        badge.textContent = items.length;
                    }
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-danger">Помилка завантаження компаній</td></tr>';
                });
        }

        function renderFirms(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>
                        <div>${escapeHtml(item.name || '')}</div>
                        <div class="company-meta">INN: ${escapeHtml(item.inn || '—')} | RegNum: ${escapeHtml(item.regnum || '—')}</div>
                    </td>
                    <td>${escapeHtml(item.phone || '—')}</td>
                    <td>${escapeHtml(item.town || '—')}</td>
                    <td>${escapeHtml(item.address || '—')}</td>
                    <td>${escapeHtml(item.direktor || '—')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function editFirm(id) {
            fetch(`/settings/firms/${id}`)
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || 'Компанію не знайдено');
                        return;
                    }

                    document.getElementById('firm-id').value = data.id || '';
                    document.getElementById('firm-name').value = data.name || '';
                    document.getElementById('firm-regnum').value = data.regnum || '';
                    document.getElementById('firm-inn').value = data.inn || '';
                    document.getElementById('firm-schet').value = data.schet || '';
                    document.getElementById('firm-bank').value = data.bank || '';
                    document.getElementById('firm-mfo').value = data.mfo || '';
                    document.getElementById('firm-town').value = data.town || '';
                    document.getElementById('firm-address').value = data.address || '';
                    document.getElementById('firm-map').value = data.map || '';
                    document.getElementById('firm-view').value = data.view || '';
                    document.getElementById('firm-phone').value = data.phone || '';
                    document.getElementById('firm-direktor').value = data.direktor || '';
                    document.getElementById('firm-pidpys').value = data.pidpys || '';
                    document.getElementById('firm-pechat').value = data.pechat || '';

                    showFirmForm();
                })
                .catch(() => alert('Помилка завантаження компанії'));
        }

        function deleteFirm(id, btn) {
            if (!confirm('Видалити компанію?')) return;

            fetch(`/settings/firms/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(async (r) => {
                const data = await r.json();
                return { ok: r.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(data.message || 'Помилка видалення');
                    return;
                }

                btn.closest('tr').remove();
                const rest = tbody.querySelectorAll('tr').length;
                document.getElementById('badge-firms').textContent = rest;
                if (!rest) {
                    emptyMsg.style.display = 'block';
                }
            })
            .catch(() => alert('Помилка мережі'));
        }

        function resetFirmForm() {
            form.reset();
            document.getElementById('firm-id').value = '';
        }

        function showFirmForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideFirmForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endsection
