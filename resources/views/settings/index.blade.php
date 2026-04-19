@extends('home')

@section('title', __('settings.title'))

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
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalProjects">
                <div class="card-body text-center">
                    <h5 class="card-title">📁 {{ __('settings.projects') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.projects_desc') }}</p>
                    <span class="badge bg-primary" id="badge-projects">{{ count($projects ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="status" data-title="📊 Статуси">
                <div class="card-body text-center">
                    <h5 class="card-title">📊 Статуси</h5>
                    <p class="card-text text-muted">Статуси документів</p>
                    <span class="badge bg-success" id="badge-status">{{ count($statuses ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="reestr" data-title="💳 Види платежів">
                <div class="card-body text-center">
                    <h5 class="card-title">💳 Види платежів</h5>
                    <p class="card-text text-muted">Реєстр платежів</p>
                    <span class="badge bg-info" id="badge-reestr">{{ count($reestrs ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="tgroup" data-title="👥 Тип клієнта">
                <div class="card-body text-center">
                    <h5 class="card-title">👥 Тип клієнта</h5>
                    <p class="card-text text-muted">Типи клієнтів і цінові групи</p>
                    <span class="badge bg-secondary" id="badge-tgroup">{{ count($tgroups ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="tclient" data-title="🏷 Тип контрагента">
                <div class="card-body text-center">
                    <h5 class="card-title">🏷 Тип контрагента</h5>
                    <p class="card-text text-muted">Ролі контрагентів і підрозділів</p>
                    <span class="badge bg-dark" id="badge-tclient">{{ count($tclients ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="oplata" data-title="💰 Каса">
                <div class="card-body text-center">
                    <h5 class="card-title">💰 Каса</h5>
                    <p class="card-text text-muted">Види оплати</p>
                    <span class="badge bg-warning text-dark" id="badge-oplata">{{ count($oplatas ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="sklads" data-title="🏢 Офіси">
                <div class="card-body text-center">
                    <h5 class="card-title">🏢 Офіси</h5>
                    <p class="card-text text-muted">Список офісів</p>
                    <span class="badge bg-secondary" id="badge-sklads">{{ count($sklads ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="deposit" data-title="🏦 Депозити">
                <div class="card-body text-center">
                    <h5 class="card-title">🏦 Депозити</h5>
                    <p class="card-text text-muted">Депозитні рахунки</p>
                    <span class="badge bg-dark" id="badge-deposit">{{ count($deposits ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-info setting-card" data-bs-toggle="modal" data-bs-target="#modalCatalog">
                <div class="card-body text-center">
                    <h5 class="card-title">🌐 {{ __('settings.languages_regions') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.languages_regions_desc') }}</p>
                    <span class="badge bg-info text-dark" id="badge-field-total">{{ $fieldTranslationsCount ?? 0 }}</span>
                    <div class="small text-muted mt-2">catalog: <span id="badge-catalog">{{ $fieldCatalogTopCount ?? 0 }}</span> | city: <span id="badge-city">{{ $fieldCityCount ?? 0 }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-primary setting-card" data-bs-toggle="modal" data-bs-target="#modalProfile">
                <div class="card-body text-center">
                    <h5 class="card-title">👤 Профіль</h5>
                    <p class="card-text text-muted">{{ session('login', '') }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-warning setting-card" data-bs-toggle="modal" data-bs-target="#modalFirms">
                <div class="card-body text-center">
                    <h5 class="card-title">🏛 Мої компанії</h5>
                    <p class="card-text text-muted">Редагування, додавання та видалення компаній</p>
                    <span class="badge bg-warning text-dark" id="badge-firms">{{ count($myCompanies ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-danger setting-card" data-bs-toggle="modal" data-bs-target="#modalBanners">
                <div class="card-body text-center">
                    <h5 class="card-title">🎞 Банерна карусель</h5>
                    <p class="card-text text-muted">Банери для першого екрана laravel-react</p>
                    <span class="badge bg-danger" id="badge-banners">{{ $bannerCarouselCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-success setting-card" data-bs-toggle="modal" data-bs-target="#modalAccounts">
                <div class="card-body text-center">
                    <h5 class="card-title">📚 План счетов</h5>
                    <p class="card-text text-muted">Счета и привязка к видам платежа</p>
                    <span class="badge bg-success" id="badge-accounts">{{ $accountsCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalWeb3Tokens" style="border-color: #f3ba2f;">
                <div class="card-body text-center">
                    <h5 class="card-title">🪙 Web3 Токены</h5>
                    <p class="card-text text-muted">Дополнительные токены (ERC-20)</p>
                    <span class="badge" style="background:#f3ba2f; color:#000;" id="badge-web3-tokens">{{ count($web3Tokens ?? []) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAccounts" tabindex="-1" aria-labelledby="modalAccountsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalAccountsLabel">📚 План счетов</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-account-add">+ Добавить счет</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="account-form-area" style="display:none;">
                <form id="account-form">
                    <input type="hidden" id="account-id" value="">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Код <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account-code" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Название <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account-name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Тип <span class="text-danger">*</span></label>
                            <select class="form-select" id="account-type" required>
                                <option value="asset">Актив</option>
                                <option value="liability">Пассив</option>
                                <option value="equity">Капитал</option>
                                <option value="income">Доход</option>
                                <option value="expense">Расход</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Родительский счет</label>
                            <select class="form-select" id="account-parent-id">
                                <option value="">— без родителя —</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" id="btn-account-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="account-list-area">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h6 class="mb-3">Счета</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Код</th>
                                        <th>Название</th>
                                        <th>Тип</th>
                                        <th>Родитель</th>
                                        <th class="text-end">Дії</th>
                                    </tr>
                                </thead>
                                <tbody id="accounts-tbody"></tbody>
                            </table>
                        </div>
                        <p class="text-center text-muted" id="accounts-empty-msg" style="display:none">Счетов пока нет</p>
                    </div>

                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Привязка к видам платежа</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-payment-bindings-reload">Оновити</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Вид платежа</th>
                                        <th>Документы</th>
                                        <th>Дебет</th>
                                        <th>Кредит</th>
                                        <th class="text-end">Дії</th>
                                    </tr>
                                </thead>
                                <tbody id="payment-bindings-tbody"></tbody>
                            </table>
                        </div>
                        <p class="text-center text-muted" id="payment-bindings-empty-msg" style="display:none">Видов платежа пока нет</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalWeb3Tokens" tabindex="-1" aria-labelledby="modalWeb3TokensLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalWeb3TokensLabel">🪙 Web3 Токены</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-web3-add">+ Добавить токен</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body" id="web3-form-area" style="display:none">
                <form id="web3-form">
                    <input type="hidden" id="web3-id" value="">
                    <input type="hidden" id="web3-cgid" value="">
                    
                    <div class="mb-3 position-relative">
                        <label class="form-label text-warning">🔍 Поиск по CoinGecko (Автозаполнение)</label>
                        <input type="text" class="form-control" autocomplete="off" id="web3-cg-search" placeholder="Введите название или тикер, например: USDT">
                        <ul class="list-group position-absolute w-100 shadow" id="web3-cg-results" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;"></ul>
                    </div>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Тикер (Символ) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="web3-symbol" placeholder="USDT" required>
                            <div class="form-text">Например: USDC, UNI, PEPE</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Полное название</label>
                            <input type="text" class="form-control" id="web3-name" placeholder="Tether USD">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Смарт-контракт (Address) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="web3-address" placeholder="0x... или Solana mint address" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chain ID (Сеть) <span class="text-danger">*</span></label>
                            <select class="form-select" id="web3-chain" required>
                                <option value="0x1">Ethereum Mainnet (0x1)</option>
                                <option value="0x38">BNB Smart Chain (0x38)</option>
                                <option value="0x89">Polygon (0x89)</option>
                                <option value="0xa4b1">Arbitrum One (0xa4b1)</option>
                                <option value="0x2105">Base (0x2105)</option>
                                <option value="0xa">Optimism (0xa)</option>
                                <option value="0xa86a">Avalanche C-Chain (0xa86a)</option>
                                <option value="solana">Solana</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Decimals <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="web3-decimals" value="18" required>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Сохранить</button>
                        <button type="button" class="btn btn-secondary" id="btn-web3-cancel">Отмена</button>
                    </div>
                </form>
            </div>
            <div class="modal-body" id="web3-list-area">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Сеть</th>
                                <th>Тикер</th>
                                <th>Контракт</th>
                                <th>Decimals</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="web3-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="web3-empty-msg" style="display:none">Пользовательских токенов нет</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCatalog" tabindex="-1" aria-labelledby="modalCatalogLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="modal-title" id="modalCatalogLabel">🌐 {{ __('settings.languages_regions') }}</h5>
                    <div class="small text-muted" id="catalog-current-level">Категории и подписи</div>
                </div>
                <div class="btn-group btn-group-sm ms-md-3" role="group" aria-label="Режим справочника">
                    <button type="button" class="btn btn-outline-primary active" id="btn-field-mode-catalog">Категории/Надписи</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-field-mode-city">Регионы</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-md-3" id="btn-catalog-back" style="display:none;">← Назад</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-catalog-add">+ Добавить запись</button>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="catalog-form-area" style="display:none">
                <form id="catalog-form">
                    <input type="hidden" id="catalog-id" value="">
                    <input type="hidden" id="catalog-parent-id" value="0">
                    <input type="hidden" id="catalog-keyfield" value="catalog">

                    <div class="alert alert-secondary py-2 mb-3">
                        <strong>Раздел:</strong> <span id="catalog-parent-label">Категории и подписи</span>
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

                    <div class="row" id="catalog-flags-row">
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

                    <div class="row" id="catalog-description-row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Link</label>
                            <input type="text" class="form-control" id="catalog-link" maxlength="35" placeholder="slug или ссылка из field.link">
                        </div>
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
                                <th id="catalog-flags-head">Прапори</th>
                                <th id="catalog-description-head">Опис</th>
                                <th id="catalog-children-head">Підкатегорії</th>
                                <th class="text-end">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="catalog-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="catalog-empty-msg" style="display:none">Записей пока нет</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProjects" tabindex="-1" aria-labelledby="modalProjectsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalProjectsLabel">📁 Проєкти</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-project-add">+ Додати</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="project-form-area" style="display:none">
                <form id="project-form" enctype="multipart/form-data">
                    <input type="hidden" id="project-id" value="">
                    <input type="hidden" id="project-foto-existing" value="">
                    <input type="hidden" id="project-foto-header-existing" value="">
                    <input type="hidden" id="project-foto-footer-existing" value="">
                    <input type="hidden" id="project-num" value="0">

                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control" id="project-id-display" readonly>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Назва <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project-name" maxlength="50" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">userid</label>
                            <input type="number" class="form-control" id="project-userid" min="0" value="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">phone</label>
                            <textarea class="form-control" id="project-phone" rows="2"></textarea>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">description</label>
                            <textarea class="form-control" id="project-description" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">telegram</label>
                            <textarea class="form-control" id="project-telegram" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">instagram</label>
                            <textarea class="form-control" id="project-instagram" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">twitter</label>
                            <textarea class="form-control" id="project-twitter" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">facebook</label>
                            <textarea class="form-control" id="project-facebook" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">foto</label>
                            <input type="file" class="form-control" id="project-foto-file" accept="image/*">
                            <div class="form-text">PNG, JPG, WEBP або GIF до 4 МБ</div>
                            <div class="firm-media-preview mt-2" id="project-foto-preview-wrap" hidden>
                                <img src="" alt="foto" id="project-foto-preview">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">foto_header</label>
                            <input type="file" class="form-control" id="project-foto-header-file" accept="image/*">
                            <div class="form-text">PNG, JPG, WEBP або GIF до 4 МБ</div>
                            <div class="firm-media-preview mt-2" id="project-foto-header-preview-wrap" hidden>
                                <img src="" alt="foto_header" id="project-foto-header-preview">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">foto_footer</label>
                            <input type="file" class="form-control" id="project-foto-footer-file" accept="image/*">
                            <div class="form-text">PNG, JPG, WEBP або GIF до 4 МБ</div>
                            <div class="firm-media-preview mt-2" id="project-foto-footer-preview-wrap" hidden>
                                <img src="" alt="foto_footer" id="project-foto-footer-preview">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">htmlkeys</label>
                            <textarea class="form-control" id="project-htmlkeys" rows="3"></textarea>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="project-web">
                                <label class="form-check-label" for="project-web">web</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="project-hit">
                                <label class="form-check-label" for="project-hit">hit</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" id="btn-project-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="project-list-area">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>num</th>
                                <th>Назва</th>
                                <th>userid</th>
                                <th>Телефон</th>
                                <th>Прапори</th>
                                <th class="text-end">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="projects-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="projects-empty-msg" style="display:none">Проєктів ще немає</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrud" tabindex="-1" aria-labelledby="modalCrudLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
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
                    <div class="mb-3" id="form-doc-row" style="display:none;">
                        <label class="form-label">Показывать в документах</label>
                        <div class="d-flex flex-column gap-2">
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-po" value="PO">
                                <span>Получение денег</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-ro" value="RO">
                                <span>Выдача денег</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-deposit" value="DEPOSIT">
                                <span>Депозиты</span>
                            </label>
                        </div>
                        <div class="form-text">Для яких документів доступний цей вид платежу.</div>
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
                            <th id="crud-status-column">Статус</th>
                            <th id="crud-doc-column" style="display:none;">Документ</th>
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
        <div class="modal-content glass-card border-0">
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
                            <label class="form-label">Фото підпису</label>
                            <input type="file" class="form-control" id="firm-pidpys-file" accept="image/*">
                            <div class="form-text">PNG, JPG, WEBP або GIF до 4 МБ</div>
                            <div class="firm-media-preview mt-2" id="firm-pidpys-preview-wrap" hidden>
                                <img src="" alt="Підпис" id="firm-pidpys-preview">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Фото печатки</label>
                            <input type="file" class="form-control" id="firm-pechat-file" accept="image/*">
                            <div class="form-text">PNG, JPG, WEBP або GIF до 4 МБ</div>
                            <div class="firm-media-preview mt-2" id="firm-pechat-preview-wrap" hidden>
                                <img src="" alt="Печатка" id="firm-pechat-preview">
                            </div>
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

<div class="modal fade" id="modalBanners" tabindex="-1" aria-labelledby="modalBannersLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalBannersLabel">🎞 Банерна карусель</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-banner-add">+ Додати банер</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="banner-form-area" style="display:none">
                <form id="banner-form">
                    <input type="hidden" id="banner-id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Заголовок <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="banner-title" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Кнопка</label>
                            <input type="text" class="form-control" id="banner-button-text" placeholder="Детальніше">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Порядок</label>
                            <input type="number" class="form-control" id="banner-sort-order" min="0" value="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Посилання</label>
                            <input type="text" class="form-control" id="banner-link-url" placeholder="/news або https://...">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Видимість</label>
                            <select class="form-select" id="banner-vision">
                                <option value="1">Показувати</option>
                                <option value="0">Приховати</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Підзаголовок</label>
                        <textarea class="form-control" id="banner-subtitle" rows="4"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Зображення банера</label>
                            <input type="file" class="form-control" id="banner-image-file" accept="image/*">
                            <div class="form-text">PNG, JPG, WEBP або GIF до 6 МБ</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Попередній перегляд</label>
                            <div class="firm-media-preview" id="banner-image-preview-wrap" hidden>
                                <img src="" alt="Банер" id="banner-image-preview">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" id="btn-banner-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="banner-list-area">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Банер</th>
                                <th>Посилання</th>
                                <th>Порядок</th>
                                <th>Видимість</th>
                                <th class="text-end">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="banners-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="banners-empty-msg" style="display:none">Банерів ще немає</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProfile" tabindex="-1" aria-labelledby="modalProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card border-0">
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
                                        <p class="wallet-link-text mb-0">Після прив'язки EVM або Solana гаманець зможе входити через Web3 і буде асоційований з вашим контрагентом.</p>
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

    .modal .form-label,
    .modal label,
    .modal .form-check-label {
        color: #000;
    }

    .modal .form-text {
        color: #000;
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

    .firm-media-preview {
        min-height: 96px;
        border: 1px dashed #ced4da;
        border-radius: 10px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px;
    }

    .firm-media-preview img {
        max-width: 100%;
        max-height: 140px;
        object-fit: contain;
        display: block;
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

    #project-form .form-label {
        color: #000;
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

    initProjectsCrud(csrf);
    initConfCrud(csrf);
    initCatalogCrud(csrf);
    initFirmsCrud(csrf);
    initBannerCrud(csrf);
    initAccountsCrud(csrf);
    initWalletLink(csrf);
    initWeb3TokenCrud(csrf);


    function initWeb3TokenCrud(csrfToken) {
        const modal = document.getElementById('modalWeb3Tokens');
        const listArea = document.getElementById('web3-list-area');
        const formArea = document.getElementById('web3-form-area');
        const tbody = document.getElementById('web3-tbody');
        const emptyMsg = document.getElementById('web3-empty-msg');
        const form = document.getElementById('web3-form');
        const addBtn = document.getElementById('btn-web3-add');
        const cancelBtn = document.getElementById('btn-web3-cancel');
        const searchInput = document.getElementById('web3-cg-search');
        const resultsList = document.getElementById('web3-cg-results');
        const chainSelect = document.getElementById('web3-chain');
        
        let cgSearchTimeout = null;
        let currentCgPlatforms = null;

        modal.addEventListener('show.bs.modal', () => {
            hideWeb3Form();
            loadWeb3Tokens();
        });

        addBtn.addEventListener('click', () => {
            resetWeb3Form();
            showWeb3Form();
        });

        cancelBtn.addEventListener('click', () => {
            hideWeb3Form();
            resetWeb3Form();
        });

        // CoinGecko autocomplete
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            if (query.length < 2) {
                resultsList.style.display = 'none';
                return;
            }
            clearTimeout(cgSearchTimeout);
            cgSearchTimeout = setTimeout(() => {
                fetch(`https://api.coingecko.com/api/v3/search?query=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        resultsList.innerHTML = '';
                        if (!data.coins || !data.coins.length) {
                            resultsList.style.display = 'none';
                            return;
                        }
                        data.coins.slice(0, 10).forEach(coin => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action d-flex align-items-center cursor-pointer';
                            li.style.cursor = 'pointer';
                            li.innerHTML = `<img src="${coin.thumb}" class="me-2 rounded-circle" width="20" height="20"> <strong>${coin.symbol}</strong> <span class="ms-2 text-muted text-truncate" style="max-width: 150px;">${coin.name}</span>`;
                            li.addEventListener('click', () => selectCgCoin(coin));
                            resultsList.appendChild(li);
                        });
                        resultsList.style.display = 'block';
                    });
            }, 400);
        });

        // Click outside closes search
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsList.contains(e.target)) {
                resultsList.style.display = 'none';
            }
        });

        chainSelect.addEventListener('change', () => {
            if (chainSelect.value === 'solana' && !currentCgPlatforms) {
                document.getElementById('web3-decimals').value = '9';
            }

            if (currentCgPlatforms) {
                const chainId = chainSelect.value;
                const platformMap = {
                    '0x1': 'ethereum',
                    '0x38': 'binance-smart-chain',
                    '0x89': 'polygon-pos',
                    '0xa4b1': 'arbitrum-one',
                    '0x2105': 'base',
                    '0xa': 'optimistic-ethereum',
                    '0xa86a': 'avalanche',
                    'solana': 'solana'
                };
                const cgPlatformName = platformMap[chainId];
                if (cgPlatformName && currentCgPlatforms[cgPlatformName]) {
                    const platformDetails = currentCgPlatforms[cgPlatformName];
                    document.getElementById('web3-address').value = platformDetails.contract_address || '';
                    document.getElementById('web3-decimals').value = platformDetails.decimal_place || (chainId === 'solana' ? '9' : '18');
                }
            }
        });

        function selectCgCoin(coin) {
            searchInput.value = coin.name;
            resultsList.style.display = 'none';
            
            // Set basic info
            document.getElementById('web3-cgid').value = coin.id;
            document.getElementById('web3-symbol').value = (coin.symbol || '').toUpperCase();
            document.getElementById('web3-name').value = coin.name || '';
            
            // Fetch detailed coin info
            fetch(`https://api.coingecko.com/api/v3/coins/${coin.id}?localization=false&tickers=false&market_data=false&community_data=false&developer_data=false`)
                .then(r => r.json())
                .then(data => {
                    if (data.detail_platforms) {
                        currentCgPlatforms = data.detail_platforms;
                        // Fire change event to auto-fill the contract details for current selected chain
                        chainSelect.dispatchEvent(new Event('change'));
                    }
                })
                .catch(() => alert('Ошибка загрузки данных контрактов из CoinGecko'));
        }

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') editWeb3Token(id);
            if (btn.dataset.action === 'delete') deleteWeb3Token(id, btn);
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('web3-id').value;
            const payload = {
                type: 'web3_token',
                name: document.getElementById('web3-symbol').value.trim(),
                doc: document.getElementById('web3-name').value.trim(),
                color: document.getElementById('web3-address').value.trim(),
                status: document.getElementById('web3-decimals').value.trim(),
                vision: document.getElementById('web3-chain').value,
                constanta: document.getElementById('web3-cgid').value.trim()
            };

            const url = id ? `/settings/api/${id}` : '/settings/api';
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Ошибка');
                    return;
                }
                hideWeb3Form();
                resetWeb3Form();
                loadWeb3Tokens();
            })
            .catch(() => alert('Ошибка сети'));
        });

        function loadWeb3Tokens() {
            fetch('/settings/api/web3_token')
                .then(r => r.json())
                .then(items => {
                    renderWeb3Tokens(items);
                    const badge = document.getElementById('badge-web3-tokens');
                    if (badge) badge.textContent = items.length;
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Ошибка загрузки токенов</td></tr>';
                });
        }

        function normalizeChainId(value) {
            if (value === null || value === undefined) return null;

            if (typeof value === 'number' && Number.isFinite(value)) {
                return '0x' + value.toString(16);
            }

            if (typeof value !== 'string') return null;
            const raw = value.trim().toLowerCase();
            if (!raw) return null;

            if (raw.startsWith('0x')) {
                const n = parseInt(raw, 16);
                return Number.isFinite(n) ? ('0x' + n.toString(16)) : null;
            }

            if (/^\d+$/.test(raw)) {
                const n = parseInt(raw, 10);
                return Number.isFinite(n) ? ('0x' + n.toString(16)) : null;
            }

            if (/^[0-9a-f]+$/.test(raw)) {
                const n = parseInt(raw, 16);
                return Number.isFinite(n) ? ('0x' + n.toString(16)) : null;
            }

            return null;
        }

        function getChainName(chainId) {
            const strings = {
                '0x1': 'Ethereum',
                '0x38': 'BSC',
                '0x89': 'Polygon',
                '0xa4b1': 'Arbitrum',
                '0x2105': 'Base',
                '0xa': 'Optimism',
                '0xa86a': 'Avalanche',
                'solana': 'Solana'
            };
            const normalized = String(chainId || '').toLowerCase() === 'solana'
                ? 'solana'
                : (normalizeChainId(chainId) || chainId);
            return strings[normalized] || normalized;
        }

        function renderWeb3Tokens(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }
            emptyMsg.style.display = 'none';
            items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(getChainName(item.vision))}</td>
                    <td><strong>${escapeHtml(item.name)}</strong><br><small class="text-muted">${escapeHtml(item.doc || '')}</small></td>
                    <td><code>${escapeHtml(item.color)}</code></td>
                    <td>${escapeHtml(item.status || '18')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function editWeb3Token(id) {
            fetch(`/settings/api/web3_token/${id}`)
                .then(r => r.json())
                .then(item => {
                    document.getElementById('web3-id').value = item.id;
                    document.getElementById('web3-cgid').value = item.constanta || '';
                    document.getElementById('web3-symbol').value = item.name || '';
                    document.getElementById('web3-name').value = item.doc || '';
                    document.getElementById('web3-address').value = item.color || '';
                    document.getElementById('web3-decimals').value = item.status || '18';
                    document.getElementById('web3-chain').value = String(item.vision || '').toLowerCase() === 'solana'
                        ? 'solana'
                        : (normalizeChainId(item.vision) || '0x1');
                    searchInput.value = '';
                    currentCgPlatforms = null;
                    showWeb3Form();
                })
                .catch(() => alert('Ошибка загрузки'));
        }

        function deleteWeb3Token(id, btn) {
            if (!confirm('Удалить этот токен?')) return;
            fetch(`/settings/api/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).then(r => r.json()).then(data => {
                if(data.success) {
                    btn.closest('tr').remove();
                    loadWeb3Tokens();
                }
            });
        }

        function resetWeb3Form() {
            form.reset();
            document.getElementById('web3-id').value = '';
            document.getElementById('web3-chain').value = '0x1';
            document.getElementById('web3-decimals').value = '18';
        }

        function showWeb3Form() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideWeb3Form() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }
    }

    function initProjectsCrud(csrfToken) {
        const modal = document.getElementById('modalProjects');
        const tbody = document.getElementById('projects-tbody');
        const formArea = document.getElementById('project-form-area');
        const listArea = document.getElementById('project-list-area');
        const form = document.getElementById('project-form');
        const emptyMsg = document.getElementById('projects-empty-msg');
        const addBtn = document.getElementById('btn-project-add');
        const cancelBtn = document.getElementById('btn-project-cancel');
        const badge = document.getElementById('badge-projects');
        const fotoFileInput = document.getElementById('project-foto-file');
        const fotoHeaderFileInput = document.getElementById('project-foto-header-file');
        const fotoFooterFileInput = document.getElementById('project-foto-footer-file');

        if (!modal || !tbody || !form || !addBtn || !cancelBtn) {
            return;
        }

        bindProjectPreview(fotoFileInput, 'project-foto-preview-wrap', 'project-foto-preview');
        bindProjectPreview(fotoHeaderFileInput, 'project-foto-header-preview-wrap', 'project-foto-header-preview');
        bindProjectPreview(fotoFooterFileInput, 'project-foto-footer-preview-wrap', 'project-foto-footer-preview');

        const parseResponseData = async (response) => {
            const raw = await response.text().catch(() => '');
            if (!raw) {
                return {};
            }

            try {
                return JSON.parse(raw);
            } catch (_) {
                return { message: raw };
            }
        };

        const extractErrorMessage = (payload, fallback = 'Помилка запиту') => {
            if (payload && typeof payload.message === 'string' && payload.message.trim()) {
                return payload.message.trim();
            }

            const errors = payload && typeof payload.errors === 'object' && payload.errors ? payload.errors : null;
            if (errors) {
                for (const key of Object.keys(errors)) {
                    const value = errors[key];
                    if (Array.isArray(value) && value[0]) {
                        return String(value[0]);
                    }
                    if (typeof value === 'string' && value.trim()) {
                        return value.trim();
                    }
                }
            }

            return fallback;
        };

        modal.addEventListener('show.bs.modal', () => {
            hideForm();
            loadProjects();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideForm();
            resetProjectForm();
        });

        addBtn.addEventListener('click', () => {
            resetProjectForm();
            showForm();
        });

        cancelBtn.addEventListener('click', () => {
            hideForm();
            resetProjectForm();
        });

        tbody.addEventListener('click', (event) => {
            const btn = event.target.closest('.action-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editProject(id);
                return;
            }

            if (btn.dataset.action === 'delete') {
                deleteProject(id, btn);
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const id = document.getElementById('project-id').value;
            const payload = new FormData();
            payload.append('num', String(Number(document.getElementById('project-num').value || 0)));
            payload.append('name', document.getElementById('project-name').value.trim());
            payload.append('phone', document.getElementById('project-phone').value.trim());
            payload.append('telegram', document.getElementById('project-telegram').value.trim());
            payload.append('instagram', document.getElementById('project-instagram').value.trim());
            payload.append('twitter', document.getElementById('project-twitter').value.trim());
            payload.append('facebook', document.getElementById('project-facebook').value.trim());
            payload.append('userid', String(Number(document.getElementById('project-userid').value || 0)));
            payload.append('foto', document.getElementById('project-foto-existing').value.trim());
            payload.append('foto_header', document.getElementById('project-foto-header-existing').value.trim());
            payload.append('foto_footer', document.getElementById('project-foto-footer-existing').value.trim());
            payload.append('description', document.getElementById('project-description').value.trim());
            payload.append('web', document.getElementById('project-web').checked ? '1' : '0');
            payload.append('hit', document.getElementById('project-hit').checked ? '1' : '0');
            payload.append('htmlkeys', document.getElementById('project-htmlkeys').value.trim());

            if (fotoFileInput?.files?.[0]) {
                payload.append('foto_file', fotoFileInput.files[0]);
            }
            if (fotoHeaderFileInput?.files?.[0]) {
                payload.append('foto_header_file', fotoHeaderFileInput.files[0]);
            }
            if (fotoFooterFileInput?.files?.[0]) {
                payload.append('foto_footer_file', fotoFooterFileInput.files[0]);
            }
            if (id) {
                payload.append('_method', 'PUT');
            }

            if (!String(payload.get('name') || '').trim()) {
                alert('Вкажи назву проєкту');
                return;
            }

            fetch(id ? `/settings/projects/${id}` : '/settings/projects', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: payload,
            })
            .then(async (response) => {
                const data = await parseResponseData(response);
                return { ok: response.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(extractErrorMessage(data, 'Помилка збереження'));
                    return;
                }

                hideForm();
                resetProjectForm();
                loadProjects();
            })
            .catch((error) => alert(error?.message || 'Помилка мережі'));
        });

        function loadProjects() {
            fetch('/settings/projects')
                .then(async (response) => {
                    const data = await parseResponseData(response);
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        throw new Error(extractErrorMessage(data, 'Помилка завантаження'));
                    }

                    renderProjects(Array.isArray(data) ? data : []);
                })
                .catch((error) => {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-danger">${escapeHtml(error?.message || 'Помилка завантаження')}</td></tr>`;
                    emptyMsg.style.display = 'none';
                });
        }

        function renderProjects(items) {
            tbody.innerHTML = '';

            if (!items.length) {
                emptyMsg.style.display = 'block';
                if (badge) {
                    badge.textContent = '0';
                }
                return;
            }

            emptyMsg.style.display = 'none';
            if (badge) {
                badge.textContent = String(items.length);
            }

            items.forEach((item) => {
                const flags = [];
                if (Number(item.web) === 1) flags.push('<span class="badge bg-primary">web</span>');
                if (Number(item.hit) === 1) flags.push('<span class="badge bg-warning text-dark">hit</span>');

                const projectPhone = item.phone
                    ? `<a href="tel:${escapeHtml(item.phone)}">${escapeHtml(item.phone)}</a>`
                    : '—';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id ?? ''}</td>
                    <td>${escapeHtml(item.num ?? 0)}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(item.name || '')}</div>
                        <div class="company-meta">${escapeHtml(item.description || '')}</div>
                    </td>
                    <td>${escapeHtml(item.userid ?? 0)}</td>
                    <td>${projectPhone}</td>
                    <td>${flags.join(' ') || '—'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        ${item.can_delete ? `<button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>` : '<span class="text-muted small">Без прав на видалення</span>'}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function editProject(id) {
            fetch(`/settings/projects/${id}`)
                .then(async (response) => {
                    const data = await parseResponseData(response);
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(extractErrorMessage(data, 'Проєкт не знайдено'));
                        return;
                    }

                    fillProjectForm(data);
                    showForm();
                })
                .catch((error) => alert(error?.message || 'Помилка завантаження'));
        }

        function deleteProject(id, btn) {
            if (!confirm('Видалити цей проєкт?')) return;

            fetch(`/settings/projects/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            })
            .then(async (response) => {
                const data = await parseResponseData(response);
                return { ok: response.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(extractErrorMessage(data, 'Помилка видалення'));
                    return;
                }

                btn.closest('tr')?.remove();
                if (!tbody.children.length) {
                    emptyMsg.style.display = 'block';
                }
                loadProjects();
            })
            .catch((error) => alert(error?.message || 'Помилка мережі'));
        }

        function fillProjectForm(item) {
            document.getElementById('project-id').value = item.id ?? '';
            document.getElementById('project-id-display').value = item.id ?? '';
            document.getElementById('project-num').value = item.num ?? 0;
            document.getElementById('project-name').value = item.name || '';
            document.getElementById('project-phone').value = item.phone || '';
            document.getElementById('project-telegram').value = item.telegram || '';
            document.getElementById('project-instagram').value = item.instagram || '';
            document.getElementById('project-twitter').value = item.twitter || '';
            document.getElementById('project-facebook').value = item.facebook || '';
            document.getElementById('project-userid').value = item.userid ?? 0;
            document.getElementById('project-foto-existing').value = item.foto || '';
            document.getElementById('project-foto-header-existing').value = item.foto_header || '';
            document.getElementById('project-foto-footer-existing').value = item.foto_footer || '';
            document.getElementById('project-description').value = item.description || '';
            document.getElementById('project-web').checked = Number(item.web) === 1;
            document.getElementById('project-hit').checked = Number(item.hit) === 1;
            document.getElementById('project-htmlkeys').value = item.htmlkeys || '';
            if (fotoFileInput) fotoFileInput.value = '';
            if (fotoHeaderFileInput) fotoHeaderFileInput.value = '';
            if (fotoFooterFileInput) fotoFooterFileInput.value = '';
            setProjectPreview('project-foto-preview-wrap', 'project-foto-preview', item.foto_preview || '');
            setProjectPreview('project-foto-header-preview-wrap', 'project-foto-header-preview', item.foto_header_preview || '');
            setProjectPreview('project-foto-footer-preview-wrap', 'project-foto-footer-preview', item.foto_footer_preview || '');
        }

        function resetProjectForm() {
            document.getElementById('project-id').value = '';
            document.getElementById('project-id-display').value = '';
            document.getElementById('project-num').value = '0';
            document.getElementById('project-name').value = '';
            document.getElementById('project-phone').value = '';
            document.getElementById('project-telegram').value = '';
            document.getElementById('project-instagram').value = '';
            document.getElementById('project-twitter').value = '';
            document.getElementById('project-facebook').value = '';
            document.getElementById('project-userid').value = '0';
            document.getElementById('project-foto-existing').value = '';
            document.getElementById('project-foto-header-existing').value = '';
            document.getElementById('project-foto-footer-existing').value = '';
            document.getElementById('project-description').value = '';
            document.getElementById('project-web').checked = false;
            document.getElementById('project-hit').checked = false;
            document.getElementById('project-htmlkeys').value = '';
            if (fotoFileInput) fotoFileInput.value = '';
            if (fotoHeaderFileInput) fotoHeaderFileInput.value = '';
            if (fotoFooterFileInput) fotoFooterFileInput.value = '';
            setProjectPreview('project-foto-preview-wrap', 'project-foto-preview', '');
            setProjectPreview('project-foto-header-preview-wrap', 'project-foto-header-preview', '');
            setProjectPreview('project-foto-footer-preview-wrap', 'project-foto-footer-preview', '');
        }

        function showForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }

        function bindProjectPreview(input, wrapId, imageId) {
            if (!input) {
                return;
            }

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    setProjectPreview(wrapId, imageId, reader.result || '');
                };
                reader.readAsDataURL(file);
            });
        }

        function setProjectPreview(wrapId, imageId, source) {
            const wrap = document.getElementById(wrapId);
            const image = document.getElementById(imageId);
            if (!wrap || !image) {
                return;
            }

            if (!source) {
                image.src = '';
                wrap.hidden = true;
                return;
            }

            image.src = source;
            wrap.hidden = false;
        }
    }

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
        const statusColumn = document.getElementById('crud-status-column');
        const docRow = document.getElementById('form-doc-row');
        const docColumn = document.getElementById('crud-doc-column');
        const docCheckboxes = [
            document.getElementById('form-doc-po'),
            document.getElementById('form-doc-ro'),
            document.getElementById('form-doc-deposit'),
        ];

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
            setDocFlags('');
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
                vision: currentType === 'sklads' ? document.getElementById('form-status').value : '1',
            };

            if (currentType === 'reestr') {
                payload.doc = getDocFlags();
            }

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
                const docHtml = currentType === 'reestr'
                    ? escapeHtml(item.doc_label || 'Все документы')
                    : '';
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
                } else if (currentType === 'sklads') {
                    statusLabel = String(item.vision) === '1'
                        ? '<span class="badge bg-success">Видимий</span>'
                        : '<span class="badge bg-secondary">Прихований</span>';
                } else if (String(item.status) === '1') {
                    statusLabel = '<span class="badge bg-success">Активний</span>';
                }

                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>${escapeHtml(item.name || '')}</td>
                    <td>${colorHtml}</td>
                    <td>${statusLabel}</td>
                    ${currentType === 'reestr' ? `<td>${docHtml}</td>` : ''}
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
                    document.getElementById('form-status').value = currentType === 'sklads'
                        ? (item.vision ?? '1')
                        : (item.status ?? '1');
                    setDocFlags(item.doc || '');
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
            const isReestr = currentType === 'reestr';
            docRow.style.display = isReestr ? 'block' : 'none';
            docColumn.style.display = isReestr ? '' : 'none';

            if (currentType === 'tgroup') {
                statusColumn.textContent = 'Статус';
                statusLabel.textContent = 'Тип групи';
                statusSelect.innerHTML = `
                    <option value="1">Роздрібна група</option>
                    <option value="0">Додаткова група</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = 'Для виділення роздрібної групи цін використовуй status = 1.';
            } else if (currentType === 'tclient') {
                statusColumn.textContent = 'Статус';
                statusLabel.textContent = 'Підрозділ';
                statusSelect.innerHTML = `
                    <option value="0">Прочие</option>
                    <option value="1">Відділ продаж</option>
                    <option value="2">Виробництво</option>
                    <option value="3">Адміністрація</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = 'status: 1 = відділ продаж, 2 = виробництво, 3 = адміністрація, 0 = прочие.';
            } else if (currentType === 'sklads') {
                statusColumn.textContent = 'Видимість';
                statusLabel.textContent = 'Видимість';
                statusSelect.innerHTML = `
                    <option value="1">Видимий</option>
                    <option value="0">Прихований</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = 'Для офісів використовується поле vision: 1 = видимий, 0 = прихований.';
            } else {
                statusColumn.textContent = 'Статус';
                statusLabel.textContent = 'Статус';
                statusSelect.innerHTML = `
                    <option value="1">Активний</option>
                    <option value="0">Неактивний</option>
                `;
                statusHelp.style.display = 'none';
                statusHelp.textContent = '';
            }
        }

        function getDocFlags() {
            return docCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value)
                .join(',');
        }

        function setDocFlags(value) {
            const flags = String(value || '')
                .split(',')
                .map((item) => item.trim().toUpperCase())
                .filter(Boolean);

            docCheckboxes.forEach((checkbox) => {
                checkbox.checked = flags.includes(checkbox.value);
            });
        }
    }

    function initAccountsCrud(csrfToken) {
        const modal = document.getElementById('modalAccounts');
        const formArea = document.getElementById('account-form-area');
        const listArea = document.getElementById('account-list-area');
        const form = document.getElementById('account-form');
        const tbody = document.getElementById('accounts-tbody');
        const emptyMsg = document.getElementById('accounts-empty-msg');
        const addBtn = document.getElementById('btn-account-add');
        const cancelBtn = document.getElementById('btn-account-cancel');
        const badge = document.getElementById('badge-accounts');
        const parentSelect = document.getElementById('account-parent-id');
        const bindingsTbody = document.getElementById('payment-bindings-tbody');
        const bindingsEmptyMsg = document.getElementById('payment-bindings-empty-msg');
        const reloadBindingsBtn = document.getElementById('btn-payment-bindings-reload');

        if (!modal || !form || !tbody || !bindingsTbody || !addBtn || !cancelBtn) {
            return;
        }

        let accountsCache = [];

        modal.addEventListener('show.bs.modal', () => {
            hideAccountForm();
            loadAccounts();
            loadBindings();
        });

        addBtn.addEventListener('click', () => {
            resetAccountForm();
            showAccountForm();
        });

        cancelBtn.addEventListener('click', hideAccountForm);
        reloadBindingsBtn.addEventListener('click', loadBindings);

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editAccount(id);
            }
            if (btn.dataset.action === 'delete') {
                deleteAccount(id);
            }
        });

        bindingsTbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.binding-save-btn');
            if (!btn) return;
            saveBinding(btn.dataset.id);
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = document.getElementById('account-id').value;
            const payload = {
                code: document.getElementById('account-code').value.trim(),
                name: document.getElementById('account-name').value.trim(),
                type: document.getElementById('account-type').value,
                parent_id: parentSelect.value || null,
            };

            if (!payload.code || !payload.name) {
                return;
            }

            fetch(id ? `/settings/accounts/${id}` : '/settings/accounts', {
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
                    alert(data.message || 'Помилка збереження рахунку');
                    return;
                }

                hideAccountForm();
                loadAccounts();
                loadBindings();
            })
            .catch(() => alert('Помилка мережі'));
        });

        function loadAccounts() {
            fetch('/settings/accounts')
                .then((r) => r.json())
                .then((items) => {
                    accountsCache = items || [];
                    renderAccounts(accountsCache);
                    renderParentOptions(accountsCache);
                    badge.textContent = accountsCache.length;
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Помилка завантаження</td></tr>';
                });
        }

        function loadBindings() {
            fetch('/settings/payment-type-account-bindings')
                .then((r) => r.json())
                .then(renderBindings)
                .catch(() => {
                    bindingsTbody.innerHTML = '<tr><td colspan="5" class="text-danger">Помилка завантаження</td></tr>';
                });
        }

        function renderAccounts(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-semibold">${escapeHtml(item.code || '')}</td>
                    <td>${escapeHtml(item.name || '')}</td>
                    <td>${escapeHtml(accountTypeLabel(item.type || ''))}</td>
                    <td>${escapeHtml(item.parent_code ? `${item.parent_code} | ${item.parent_name || ''}` : '—')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderParentOptions(items) {
            const currentId = document.getElementById('account-id').value;
            parentSelect.innerHTML = '<option value="">— без родителя —</option>';
            items.forEach((item) => {
                if (String(item.id) === String(currentId)) {
                    return;
                }
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.code} | ${item.name}`;
                parentSelect.appendChild(option);
            });
        }

        function renderBindings(items) {
            bindingsTbody.innerHTML = '';
            if (!items.length) {
                bindingsEmptyMsg.style.display = 'block';
                return;
            }

            bindingsEmptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(item.name || '')}</td>
                    <td class="small text-muted">${escapeHtml(item.doc_label || 'Все документы')}</td>
                    <td>${buildAccountSelect(`binding-debit-${item.id}`, item.debit_account_id)}</td>
                    <td>${buildAccountSelect(`binding-credit-${item.id}`, item.credit_account_id)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-success binding-save-btn" data-id="${item.id}">💾</button>
                    </td>
                `;
                bindingsTbody.appendChild(tr);
            });
        }

        function buildAccountSelect(id, selectedId) {
            const options = ['<option value="">—</option>'].concat(accountsCache.map((item) => {
                const selected = String(selectedId || '') === String(item.id) ? 'selected' : '';
                return `<option value="${item.id}" ${selected}>${escapeHtml(item.code)} | ${escapeHtml(item.name)}</option>`;
            }));

            return `<select class="form-select form-select-sm" id="${id}">${options.join('')}</select>`;
        }

        function editAccount(id) {
            fetch(`/settings/accounts/${id}`)
                .then((r) => r.json())
                .then((item) => {
                    document.getElementById('account-id').value = item.id || '';
                    document.getElementById('account-code').value = item.code || '';
                    document.getElementById('account-name').value = item.name || '';
                    document.getElementById('account-type').value = item.type || 'asset';
                    renderParentOptions(accountsCache);
                    parentSelect.value = item.parent_id || '';
                    showAccountForm();
                })
                .catch(() => alert('Помилка завантаження рахунку'));
        }

        function deleteAccount(id) {
            if (!confirm('Видалити цей рахунок?')) return;

            fetch(`/settings/accounts/${id}`, {
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

                loadAccounts();
                loadBindings();
            })
            .catch(() => alert('Помилка мережі'));
        }

        function saveBinding(id) {
            const payload = {
                debit_account_id: document.getElementById(`binding-debit-${id}`).value || null,
                credit_account_id: document.getElementById(`binding-credit-${id}`).value || null,
            };

            fetch(`/settings/payment-type-account-bindings/${id}`, {
                method: 'PUT',
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
                    alert(data.message || 'Помилка збереження привязки');
                    return;
                }

                loadBindings();
            })
            .catch(() => alert('Помилка мережі'));
        }

        function accountTypeLabel(type) {
            return ({
                asset: 'Актив',
                liability: 'Пассив',
                equity: 'Капитал',
                income: 'Доход',
                expense: 'Расход',
            }[type] || type);
        }

        function resetAccountForm() {
            form.reset();
            document.getElementById('account-id').value = '';
            document.getElementById('account-type').value = 'asset';
            renderParentOptions(accountsCache);
            parentSelect.value = '';
        }

        function showAccountForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideAccountForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
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
            if (!window.appWallet || typeof window.appWallet.openModal !== 'function' || typeof window.appWallet.signMessage !== 'function') {
                setFeedback('Модуль Web3-гаманців не готовий. Оновіть сторінку та спробуйте ще раз.', true);
                return;
            }

            setFeedback('Оберіть гаманець у вікні підключення...');

            window.appWallet.openModal({
                autoLogin: false,
                onConnected: async ({ address, chainId, provider, walletType }) => {
                    try {
                        const normalizedType = walletType === 'solana' ? 'solana' : 'evm';
                        const network = normalizedType === 'solana'
                            ? 'Solana'
                            : (chainId ? `EVM ${chainId}` : 'EVM');

                        const challenge = await postJson('{{ route('wallet.challenge') }}', {
                            address,
                            wallet_type: normalizedType,
                        });

                        const signature = await window.appWallet.signMessage({
                            provider,
                            walletType: normalizedType,
                            address,
                            message: challenge.message,
                        });

                        const result = await postJson('{{ route('wallet.link') }}', {
                            address,
                            signature,
                            network,
                            wallet_type: normalizedType,
                        });

                        const user = result.user || {};
                        updateWalletState(user.wallets || []);
                        setFeedback('Гаманець успішно додано до вашого контрагента.');
                    } catch (error) {
                        setFeedback(error.message || 'Не вдалося прив’язати гаманець.', true);
                        throw error;
                    }
                }
            });
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
        const badgeCatalog = document.getElementById('badge-catalog');
        const badgeCity = document.getElementById('badge-city');
        const badgeTotal = document.getElementById('badge-field-total');
        const modeCatalogBtn = document.getElementById('btn-field-mode-catalog');
        const modeCityBtn = document.getElementById('btn-field-mode-city');
        const flagsRow = document.getElementById('catalog-flags-row');
        const descriptionRow = document.getElementById('catalog-description-row');
        const flagsHead = document.getElementById('catalog-flags-head');
        const descriptionHead = document.getElementById('catalog-description-head');
        const childrenHead = document.getElementById('catalog-children-head');

        let currentKeyfield = 'catalog';
        let currentParentId = '0';
        let breadcrumb = [{ id: 0, name: 'Категории/Надписи' }];

        const fieldModeConfig = {
            catalog: {
                root: 'Категории/Надписи',
                current: 'Категории и подписи',
                addLabel: '+ Добавить запись',
                empty: 'Записей на этом уровне пока нет',
                allowChildren: true,
                showExtra: true,
            },
            city: {
                root: 'Регионы',
                current: 'Список регионов',
                addLabel: '+ Добавить регион',
                empty: 'Регионов пока нет',
                allowChildren: false,
                showExtra: false,
            },
        };

        modal.addEventListener('show.bs.modal', () => {
            switchFieldMode('catalog');
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideCatalogForm();
            resetCatalogForm();
            currentKeyfield = 'catalog';
            currentParentId = '0';
            breadcrumb = [{ id: 0, name: fieldModeConfig.catalog.root }];
        });

        modeCatalogBtn?.addEventListener('click', () => switchFieldMode('catalog'));
        modeCityBtn?.addEventListener('click', () => switchFieldMode('city'));

        addBtn.addEventListener('click', () => {
            resetCatalogForm();
            document.getElementById('catalog-parent-id').value = currentKeyfield === 'catalog' ? currentParentId : '0';
            document.getElementById('catalog-keyfield').value = currentKeyfield;
            parentLabel.textContent = getCurrentParentName();
            showCatalogForm();
        });

        cancelBtn.addEventListener('click', () => {
            hideCatalogForm();
            resetCatalogForm();
        });

        backBtn.addEventListener('click', () => {
            if (currentKeyfield === 'catalog' && breadcrumb.length > 1) {
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
                keyfield: currentKeyfield,
                parent_id: currentKeyfield === 'catalog'
                    ? (document.getElementById('catalog-parent-id').value || currentParentId)
                    : '0',
                name_ru: document.getElementById('catalog-name-ru').value.trim(),
                name_ua: document.getElementById('catalog-name-ua').value.trim(),
                name_en: document.getElementById('catalog-name-en').value.trim(),
                link: document.getElementById('catalog-link').value.trim(),
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

            fetch(id ? `/settings/fields/${id}` : '/settings/fields', {
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
            const targetParentId = currentKeyfield === 'catalog' ? parentId : '0';
            fetch(`/settings/fields?keyfield=${encodeURIComponent(currentKeyfield)}&parent_id=${encodeURIComponent(targetParentId)}`)
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || 'Помилка завантаження каталогу');
                        return;
                    }

                    currentParentId = currentKeyfield === 'catalog'
                        ? String(data.currentParentId ?? '0')
                        : '0';
                    breadcrumb = Array.isArray(data.breadcrumb) && data.breadcrumb.length
                        ? data.breadcrumb
                        : [{ id: 0, name: fieldModeConfig[currentKeyfield].root }];

                    renderBreadcrumb(breadcrumb);
                    renderCatalog(data.items || []);

                    const currentParentName = data.currentParent?.name_ru || fieldModeConfig[currentKeyfield].root;
                    currentLevel.textContent = currentKeyfield === 'catalog'
                        ? (currentParentId === '0' ? fieldModeConfig.catalog.current : `Подкатегории: ${currentParentName}`)
                        : fieldModeConfig.city.current;
                    parentLabel.textContent = currentKeyfield === 'catalog'
                        ? (currentParentId === '0' ? fieldModeConfig.catalog.root : currentParentName)
                        : fieldModeConfig.city.root;
                    backBtn.style.display = currentKeyfield === 'catalog' && currentParentId !== '0' ? 'inline-block' : 'none';
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-danger">Помилка завантаження довідника</td></tr>';
                });
        }

        function renderCatalog(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.textContent = fieldModeConfig[currentKeyfield].empty;
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                const childLabel = currentKeyfield === 'catalog' && item.children_count > 0
                    ? `<span class="badge bg-info text-dark">${item.children_count}</span>`
                    : '<span class="text-muted">0</span>';
                const flags = fieldModeConfig[currentKeyfield].showExtra ? `
                    <div>${item.visible === '1' ? '<span class="badge bg-success">Показувати</span>' : '<span class="badge bg-secondary">Приховано</span>'}</div>
                    <div class="mt-1">${item.firstpage === '1' ? '<span class="badge bg-warning text-dark">Перша сторінка</span>' : '<span class="badge bg-light text-dark">Звичайна</span>'}</div>
                ` : '<span class="text-muted">—</span>';
                const description = fieldModeConfig[currentKeyfield].showExtra ? `
                    <div><strong>Link:</strong> ${escapeHtml(shortText(item.link || '—'))}</div>
                    <div>${escapeHtml(shortText(item.description_ru || '—'))}</div>
                    <div class="catalog-meta">UA: ${escapeHtml(shortText(item.description_ua || '—'))}</div>
                    <div class="catalog-meta">EN: ${escapeHtml(shortText(item.description_en || '—'))}</div>
                ` : '<span class="text-muted">—</span>';
                const openButton = currentKeyfield === 'catalog'
                    ? `<button class="btn btn-sm btn-outline-secondary action-btn" data-action="open" data-id="${item.id}">📂</button>`
                    : '';
                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>${item.num ?? 0}</td>
                    <td>
                        <div><strong>RU:</strong> ${escapeHtml(item.name_ru || '')}</div>
                        <div class="catalog-meta"><strong>UA:</strong> ${escapeHtml(item.name_ua || '—')}</div>
                        <div class="catalog-meta"><strong>EN:</strong> ${escapeHtml(item.name_en || '—')}</div>
                    </td>
                    <td>${flags}</td>
                    <td>${description}</td>
                    <td>${childLabel}</td>
                    <td class="text-end">
                        ${openButton}
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
            fetch(`/settings/fields/${id}?keyfield=${encodeURIComponent(currentKeyfield)}`)
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
                    document.getElementById('catalog-keyfield').value = data.keyfield || currentKeyfield;
                    document.getElementById('catalog-parent-id').value = data.parent_id || '0';
                    document.getElementById('catalog-name-ru').value = data.name_ru || '';
                    document.getElementById('catalog-name-ua').value = data.name_ua || '';
                    document.getElementById('catalog-name-en').value = data.name_en || '';
                    document.getElementById('catalog-link').value = data.link || '';
                    document.getElementById('catalog-num').value = data.num ?? 0;
                    document.getElementById('catalog-visible').checked = String(data.visible ?? '1') === '1';
                    document.getElementById('catalog-firstpage').checked = String(data.firstpage ?? '0') === '1';
                    document.getElementById('catalog-description-ru').value = data.description_ru || '';
                    document.getElementById('catalog-description-ua').value = data.description_ua || '';
                    document.getElementById('catalog-description-en').value = data.description_en || '';

                    parentLabel.textContent = currentKeyfield === 'catalog' && data.parent_id && data.parent_id !== '0'
                        ? getCurrentParentName(data.parent_id)
                        : fieldModeConfig[currentKeyfield].root;

                    showCatalogForm();
                })
                .catch(() => alert('Помилка завантаження категорії'));
        }

        function deleteCategory(id) {
            if (!confirm('Видалити категорію?')) return;

            fetch(`/settings/fields/${id}?keyfield=${encodeURIComponent(currentKeyfield)}`, {
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
            Promise.all([
                fetch('/settings/fields?keyfield=catalog&parent_id=0').then((r) => r.json()),
                fetch('/settings/fields?keyfield=city&parent_id=0').then((r) => r.json()),
            ]).then(([catalogData, cityData]) => {
                const catalogCount = (catalogData.items || []).length;
                const cityCount = (cityData.items || []).length;

                if (badgeCatalog) {
                    badgeCatalog.textContent = String(catalogCount);
                }
                if (badgeCity) {
                    badgeCity.textContent = String(cityCount);
                }
                if (badgeTotal) {
                    badgeTotal.textContent = String(catalogCount + cityCount);
                }
            });
        }

        function getCurrentParentName(parentId = currentParentId) {
            const found = breadcrumb.find((item) => String(item.id) === String(parentId));
            return found ? found.name : fieldModeConfig[currentKeyfield].root;
        }

        function resetCatalogForm() {
            form.reset();
            document.getElementById('catalog-id').value = '';
            document.getElementById('catalog-keyfield').value = currentKeyfield;
            document.getElementById('catalog-parent-id').value = currentKeyfield === 'catalog' ? currentParentId : '0';
            document.getElementById('catalog-link').value = '';
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

        function switchFieldMode(mode) {
            currentKeyfield = mode;
            currentParentId = '0';
            breadcrumb = [{ id: 0, name: fieldModeConfig[mode].root }];
            hideCatalogForm();
            resetCatalogForm();
            applyFieldMode();
            loadCatalog('0');
        }

        function applyFieldMode() {
            const config = fieldModeConfig[currentKeyfield];
            modeCatalogBtn?.classList.toggle('active', currentKeyfield === 'catalog');
            modeCityBtn?.classList.toggle('active', currentKeyfield === 'city');
            addBtn.textContent = config.addLabel;
            currentLevel.textContent = config.current;
            parentLabel.textContent = config.root;
            emptyMsg.textContent = config.empty;

            const display = config.showExtra ? '' : 'none';
            if (flagsRow) flagsRow.style.display = display;
            if (descriptionRow) descriptionRow.style.display = display;
            if (flagsHead) flagsHead.style.display = display;
            if (descriptionHead) descriptionHead.style.display = display;
            if (childrenHead) childrenHead.textContent = config.allowChildren ? 'Підкатегорії' : 'Записів';
            if (breadcrumbBox) breadcrumbBox.style.display = config.allowChildren ? '' : 'none';
            if (backBtn) backBtn.style.display = 'none';
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
        const signatureInput = document.getElementById('firm-pidpys-file');
        const stampInput = document.getElementById('firm-pechat-file');

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

        signatureInput.addEventListener('change', () => {
            updateFirmImagePreview(signatureInput, 'firm-pidpys-preview', 'firm-pidpys-preview-wrap');
        });

        stampInput.addEventListener('change', () => {
            updateFirmImagePreview(stampInput, 'firm-pechat-preview', 'firm-pechat-preview-wrap');
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

            if (!payload.get('name')) {
                alert('Вкажіть назву компанії');
                return;
            }

            fetch(id ? `/settings/firms/${id}` : '/settings/firms', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: payload,
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
            const payload = new FormData();
            payload.append('name', document.getElementById('firm-name').value.trim());
            payload.append('regnum', document.getElementById('firm-regnum').value.trim());
            payload.append('inn', document.getElementById('firm-inn').value.trim());
            payload.append('schet', document.getElementById('firm-schet').value.trim());
            payload.append('bank', document.getElementById('firm-bank').value.trim());
            payload.append('mfo', document.getElementById('firm-mfo').value.trim());
            payload.append('town', document.getElementById('firm-town').value.trim());
            payload.append('address', document.getElementById('firm-address').value.trim());
            payload.append('map', document.getElementById('firm-map').value.trim());
            payload.append('view', document.getElementById('firm-view').value.trim());
            payload.append('phone', document.getElementById('firm-phone').value.trim());
            payload.append('direktor', document.getElementById('firm-direktor').value.trim());

            const id = document.getElementById('firm-id').value;
            if (id) {
                payload.append('_method', 'PUT');
            }

            if (signatureInput.files[0]) {
                payload.append('pidpys_file', signatureInput.files[0]);
            }

            if (stampInput.files[0]) {
                payload.append('pechat_file', stampInput.files[0]);
            }

            return payload;
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
                    updateFirmImagePreview(null, 'firm-pidpys-preview', 'firm-pidpys-preview-wrap', data.pidpys_preview || '');
                    updateFirmImagePreview(null, 'firm-pechat-preview', 'firm-pechat-preview-wrap', data.pechat_preview || '');

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
            updateFirmImagePreview(null, 'firm-pidpys-preview', 'firm-pidpys-preview-wrap', '');
            updateFirmImagePreview(null, 'firm-pechat-preview', 'firm-pechat-preview-wrap', '');
        }

        function showFirmForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideFirmForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }

        function updateFirmImagePreview(input, imageId, wrapId, explicitUrl = '') {
            const image = document.getElementById(imageId);
            const wrap = document.getElementById(wrapId);

            if (explicitUrl) {
                image.src = explicitUrl;
                wrap.hidden = false;
                return;
            }

            const file = input?.files?.[0];
            if (!file) {
                image.src = '';
                wrap.hidden = true;
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                image.src = reader.result;
                wrap.hidden = false;
            };
            reader.readAsDataURL(file);
        }
    }

    function initBannerCrud(csrfToken) {
        const modal = document.getElementById('modalBanners');
        const listArea = document.getElementById('banner-list-area');
        const formArea = document.getElementById('banner-form-area');
        const tbody = document.getElementById('banners-tbody');
        const emptyMsg = document.getElementById('banners-empty-msg');
        const form = document.getElementById('banner-form');
        const addBtn = document.getElementById('btn-banner-add');
        const cancelBtn = document.getElementById('btn-banner-cancel');
        const imageInput = document.getElementById('banner-image-file');

        modal.addEventListener('show.bs.modal', () => {
            hideBannerForm();
            loadBanners();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideBannerForm();
            resetBannerForm();
        });

        addBtn.addEventListener('click', () => {
            resetBannerForm();
            showBannerForm();
        });

        cancelBtn.addEventListener('click', () => {
            hideBannerForm();
            resetBannerForm();
        });

        imageInput.addEventListener('change', () => {
            updateImagePreview(imageInput, 'banner-image-preview', 'banner-image-preview-wrap');
        });

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editBanner(id);
            }
            if (btn.dataset.action === 'delete') {
                deleteBanner(id, btn);
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = document.getElementById('banner-id').value;
            const payload = collectBannerPayload(id);

            if (!payload.get('title')) {
                alert('Вкажіть заголовок банера');
                return;
            }

            fetch(id ? `/settings/banners/${id}` : '/settings/banners', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: payload,
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

                hideBannerForm();
                resetBannerForm();
                loadBanners();
            })
            .catch(() => alert('Помилка мережі'));
        });

        function collectBannerPayload(id) {
            const payload = new FormData();
            payload.append('title', document.getElementById('banner-title').value.trim());
            payload.append('subtitle', document.getElementById('banner-subtitle').value.trim());
            payload.append('button_text', document.getElementById('banner-button-text').value.trim());
            payload.append('link_url', document.getElementById('banner-link-url').value.trim());
            payload.append('sort_order', document.getElementById('banner-sort-order').value.trim() || '0');
            payload.append('vision', document.getElementById('banner-vision').value);

            if (id) {
                payload.append('_method', 'PUT');
            }

            if (imageInput.files[0]) {
                payload.append('image_file', imageInput.files[0]);
            }

            return payload;
        }

        function loadBanners() {
            fetch('/settings/banners')
                .then((r) => r.json())
                .then((items) => {
                    renderBanners(items);
                    const badge = document.getElementById('badge-banners');
                    if (badge) {
                        badge.textContent = items.length;
                    }
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-danger">Помилка завантаження банерів</td></tr>';
                });
        }

        function renderBanners(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                const preview = item.image_url
                    ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title || '')}" style="width:120px;height:64px;object-fit:cover;border-radius:8px;display:block;margin-bottom:8px;">`
                    : '';
                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>
                        ${preview}
                        <div><strong>${escapeHtml(item.title || '')}</strong></div>
                        <div class="company-meta">${escapeHtml(item.subtitle || '—')}</div>
                    </td>
                    <td>${escapeHtml(item.link_url || '—')}</td>
                    <td>${item.sort_order ?? 0}</td>
                    <td>${String(item.vision) === '1' ? '<span class="badge bg-success">Показується</span>' : '<span class="badge bg-secondary">Прихований</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function editBanner(id) {
            fetch(`/settings/banners/${id}`)
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || 'Банер не знайдено');
                        return;
                    }

                    document.getElementById('banner-id').value = data.id || '';
                    document.getElementById('banner-title').value = data.title || '';
                    document.getElementById('banner-subtitle').value = data.subtitle || '';
                    document.getElementById('banner-button-text').value = data.button_text || '';
                    document.getElementById('banner-link-url').value = data.link_url || '';
                    document.getElementById('banner-sort-order').value = data.sort_order ?? 0;
                    document.getElementById('banner-vision').value = String(data.vision ?? 1);
                    updateImagePreview(null, 'banner-image-preview', 'banner-image-preview-wrap', data.image_url || '');

                    showBannerForm();
                })
                .catch(() => alert('Помилка завантаження банера'));
        }

        function deleteBanner(id, btn) {
            if (!confirm('Видалити банер?')) return;

            fetch(`/settings/banners/${id}`, {
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
                document.getElementById('badge-banners').textContent = rest;
                if (!rest) {
                    emptyMsg.style.display = 'block';
                }
            })
            .catch(() => alert('Помилка мережі'));
        }

        function resetBannerForm() {
            form.reset();
            document.getElementById('banner-id').value = '';
            document.getElementById('banner-sort-order').value = '0';
            document.getElementById('banner-vision').value = '1';
            updateImagePreview(null, 'banner-image-preview', 'banner-image-preview-wrap', '');
        }

        function showBannerForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
        }

        function hideBannerForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }
    }

    function updateImagePreview(input, imageId, wrapId, explicitUrl = '') {
        const image = document.getElementById(imageId);
        const wrap = document.getElementById(wrapId);

        if (explicitUrl) {
            image.src = explicitUrl;
            wrap.hidden = false;
            return;
        }

        const file = input?.files?.[0];
        if (!file) {
            image.src = '';
            wrap.hidden = true;
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            image.src = reader.result;
            wrap.hidden = false;
        };
        reader.readAsDataURL(file);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endsection
