@extends('home')

@section('title', __('settings.title'))

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
@include('settings.partials.i18n')

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
                    <span class="badge bg-primary" id="badge-projects">{{ $projectsCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="status" data-title="📊 {{ __('settings.cards.statuses.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">📊 {{ __('settings.cards.statuses.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.statuses.description') }}</p>
                    <span class="badge bg-success" id="badge-status">{{ count($statuses ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="reestr" data-title="💳 {{ __('settings.cards.payment_types.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">💳 {{ __('settings.cards.payment_types.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.payment_types.description') }}</p>
                    <span class="badge bg-info" id="badge-reestr">{{ count($reestrs ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="asset_type" data-title="🏗 Типы активов">
                <div class="card-body text-center">
                    <h5 class="card-title">🏗 Типы активов</h5>
                    <p class="card-text text-muted">Оборудование, недвижимость, ценные бумаги, криптоактивы и R&D</p>
                    <span class="badge bg-warning text-dark" id="badge-asset_type">{{ count($assetTypes ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="tgroup" data-title="👥 {{ __('settings.cards.client_type.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">👥 {{ __('settings.cards.client_type.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.client_type.description') }}</p>
                    <span class="badge bg-secondary" id="badge-tgroup">{{ count($tgroups ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="tclient" data-title="🏷 {{ __('settings.cards.counterparty_type.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">🏷 {{ __('settings.cards.counterparty_type.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.counterparty_type.description') }}</p>
                    <span class="badge bg-dark" id="badge-tclient">{{ count($tclients ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="oplata" data-title="💰 {{ __('settings.cards.cashbox.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">💰 {{ __('settings.cards.cashbox.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.cashbox.description') }}</p>
                    <span class="badge bg-warning text-dark" id="badge-oplata">{{ count($oplatas ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="currency" data-title="💱 {{ __('settings.cards.currencies.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">💱 {{ __('settings.cards.currencies.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.currencies.description') }}</p>
                    <span class="badge bg-info text-dark" id="badge-currency">{{ count($currencies ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="faq" data-title="❓ FAQ">
                <div class="card-body text-center">
                    <h5 class="card-title">❓ FAQ</h5>
                    <p class="card-text text-muted">Вопросы и ответы для страниц сайта</p>
                    <span class="badge bg-info text-dark" id="badge-faq">{{ count($faqs ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="sklads" data-title="🏢 {{ __('settings.cards.offices.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">🏢 {{ __('settings.cards.offices.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.offices.description') }}</p>
                    <span class="badge bg-secondary" id="badge-sklads">{{ count($sklads ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCrud" data-type="deposit" data-title="🏦 {{ __('settings.cards.deposits.modal_title') }}">
                <div class="card-body text-center">
                    <h5 class="card-title">🏦 {{ __('settings.cards.deposits.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.deposits.description') }}</p>
                    <span class="badge bg-dark" id="badge-deposit">{{ count($deposits ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-info setting-card" data-bs-toggle="modal" data-bs-target="#modalCatalog" data-field-mode="catalog">
                <div class="card-body text-center">
                    <h5 class="card-title">🌐 {{ __('settings.catalog_directory') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.catalog_directory_desc') }}</p>
                    <span class="badge bg-info text-dark" id="badge-catalog">{{ $fieldCatalogTopCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-info setting-card" data-bs-toggle="modal" data-bs-target="#modalCatalog" data-field-mode="city">
                <div class="card-body text-center">
                    <h5 class="card-title">📍 {{ __('settings.regions_cities') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.regions_cities_desc') }}</p>
                    <span class="badge bg-info text-dark" id="badge-city">{{ $fieldCityCount ?? 0 }}</span>
                    <div class="small text-muted mt-2">
                        {{ __('settings.catalog.regions_count') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalCatalogFilters">
                <div class="card-body text-center">
                    <h5 class="card-title">🔎 {{ __('settings.cards.catalog_filters.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.catalog_filters.description') }}</p>
                    <span class="badge bg-primary" id="badge-catalog-filters">{{ $catalogFiltersGroupCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-primary setting-card" data-bs-toggle="modal" data-bs-target="#modalProfile">
                <div class="card-body text-center">
                    <h5 class="card-title">👤 {{ __('settings.cards.profile.title') }}</h5>
                    <p class="card-text text-muted">{{ session('login', '') }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-warning setting-card" data-bs-toggle="modal" data-bs-target="#modalFirms">
                <div class="card-body text-center">
                    <h5 class="card-title">🏛 {{ __('settings.cards.my_companies.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.my_companies.description') }}</p>
                    <span class="badge bg-warning text-dark" id="badge-firms">{{ count($myCompanies ?? []) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-danger setting-card" data-bs-toggle="modal" data-bs-target="#modalBanners">
                <div class="card-body text-center">
                    <h5 class="card-title">🎞 {{ __('settings.cards.banner_carousel.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.banner_carousel.description') }}</p>
                    <span class="badge bg-danger" id="badge-banners">{{ $bannerCarouselCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-secondary setting-card" data-bs-toggle="modal" data-bs-target="#modalSitemap">
                <div class="card-body text-center">
                    <h5 class="card-title">🗺 {{ __('settings.sitemap') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.sitemap_desc') }}</p>
                    <span class="badge bg-secondary" id="badge-sitemap">{{ !empty($sitemapInfo['exists']) ? 'XML' : '—' }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-success setting-card" data-bs-toggle="modal" data-bs-target="#modalAccounts">
                <div class="card-body text-center">
                    <h5 class="card-title">📚 {{ __('settings.cards.chart_of_accounts.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.chart_of_accounts.description') }}</p>
                    <span class="badge bg-success" id="badge-accounts">{{ $accountsCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 border-warning setting-card" data-bs-toggle="modal" data-bs-target="#modalReportRules">
                <div class="card-body text-center">
                    <h5 class="card-title">📈 Правила отчетов</h5>
                    <p class="card-text text-muted">Документы и виды платежей для Cash Flow</p>
                    <span class="badge bg-warning text-dark">3</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalKnowledgeBase" style="border-color: #a5b4fc;">
                <div class="card-body text-center">
                    <h5 class="card-title">🧠 {{ __('settings.cards.knowledge_base.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.knowledge_base.description') }}</p>
                    <span class="badge" style="background:#a5b4fc;color:#020617;" id="badge-knowledge-base">{{ $knowledgeBaseCount ?? 0 }}</span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalSitemap" tabindex="-1" aria-labelledby="modalSitemapLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSitemapLabel">🗺 {{ __('settings.sitemap') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('settings.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary mb-3">
                    {{ __('settings.sitemap_modal.intro') }}
                </div>

                <dl class="row mb-3">
                    <dt class="col-sm-4">{{ __('settings.sitemap_modal.status_label') }}</dt>
                    <dd class="col-sm-8" id="sitemap-status-text">{{ !empty($sitemapInfo['exists']) ? __('settings.sitemap_modal.file_ready') : __('settings.sitemap_modal.file_missing') }}</dd>

                    <dt class="col-sm-4">{{ __('settings.sitemap_modal.last_generation') }}</dt>
                    <dd class="col-sm-8" id="sitemap-lastmod-text">
                        @if(!empty($sitemapInfo['last_modified_at']))
                            {{ date('Y-m-d H:i:s', $sitemapInfo['last_modified_at']) }}
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4">{{ __('settings.sitemap_modal.public_url') }}</dt>
                    <dd class="col-sm-8">
                        <a href="{{ $sitemapInfo['public_url'] ?? '#' }}" id="sitemap-public-link" target="_blank" rel="noopener noreferrer">
                            {{ $sitemapInfo['public_url'] ?? '—' }}
                        </a>
                    </dd>
                </dl>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="btn-sitemap-generate">🔄 {{ __('settings.sitemap_modal.btn_generate') }}</button>
                    <a href="{{ $sitemapInfo['public_url'] ?? '#' }}" class="btn btn-outline-secondary" id="btn-sitemap-open" target="_blank" rel="noopener noreferrer">🌍 {{ __('settings.sitemap_modal.btn_open') }}</a>
                </div>
                <div class="small text-muted mt-3" id="sitemap-feedback"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAccounts" tabindex="-1" aria-labelledby="modalAccountsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable accounts-modal-dialog">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalAccountsLabel">📚 {{ __('settings.cards.chart_of_accounts.modal_title') }}</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-account-add">+ {{ __('settings.accounts.add_account') }}</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('settings.common.close') }}"></button>
            </div>

            <div class="modal-body" id="account-form-area" style="display:none;">
                <form id="account-form">
                    <input type="hidden" id="account-id" value="">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('settings.accounts.code') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account-code" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">{{ __('settings.accounts.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="account-name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.accounts.type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="account-type" required>
                                <option value="asset">{{ __('settings.accounts.type_asset') }}</option>
                                <option value="liability">{{ __('settings.accounts.type_liability') }}</option>
                                <option value="equity">{{ __('settings.accounts.type_equity') }}</option>
                                <option value="income">{{ __('settings.accounts.type_income') }}</option>
                                <option value="expense">{{ __('settings.accounts.type_expense') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.accounts.parent') }}</label>
                            <select class="form-select" id="account-parent-id">
                                <option value="">{{ __('settings.accounts.no_parent') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.accounts.currency') }}</label>
                            <select class="form-select" id="account-currency"></select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 {{ __('settings.common.save') }}</button>
                        <button type="button" class="btn btn-secondary" id="btn-account-cancel">{{ __('settings.common.cancel') }}</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="account-list-area">
                <ul class="nav nav-tabs mb-3" id="accounts-modal-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="accounts-tab" data-bs-toggle="tab"
                            data-bs-target="#accounts-tab-pane" type="button" role="tab"
                            aria-controls="accounts-tab-pane" aria-selected="true">
                            {{ __('settings.accounts.accounts_heading') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="analytical-accounts-tab" data-bs-toggle="tab"
                            data-bs-target="#analytical-accounts-tab-pane" type="button" role="tab"
                            aria-controls="analytical-accounts-tab-pane" aria-selected="false">
                            {{ __('settings.accounts.analytics_heading') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payment-bindings-tab" data-bs-toggle="tab"
                            data-bs-target="#payment-bindings-tab-pane" type="button" role="tab"
                            aria-controls="payment-bindings-tab-pane" aria-selected="false">
                            {{ __('settings.accounts.bindings_heading') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="accounts-tab-pane" role="tabpanel"
                        aria-labelledby="accounts-tab" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle w-100 accounts-wide-table">
                                <colgroup>
                                    <col class="accounts-wide-table__first-column">
                                    <col>
                                    <col>
                                    <col>
                                    <col>
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>{{ __('settings.accounts.th_code') }}</th>
                                        <th>{{ __('settings.accounts.th_name') }}</th>
                                        <th>{{ __('settings.accounts.th_type') }}</th>
                                        <th>{{ __('settings.accounts.th_currency') }}</th>
                                        <th>{{ __('settings.accounts.th_parent') }}</th>
                                        <th class="text-end">{{ __('settings.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="accounts-tbody"></tbody>
                            </table>
                        </div>
                        <p class="text-center text-muted" id="accounts-empty-msg" style="display:none">{{ __('settings.accounts.empty_accounts') }}</p>
                    </div>

                    <div class="tab-pane fade" id="analytical-accounts-tab-pane" role="tabpanel"
                        aria-labelledby="analytical-accounts-tab" tabindex="0">
                        <div class="small text-muted mb-3">{{ __('settings.accounts.analytics_help') }}</div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle w-100 accounts-wide-table">
                                <colgroup>
                                    <col class="accounts-wide-table__first-column">
                                    <col>
                                    <col>
                                    <col>
                                    <col>
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>{{ __('settings.accounts.th_code') }}</th>
                                        <th>{{ __('settings.accounts.th_name') }}</th>
                                        <th>{{ __('settings.accounts.th_type') }}</th>
                                        <th>{{ __('settings.accounts.th_currency') }}</th>
                                        <th>{{ __('settings.accounts.th_parent') }}</th>
                                        <th class="text-end">{{ __('settings.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="analytical-accounts-tbody"></tbody>
                            </table>
                        </div>
                        <p class="text-center text-muted" id="analytical-accounts-empty-msg" style="display:none">{{ __('settings.accounts.empty_analytics') }}</p>
                    </div>

                    <div class="tab-pane fade" id="payment-bindings-tab-pane" role="tabpanel"
                        aria-labelledby="payment-bindings-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="small text-muted">{{ __('settings.accounts.bindings_help') }}</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-payment-bindings-reload">{{ __('settings.common.refresh') }}</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle w-100 accounts-wide-table">
                                <colgroup>
                                    <col class="accounts-wide-table__first-column">
                                    <col>
                                    <col>
                                    <col>
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>{{ __('settings.accounts.th_payment_type') }}</th>
                                        <th>{{ __('settings.accounts.th_documents') }}</th>
                                        <th>{{ __('settings.accounts.th_debit') }}</th>
                                        <th>{{ __('settings.accounts.th_credit') }}</th>
                                        <th class="text-end">{{ __('settings.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="payment-bindings-tbody"></tbody>
                            </table>
                        </div>
                        <p class="text-center text-muted" id="payment-bindings-empty-msg" style="display:none">{{ __('settings.accounts.empty_payments') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReportRules" tabindex="-1" aria-labelledby="modalReportRulesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReportRulesLabel">📈 Правила отчетов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted small mb-3">
                    Отчет Cash Flow строится по проведенным денежным проводкам счетов 301 и 311. Вид деятельности в первую очередь берется из настройки «Виды платежей», поле «Вид деятельности». Если у вида платежа значение не задано, применяется правило по типу документа.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle report-rules-table">
                        <thead>
                            <tr>
                                <th>Вид деятельности</th>
                                <th>Документы</th>
                                <th>Движение денег</th>
                                <th>Как попадает в отчет</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success">Операционная</span></td>
                                <td><code>PO</code>, <code>CPO</code>, <code>RO</code>, <code>CRO</code>, <code>ZP</code>, <code>PPO</code>, <code>PRO</code></td>
                                <td>Поступления от клиентов и продаж; выплаты зарплаты, аренды, налогов, поставщикам и прочие текущие платежи.</td>
                                <td>Если «Вид платежа» документа = «Операционная» или если вид платежа не задан, но тип документа входит в этот список.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">Инвестиционная</span></td>
                                <td><code>PP</code> и любые денежные документы с видом платежа «Инвестиционная»</td>
                                <td>Покупка и продажа оборудования, активов, инвестиций, депозитных/инвестиционных операций.</td>
                                <td>Если «Вид платежа» документа = «Инвестиционная»; для <code>PP</code> это fallback-правило.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-secondary">Финансовая</span></td>
                                <td>Любые денежные документы с видом платежа «Финансовая»; остальные денежные проводки без операционного или инвестиционного правила</td>
                                <td>Кредиты, займы, дивиденды, взносы собственников, привлечение или возврат финансирования.</td>
                                <td>Если «Вид платежа» документа = «Финансовая» или денежная проводка не распознана предыдущими правилами.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info small mb-0">
                    Для изменения классификации откройте <strong>Settings → Виды платежей</strong> и задайте «Вид деятельности» у нужного вида платежа. Это точнее, чем ключевые слова в примечании документа.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #modalAccounts .accounts-modal-dialog {
        width: min(96vw, 1680px);
        max-width: min(96vw, 1680px);
    }

    #modalAccounts .accounts-wide-table {
        min-width: 980px;
        table-layout: fixed;
    }

    #modalAccounts .accounts-wide-table__first-column {
        width: 20%;
    }

    #modalAccounts #payment-bindings-tab-pane .form-select {
        min-width: 260px;
    }

    .pool-deposit-row {
        cursor: pointer;
    }
</style>

<div class="modal fade" id="modalCatalog" tabindex="-1" aria-labelledby="modalCatalogLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="modal-title" id="modalCatalogLabel">🌐 {{ __('settings.catalog_directory') }}</h5>
                    <div class="small text-muted" id="catalog-current-level">{{ __('settings.catalog_modal.current_level_catalog') }}</div>
                </div>
                <div class="btn-group btn-group-sm ms-md-3" role="group" aria-label="{{ __('settings.catalog_modal.aria_mode') }}">
                    <button type="button" class="btn btn-outline-primary active" id="btn-field-mode-catalog">{{ __('settings.catalog_modal.btn_mode_catalog') }}</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-field-mode-city">{{ __('settings.catalog_modal.btn_mode_city') }}</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-md-3" id="btn-catalog-back" style="display:none;">← {{ __('settings.common.back') }}</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-catalog-add">{{ __('settings.catalog_modal.btn_add_entry') }}</button>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="{{ __('settings.common.close') }}"></button>
            </div>

            <div class="modal-body" id="catalog-form-area" style="display:none">
                <form id="catalog-form">
                    <input type="hidden" id="catalog-id" value="">
                    <input type="hidden" id="catalog-parent-id" value="0">
                    <input type="hidden" id="catalog-keyfield" value="catalog">

                    <div class="alert alert-secondary py-2 mb-3">
                        <strong>{{ __('settings.catalog_modal.section_label') }}</strong> <span id="catalog-parent-label">{{ __('settings.catalog_modal.current_level_catalog') }}</span>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.name_ru') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="catalog-name-ru" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.name_ua') }}</label>
                            <input type="text" class="form-control" id="catalog-name-ua">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.name_en') }}</label>
                            <input type="text" class="form-control" id="catalog-name-en">
                        </div>
                    </div>

                    <div class="row" id="catalog-flags-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.num') }}</label>
                            <input type="number" class="form-control" id="catalog-num" min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="catalog-visible" checked>
                                <label class="form-check-label" for="catalog-visible">{{ __('settings.catalog_modal.visible') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="catalog-firstpage">
                                <label class="form-check-label" for="catalog-firstpage">{{ __('settings.catalog_modal.firstpage') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="catalog-description-row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.link') }}</label>
                            <input type="text" class="form-control" id="catalog-link" maxlength="35" placeholder="{{ __('settings.catalog_modal.link_placeholder') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.news_catalog') }}</label>
                            <select class="form-select" id="catalog-news-catalog">
                                <option value="">{{ __('settings.catalog_modal.news_none') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.file_upload') }}</label>
                            <input type="hidden" id="catalog-file-path">
                            <input type="file" class="form-control" id="catalog-file-upload" accept="image/*">
                            <div class="form-text" id="catalog-file-current">{{ __('settings.catalog_modal.file_not_uploaded') }}</div>
                            <div class="mt-2" id="catalog-file-preview-wrap" hidden>
                                <img
                                    src=""
                                    alt="{{ __('settings.catalog_modal.file_upload') }}"
                                    id="catalog-file-preview"
                                    class="img-thumbnail"
                                    style="display:block;max-width:220px;max-height:160px;object-fit:contain;"
                                >
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.descr_ru') }}</label>
                            <textarea class="form-control" id="catalog-description-ru" rows="4"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.descr_ua') }}</label>
                            <textarea class="form-control" id="catalog-description-ua" rows="4"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.catalog_modal.descr_en') }}</label>
                            <textarea class="form-control" id="catalog-description-en" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 {{ __('settings.common.save') }}</button>
                        <button type="button" class="btn btn-secondary" id="btn-catalog-cancel">{{ __('settings.common.cancel') }}</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="catalog-list-area">
                <div class="mb-3" id="catalog-breadcrumb"></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle catalog-table">
                        <thead>
                            <tr>
                                <th class="catalog-id-cell">{{ __('settings.catalog_modal.th_id') }}</th>
                                <th>{{ __('settings.catalog_modal.th_names') }}</th>
                                <th id="catalog-description-head" class="catalog-description-cell">{{ __('settings.catalog_modal.th_description') }}</th>
                                <th id="catalog-flags-head" class="catalog-flags-cell">{{ __('settings.catalog_modal.th_flags') }}</th>
                                <th id="catalog-children-head" class="catalog-children-cell">{{ __('settings.catalog_modal.th_subcategories') }}</th>
                                <th class="catalog-actions-cell">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="catalog-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="catalog-empty-msg" style="display:none">{{ __('settings.field_modes.catalog.empty') }}</p>
            </div>

            <div class="modal-body" id="region-cities-area" style="display:none">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h6 class="mb-0" id="region-cities-title"></h6>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-region-city-add">{{ __('settings.catalog_modal.add_city') }}</button>
                </div>
                <div id="region-city-form-area" class="glass-card p-3 mb-3" style="display:none">
                    <form id="region-city-form">
                        <input type="hidden" id="region-city-id">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('settings.catalog_modal.city_name_ua') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="region-city-val" maxlength="60" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('settings.catalog_modal.city_name_ru') }}</label>
                                <input type="text" class="form-control" id="region-city-valru" maxlength="60">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('settings.catalog_modal.city_name_en') }}</label>
                                <input type="text" class="form-control" id="region-city-valen" maxlength="60">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">{{ __('settings.catalog_modal.num') }}</label>
                                <input type="number" class="form-control" id="region-city-num" min="0" max="65535" value="0">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">{{ __('settings.common.save') }}</button>
                            <button type="button" class="btn btn-secondary" id="btn-region-city-cancel">{{ __('settings.common.cancel') }}</button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>UA (val)</th>
                                <th>RU (valru)</th>
                                <th>EN (valen)</th>
                                <th>num</th>
                                <th class="text-end">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="region-cities-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="region-cities-empty" style="display:none">{{ __('settings.catalog_modal.cities_empty') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCatalogFilters" tabindex="-1" aria-labelledby="modalCatalogFiltersLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header flex-wrap gap-2">
                <h5 class="modal-title" id="modalCatalogFiltersLabel">🔎 Фільтри каталогу</h5>
                <button type="button" class="btn btn-sm btn-primary" id="btn-catalog-filters-add-group">+ Група</button>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div id="catalog-filters-main-area">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-10">
                            <label class="form-label" for="catalog-filters-category">Категорія (field.keyfield = catalog)</label>
                            <select class="form-select" id="catalog-filters-category">
                                <option value="">— оберіть —</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-primary w-100" id="btn-catalog-filters-load">Показати</button>
                        </div>
                    </div>
                    <div id="catalog-filters-list" class="catalog-filters-tree"></div>
                    <p class="text-center text-muted mt-3" id="catalog-filters-empty" style="display:none;">Немає даних для обраної категорії</p>
                </div>
                <div id="catalog-filters-form-area" style="display:none;">
                    <form id="catalog-filters-form">
                        <input type="hidden" id="catalog-filters-record-id" value="">
                        <input type="hidden" id="catalog-filters-catalog-id" value="">
                        <input type="hidden" id="catalog-filters-is-group" value="1">
                        <input type="hidden" id="catalog-filters-parent-group-id" value="">
                        <div class="alert alert-secondary py-2" id="catalog-filters-form-hint"></div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Назва UA (val) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="catalog-filters-val" maxlength="60" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Назва RU (valru)</label>
                                <input type="text" class="form-control" id="catalog-filters-valru" maxlength="60">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Назва EN (valen)</label>
                                <input type="text" class="form-control" id="catalog-filters-valen" maxlength="60">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Порядок (num)</label>
                                <input type="number" class="form-control" id="catalog-filters-num" min="0" max="65535" value="0">
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-success">Зберегти</button>
                            <button type="button" class="btn btn-secondary" id="btn-catalog-filters-form-cancel">Скасувати</button>
                        </div>
                    </form>
                </div>
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
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Назва <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project-name" maxlength="50" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Тип проекта</label>
                            <select class="form-select" id="project-type">
                                <option value="">Не указан</option>
                                <option value="trade">Торговля</option>
                                <option value="bank">Банк</option>
                                <option value="insurance">Страхование</option>
                                <option value="education">Образование</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 position-relative">
                            <label class="form-label">Холдинг</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="project-holding" autocomplete="off" placeholder="Введите или выберите">
                                <button type="button" class="btn btn-outline-secondary" id="project-holding-toggle">▾</button>
                            </div>
                            <div class="project-holding-menu shadow-sm" id="project-holding-menu" hidden></div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">userid</label>
                            <input type="number" class="form-control" id="project-userid" min="0" value="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">email</label>
                            <input type="email" class="form-control" id="project-email" maxlength="255" placeholder="fallback при зміні проєкту, якщо у сесії немає email">
                            <div class="form-text small text-muted">Звичайно email береться з облікового запису; це поле — резерв для перемикання проєкту.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">phone</label>
                            <input type="text" class="form-control" id="project-phone" maxlength="255">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">url</label>
                            <textarea class="form-control" id="project-url" rows="2" placeholder="https://example.com"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
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
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="project-constanta">
                                <label class="form-check-label" for="project-constanta">Маркетплейс</label>
                                <div class="form-text">Категорії цього проєкту доступні іншим проєктам.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-outline-danger" id="btn-project-delete" style="display:none;">🗑 Видалити</button>
                        <button type="button" class="btn btn-secondary" id="btn-project-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="project-list-area">
                <div class="project-list-toolbar mb-3">
                    <input type="search" class="form-control" id="projects-search" placeholder="Поиск по названию проекта" autocomplete="off">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="projects-all">
                        <label class="form-check-label" for="projects-all">Все проекты</label>
                    </div>
                </div>
                <div class="table-responsive project-list-scroll" id="projects-scroll-area">
                    <table class="table table-hover table-sm align-middle project-compact-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Назва</th>
                                <th>Статус</th>
                                <th>Холдинг</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody id="projects-tbody"></tbody>
                    </table>
                    <div class="text-center text-muted py-2" id="projects-loading" hidden>Загрузка...</div>
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

            <div class="modal-body pb-0" id="currency-tabs-area" style="display:none;">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active" id="currency-tab-directory">Справочник</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" id="currency-tab-exchange">Обмен</button>
                    </li>
                </ul>
            </div>

            <div class="modal-body" id="conf-form-area" style="display:none">
                <form id="crud-form">
                    <input type="hidden" id="form-id" value="">
                    <input type="hidden" id="form-foto-existing" value="">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="form-name" class="form-label">Назва <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="form-name" required>
                        </div>
                        <div class="col-md-4 mb-3" id="form-color-row">
                            <label for="form-color" class="form-label" id="form-color-label">Колір</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color" id="form-color-picker" value="#ffffff">
                                <input type="text" class="form-control" id="form-color" placeholder="#hex">
                                <select class="form-select" id="form-faq-page" style="display:none;">
                                    <option value="academy">academy</option>
                                    <option value="portfolio">portfolio</option>
                                    <option value="swap">swap</option>
                                    <option value="articles">articles</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" id="form-visibility-row" style="display:none;">
                            <label class="form-label d-block">Видимість</label>
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="form-visibility-checkbox">
                                <label class="form-check-label" for="form-visibility-checkbox" id="form-visibility-label">Видимий</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" id="form-default-row" style="display:none;">
                            <label class="form-label d-block">Default</label>
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="form-default-checkbox">
                                <label class="form-check-label" for="form-default-checkbox">За замовчуванням</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="form-status-row">
                        <label for="form-status" class="form-label" id="form-status-label">Статус</label>
                        <select class="form-select" id="form-status">
                            <option value="1">Активний</option>
                            <option value="0">Неактивний</option>
                        </select>
                        <div class="form-text" id="form-status-help" style="display:none;"></div>
                    </div>
                    <div class="mb-3" id="form-currency-row" style="display:none;">
                        <label for="form-currency" class="form-label">{{ __('settings.crud.currency_label') }}</label>
                        <select class="form-select" id="form-currency"></select>
                    </div>
                    <div class="mb-3" id="form-description-row" style="display:none;">
                        <label for="form-description" class="form-label">Описание</label>
                        <textarea class="form-control" id="form-description" rows="4" placeholder="Описание валюты, реквизиты или подсказка для обмена"></textarea>
                        <div class="form-text">Показывается на странице swap в пункте 3.</div>
                    </div>
                    <div id="form-faq-fields" style="display:none;">
                        <div class="alert alert-info py-2">
                            В поле page вводите page_key страницы, например swap, academy, portfolio или articles.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="form-faq-question-ua" class="form-label">Вопрос (укр)</label>
                                <input type="text" class="form-control form-faq-question" id="form-faq-question-ua" data-lang="ua">
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-answer-ua" class="form-label">Ответ (укр)</label>
                                <textarea class="form-control form-faq-answer" id="form-faq-answer-ua" data-lang="ua" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-question-ru" class="form-label">Вопрос (рус)</label>
                                <input type="text" class="form-control form-faq-question" id="form-faq-question-ru" data-lang="ru">
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-answer-ru" class="form-label">Ответ (рус)</label>
                                <textarea class="form-control form-faq-answer" id="form-faq-answer-ru" data-lang="ru" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-question-en" class="form-label">Вопрос (англ)</label>
                                <input type="text" class="form-control form-faq-question" id="form-faq-question-en" data-lang="en">
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-answer-en" class="form-label">Ответ (англ)</label>
                                <textarea class="form-control form-faq-answer" id="form-faq-answer-en" data-lang="en" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-question-es" class="form-label">Вопрос (испан)</label>
                                <input type="text" class="form-control form-faq-question" id="form-faq-question-es" data-lang="es">
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-answer-es" class="form-label">Ответ (испан)</label>
                                <textarea class="form-control form-faq-answer" id="form-faq-answer-es" data-lang="es" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-question-fr" class="form-label">Вопрос (франц)</label>
                                <input type="text" class="form-control form-faq-question" id="form-faq-question-fr" data-lang="fr">
                            </div>
                            <div class="col-md-6">
                                <label for="form-faq-answer-fr" class="form-label">Ответ (франц)</label>
                                <textarea class="form-control form-faq-answer" id="form-faq-answer-fr" data-lang="fr" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="form-doc-row" style="display:none;">
                        <label class="form-label">Показывать в документах</label>
                        <div class="d-flex flex-column gap-2">
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-po" value="PO">
                                <span>Получение денег</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-ppo" value="PPO">
                                <span>Приход денег (PPO)</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-ro" value="RO">
                                <span>Выдача денег</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-deposit" value="DEPOSIT">
                                <span>Депозиты</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-zp" value="ZP">
                                <span>Выдача зарплаты (ZP)</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-pro" value="PRO">
                                <span>Личные средства (PRO)</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-asset" value="ASSET">
                                <span>Активы</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0" type="checkbox" id="form-doc-fin" value="FIN">
                                <span>Финансирование</span>
                            </label>
                        </div>
                        <div class="form-text">Для яких документів доступний цей вид платежу.</div>
                    </div>
                    <div class="mb-3" id="form-cost-type-row" style="display:none;">
                        <label for="form-cost-type" class="form-label">Тип затрат</label>
                        <select class="form-select" id="form-cost-type">
                            <option value="1">Переменные</option>
                            <option value="0">Постоянные</option>
                        </select>
                        <div class="form-text">Используется в отчете P&amp;L для разделения расходов.</div>
                    </div>
                    <div class="mb-3" id="form-cash-flow-activity-row" style="display:none;">
                        <label for="form-cash-flow-activity" class="form-label">Вид деятельности</label>
                        <select class="form-select" id="form-cash-flow-activity">
                            <option value="operating">Операционная</option>
                            <option value="investing">Инвестиционная</option>
                            <option value="financing">Финансовая</option>
                        </select>
                        <div class="form-text">Используется в отчете Cash Flow для классификации поступлений и выплат.</div>
                    </div>
                    <div id="form-office-fields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="form-phone" class="form-label">Телефон</label>
                                <input type="text" class="form-control" id="form-phone" placeholder="+380...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="form-office-city-search" class="form-label">Город</label>
                                <input type="hidden" id="form-office-city-id">
                                <input type="text" class="form-control" id="form-office-city-search" list="office-city-options" placeholder="Начните вводить город">
                                <datalist id="office-city-options"></datalist>
                                <div class="form-text">Выберите город из справочника городов.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="form-address" class="form-label">Адреса</label>
                                <input type="text" class="form-control" id="form-address" placeholder="Місто, вулиця, офіс">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="form-google-map" class="form-label">Google Maps</label>
                            <textarea class="form-control" id="form-google-map" rows="4" placeholder="Посилання або iframe-код карти"></textarea>
                            <div class="form-text">Можна вставити посилання на карту або embed iframe.</div>
                        </div>
                        <div class="mb-3">
                            <label for="form-foto-file" class="form-label">Фото офісу</label>
                            <input type="file" class="form-control" id="form-foto-file" accept="image/*">
                            <div class="firm-media-preview mt-2" id="form-foto-preview-wrap" hidden>
                                <img src="" alt="Фото офісу" id="form-foto-preview" style="max-width:220px;max-height:140px;object-fit:cover;border-radius:12px;border:1px solid rgba(255,255,255,.18);">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-outline-danger" id="btn-delete" style="display:none;">🗑 Видалити</button>
                        <button type="button" class="btn btn-secondary" id="btn-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="conf-list-area">
                <table class="table table-hover table-sm conf-crud-table">
                    <thead>
                        <tr>
                            <th class="conf-id-col">#</th>
                            <th class="conf-name-col">Назва</th>
                            <th id="crud-color-column" class="conf-color-col">Колір</th>
                            <th id="crud-currency-column" class="conf-currency-col" style="display:none;">{{ __('settings.crud.currency_column') }}</th>
                            <th id="crud-description-column" class="conf-description-col" style="display:none;">Описание</th>
                            <th id="crud-default-column" class="conf-default-col" style="display:none;">Default</th>
                            <th id="crud-status-column" class="conf-status-col">Статус</th>
                            <th id="crud-phone-column" class="conf-phone-col" style="display:none;">Телефон</th>
                            <th id="crud-city-column" class="conf-city-col" style="display:none;">Город</th>
                            <th id="crud-address-column" class="conf-address-col" style="display:none;">Адреса</th>
                            <th id="crud-doc-column" class="conf-doc-col" style="display:none;">Документ</th>
                            <th id="crud-cost-type-column" class="conf-cost-type-col" style="display:none;">Тип затрат</th>
                            <th id="crud-cash-flow-activity-column" class="conf-cash-flow-activity-col" style="display:none;">Вид деятельности</th>
                            <th class="text-end conf-actions-col">Дії</th>
                        </tr>
                    </thead>
                    <tbody id="crud-tbody"></tbody>
                </table>
                <p class="text-center text-muted" id="empty-msg" style="display:none">Немає записів</p>
            </div>

            <div class="modal-body" id="currency-exchange-area" style="display:none;">
                <form id="currency-exchange-form" class="row g-3">
                    <div class="col-md-6">
                        <label for="currency-exchange-usd-uah-rate" class="form-label">Курс обмена USD/UAH</label>
                        <input type="number" step="0.000001" min="0.000001" class="form-control" id="currency-exchange-usd-uah-rate" required>
                        <div class="form-text">Сколько UAH соответствует 1 USD.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="currency-exchange-income-percent" class="form-label">Процент дохода</label>
                        <input type="number" step="0.0001" min="0" max="100" class="form-control" id="currency-exchange-income-percent">
                        <div class="form-text">Используется как спред в форме обмена AV8.</div>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <span class="small text-muted" id="currency-exchange-status"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPoolDepositInfo" tabindex="-1" aria-labelledby="modalPoolDepositInfoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPoolDepositInfoLabel">Пул</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Balance</div>
                        <div class="fw-semibold" id="pool-info-balance">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">balance_usdc</div>
                        <div class="fw-semibold" id="pool-info-balance-usdc">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Доходность</div>
                        <div class="fw-semibold" id="pool-info-apy">—</div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Описание</div>
                    <div id="pool-info-description">—</div>
                </div>
                <div>
                    <div class="text-muted small">Object</div>
                    <div class="font-monospace small" id="pool-info-object">—</div>
                </div>
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

<!-- Модальное окно для управления базой знаний -->
<div class="modal fade" id="modalKnowledgeBase" tabindex="-1" aria-labelledby="modalKnowledgeBaseLabel" aria-hidden="true" data-session-fid="{{ $fid ?? '' }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center flex-wrap gap-1">
                <h5 class="modal-title" id="modalKnowledgeBaseLabel">🧠 {{ __('settings.cards.knowledge_base.modal_title') }}</h5>
                <div class="d-flex align-items-center gap-1 ms-auto">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-kb-add">+ {{ __('settings.common.add') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-kb-manage-categories" title="{{ __('settings.knowledge_base.manage_categories') }}">⚙️ <span class="d-none d-md-inline">{{ __('settings.knowledge_base.manage_categories') }}</span></button>
                    <button type="button" class="btn btn-sm btn-outline-info" id="btn-kb-manage-tools" title="{{ __('settings.tools.modal_title') }}">🔧 <span class="d-none d-md-inline">{{ __('settings.tools.tab_label') }}</span></button>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="{{ __('settings.common.close') }}"></button>
                </div>
            </div>

            <!-- Форма добавления/редактирования -->
            <div class="modal-body" id="kb-form-area" style="display:none;">
                <form id="kb-form">
                    <input type="hidden" id="kb-id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="kb-title">{{ __('settings.knowledge_base.title_label') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kb-title" maxlength="255" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="kb-category">{{ __('settings.knowledge_base.category_label') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="kb-category" required>
                                <option value="">{{ __('settings.knowledge_base.select_category') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="kb-active" checked>
                                <label class="form-check-label" for="kb-active">{{ __('settings.knowledge_base.active_label') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="kb-content">{{ __('settings.knowledge_base.content_label') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="kb-content" rows="6" minlength="10" maxlength="10000" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.knowledge_base.tools_label') }}</label>
                        <select class="form-select" id="kb-tools" multiple size="4">
                            <option value="">{{ __('settings.knowledge_base.tools_loading') }}</option>
                        </select>
                        <small class="text-muted">{{ __('settings.knowledge_base.tools_hint') }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 {{ __('settings.common.save') }}</button>
                        <button type="button" class="btn btn-secondary" id="btn-kb-cancel">{{ __('settings.common.cancel') }}</button>
                    </div>
                </form>
            </div>

            <!-- Быстрые вкладки-фильтры -->
            <div class="modal-body pt-0 pb-0" id="kb-tab-bar">
                <div class="d-flex gap-1 flex-wrap mb-2 pb-1 border-bottom">
                    <button type="button" class="btn btn-sm btn-kb-tab active" data-category="" data-tab="all">{{ __('settings.knowledge_base.all_tab') }}</button>
                </div>
            </div>

            <!-- Список записей -->
            <div class="modal-body" id="kb-list-area">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="kb-search-input" placeholder="{{ __('settings.knowledge_base.search_placeholder') }}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="kb-filter-category">
                            <option value="">{{ __('settings.knowledge_base.all_categories') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100" id="btn-kb-search">🔍 {{ __('settings.knowledge_base.filter') }}</button>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-outline-secondary w-100" id="btn-kb-refresh">🔄 {{ __('settings.common.refresh') }}</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('settings.knowledge_base.th_title') }}</th>
                                <th>{{ __('settings.knowledge_base.th_category') }}</th>
                                <th>{{ __('settings.knowledge_base.th_content') }}</th>
                                <th>{{ __('settings.knowledge_base.tools_label') }}</th>
                                <th>{{ __('settings.knowledge_base.th_active') }}</th>
                                <th class="text-end" style="width:160px;">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="kb-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="kb-empty-msg" style="display:none">{{ __('settings.knowledge_base.empty') }}</p>
                <div id="kb-pagination" class="d-flex justify-content-center gap-2 mt-2"></div>
            </div>

            <!-- Управление категориями (скрыто по умолчанию) -->
            <div class="modal-body" id="kb-category-area" style="display:none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">⚙️ {{ __('settings.knowledge_base.manage_categories') }}</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btn-kb-category-add">+ {{ __('settings.common.add') }}</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="btn-kb-category-back">← {{ __('settings.common.back') }}</button>
                    </div>
                </div>

                <!-- Форма добавления/редактирования категории -->
                <div id="kb-category-form-area" style="display:none;" class="card card-body mb-3 bg-light">
                    <form id="kb-category-form">
                        <input type="hidden" id="kb-category-id" value="">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label mb-1"><small>{{ __('settings.knowledge_base.category_key_label') }}</small></label>
                                <input type="text" class="form-control form-control-sm" id="kb-category-key" maxlength="80" required placeholder="example_key">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1"><small>{{ __('settings.knowledge_base.category_name_label') }}</small></label>
                                <input type="text" class="form-control form-control-sm" id="kb-category-name" maxlength="255" required placeholder="{{ __('settings.knowledge_base.category_name_placeholder') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1"><small>{{ __('settings.knowledge_base.category_sort_label') }}</small></label>
                                <input type="number" class="form-control form-control-sm" id="kb-category-sort" value="0" min="0" max="65535">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="kb-category-active" checked>
                                    <label class="form-check-label"><small>{{ __('settings.knowledge_base.active_label') }}</small></label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-sm btn-success">💾 {{ __('settings.common.save') }}</button>
                            <button type="button" class="btn btn-sm btn-secondary" id="btn-kb-category-form-cancel">{{ __('settings.common.cancel') }}</button>
                        </div>
                    </form>
                </div>

                <!-- Список категорий -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th class="kb-category-key-col">{{ __('settings.knowledge_base.category_key_th') }}</th>
                                <th class="kb-category-name-col">{{ __('settings.knowledge_base.category_name_th') }}</th>
                                <th class="text-center kb-category-sort-col">{{ __('settings.knowledge_base.category_sort_th') }}</th>
                                <th class="text-center kb-category-active-col">{{ __('settings.knowledge_base.th_active') }}</th>
                                <th class="text-end kb-category-actions-col">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="kb-category-tbody">
                            <tr><td colspan="5" class="text-center text-muted">{{ __('settings.common.loading') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="kb-category-empty" style="display:none">{{ __('settings.knowledge_base.no_categories') }}</p>
            </div>

            <!-- Управление инструментами (скрыто по умолчанию) -->
            <div class="modal-body" id="kb-tools-area" style="display:none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">🔧 {{ __('settings.tools.modal_title') }}</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btn-kb-tool-add">+ {{ __('settings.common.add') }}</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="btn-kb-tool-back">← {{ __('settings.common.back') }}</button>
                    </div>
                </div>

                <!-- Форма добавления/редактирования инструмента -->
                <div id="kb-tool-form-area" style="display:none;" class="card card-body mb-3 bg-light">
                    <form id="kb-tool-form">
                        <input type="hidden" id="kb-tool-id" value="">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label mb-1"><small>{{ __('settings.tools.key_label') }} <span class="text-danger">*</span></small></label>
                                <input type="text" class="form-control form-control-sm" id="kb-tool-key" maxlength="80" required placeholder="function_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1"><small>{{ __('settings.tools.name_label') }} <span class="text-danger">*</span></small></label>
                                <input type="text" class="form-control form-control-sm" id="kb-tool-name" maxlength="255" required placeholder="{{ __('settings.tools.name_label') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1"><small>{{ __('settings.common.sort') ?? 'Sort' }}</small></label>
                                <input type="number" class="form-control form-control-sm" id="kb-tool-sort" value="0" min="0" max="65535">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="kb-tool-active" checked>
                                    <label class="form-check-label"><small>{{ __('settings.tools.active_label') }}</small></label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-12">
                                <label class="form-label mb-1"><small>{{ __('settings.tools.description_label') }}</small></label>
                                <textarea class="form-control form-control-sm" id="kb-tool-description" rows="2" maxlength="5000" placeholder="{{ __('settings.tools.description_label') }}"></textarea>
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-12">
                                <label class="form-label mb-1"><small>{{ __('settings.tools.schema_label') }} <span class="text-danger">*</span></small></label>
                                <textarea class="form-control form-control-sm font-monospace" id="kb-tool-schema" rows="6" required placeholder='{{ __('settings.tools.schema_placeholder') }}'></textarea>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-sm btn-success">💾 {{ __('settings.common.save') }}</button>
                            <button type="button" class="btn btn-sm btn-secondary" id="btn-kb-tool-form-cancel">{{ __('settings.common.cancel') }}</button>
                        </div>
                    </form>
                </div>

                <!-- Список инструментов -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th class="kb-tool-key-col">{{ __('settings.tools.th_key') }}</th>
                                <th class="kb-tool-name-col">{{ __('settings.tools.th_name') }}</th>
                                <th class="kb-tool-desc-col">{{ __('settings.tools.th_description') }}</th>
                                <th class="text-center kb-tool-sort-col" style="width:50px;">{{ __('settings.common.sort') ?? 'Sort' }}</th>
                                <th class="text-center kb-tool-active-col" style="width:60px;">{{ __('settings.tools.th_active') }}</th>
                                <th class="text-end kb-tool-actions-col" style="width:160px;">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="kb-tool-tbody">
                            <tr><td colspan="6" class="text-center text-muted">{{ __('settings.tools.loading') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="kb-tool-empty" style="display:none">{{ __('settings.tools.empty') }}</p>
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="profile-balance-tab" data-bs-toggle="tab" data-bs-target="#profileBalanceTab" type="button" role="tab">💰 Баланс</button>
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

                    <div class="tab-pane fade" id="profileBalanceTab">
                        @php
                            $profileBalanceRows = collect(old('balance_amounts', []))->map(function ($amount, $key) {
                                return [
                                    'key' => (string) $key,
                                    'amount' => $amount,
                                    'currency' => old('balance_currencies.' . $key, 'UAH'),
                                    'is_default' => (string) old('default_balance_key', '0') === (string) $key,
                                ];
                            })->values();

                            if ($profileBalanceRows->isEmpty()) {
                                $profileBalanceRows = collect($profileBalances ?? [])->values()->map(function ($balance, $index) {
                                    return [
                                        'key' => (string) $index,
                                        'amount' => $balance['amount'] ?? '',
                                        'currency' => $balance['currency'] ?? 'UAH',
                                        'is_default' => (bool) ($balance['is_default'] ?? $index === 0),
                                    ];
                                });
                            }

                            $profileBalanceCurrencyOptions = collect($currencies ?? [])->map(function ($currency) {
                                return strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($currency->currency ?? $currency->name ?? 'UAH')));
                            })->filter()->unique()->values();

                            if ($profileBalanceCurrencyOptions->isEmpty()) {
                                $profileBalanceCurrencyOptions = collect(['UAH']);
                            }
                        @endphp
                        <form action="{{ route('settings.profileBalancesUpdate') }}" method="post" id="profile-balance-form">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 35%;">Сума</th>
                                            <th style="width: 25%;">Валюта</th>
                                            <th style="width: 20%;">За замовчуванням</th>
                                            <th class="text-end" style="width: 20%;">Дія</th>
                                        </tr>
                                    </thead>
                                    <tbody id="profile-balance-rows">
                                        @forelse($profileBalanceRows as $row)
                                        <tr class="profile-balance-row">
                                            <td>
                                                <input type="text" name="balance_amounts[{{ $row['key'] }}]" class="form-control form-control-sm profile-balance-amount" value="{{ $row['amount'] }}" inputmode="numeric" autocomplete="off" placeholder="0.00">
                                            </td>
                                            <td>
                                                <select name="balance_currencies[{{ $row['key'] }}]" class="form-select form-select-sm">
                                                    @foreach($profileBalanceCurrencyOptions as $currency)
                                                        <option value="{{ $currency }}" @selected($currency === ($row['currency'] ?? 'UAH'))>{{ $currency }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input profile-balance-default" type="radio" name="default_balance_key" value="{{ $row['key'] }}" @checked((bool) $row['is_default'])>
                                                    <label class="form-check-label">Основний</label>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger profile-balance-remove">Видалити</button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr id="profile-balance-empty">
                                            <td colspan="4" class="text-center text-muted">Баланс не вказано</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="profile-balance-add">Додати баланс</button>
                                <button type="submit" class="btn btn-sm btn-primary">Зберегти баланси</button>
                            </div>
                            <div class="form-text mt-2">Валюта не може повторюватися. Баланси зберігаються окремими рядками в кеші балансів користувача.</div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .kb-category-key-col {
        width: 35%;
    }
    .kb-category-name-col {
        width: 35%;
    }
    .kb-category-sort-col {
        width: 10%;
    }
    .kb-category-active-col {
        width: 10%;
    }
    .kb-category-actions-col {
        width: 10%;
        white-space: nowrap;
    }

    .setting-card {
        cursor: pointer;
    }

    .btn-kb-tab {
        border: 1px solid transparent;
        border-radius: 20px;
        padding: 2px 14px;
        font-size: 0.85rem;
        transition: all .15s ease;
        color: #6c757d;
        background: transparent;
    }
    .btn-kb-tab:hover {
        background: rgba(165, 180, 252, 0.15);
        color: #4a5568;
    }
    .btn-kb-tab.active {
        background: #a5b4fc;
        color: #020617;
        border-color: #a5b4fc;
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

    #modalCrud #conf-list-area {
        padding: 0.5rem;
    }

    .conf-crud-table {
        table-layout: fixed;
        width: 100%;
        font-size: 0.82rem;
        margin-bottom: 0;
    }

    .conf-crud-table > :not(caption) > * > * {
        padding: 0.2rem 0.25rem;
        vertical-align: middle;
    }

    .conf-crud-table th,
    .conf-crud-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conf-crud-table th {
        white-space: nowrap;
    }

    .conf-crud-table .badge {
        font-size: 0.68rem;
        padding: 0.22em 0.4em;
    }

    .conf-crud-table .action-btn {
        min-width: 28px;
        padding: 1px 5px;
        line-height: 1.3;
    }

    .conf-id-col {
        width: 7%;
        white-space: nowrap;
    }

    .conf-name-col {
        width: 23%;
    }

    .conf-color-col {
        width: 14%;
    }

    .conf-currency-col {
        width: 13%;
    }

    .conf-default-col {
        width: 13%;
    }

    .conf-status-col {
        width: 15%;
    }

    .conf-phone-col {
        width: 14%;
    }

    .conf-address-col {
        width: 24%;
    }

    .conf-doc-col {
        width: 20%;
    }

    .conf-actions-col {
        width: 12%;
        white-space: nowrap;
    }

    .report-rules-table {
        table-layout: fixed;
        font-size: 0.82rem;
    }

    .report-rules-table th,
    .report-rules-table td {
        vertical-align: middle;
    }

    .report-rules-table td {
        white-space: normal;
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
        font-size: 0.78rem;
        color: #6b7280;
        line-height: 1.25;
    }

    .catalog-table {
        font-size: 0.84rem;
    }

    .catalog-table > :not(caption) > * > * {
        padding: 0.28rem 0.38rem;
    }

    .catalog-table .badge {
        font-size: 0.68rem;
        padding: 0.22em 0.42em;
        font-weight: 500;
    }

    .catalog-table .action-btn {
        min-width: 30px;
        padding: 1px 6px;
        line-height: 1.35;
    }

    .catalog-id-cell {
        width: 5%;
        white-space: nowrap;
    }

    .catalog-flags-cell {
        width: 10%;
    }

    .catalog-description-cell {
        width: 65%;
    }

    .catalog-children-cell {
        width: 10%;
        text-align: center;
    }

    .catalog-actions-cell {
        width: 10%;
        white-space: nowrap;
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

    .project-list-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
    }

    .project-list-toolbar .form-control {
        max-width: 420px;
    }

    .project-list-toolbar .form-check {
        flex: 0 0 auto;
    }

    .project-list-scroll {
        max-height: 55vh;
        overflow-y: auto;
    }

    .project-compact-table thead {
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .project-compact-table {
        margin-bottom: 0;
        font-size: 0.86rem;
    }

    .project-compact-table th,
    .project-compact-table td {
        padding: 0.22rem 0.35rem;
        vertical-align: middle;
    }

    .project-compact-table tbody tr {
        cursor: pointer;
    }

    .project-compact-table tbody .project-scope-group {
        cursor: default;
    }

    .project-scope-group td {
        padding: 0.55rem 0.45rem;
        background: rgba(251, 191, 36, 0.1);
        color: #fbbf24;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .project-compact-table .company-meta,
    .project-compact-table .small {
        font-size: 0.76rem;
        line-height: 1.15;
    }

    .project-compact-table .btn-sm {
        padding: 0.08rem 0.32rem;
        font-size: 0.74rem;
        line-height: 1.2;
    }

    .project-holding-menu {
        position: absolute;
        z-index: 1060;
        top: calc(100% - 0.75rem);
        left: 0.75rem;
        right: 0.75rem;
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: #fff;
    }

    .project-holding-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.45rem 0.65rem;
        color: #111827;
        cursor: pointer;
    }

    .project-holding-item:hover {
        background: #f3f4f6;
    }

    .project-holding-delete {
        flex: 0 0 auto;
        border: 0;
        background: transparent;
        color: #dc3545;
        font-size: 1rem;
        line-height: 1;
        padding: 0.15rem 0.25rem;
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
    const catalogNewsOptions = @json($catalogNewsOptions ?? []);

    initProjectsCrud(csrf);
    initConfCrud(csrf);
    initCatalogCrud(csrf, catalogNewsOptions);
    initCatalogFiltersCrud(csrf);
    initFirmsCrud(csrf);
    initBannerCrud(csrf);
    initAccountsCrud(csrf);
    initWalletLink(csrf);
    initProfileBalances();

    function initProfileBalances() {
        const tbody = document.getElementById('profile-balance-rows');
        const addBtn = document.getElementById('profile-balance-add');
        const currencyOptions = @json($profileBalanceCurrencyOptions ?? collect(['UAH']));
        let newRowCounter = 0;

        if (!tbody || !addBtn) {
            return;
        }

        tbody.querySelectorAll('.profile-balance-amount').forEach(bindProfileBalanceTerminalAmount);

        addBtn.addEventListener('click', () => {
            const key = `new_${Date.now()}_${newRowCounter++}`;
            const hasRows = tbody.querySelectorAll('.profile-balance-row').length > 0;
            const emptyRow = document.getElementById('profile-balance-empty');

            if (emptyRow) {
                emptyRow.remove();
            }

            tbody.insertAdjacentHTML('beforeend', renderProfileBalanceRow(key, '', currencyOptions[0] || 'UAH', !hasRows));
            bindProfileBalanceTerminalAmount(tbody.querySelector('.profile-balance-row:last-child .profile-balance-amount'));
        });

        tbody.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('.profile-balance-remove');
            if (!removeBtn) {
                return;
            }

            const row = removeBtn.closest('.profile-balance-row');
            const wasDefault = row?.querySelector('.profile-balance-default')?.checked;
            row?.remove();

            const rows = Array.from(tbody.querySelectorAll('.profile-balance-row'));
            if (wasDefault && rows[0]) {
                const firstDefault = rows[0].querySelector('.profile-balance-default');
                if (firstDefault) {
                    firstDefault.checked = true;
                }
            }

            if (rows.length === 0) {
                tbody.insertAdjacentHTML('beforeend', '<tr id="profile-balance-empty"><td colspan="4" class="text-center text-muted">Баланс не вказано</td></tr>');
            }
        });

        function renderProfileBalanceRow(key, amount, selectedCurrency, isDefault) {
            const options = currencyOptions.map((currency) => {
                const value = escapeProfileBalanceHtml(currency);
                return `<option value="${value}" ${currency === selectedCurrency ? 'selected' : ''}>${value}</option>`;
            }).join('');

            return `
                <tr class="profile-balance-row">
                    <td>
                        <input type="text" name="balance_amounts[${escapeProfileBalanceHtml(key)}]" class="form-control form-control-sm profile-balance-amount" value="${escapeProfileBalanceHtml(amount)}" inputmode="numeric" autocomplete="off" placeholder="0.00">
                    </td>
                    <td>
                        <select name="balance_currencies[${escapeProfileBalanceHtml(key)}]" class="form-select form-select-sm">${options}</select>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input profile-balance-default" type="radio" name="default_balance_key" value="${escapeProfileBalanceHtml(key)}" ${isDefault ? 'checked' : ''}>
                            <label class="form-check-label">Основний</label>
                        </div>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger profile-balance-remove">Видалити</button>
                    </td>
                </tr>
            `;
        }

        function escapeProfileBalanceHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function bindProfileBalanceTerminalAmount(input) {
            if (!input || input.dataset.terminalAmountBound === '1') {
                return;
            }

            input.dataset.terminalAmountBound = '1';

            const formatTerminalAmount = (cents) => (Math.max(0, cents) / 100).toFixed(2);
            const parseAmountToCents = (value) => {
                const normalized = String(value || '').replace(/\s/g, '').replace(',', '.');
                const amount = parseFloat(normalized);

                return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
            };
            const syncValue = (cents) => {
                input.dataset.terminalAmountCents = String(Math.max(0, cents));
                input.value = formatTerminalAmount(cents);
            };
            const getDigits = () => {
                const cents = parseInt(input.dataset.terminalAmountCents || '0', 10) || 0;

                return String(cents);
            };
            const appendDigit = (digit) => {
                const currentDigits = input.dataset.terminalAmountFresh === '1' ? '' : getDigits();
                const nextDigits = (currentDigits + digit).replace(/^0+(?=\d)/, '');

                syncValue(parseInt(nextDigits || '0', 10));
                input.dataset.terminalAmountFresh = '0';
            };
            const removeLastDigit = () => {
                const nextDigits = getDigits().slice(0, -1);

                syncValue(parseInt(nextDigits || '0', 10));
                input.dataset.terminalAmountFresh = '0';
            };

            syncValue(parseAmountToCents(input.value));

            input.addEventListener('focus', () => {
                input.dataset.terminalAmountFresh = '1';
                syncValue(parseAmountToCents(input.value));
                input.select();
            });
            input.addEventListener('beforeinput', (event) => {
                if (event.inputType === 'insertText' && /^\d$/.test(event.data || '')) {
                    event.preventDefault();
                    appendDigit(event.data);
                    return;
                }

                if (event.inputType === 'deleteContentBackward') {
                    event.preventDefault();
                    removeLastDigit();
                    return;
                }

                if (event.inputType === 'deleteContentForward') {
                    event.preventDefault();
                    syncValue(0);
                    input.dataset.terminalAmountFresh = '0';
                }
            });
            input.addEventListener('keydown', (event) => {
                if (event.ctrlKey || event.metaKey || event.altKey) {
                    return;
                }

                if (/^\d$/.test(event.key)) {
                    event.preventDefault();
                    appendDigit(event.key);
                    return;
                }

                if (event.key === 'Backspace') {
                    event.preventDefault();
                    removeLastDigit();
                    return;
                }

                if (event.key === 'Delete') {
                    event.preventDefault();
                    syncValue(0);
                    input.dataset.terminalAmountFresh = '0';
                }
            });
            input.addEventListener('paste', (event) => {
                event.preventDefault();
                const text = event.clipboardData?.getData('text') || '';
                const digits = text.replace(/\D/g, '');

                syncValue(parseInt(digits || '0', 10));
                input.dataset.terminalAmountFresh = '0';
            });
            input.addEventListener('input', () => {
                syncValue(parseAmountToCents(input.value));
                input.dataset.terminalAmountFresh = '0';
            });
        }
    }

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
        
        let zerionSearchTimeout = null;
        let currentZerionImplementations = [];

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

        // Zerion autocomplete
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            if (query.length < 2) {
                resultsList.style.display = 'none';
                return;
            }
            clearTimeout(zerionSearchTimeout);
            zerionSearchTimeout = setTimeout(() => {
                const params = new URLSearchParams({
                    query,
                    chain_id: chainSelect.value || '0x1',
                });

                fetch(`/settings/api/web3-token-search?${params.toString()}`)
                    .then(r => r.json())
                    .then(data => {
                        resultsList.innerHTML = '';
                        if (!Array.isArray(data) || !data.length) {
                            resultsList.style.display = 'none';
                            return;
                        }
                        data.forEach(token => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action d-flex align-items-center cursor-pointer';
                            li.style.cursor = 'pointer';
                            li.innerHTML = `<img src="${escapeHtml(token.icon_url || '')}" class="me-2 rounded-circle" width="20" height="20"> <strong>${escapeHtml(token.symbol || '')}</strong> <span class="ms-2 text-muted text-truncate" style="max-width: 150px;">${escapeHtml(token.name || '')}</span>`;
                            li.addEventListener('click', () => selectZerionToken(token));
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
            if (chainSelect.value === 'solana' && currentZerionImplementations.length === 0) {
                document.getElementById('web3-decimals').value = '9';
            }

            if (currentZerionImplementations.length > 0) {
                applyZerionImplementation(chainSelect.value);
            }
        });

        function applyZerionImplementation(chainId) {
            const implementation = currentZerionImplementations.find((item) => item.chain_id === chainId);
            if (!implementation) {
                document.getElementById('web3-address').value = '';
                document.getElementById('web3-decimals').value = String(chainId === 'solana' ? 9 : 18);
                return;
            }

            document.getElementById('web3-address').value = implementation.address || '';
            document.getElementById('web3-decimals').value = String(implementation.decimals || (chainId === 'solana' ? 9 : 18));
        }

        function selectZerionToken(token) {
            searchInput.value = token.name || token.symbol || '';
            resultsList.style.display = 'none';

            document.getElementById('web3-cgid').value = token.id || '';
            document.getElementById('web3-symbol').value = (token.symbol || '').toUpperCase();
            document.getElementById('web3-name').value = token.name || '';
            currentZerionImplementations = Array.isArray(token.implementations) ? token.implementations : [];
            applyZerionImplementation(chainSelect.value);
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
                constanta: document.getElementById('web3-cgid').value.trim(),
                commission: document.getElementById('web3-commission').value.trim() || '0'
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
            .then(async (r) => {
                const raw = await r.text().catch(() => '');
                let data = {};
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (_) {
                    data = { success: false, message: raw || `HTTP ${r.status}` };
                }

                if (!r.ok) {
                    throw new Error(data.message || `HTTP ${r.status}`);
                }

                return data;
            })
            .then(data => {
                if (!data.success) {
                    alert(data.message || _ts('js.error_generic'));
                    return;
                }
                hideWeb3Form();
                resetWeb3Form();
                loadWeb3Tokens();
            })
            .catch((error) => alert(error?.message || _ts('js.network_error')));
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
                    tbody.innerHTML = '<tr><td colspan="6" class="text-danger">' + _ts('js.load_error_tokens') + '</td></tr>';
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
                    <td>${Number(item.commission || 0).toFixed(4)}%</td>
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
                    document.getElementById('web3-commission').value = Number(item.commission || 0).toFixed(4);
                    document.getElementById('web3-chain').value = String(item.vision || '').toLowerCase() === 'solana'
                        ? 'solana'
                        : (normalizeChainId(item.vision) || '0x1');
                    searchInput.value = '';
                    currentZerionImplementations = [];
                    showWeb3Form();
                })
                .catch(() => alert(_ts('js.load_error')));
        }

        function deleteWeb3Token(id, btn) {
            if (!confirm(_ts('js.delete_token_confirm'))) return;
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
            document.getElementById('web3-commission').value = '0';
            currentZerionImplementations = [];
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
        const searchInput = document.getElementById('projects-search');
        const allProjectsInput = document.getElementById('projects-all');
        const scrollArea = document.getElementById('projects-scroll-area');
        const loadingIndicator = document.getElementById('projects-loading');
        const addBtn = document.getElementById('btn-project-add');
        const cancelBtn = document.getElementById('btn-project-cancel');
        const deleteBtn = document.getElementById('btn-project-delete');
        const badge = document.getElementById('badge-projects');
        const fotoFileInput = document.getElementById('project-foto-file');
        const fotoHeaderFileInput = document.getElementById('project-foto-header-file');
        const fotoFooterFileInput = document.getElementById('project-foto-footer-file');
        const holdingInput = document.getElementById('project-holding');
        const holdingToggle = document.getElementById('project-holding-toggle');
        const holdingMenu = document.getElementById('project-holding-menu');
        let holdings = [];
        let projectsPage = 1;
        let projectsLastPage = 1;
        let projectsLoading = false;
        let projectsSearchTimer = null;
        let projectsRequestController = null;
        let lastRenderedProjectGroup = '';

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

        const extractErrorMessage = (payload, fallback = _ts('js.request_error')) => {
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
            if (searchInput) searchInput.value = '';
            if (allProjectsInput) allProjectsInput.checked = false;
            if (scrollArea) scrollArea.scrollTop = 0;
            loadHoldings();
            loadProjects(true);
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

        searchInput?.addEventListener('input', () => {
            window.clearTimeout(projectsSearchTimer);
            projectsSearchTimer = window.setTimeout(() => loadProjects(true), 300);
        });

        allProjectsInput?.addEventListener('change', () => loadProjects(true));

        scrollArea?.addEventListener('scroll', () => {
            const distanceToBottom = scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight;
            if (distanceToBottom <= 120 && !projectsLoading && projectsPage < projectsLastPage) {
                loadProjects(false);
            }
        });
        deleteBtn?.addEventListener('click', () => {
            const id = document.getElementById('project-id').value;
            if (!id) return;
            deleteProject(id);
        });

        tbody.addEventListener('click', (event) => {
            const btn = event.target.closest('.action-btn');
            if (btn) {
                event.stopPropagation();

                const id = btn.dataset.id;
                if (btn.dataset.action === 'delete') {
                    deleteProject(id, btn);
                }
                return;
            }

            const row = event.target.closest('tr[data-project-id]');
            if (row) {
                editProject(row.dataset.projectId);
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const id = document.getElementById('project-id').value;
            const payload = new FormData();
            payload.append('num', String(Number(document.getElementById('project-num').value || 0)));
            payload.append('name', document.getElementById('project-name').value.trim());
            payload.append('project_type', document.getElementById('project-type').value);
            payload.append('holding_name', holdingInput?.value.trim() || '');
            payload.append('email', document.getElementById('project-email').value.trim());
            payload.append('phone', document.getElementById('project-phone').value.trim());
            payload.append('url', document.getElementById('project-url').value.trim());
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
            payload.append('constanta', document.getElementById('project-constanta').checked ? '1' : '0');
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
                alert(_ts('js.project_name_required'));
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
                    alert(extractErrorMessage(data, _ts('js.save_error')));
                    return;
                }

                hideForm();
                resetProjectForm();
                loadHoldings();
                loadProjects();

                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            })
            .catch((error) => alert(error?.message || _ts('js.network_error')));
        });

        function loadProjects(reset = true) {
            if (projectsLoading && !reset) {
                return;
            }

            if (reset) {
                projectsRequestController?.abort();
                projectsPage = 1;
                projectsLastPage = 1;
                lastRenderedProjectGroup = '';
                tbody.innerHTML = '';
                emptyMsg.style.display = 'none';
                if (scrollArea) scrollArea.scrollTop = 0;
            } else {
                projectsPage += 1;
            }

            const requestedPage = projectsPage;
            const params = new URLSearchParams({ page: String(requestedPage) });
            const search = searchInput?.value.trim() || '';
            if (search) params.set('search', search);
            if (allProjectsInput?.checked) params.set('all_projects', '1');

            const controller = new AbortController();
            projectsRequestController = controller;
            projectsLoading = true;
            if (loadingIndicator) loadingIndicator.hidden = false;

            fetch(`/settings/projects?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then(async (response) => {
                    const data = await parseResponseData(response);
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        throw new Error(extractErrorMessage(data, _ts('js.load_error')));
                    }

                    projectsPage = Number(data.current_page) || requestedPage;
                    projectsLastPage = Number(data.last_page) || projectsPage;
                    renderProjects(Array.isArray(data.data) ? data.data : [], requestedPage > 1, Number(data.total) || 0);
                })
                .catch((error) => {
                    if (error?.name === 'AbortError') {
                        return;
                    }
                    if (requestedPage > 1) {
                        projectsPage = requestedPage - 1;
                    }
                    const errorRow = `<tr class="projects-load-error"><td colspan="7" class="text-danger">${escapeHtml(error?.message || _ts('js.load_error'))}</td></tr>`;
                    if (requestedPage > 1) {
                        tbody.querySelector('.projects-load-error')?.remove();
                        tbody.insertAdjacentHTML('beforeend', errorRow);
                    } else {
                        tbody.innerHTML = errorRow;
                    }
                    emptyMsg.style.display = 'none';
                })
                .finally(() => {
                    if (projectsRequestController === controller) {
                        projectsLoading = false;
                        projectsRequestController = null;
                        if (loadingIndicator) loadingIndicator.hidden = true;
                    }
                });
        }

        function renderProjects(items, append = false, total = items.length) {
            tbody.querySelector('.projects-load-error')?.remove();
            if (!append) {
                tbody.innerHTML = '';
                lastRenderedProjectGroup = '';
            }

            if (!append && !items.length) {
                emptyMsg.style.display = 'block';
                if (badge) {
                    badge.textContent = '0';
                }
                return;
            }

            emptyMsg.style.display = 'none';
            if (badge) {
                badge.textContent = String(total);
            }

            items.forEach((item) => {
                const projectEmail = item.email
                    ? `<a href="mailto:${escapeHtml(item.email)}">${escapeHtml(item.email)}</a>`
                    : '—';
                const projectPhone = item.phone
                    ? `<a href="tel:${escapeHtml(item.phone)}">${escapeHtml(item.phone)}</a>`
                    : '—';
                const projectUrl = item.url
                    ? `<a href="${escapeHtml(item.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(item.url)}</a>`
                    : '—';
                const projectRole = item.user_role === 'creator'
                    ? 'Создатель'
                    : (item.user_role === 'employee' ? 'Сотрудник' : '');
                const projectType = item.project_type_label || projectTypeLabel(item.project_type) || '';
                const holdingName = item.holding_name || '—';

                if (item.scope_group && item.scope_group !== lastRenderedProjectGroup) {
                    const groupRow = document.createElement('tr');
                    groupRow.className = 'project-scope-group';
                    groupRow.innerHTML = `<td colspan="7">${escapeHtml(item.scope_group_label || '')}</td>`;
                    tbody.appendChild(groupRow);
                    lastRenderedProjectGroup = item.scope_group;
                }

                const tr = document.createElement('tr');
                tr.dataset.projectId = item.id;
                tr.innerHTML = `
                    <td>${item.id ?? ''}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(item.name || '')}</div>
                        ${projectType ? `<div class="company-meta text-muted">${escapeHtml(projectType)}</div>` : ''}
                        ${Number(item.constanta) === 1 ? '<span class="badge bg-warning text-dark mt-1">Маркетплейс</span>' : ''}
                    </td>
                    <td>${projectRole ? `<span class="badge bg-secondary">${escapeHtml(projectRole)}</span>` : ''}</td>
                    <td>${escapeHtml(holdingName)}</td>
                    <td>${projectEmail}</td>
                    <td>
                        <div>${projectPhone}</div>
                        <div class="small text-muted">${projectUrl}</div>
                    </td>
                    <td class="text-end">
                        ${item.can_delete ? `<button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">${escapeHtml(_ts('crud.delete'))}</button>` : ''}
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
                        alert(extractErrorMessage(data, _ts('js.project_not_found')));
                        return;
                    }

                    fillProjectForm(data);
                    showForm();
                })
                .catch((error) => alert(error?.message || _ts('js.load_error')));
        }

        function deleteProject(id, btn) {
            if (!confirm(_ts('js.delete_project_confirm'))) return;

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
                    alert(extractErrorMessage(data, _ts('js.delete_error_project')));
                    return;
                }

                hideForm();
                resetProjectForm();
                loadProjects();
            })
            .catch((error) => alert(error?.message || _ts('js.network_error')));
        }

        function fillProjectForm(item) {
            document.getElementById('project-id').value = item.id ?? '';
            document.getElementById('project-id-display').value = item.id ?? '';
            document.getElementById('project-num').value = item.num ?? 0;
            document.getElementById('project-name').value = item.name || '';
            document.getElementById('project-type').value = item.project_type || '';
            if (holdingInput) holdingInput.value = item.holding_name || '';
            document.getElementById('project-email').value = item.email || '';
            document.getElementById('project-phone').value = item.phone || '';
            document.getElementById('project-url').value = item.url || '';
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
            document.getElementById('project-constanta').checked = Number(item.constanta) === 1;
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
            document.getElementById('project-type').value = '';
            if (holdingInput) holdingInput.value = '';
            document.getElementById('project-email').value = '';
            document.getElementById('project-phone').value = '';
            document.getElementById('project-url').value = '';
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
            document.getElementById('project-constanta').checked = false;
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
            if (deleteBtn) {
                deleteBtn.style.display = document.getElementById('project-id').value ? '' : 'none';
            }
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
            }
            hideHoldingMenu();
        }

        async function loadHoldings() {
            if (!holdingInput || !holdingMenu) {
                return;
            }

            try {
                const response = await fetch('/settings/holdings', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await parseResponseData(response);

                if (!response.ok) {
                    throw new Error(extractErrorMessage(data, _ts('js.load_error')));
                }

                holdings = Array.isArray(data) ? data : [];
                renderHoldingMenu();
            } catch (error) {
                holdings = [];
                renderHoldingMenu(error?.message || _ts('js.load_error'));
            }
        }

        function renderHoldingMenu(errorMessage = '') {
            if (!holdingInput || !holdingMenu) {
                return;
            }

            const query = holdingInput.value.trim().toLowerCase();
            const visibleItems = holdings.filter((item) => !query || String(item.name || '').toLowerCase().includes(query));

            if (errorMessage) {
                holdingMenu.innerHTML = `<div class="project-holding-item text-danger">${escapeHtml(errorMessage)}</div>`;
                return;
            }

            if (!visibleItems.length) {
                holdingMenu.innerHTML = '<div class="project-holding-item text-muted">Холдинги не найдены</div>';
                return;
            }

            holdingMenu.innerHTML = visibleItems.map((item) => `
                <div class="project-holding-item" data-holding-id="${escapeHtml(item.id)}" data-holding-name="${escapeHtml(item.name || '')}">
                    <span class="text-truncate">${escapeHtml(item.name || '')}</span>
                    <button type="button" class="project-holding-delete" title="Удалить" aria-label="Удалить холдинг" data-holding-delete="${escapeHtml(item.id)}">×</button>
                </div>
            `).join('');
        }

        function showHoldingMenu() {
            if (!holdingMenu) {
                return;
            }

            renderHoldingMenu();
            holdingMenu.hidden = false;
        }

        function hideHoldingMenu() {
            if (holdingMenu) {
                holdingMenu.hidden = true;
            }
        }

        async function deleteHolding(id) {
            if (!confirm('Удалить холдинг?')) {
                return;
            }

            try {
                const response = await fetch(`/settings/holdings/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await parseResponseData(response);

                if (!response.ok || data.success === false) {
                    alert(extractErrorMessage(data, 'Не удалось удалить холдинг.'));
                    return;
                }

                const removed = holdings.find((item) => String(item.id) === String(id));
                holdings = holdings.filter((item) => String(item.id) !== String(id));
                if (removed && holdingInput && holdingInput.value.trim() === String(removed.name || '').trim()) {
                    holdingInput.value = '';
                }
                renderHoldingMenu();
            } catch (error) {
                alert(error?.message || _ts('js.network_error'));
            }
        }

        holdingInput?.addEventListener('focus', showHoldingMenu);
        holdingInput?.addEventListener('input', showHoldingMenu);
        holdingToggle?.addEventListener('click', () => {
            if (!holdingMenu || holdingMenu.hidden) {
                showHoldingMenu();
            } else {
                hideHoldingMenu();
            }
        });
        holdingMenu?.addEventListener('click', (event) => {
            const deleteButton = event.target.closest('[data-holding-delete]');
            if (deleteButton) {
                event.preventDefault();
                event.stopPropagation();
                deleteHolding(deleteButton.dataset.holdingDelete);
                return;
            }

            const item = event.target.closest('.project-holding-item[data-holding-name]');
            if (!item || !holdingInput) {
                return;
            }

            holdingInput.value = item.dataset.holdingName || '';
            hideHoldingMenu();
        });
        document.addEventListener('click', (event) => {
            if (!holdingInput || !holdingMenu || !holdingToggle) {
                return;
            }

            if (!holdingInput.contains(event.target) && !holdingMenu.contains(event.target) && !holdingToggle.contains(event.target)) {
                hideHoldingMenu();
            }
        });

        function projectTypeLabel(value) {
            return {
                trade: 'Торговля',
                bank: 'Банк',
                insurance: 'Страхование',
                education: 'Образование',
            }[value] || '';
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
        const poolInfoModal = document.getElementById('modalPoolDepositInfo');
        const poolInfoBootstrapModal = poolInfoModal ? new bootstrap.Modal(poolInfoModal) : null;
        const tbody = document.getElementById('crud-tbody');
        const formArea = document.getElementById('conf-form-area');
        const listArea = document.getElementById('conf-list-area');
        const form = document.getElementById('crud-form');
        const emptyMsg = document.getElementById('empty-msg');
        const addBtn = document.getElementById('btn-add');
        const cancelBtn = document.getElementById('btn-cancel');
        const deleteBtn = document.getElementById('btn-delete');
        const currencyTabsArea = document.getElementById('currency-tabs-area');
        const currencyTabDirectory = document.getElementById('currency-tab-directory');
        const currencyTabExchange = document.getElementById('currency-tab-exchange');
        const currencyExchangeArea = document.getElementById('currency-exchange-area');
        const currencyExchangeForm = document.getElementById('currency-exchange-form');
        const currencyExchangeUsdUahRate = document.getElementById('currency-exchange-usd-uah-rate');
        const currencyExchangeIncomePercent = document.getElementById('currency-exchange-income-percent');
        const currencyExchangeStatus = document.getElementById('currency-exchange-status');
        const colorPicker = document.getElementById('form-color-picker');
        const colorRow = document.getElementById('form-color-row');
        const colorLabel = document.getElementById('form-color-label');
        const colorInput = document.getElementById('form-color');
        const faqPageSelect = document.getElementById('form-faq-page');
        const statusRow = document.getElementById('form-status-row');
        const statusLabel = document.getElementById('form-status-label');
        const statusSelect = document.getElementById('form-status');
        const statusHelp = document.getElementById('form-status-help');
        const currencyRow = document.getElementById('form-currency-row');
        const currencySelect = document.getElementById('form-currency');
        const descriptionRow = document.getElementById('form-description-row');
        const descriptionInput = document.getElementById('form-description');
        const faqFields = document.getElementById('form-faq-fields');
        const faqQuestionInputs = Array.from(document.querySelectorAll('.form-faq-question'));
        const faqAnswerInputs = Array.from(document.querySelectorAll('.form-faq-answer'));
        const visibilityRow = document.getElementById('form-visibility-row');
        const visibilityCheckbox = document.getElementById('form-visibility-checkbox');
        const visibilityLabel = document.getElementById('form-visibility-label');
        const defaultRow = document.getElementById('form-default-row');
        const defaultCheckbox = document.getElementById('form-default-checkbox');
        const defaultColumn = document.getElementById('crud-default-column');
        const statusColumn = document.getElementById('crud-status-column');
        const docRow = document.getElementById('form-doc-row');
        const docColumn = document.getElementById('crud-doc-column');
        const costTypeRow = document.getElementById('form-cost-type-row');
        const costTypeSelect = document.getElementById('form-cost-type');
        const costTypeColumn = document.getElementById('crud-cost-type-column');
        const cashFlowActivityRow = document.getElementById('form-cash-flow-activity-row');
        const cashFlowActivitySelect = document.getElementById('form-cash-flow-activity');
        const cashFlowActivityColumn = document.getElementById('crud-cash-flow-activity-column');
        const officeFields = document.getElementById('form-office-fields');
        const phoneInput = document.getElementById('form-phone');
        const officeCityIdInput = document.getElementById('form-office-city-id');
        const officeCitySearchInput = document.getElementById('form-office-city-search');
        const officeCityOptions = document.getElementById('office-city-options');
        const addressInput = document.getElementById('form-address');
        const googleMapInput = document.getElementById('form-google-map');
        const fotoExistingInput = document.getElementById('form-foto-existing');
        const fotoFileInput = document.getElementById('form-foto-file');
        const colorColumn = document.getElementById('crud-color-column');
        const currencyColumn = document.getElementById('crud-currency-column');
        const descriptionColumn = document.getElementById('crud-description-column');
        const nameColumn = document.querySelector('#modalCrud thead .conf-name-col');
        const phoneColumn = document.getElementById('crud-phone-column');
        const cityColumn = document.getElementById('crud-city-column');
        const addressColumn = document.getElementById('crud-address-column');
        const docCheckboxes = [
            document.getElementById('form-doc-po'),
            document.getElementById('form-doc-ppo'),
            document.getElementById('form-doc-ro'),
            document.getElementById('form-doc-deposit'),
            document.getElementById('form-doc-zp'),
            document.getElementById('form-doc-pro'),
            document.getElementById('form-doc-asset'),
            document.getElementById('form-doc-fin'),
        ];
        const settingsDepositsUsePools = @json((bool) ($settingsDepositsUsePools ?? false));
        const isBankProject = @json(($currentProjectType ?? '') === 'bank');

        let currentType = '';
        let currencyOptions = @json(($currencies ?? collect())->pluck('name')->values());
        let poolDepositItems = new Map();
        let currentCurrencyTab = 'directory';
        let officeCitySearchTimer = null;
        let officeCityOptionsMap = new Map();

        fotoFileInput?.addEventListener('change', () => {
            updateImagePreview(fotoFileInput, 'form-foto-preview', 'form-foto-preview-wrap');
        });

        officeCitySearchInput?.addEventListener('input', () => {
            officeCityIdInput.value = '';
            scheduleOfficeCitySearch(officeCitySearchInput.value);
        });

        officeCitySearchInput?.addEventListener('change', () => {
            applyOfficeCitySelectionFromInput();
        });

        officeCitySearchInput?.addEventListener('blur', () => {
            applyOfficeCitySelectionFromInput();
        });

        modal.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            currentType = btn.dataset.type;
            document.getElementById('modalCrudLabel').textContent = btn.dataset.title;
            currentCurrencyTab = 'directory';
            configureCurrencyTabs();
            addBtn.style.display = isPoolDepositMode() ? 'none' : '';
            configureStatusField();
            hideForm();
            loadData();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideForm();
            currentType = '';
            currentCurrencyTab = 'directory';
            configureCurrencyTabs();
            addBtn.style.display = '';
            configureStatusField();
        });

        currencyTabDirectory?.addEventListener('click', () => {
            currentCurrencyTab = 'directory';
            configureCurrencyTabs();
            hideForm();
        });

        currencyTabExchange?.addEventListener('click', () => {
            currentCurrencyTab = 'exchange';
            configureCurrencyTabs();
            hideForm();
            loadCurrencyExchangeSettings();
        });

        currencyExchangeForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            saveCurrencyExchangeSettings();
        });

        addBtn.addEventListener('click', () => {
            if (isPoolDepositMode()) {
                return;
            }
            document.getElementById('form-id').value = '';
            document.getElementById('form-name').value = '';
            colorInput.value = '';
            colorPicker.value = '#ffffff';
            if (faqPageSelect) faqPageSelect.value = 'academy';
            clearFaqFields();
            document.getElementById('form-status').value = currentType === 'tclient' ? '0' : '1';
            descriptionInput.value = '';
            setDocFlags('');
            setCostType('1');
            setCashFlowActivity('operating');
            resetOfficeFields();
            if (defaultCheckbox) {
                defaultCheckbox.checked = false;
            }
            populateCurrencySelect(defaultCurrencyCode());
            showForm();
        });

        cancelBtn.addEventListener('click', hideForm);
        deleteBtn.addEventListener('click', () => {
            const id = document.getElementById('form-id').value;
            if (!id) return;
            deleteItem(id);
        });

        colorPicker.addEventListener('input', (e) => {
            colorInput.value = e.target.value;
        });

        colorInput.addEventListener('input', (e) => {
            if (/^#[0-9a-fA-F]{6}$/.test(e.target.value)) {
                colorPicker.value = e.target.value;
            }
        });

        visibilityCheckbox?.addEventListener('change', () => {
            visibilityLabel.textContent = visibilityCheckbox.checked ? _ts('crud.visible') : _ts('crud.hidden');
        });

        tbody.addEventListener('click', (e) => {
            const poolRow = e.target.closest('tr[data-action="pool-info"]');
            if (poolRow && isPoolDepositMode()) {
                showPoolDepositInfo(poolRow.dataset.id);
                return;
            }

            const btn = e.target.closest('.action-btn');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editItem(id);
            }
            if (btn.dataset.action === 'pool-info') {
                showPoolDepositInfo(id);
            }
            if (btn.dataset.action === 'delete') {
                if (isPoolDepositMode()) {
                    return;
                }
                deleteItem(id, btn);
            }
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = document.getElementById('form-id').value;
            const payload = {
                type: currentType,
                name: document.getElementById('form-name').value.trim(),
                color: currentType === 'faq' ? '' : colorInput.value.trim(),
                status: document.getElementById('form-status').value,
                vision: currentType === 'sklads'
                    ? (visibilityCheckbox.checked ? '1' : '0')
                    : '1',
            };

            if (currentType === 'currency' || currentType === 'faq') {
                payload.description = descriptionInput.value.trim();
            }

            if (currentType === 'faq') {
                payload.faq = getFaqPayload();
            }

            if (currentType === 'sklads' || currentType === 'oplata') {
                payload.is_default = defaultCheckbox?.checked ? 1 : 0;
            }

            if (currentType === 'oplata' || currentType === 'deposit') {
                payload.currency = currencySelect.value || defaultCurrencyCode();
            }

            if (currentType === 'reestr') {
                payload.doc = getDocFlags();
                payload.constanta = costTypeSelect.value || '1';
                payload.vision = cashFlowActivitySelect.value || 'operating';
            }

            if (!payload.name) return;

            const request = currentType === 'sklads'
                ? submitOfficeForm(id, payload)
                : fetch(id ? `/settings/api/${id}` : '/settings/api', {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

            request
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    alert(data.message || _ts('js.error_generic'));
                    return;
                }
                hideForm();
                loadData();
                updateBadge();
            })
            .catch(() => alert(_ts('js.network_error')));
        });

        function clearFaqFields() {
            faqQuestionInputs.forEach((input) => {
                input.value = '';
            });
            faqAnswerInputs.forEach((input) => {
                input.value = '';
            });
        }

        function getFaqPayload() {
            const payload = { questions: {}, answers: {} };
            faqQuestionInputs.forEach((input) => {
                payload.questions[input.dataset.lang] = input.value.trim();
            });
            faqAnswerInputs.forEach((input) => {
                payload.answers[input.dataset.lang] = input.value.trim();
            });
            return payload;
        }

        function setFaqFields(item) {
            if (currentType !== 'faq') {
                return;
            }
            const questions = item.questions || {};
            const answers = item.answers || {};
            faqQuestionInputs.forEach((input) => {
                input.value = questions[input.dataset.lang] || '';
            });
            faqAnswerInputs.forEach((input) => {
                input.value = answers[input.dataset.lang] || '';
            });
        }

        function loadData() {
            fetch(`/settings/api/${currentType}`)
                .then((r) => r.json())
                .then(renderTable)
                .catch(() => {
                    tbody.innerHTML = `<tr><td colspan="${getTableColumnCount()}" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
                });
        }

        function renderTable(items) {
            tbody.innerHTML = '';
            poolDepositItems = new Map();
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach((item) => {
                if (isPoolDepositMode()) {
                    renderPoolDepositRow(item);
                    return;
                }

                const tr = document.createElement('tr');
                const colorHtml = currentType === 'faq'
                    ? escapeHtml(item.question || item.color || '—')
                    : item.color
                        ? `<span style="display:inline-block;width:16px;height:16px;background:${item.color};border-radius:3px;vertical-align:middle"></span> ${escapeHtml(item.color)}`
                        : '—';
                const docHtml = currentType === 'reestr'
                    ? escapeHtml(item.doc_label || _ts('crud.all_documents'))
                    : '';
                const costTypeHtml = currentType === 'reestr'
                    ? (String(item.constanta ?? '1') === '1'
                        ? '<span class="badge bg-warning text-dark">Переменные</span>'
                        : '<span class="badge bg-secondary">Постоянные</span>')
                    : '';
                const cashFlowActivityHtml = currentType === 'reestr'
                    ? cashFlowActivityBadge(item.cash_flow_activity || item.vision || 'operating')
                    : '';
                const addressHtml = currentType === 'sklads'
                    ? escapeHtml(item.address || '—')
                    : '';
                const cityHtml = currentType === 'sklads'
                    ? escapeHtml(item.city_label || '—')
                    : '';
                const currencyHtml = currentType === 'oplata' || currentType === 'deposit'
                    ? `<span class="badge bg-info text-dark">${escapeHtml(item.currency || defaultCurrencyCode())}</span>`
                    : '';
                const descriptionHtml = currentType === 'currency'
                    ? escapeHtml(item.description || item.descript || '—')
                    : '';
                const defaultHtml = currentType === 'sklads' || currentType === 'oplata'
                    ? (String(item.is_default ?? '0') === '1' ? '<span class="badge bg-primary">Default</span>' : '—')
                    : '';
                const isCompactConfTable = currentType === 'sklads' || currentType === 'oplata';
                const editLabel = isCompactConfTable ? '✏' : _ts('crud.edit');
                const deleteLabel = isCompactConfTable ? '🗑' : _ts('crud.delete');
                const deleteButtonHtml = isPoolDepositMode()
                    ? ''
                    : `<button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}" title="${escapeHtml(_ts('crud.delete'))}">${deleteLabel}</button>`;
                let statusLabel = `<span class="badge bg-secondary">${_ts('crud.inactive')}</span>`;
                if (currentType === 'tgroup') {
                    statusLabel = String(item.status) === '1'
                        ? `<span class="badge bg-warning text-dark">${_ts('crud.retail_badge')}</span>`
                        : `<span class="badge bg-secondary">${_ts('crud.extra_group_badge')}</span>`;
                } else if (currentType === 'tclient') {
                    statusLabel = ({
                        '1': `<span class="badge bg-primary">${_ts('crud.sales_dept')}</span>`,
                        '2': `<span class="badge bg-warning text-dark">${_ts('crud.production')}</span>`,
                        '3': `<span class="badge bg-danger">${_ts('crud.admin')}</span>`,
                        '0': `<span class="badge bg-secondary">${_ts('crud.other_dept')}</span>`,
                    }[String(item.status)] || `<span class="badge bg-secondary">${_ts('crud.other_dept')}</span>`);
                } else if (currentType === 'sklads') {
                    statusLabel = String(item.vision) === '1'
                        ? `<span class="badge bg-success">${_ts('crud.visible')}</span>`
                        : `<span class="badge bg-secondary">${_ts('crud.hidden')}</span>`;
                } else if (String(item.status) === '1') {
                    statusLabel = `<span class="badge bg-success">${_ts('crud.active')}</span>`;
                }

                tr.innerHTML = `
                    <td class="conf-id-col">${item.id}</td>
                    <td class="conf-name-col" title="${escapeHtml(item.name || '')}">${escapeHtml(item.name || '')}</td>
                    ${currentType === 'sklads' || currentType === 'currency' ? '' : `<td class="conf-color-col">${colorHtml}</td>`}
                    ${currentType === 'oplata' || currentType === 'deposit' ? `<td class="conf-currency-col">${currencyHtml}</td>` : ''}
                    ${currentType === 'currency' ? `<td class="conf-description-col" title="${descriptionHtml}">${descriptionHtml}</td>` : ''}
                    ${currentType === 'sklads' || currentType === 'oplata' ? `<td class="conf-default-col">${defaultHtml}</td>` : ''}
                    <td class="conf-status-col">${statusLabel}</td>
                    ${currentType === 'sklads' ? `<td class="conf-city-col" title="${escapeHtml(item.city_label || '')}">${cityHtml}</td>` : ''}
                    ${currentType === 'sklads' ? `<td class="conf-address-col" title="${escapeHtml(item.address || '')}">${addressHtml}</td>` : ''}
                    ${currentType === 'reestr' ? `<td class="conf-doc-col">${docHtml}</td>` : ''}
                    ${currentType === 'reestr' ? `<td class="conf-cost-type-col">${costTypeHtml}</td>` : ''}
                    ${currentType === 'reestr' ? `<td class="conf-cash-flow-activity-col">${cashFlowActivityHtml}</td>` : ''}
                    <td class="text-end conf-actions-col">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}" title="${escapeHtml(_ts('crud.edit'))}">${editLabel}</button>
                        ${deleteButtonHtml}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderPoolDepositRow(item) {
            const tr = document.createElement('tr');
            const symbol = item.currency || 'USDC';
            const apy = `${formatNumber((item.apy_bps || 0) / 100, 2)}%`;
            const statusLabel = String(item.status) === '1'
                ? `<span class="badge bg-success">${_ts('crud.active')}</span>`
                : `<span class="badge bg-secondary">${_ts('crud.inactive')}</span>`;
            poolDepositItems.set(String(item.id), item);
            tr.classList.add('pool-deposit-row');
            tr.dataset.action = 'pool-info';
            tr.dataset.id = item.id;

            tr.innerHTML = `
                <td class="conf-id-col">${item.id}</td>
                <td class="conf-name-col" colspan="2">
                    <div class="fw-semibold">${escapeHtml(item.name || '')}</div>
                </td>
                <td class="conf-currency-col">
                    <span class="badge bg-info text-dark">${escapeHtml(symbol)}</span>
                </td>
                <td class="conf-status-col">
                    ${statusLabel}
                    <div class="text-muted small">доходность: ${escapeHtml(apy)}</div>
                </td>
                <td class="text-end conf-actions-col"></td>
            `;
            tbody.appendChild(tr);
        }

        function showPoolDepositInfo(id) {
            const item = poolDepositItems.get(String(id));
            if (!item || !poolInfoBootstrapModal) {
                return;
            }

            const symbol = item.currency || 'USDC';
            const description = item.description || item.notes || 'Описание не заполнено.';
            document.getElementById('modalPoolDepositInfoLabel').textContent = item.name || 'Пул';
            document.getElementById('pool-info-balance').textContent = `${formatNumber(item.balance || 0, 2)} ${symbol}`;
            document.getElementById('pool-info-balance-usdc').textContent = `${formatNumber(item.balance_usdc || 0, 2)} USDC`;
            document.getElementById('pool-info-apy').textContent = `${formatNumber((item.apy_bps || 0) / 100, 2)}%`;
            document.getElementById('pool-info-description').textContent = description;
            document.getElementById('pool-info-object').textContent = item.pool_object_id || '—';
            poolInfoBootstrapModal.show();
        }

        function editItem(id) {
            fetch(`/settings/api/${currentType}/${id}`)
                .then((r) => r.json())
                .then((item) => {
                    document.getElementById('form-id').value = item.id;
                    document.getElementById('form-name').value = item.name || '';
                    colorInput.value = item.color || '';
                    colorPicker.value = /^#[0-9a-fA-F]{6}$/.test(item.color || '') ? item.color : '#ffffff';
                    if (faqPageSelect) faqPageSelect.value = item.page || item.color || 'academy';
                    setFaqFields(item);
                    document.getElementById('form-status').value = currentType === 'sklads'
                        ? (item.vision ?? '1')
                        : (item.status ?? '1');
                    visibilityCheckbox.checked = String(item.vision ?? '1') === '1';
                    visibilityLabel.textContent = visibilityCheckbox.checked ? _ts('crud.visible') : _ts('crud.hidden');
                    if (defaultCheckbox) {
                        defaultCheckbox.checked = String(item.is_default ?? '0') === '1';
                    }
                    setDocFlags(item.doc || '');
                    setCostType(item.constanta ?? '1');
                    setCashFlowActivity(item.cash_flow_activity || item.vision || 'operating');
                    phoneInput.value = item.phone || '';
                    setOfficeCityFromItem(item);
                    addressInput.value = item.address || '';
                    googleMapInput.value = item.google_map || '';
                    descriptionInput.value = item.description || item.descript || '';
                    populateCurrencySelect(item.currency || defaultCurrencyCode());
                    fotoExistingInput.value = item.foto || '';
                    if (fotoFileInput) fotoFileInput.value = '';
                    updateImagePreview(null, 'form-foto-preview', 'form-foto-preview-wrap', item.foto_preview || '');
                    showForm();
                })
                .catch(() => alert(_ts('js.load_error')));
        }

        function deleteItem(id, btn) {
            if (!confirm(_ts('js.delete_record_confirm'))) return;

            fetch(`/settings/api/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    alert(data.message || _ts('js.error_generic'));
                    return;
                }
                if (btn?.closest) {
                    btn.closest('tr')?.remove();
                }
                hideForm();
                updateBadge();
                loadData();
            })
            .catch(() => alert(_ts('js.network_error')));
        }

        function updateBadge() {
            fetch(`/settings/api/${currentType}`)
                .then((r) => r.json())
                .then((items) => {
                    const badge = document.getElementById(`badge-${currentType}`);
                    if (badge) {
                        badge.textContent = items.length;
                    }
                    if (currentType === 'currency') {
                        currencyOptions = items
                            .map((item) => normalizeCurrencyCode(item.name || item.currency || ''))
                            .filter(Boolean);
                    }
                });
        }

        function configureCurrencyTabs() {
            const hasCurrencyExchangeTabs = currentType === 'currency' && isBankProject;
            if (currencyTabsArea) {
                currencyTabsArea.style.display = hasCurrencyExchangeTabs ? '' : 'none';
            }
            if (!hasCurrencyExchangeTabs) {
                currentCurrencyTab = 'directory';
            }
            currencyTabDirectory?.classList.toggle('active', currentCurrencyTab === 'directory');
            currencyTabExchange?.classList.toggle('active', currentCurrencyTab === 'exchange');
            if (currencyExchangeArea) {
                currencyExchangeArea.style.display = hasCurrencyExchangeTabs && currentCurrencyTab === 'exchange' ? 'block' : 'none';
            }
            if (listArea) {
                listArea.style.display = currentCurrencyTab === 'exchange' ? 'none' : 'block';
            }
            if (addBtn) {
                addBtn.style.display = currentCurrencyTab === 'exchange' || isPoolDepositMode() ? 'none' : '';
            }
        }

        function loadCurrencyExchangeSettings() {
            if (!currencyExchangeUsdUahRate || !currencyExchangeIncomePercent || !currencyExchangeStatus) {
                return;
            }

            currencyExchangeStatus.textContent = 'Загрузка...';
            fetch('/settings/api/currency-exchange-settings', {
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((payload) => {
                    const data = payload.data || {};
                    currencyExchangeUsdUahRate.value = data.usd_uah_rate ?? '41.666667';
                    currencyExchangeIncomePercent.value = data.income_percent ?? '0';
                    currencyExchangeStatus.textContent = '';
                })
                .catch(() => {
                    currencyExchangeStatus.textContent = _ts('js.load_error');
                });
        }

        function saveCurrencyExchangeSettings() {
            if (!currencyExchangeUsdUahRate || !currencyExchangeIncomePercent || !currencyExchangeStatus) {
                return;
            }

            currencyExchangeStatus.textContent = 'Сохранение...';
            fetch('/settings/api/currency-exchange-settings', {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    usd_uah_rate: currencyExchangeUsdUahRate.value,
                    income_percent: currencyExchangeIncomePercent.value || 0,
                }),
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (!payload.success) {
                        currencyExchangeStatus.textContent = payload.message || _ts('js.error_generic');
                        return;
                    }
                    const data = payload.data || {};
                    currencyExchangeUsdUahRate.value = data.usd_uah_rate ?? currencyExchangeUsdUahRate.value;
                    currencyExchangeIncomePercent.value = data.income_percent ?? currencyExchangeIncomePercent.value;
                    currencyExchangeStatus.textContent = 'Настройки сохранены';
                })
                .catch(() => {
                    currencyExchangeStatus.textContent = _ts('js.network_error');
                });
        }

        function showForm() {
            currentCurrencyTab = 'directory';
            configureCurrencyTabs();
            formArea.style.display = 'block';
            listArea.style.display = 'none';
            if (currencyExchangeArea) {
                currencyExchangeArea.style.display = 'none';
            }
            deleteBtn.style.display = document.getElementById('form-id').value && !isPoolDepositMode() ? '' : 'none';
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = currentCurrencyTab === 'exchange' ? 'none' : 'block';
            if (currencyExchangeArea) {
                currencyExchangeArea.style.display = currentCurrencyTab === 'exchange' && currentType === 'currency' && isBankProject ? 'block' : 'none';
            }
            deleteBtn.style.display = 'none';
            configureCurrencyTabs();
        }

        function configureStatusField() {
            const isReestr = currentType === 'reestr';
            const isOffice = currentType === 'sklads';
            const supportsDefault = currentType === 'sklads' || currentType === 'oplata';
            const hasCurrency = currentType === 'oplata' || currentType === 'deposit';
            const isCurrency = currentType === 'currency';
            const isFaq = currentType === 'faq';
            const hasDescription = isCurrency;
            docRow.style.display = isReestr ? 'block' : 'none';
            docColumn.style.display = isReestr ? '' : 'none';
            costTypeRow.style.display = isReestr ? 'block' : 'none';
            costTypeColumn.style.display = isReestr ? '' : 'none';
            cashFlowActivityRow.style.display = isReestr ? 'block' : 'none';
            cashFlowActivityColumn.style.display = isReestr ? '' : 'none';
            defaultRow.style.display = supportsDefault ? '' : 'none';
            defaultColumn.style.display = supportsDefault ? '' : 'none';
            currencyRow.style.display = hasCurrency ? 'block' : 'none';
            currencyColumn.style.display = hasCurrency ? '' : 'none';
            descriptionRow.style.display = isCurrency ? 'block' : 'none';
            if (faqFields) faqFields.style.display = isFaq ? 'block' : 'none';
            descriptionColumn.style.display = hasDescription ? '' : 'none';
            officeFields.style.display = isOffice ? 'block' : 'none';
            visibilityRow.style.display = isOffice ? '' : 'none';
            statusRow.style.display = isOffice ? 'none' : 'block';
            colorRow.style.display = isCurrency || isFaq ? 'none' : '';
            colorColumn.style.display = isOffice || isCurrency ? 'none' : '';
            colorLabel.textContent = isFaq ? 'Страница' : 'Колір';
            colorPicker.style.display = isFaq ? 'none' : '';
            colorInput.style.display = isFaq ? 'none' : '';
            if (faqPageSelect) faqPageSelect.style.display = isFaq ? '' : 'none';
            document.querySelector('label[for="form-name"]').innerHTML = isFaq
                ? 'page <span class="text-danger">*</span>'
                : 'Назва <span class="text-danger">*</span>';
            if (nameColumn) nameColumn.textContent = isFaq ? 'page' : 'Назва';
            document.querySelector('label[for="form-description"]').textContent = isFaq ? 'Ответ' : 'Описание';
            descriptionInput.placeholder = isFaq
                ? 'Ответ на вопрос FAQ'
                : 'Описание валюты, реквизиты или подсказка для обмена';
            document.querySelector('#form-description-row .form-text').textContent = isFaq
                ? 'Показывается в FAQ выбранной страницы сайта.'
                : 'Показывается на странице swap в пункте 3.';
            descriptionColumn.textContent = 'Описание';
            colorColumn.textContent = isFaq ? 'Вопрос' : 'Колір';
            phoneColumn.style.display = 'none';
            addressColumn.style.display = isOffice ? '' : 'none';
            if (cityColumn) cityColumn.style.display = isOffice ? '' : 'none';
            populateCurrencySelect(currencySelect.value || defaultCurrencyCode());

            if (currentType === 'tgroup') {
                statusColumn.textContent = _ts('crud.status_column');
                statusLabel.textContent = _ts('crud.tgroup_label');
                statusSelect.innerHTML = `
                    <option value="1">${_ts('crud.tgroup_opt_retail')}</option>
                    <option value="0">${_ts('crud.tgroup_opt_extra')}</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = _ts('crud.tgroup_help');
            } else if (currentType === 'tclient') {
                statusColumn.textContent = _ts('crud.status_column');
                statusLabel.textContent = _ts('crud.tclient_label');
                statusSelect.innerHTML = `
                    <option value="0">${_ts('crud.tclient_opt0')}</option>
                    <option value="1">${_ts('crud.tclient_opt1')}</option>
                    <option value="2">${_ts('crud.tclient_opt2')}</option>
                    <option value="3">${_ts('crud.tclient_opt3')}</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = _ts('crud.tclient_help');
            } else if (currentType === 'sklads') {
                statusColumn.textContent = _ts('crud.office_visibility_col');
                statusLabel.textContent = _ts('crud.office_visibility_label');
                statusSelect.innerHTML = `
                    <option value="1">${_ts('crud.office_opt_visible')}</option>
                    <option value="0">${_ts('crud.office_opt_hidden')}</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = _ts('crud.office_help');
            } else if (currentType === 'currency' || currentType === 'faq') {
                statusColumn.textContent = _ts('crud.status_column');
                statusLabel.textContent = _ts('crud.generic_status_label');
                statusSelect.innerHTML = `
                    <option value="1">${_ts('crud.generic_active')}</option>
                    <option value="0">${_ts('crud.generic_inactive')}</option>
                `;
                statusHelp.style.display = 'block';
                statusHelp.textContent = currentType === 'faq' ? 'Активные записи показываются в FAQ на сайте.' : _ts('crud.currency_help');
            } else {
                statusColumn.textContent = _ts('crud.status_column');
                statusLabel.textContent = _ts('crud.generic_status_label');
                statusSelect.innerHTML = `
                    <option value="1">${_ts('crud.generic_active')}</option>
                    <option value="0">${_ts('crud.generic_inactive')}</option>
                `;
                statusHelp.style.display = 'none';
                statusHelp.textContent = '';
            }
        }

        function populateCurrencySelect(selectedCurrency) {
            if (!currencySelect) {
                return;
            }

            const options = availableCurrencyCodes();
            const normalizedSelected = normalizeCurrencyCode(selectedCurrency || options[0]);
            const selected = options.includes(normalizedSelected) ? normalizedSelected : options[0];
            currencySelect.innerHTML = options
                .map((currency) => `<option value="${escapeHtml(currency)}" ${currency === selected ? 'selected' : ''}>${escapeHtml(currency)}</option>`)
                .join('');
        }

        function availableCurrencyCodes() {
            const options = Array.from(new Set(currencyOptions.map(normalizeCurrencyCode).filter(Boolean)));
            return options.length ? options : ['UAH'];
        }

        function defaultCurrencyCode() {
            return availableCurrencyCodes()[0] || 'UAH';
        }

        function normalizeCurrencyCode(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .slice(0, 10);
        }

        function getDocFlags() {
            return docCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value)
                .join(',');
        }

        function setOfficeCity(id, label) {
            if (officeCityIdInput) {
                officeCityIdInput.value = id ? String(id) : '';
            }
            if (officeCitySearchInput) {
                officeCitySearchInput.value = label || '';
            }
            if (officeCityOptions) {
                officeCityOptions.innerHTML = '';
            }
            officeCityOptionsMap = new Map();
            if (id && label) {
                officeCityOptionsMap.set(label, { id: String(id), label });
            }
        }

        function setOfficeCityFromItem(item) {
            const cityId = item.city_id || item.city?.id || '';
            const label = item.city_label || item.city?.label || '';
            setOfficeCity(cityId, label);

            if (cityId && !label) {
                fetch(`/settings/api/office-city-search?id=${encodeURIComponent(cityId)}&ignore_firma=1`, {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        const city = Array.isArray(payload.items) ? payload.items[0] : null;
                        if (!city) {
                            return;
                        }
                        const cityLabel = city.label || city.val || city.valru || city.valen || `#${city.id}`;
                        setOfficeCity(city.id, cityLabel);
                    })
                    .catch(() => {});
            }
        }

        function applyOfficeCitySelectionFromInput() {
            if (!officeCitySearchInput || !officeCityIdInput) {
                return;
            }

            const value = officeCitySearchInput.value.trim();
            const selected = officeCityOptionsMap.get(value);
            officeCityIdInput.value = selected ? selected.id : '';
        }

        function scheduleOfficeCitySearch(query) {
            window.clearTimeout(officeCitySearchTimer);
            officeCitySearchTimer = window.setTimeout(() => searchOfficeCities(query), 250);
        }

        function searchOfficeCities(query) {
            if (!officeCityOptions) {
                return;
            }

            const text = String(query || '').trim();
            if (text.length < 2) {
                officeCityOptions.innerHTML = '';
                officeCityOptionsMap = new Map();
                return;
            }

            fetch(`/settings/api/office-city-search?q=${encodeURIComponent(text)}&ignore_firma=1`, {
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((payload) => {
                    const items = Array.isArray(payload.items) ? payload.items : [];
                    officeCityOptionsMap = new Map();
                    officeCityOptions.innerHTML = items.map((city) => {
                        const label = city.label || city.val || city.valru || city.valen || `#${city.id}`;
                        officeCityOptionsMap.set(label, { id: String(city.id), label });
                        return `<option value="${escapeHtml(label)}"></option>`;
                    }).join('');
                })
                .catch(() => {
                    officeCityOptions.innerHTML = '';
                    officeCityOptionsMap = new Map();
                });
        }

        function submitOfficeForm(id, payload) {
            const formData = new FormData();
            formData.append('type', payload.type);
            formData.append('name', payload.name);
            formData.append('color', payload.color);
            formData.append('status', payload.status);
            formData.append('vision', payload.vision);
            formData.append('is_default', payload.is_default || 0);
            applyOfficeCitySelectionFromInput();
            if (officeCitySearchInput.value.trim() && !officeCityIdInput.value.trim()) {
                return Promise.resolve({
                    json: () => Promise.resolve({
                        success: false,
                        message: 'Выберите город из списка подсказок.',
                    }),
                });
            }
            formData.append('phone', phoneInput.value.trim());
            formData.append('city_id', officeCityIdInput.value.trim());
            formData.append('address', addressInput.value.trim());
            formData.append('google_map', googleMapInput.value.trim());
            formData.append('foto', fotoExistingInput.value.trim());

            const fotoFile = fotoFileInput?.files?.[0];
            if (fotoFile) {
                formData.append('foto_file', fotoFile);
            }

            if (id) {
                formData.append('_method', 'PUT');
            }

            return fetch(id ? `/settings/api/${id}` : '/settings/api', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });
        }

        function resetOfficeFields() {
            phoneInput.value = '';
            setOfficeCity('', '');
            addressInput.value = '';
            googleMapInput.value = '';
            fotoExistingInput.value = '';
            visibilityCheckbox.checked = true;
            visibilityLabel.textContent = _ts('crud.visible');
            if (fotoFileInput) fotoFileInput.value = '';
            updateImagePreview(null, 'form-foto-preview', 'form-foto-preview-wrap', '');
        }

        function getTableColumnCount() {
            let count = 5;
            if (isPoolDepositMode()) {
                return 6;
            }
            if (currentType === 'sklads') {
                return 7;
            }
            if (currentType === 'reestr') {
                count += 3;
            }
            if (currentType === 'oplata' || currentType === 'deposit') {
                count += 1;
            }
            if (currentType === 'oplata') {
                count += 1;
            }
            return count;
        }

        function isPoolDepositMode() {
            return settingsDepositsUsePools && currentType === 'deposit';
        }

        function formatNumber(value, digits = 2) {
            const number = Number(value || 0);
            return Number.isFinite(number)
                ? number.toLocaleString('ru-RU', { minimumFractionDigits: digits, maximumFractionDigits: digits })
                : '0';
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

        function setCostType(value) {
            if (!costTypeSelect) {
                return;
            }

            costTypeSelect.value = String(value ?? '1') === '0' ? '0' : '1';
        }

        function normalizeCashFlowActivity(value) {
            const activity = String(value || '').trim();

            return ['operating', 'investing', 'financing'].includes(activity) ? activity : 'operating';
        }

        function cashFlowActivityLabel(value) {
            return {
                operating: 'Операционная',
                investing: 'Инвестиционная',
                financing: 'Финансовая',
            }[normalizeCashFlowActivity(value)];
        }

        function cashFlowActivityBadge(value) {
            const activity = normalizeCashFlowActivity(value);
            const badgeClass = {
                operating: 'bg-success',
                investing: 'bg-info text-dark',
                financing: 'bg-primary',
            }[activity];

            return `<span class="badge ${badgeClass}">${escapeHtml(cashFlowActivityLabel(activity))}</span>`;
        }

        function setCashFlowActivity(value) {
            if (!cashFlowActivitySelect) {
                return;
            }

            cashFlowActivitySelect.value = normalizeCashFlowActivity(value);
        }
    }

    function initAccountsCrud(csrfToken) {
        const modal = document.getElementById('modalAccounts');
        const formArea = document.getElementById('account-form-area');
        const listArea = document.getElementById('account-list-area');
        const form = document.getElementById('account-form');
        const tbody = document.getElementById('accounts-tbody');
        const emptyMsg = document.getElementById('accounts-empty-msg');
        const analyticalTbody = document.getElementById('analytical-accounts-tbody');
        const analyticalEmptyMsg = document.getElementById('analytical-accounts-empty-msg');
        const addBtn = document.getElementById('btn-account-add');
        const cancelBtn = document.getElementById('btn-account-cancel');
        const badge = document.getElementById('badge-accounts');
        const parentSelect = document.getElementById('account-parent-id');
        const currencySelect = document.getElementById('account-currency');
        const bindingsTbody = document.getElementById('payment-bindings-tbody');
        const bindingsEmptyMsg = document.getElementById('payment-bindings-empty-msg');
        const reloadBindingsBtn = document.getElementById('btn-payment-bindings-reload');
        const accountsTab = document.getElementById('accounts-tab');
        const analyticalTab = document.getElementById('analytical-accounts-tab');
        const bindingsTab = document.getElementById('payment-bindings-tab');

        if (!modal || !form || !tbody || !analyticalTbody || !bindingsTbody || !addBtn || !cancelBtn) {
            return;
        }

        let accountsCache = [];
        let analyticalAccountsCache = [];
        const accountCurrencyOptions = @json($accountCurrencies ?? collect(['UAH']));

        modal.addEventListener('show.bs.modal', () => {
            hideAccountForm();
            addBtn.classList.toggle('d-none', !accountsTab?.classList.contains('active'));
            loadAccounts();
            loadAnalyticalAccounts();
            loadBindings();
        });

        addBtn.addEventListener('click', () => {
            resetAccountForm();
            showAccountForm();
        });

        cancelBtn.addEventListener('click', hideAccountForm);
        reloadBindingsBtn.addEventListener('click', loadBindings);
        accountsTab?.addEventListener('shown.bs.tab', () => {
            addBtn.classList.remove('d-none');
        });
        analyticalTab?.addEventListener('shown.bs.tab', () => {
            hideAccountForm();
            addBtn.classList.add('d-none');
            loadAnalyticalAccounts();
        });
        bindingsTab?.addEventListener('shown.bs.tab', () => {
            hideAccountForm();
            addBtn.classList.add('d-none');
            loadBindings();
        });

        tbody.addEventListener('click', (e) => {
            handleAccountAction(e);
        });

        analyticalTbody.addEventListener('click', (e) => {
            handleAccountAction(e);
        });

        function handleAccountAction(e) {
            const btn = e.target.closest('.action-btn');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') {
                editAccount(id);
            }
            if (btn.dataset.action === 'delete') {
                deleteAccount(id);
            }
        }

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
                currency: currencySelect.value || accountDefaultCurrencyCode(),
                parent_id: parentSelect.value || null,
            };

            if (!payload.code || !payload.name) {
                return;
            }

            fetch(id ? `/settings/accounts/${id}` : '/settings/accounts', {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Accept': 'application/json',
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
                    alert(data.message || _ts('js.save_error_account'));
                    return;
                }

                hideAccountForm();
                loadAccounts();
                loadAnalyticalAccounts();
                loadBindings();
            })
            .catch(() => alert(_ts('js.network_error')));
        });

        function loadAccounts() {
            fetch('/settings/accounts')
                .then((r) => r.json())
                .then((items) => {
                    accountsCache = items || [];
                    renderAccounts(accountsCache, tbody, emptyMsg);
                    renderParentOptions(accountsCache);
                    badge.textContent = accountsCache.length;
                })
                .catch(() => {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
                });
        }

        function loadAnalyticalAccounts() {
            fetch('/settings/analytical-accounts')
                .then((r) => r.json())
                .then((items) => {
                    analyticalAccountsCache = items || [];
                    renderAccounts(analyticalAccountsCache, analyticalTbody, analyticalEmptyMsg);
                })
                .catch(() => {
                    analyticalTbody.innerHTML = `<tr><td colspan="6" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
                });
        }

        function loadBindings() {
            fetch('/settings/payment-type-account-bindings')
                .then((r) => r.json())
                .then(renderBindings)
                .catch(() => {
                    bindingsTbody.innerHTML = `<tr><td colspan="5" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
                });
        }

        function renderAccounts(items, targetTbody, targetEmptyMsg) {
            targetTbody.innerHTML = '';
            if (!items.length) {
                targetEmptyMsg.style.display = 'block';
                return;
            }

            targetEmptyMsg.style.display = 'none';
            items.forEach((item) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-semibold">${escapeHtml(item.code || '')}</td>
                    <td>${escapeHtml(item.name || '')}</td>
                    <td>${escapeHtml(accountTypeLabel(item.type || ''))}</td>
                    <td><span class="badge bg-info text-dark">${escapeHtml(item.currency || accountDefaultCurrencyCode())}</span></td>
                    <td>${escapeHtml(item.parent_code ? `${item.parent_code} | ${item.parent_name || ''}` : '—')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">✏</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                targetTbody.appendChild(tr);
            });
        }

        function renderParentOptions(items) {
            const currentId = document.getElementById('account-id').value;
            parentSelect.innerHTML = `<option value="">${escapeHtml(_ts('accounts.no_parent'))}</option>`;
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
                    <td class="small text-muted">${escapeHtml(item.doc_label || _ts('crud.all_documents'))}</td>
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
                    populateAccountCurrencySelect(item.currency || accountDefaultCurrencyCode());
                    renderParentOptions(accountsCache);
                    parentSelect.value = item.parent_id || '';
                    showAccountForm();
                })
                .catch(() => alert(_ts('js.load_error_account')));
        }

        function deleteAccount(id) {
            if (!confirm(_ts('js.delete_account_confirm'))) return;

            fetch(`/settings/accounts/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
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
                    alert(data.message || _ts('js.delete_error_account'));
                    return;
                }

                loadAccounts();
                loadAnalyticalAccounts();
                loadBindings();
            })
            .catch(() => alert(_ts('js.network_error')));
        }

        function saveBinding(id) {
            const payload = {
                debit_account_id: document.getElementById(`binding-debit-${id}`).value || null,
                credit_account_id: document.getElementById(`binding-credit-${id}`).value || null,
            };

            fetch(`/settings/payment-type-account-bindings/${id}`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
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
                    alert(data.message || _ts('js.save_error_binding'));
                    return;
                }

                loadBindings();
            })
            .catch(() => alert(_ts('js.network_error')));
        }

        function accountTypeLabel(type) {
            return ({
                asset: _ts('accounts.type_asset'),
                liability: _ts('accounts.type_liability'),
                equity: _ts('accounts.type_equity'),
                income: _ts('accounts.type_income'),
                expense: _ts('accounts.type_expense'),
            }[type] || type);
        }

        function resetAccountForm() {
            form.reset();
            document.getElementById('account-id').value = '';
            document.getElementById('account-type').value = 'asset';
            populateAccountCurrencySelect(accountDefaultCurrencyCode());
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

        function populateAccountCurrencySelect(selectedCurrency) {
            if (!currencySelect) {
                return;
            }

            const options = accountAvailableCurrencyCodes();
            const selected = options.includes(accountNormalizeCurrencyCode(selectedCurrency || options[0]))
                ? accountNormalizeCurrencyCode(selectedCurrency || options[0])
                : options[0];
            currencySelect.innerHTML = options
                .map((currency) => `<option value="${escapeHtml(currency)}" ${currency === selected ? 'selected' : ''}>${escapeHtml(currency)}</option>`)
                .join('');
        }

        function accountAvailableCurrencyCodes() {
            const options = Array.from(new Set(accountCurrencyOptions.map(accountNormalizeCurrencyCode).filter(Boolean)));
            return options.length ? options : ['UAH'];
        }

        function accountDefaultCurrencyCode() {
            return accountAvailableCurrencyCodes()[0] || 'UAH';
        }

        function accountNormalizeCurrencyCode(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .slice(0, 10);
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
                walletList.innerHTML = '<p id="wallet-list-empty" class="wallet-list-empty mb-0">' + escapeHtml(_ts('js.wallet_empty')) + '</p>';
                return;
            }

            walletList.innerHTML = wallets.map((wallet) => `
                <div class="wallet-list-item" data-wallet-address="${escapeHtml(wallet.address)}">
                    <div class="wallet-list-main">
                        <code title="${escapeHtml(wallet.address)}">${escapeHtml(wallet.address)}</code>
                        <span class="wallet-list-network">${escapeHtml(wallet.network || _ts('js.wallet_network_unknown'))}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger wallet-remove-btn" data-address="${escapeHtml(wallet.address)}">${escapeHtml(_ts('js.wallet_unlink'))}</button>
                </div>
            `).join('');
        };

        const updateWalletState = (wallets) => {
            const linked = wallets.length > 0;
            const latestWallet = wallets[0] || null;

            statusBadge.textContent = linked ? _ts('js.wallet_status_linked') : _ts('js.wallet_status_empty');
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
                throw new Error(data.message || _ts('js.web3_request_error'));
            }

            return data;
        };

        connectBtn.addEventListener('click', async () => {
            if (!window.appWallet || typeof window.appWallet.openModal !== 'function' || typeof window.appWallet.signMessage !== 'function') {
                setFeedback(_ts('js.web3_module_not_ready'), true);
                return;
            }

            setFeedback(_ts('js.web3_select_wallet'));

            window.appWallet.openModal({
                autoLogin: false,
                onConnected: async ({ address, chainId, provider, walletType }) => {
                    try {
                        const evmLinkWalletTypeFromChainId = (cid) => {
                            let id = null;
                            if (typeof cid === 'number' && Number.isFinite(cid)) {
                                id = '0x' + cid.toString(16);
                            } else if (typeof cid === 'string' && cid.trim()) {
                                const raw = cid.trim().toLowerCase();
                                if (raw.startsWith('0x')) {
                                    const n = parseInt(raw, 16);
                                    id = Number.isFinite(n) ? '0x' + n.toString(16) : null;
                                } else if (/^\d+$/.test(raw)) {
                                    const n = parseInt(raw, 10);
                                    id = Number.isFinite(n) ? '0x' + n.toString(16) : null;
                                }
                            }
                            if (!id) {
                                id = '0x1';
                            }
                            const map = {
                                '0x1': 'eth',
                                '0xa4b1': 'arbitrum',
                                '0x2105': 'base',
                                '0x89': 'polygon',
                                '0x38': 'bnb',
                            };
                            return map[id] || 'eth';
                        };
                        const normalizedType = walletType === 'solana' ? 'solana' : evmLinkWalletTypeFromChainId(chainId);
                        const network = normalizedType === 'solana'
                            ? 'Solana'
                            : (chainId ? `EVM ${chainId}` : 'EVM');

                        const challenge = await postJson('{{ route('wallet.challenge') }}', {
                            address,
                            wallet_type: normalizedType,
                            network: chainId || null,
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
                        setFeedback(_ts('js.wallet_link_success'));
                    } catch (error) {
                        setFeedback(error.message || _ts('js.wallet_link_failed'), true);
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
            if (!address || !confirm(_ts('js.unbind_wallet_confirm'))) {
                return;
            }

            button.disabled = true;
            setFeedback(_ts('js.wallet_unlinking'));

            try {
                const result = await postJson('{{ route('wallet.unlink') }}', { address });
                updateWalletState((result.user && result.user.wallets) || []);
                setFeedback(_ts('js.wallet_unlinked'));
            } catch (error) {
                setFeedback(error.message || _ts('js.wallet_unlink_failed'), true);
                button.disabled = false;
            }
        });
    }

    function initCatalogCrud(csrfToken, newsOptions) {
        const modal = document.getElementById('modalCatalog');
        const modalTitle = document.getElementById('modalCatalogLabel');
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
        const modeCatalogBtn = document.getElementById('btn-field-mode-catalog');
        const modeCityBtn = document.getElementById('btn-field-mode-city');
        const flagsRow = document.getElementById('catalog-flags-row');
        const descriptionRow = document.getElementById('catalog-description-row');
        const flagsHead = document.getElementById('catalog-flags-head');
        const descriptionHead = document.getElementById('catalog-description-head');
        const childrenHead = document.getElementById('catalog-children-head');
        const newsCatalogSelect = document.getElementById('catalog-news-catalog');
        const catalogFileUpload = document.getElementById('catalog-file-upload');
        const catalogFilePreview = document.getElementById('catalog-file-preview');
        const catalogFilePreviewWrap = document.getElementById('catalog-file-preview-wrap');
        const regionCitiesArea = document.getElementById('region-cities-area');
        const regionCitiesTitle = document.getElementById('region-cities-title');
        const regionCitiesTbody = document.getElementById('region-cities-tbody');
        const regionCitiesEmpty = document.getElementById('region-cities-empty');
        const regionCityFormArea = document.getElementById('region-city-form-area');
        const regionCityForm = document.getElementById('region-city-form');
        const regionCityAddBtn = document.getElementById('btn-region-city-add');
        const regionCityCancelBtn = document.getElementById('btn-region-city-cancel');
        const newsMap = new Map((newsOptions || []).map((item) => [String(item.id), item.title]));

        hydrateNewsSelect(newsCatalogSelect, newsOptions || []);

        function setCatalogFilePreview(source = '') {
            if (!catalogFilePreview || !catalogFilePreviewWrap) {
                return;
            }

            if (!source) {
                catalogFilePreview.src = '';
                catalogFilePreviewWrap.hidden = true;
                return;
            }

            catalogFilePreview.src = source;
            catalogFilePreviewWrap.hidden = false;
        }

        let currentCatalogFilePreviewUrl = '';

        catalogFileUpload?.addEventListener('change', () => {
            const file = catalogFileUpload.files?.[0];
            if (!file) {
                setCatalogFilePreview(currentCatalogFilePreviewUrl);
                return;
            }

            const reader = new FileReader();
            reader.onload = () => setCatalogFilePreview(String(reader.result || ''));
            reader.readAsDataURL(file);
        });

        let currentKeyfield = 'catalog';
        let currentParentId = '0';
        let selectedRegion = null;
        const fieldModeConfig = window.SettingsI18n.field_modes || {};
        let breadcrumb = [{ id: 0, name: fieldModeConfig.catalog.root }];

        modal.addEventListener('show.bs.modal', (event) => {
            const triggerMode = event.relatedTarget?.dataset?.fieldMode;
            switchFieldMode(triggerMode === 'city' ? 'city' : 'catalog');
        });

        modal.addEventListener('hidden.bs.modal', () => {
            hideCatalogForm();
            resetCatalogForm();
            currentKeyfield = 'catalog';
            currentParentId = '0';
            selectedRegion = null;
            breadcrumb = [{ id: 0, name: fieldModeConfig.catalog.root }];
            hideRegionCities();
        });

        modeCatalogBtn?.addEventListener('click', () => switchFieldMode('catalog'));
        modeCityBtn?.addEventListener('click', () => switchFieldMode('city'));

        addBtn.addEventListener('click', () => {
            if (selectedRegion) {
                resetRegionCityForm();
                regionCityFormArea.style.display = 'block';
                return;
            }
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
            if (selectedRegion) {
                hideRegionCities();
                loadCatalog('0');
                return;
            }
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
            if (btn.dataset.action === 'cities') {
                openRegionCities({
                    id,
                    name: btn.dataset.name || `#${id}`,
                });
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
                news_catalog_id: document.getElementById('catalog-news-catalog').value || '',
                foto1: document.getElementById('catalog-file-path').value.trim(),
                num: document.getElementById('catalog-num').value,
                visible: document.getElementById('catalog-visible').checked ? '1' : '0',
                firstpage: document.getElementById('catalog-firstpage').checked ? '1' : '0',
                description_ru: document.getElementById('catalog-description-ru').value.trim(),
                description_ua: document.getElementById('catalog-description-ua').value.trim(),
                description_en: document.getElementById('catalog-description-en').value.trim(),
            };
            const formData = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                formData.append(key, value);
            });

            const fileInput = document.getElementById('catalog-file-upload');
            if (fileInput.files && fileInput.files[0]) {
                formData.append('foto1_file', fileInput.files[0]);
            }

            if (id) {
                formData.append('_method', 'PUT');
            }

            if (!payload.name_ru) {
                alert(_ts('js.name_ru_required'));
                return;
            }

            fetch(fieldCrudUrl(id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            })
            .then(async (r) => {
                const data = await r.json();
                return { ok: r.ok, data };
            })
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(data.message || _ts('js.save_error'));
                    return;
                }

                hideCatalogForm();
                resetCatalogForm();
                loadCatalog(payload.parent_id || currentParentId);
                updateCatalogBadge();
            })
            .catch(() => alert(_ts('js.network_error')));
        });

        function loadCatalog(parentId) {
            const targetParentId = currentKeyfield === 'catalog' ? parentId : '0';
            const url = new URL('/settings/fields', window.location.origin);
            url.searchParams.set('keyfield', currentKeyfield);
            url.searchParams.set('parent_id', targetParentId);
            if (currentKeyfield === 'city') {
                url.searchParams.set('ignore_firma', '1');
            }

            fetch(url.toString())
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || _ts('js.load_error_catalog'));
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
                        ? (currentParentId === '0' ? fieldModeConfig.catalog.current : `${fieldModeConfig.catalog.subcategories_prefix}${currentParentName}`)
                        : fieldModeConfig.city.current;
                    parentLabel.textContent = currentKeyfield === 'catalog'
                        ? (currentParentId === '0' ? fieldModeConfig.catalog.root : currentParentName)
                        : fieldModeConfig.city.root;
                    backBtn.style.display = currentKeyfield === 'catalog' && currentParentId !== '0' ? 'inline-block' : 'none';
                })
                .catch(() => {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${escapeHtml(_ts('js.load_error_directory'))}</td></tr>`;
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
                const childLabel = item.children_count > 0
                    ? `<span class="badge bg-info text-dark">${item.children_count}</span>`
                    : '<span class="text-muted">0</span>';
                const flags = fieldModeConfig[currentKeyfield].showExtra ? `
                    <div>${item.visible === '1' ? `<span class="badge bg-success">${escapeHtml(_ts('catalog_modal.badge_visible'))}</span>` : `<span class="badge bg-secondary">${escapeHtml(_ts('catalog_modal.badge_hidden'))}</span>`}</div>
                    <div class="mt-1">${item.firstpage === '1' ? `<span class="badge bg-warning text-dark">${escapeHtml(_ts('catalog_modal.badge_firstpage'))}</span>` : `<span class="badge bg-light text-dark">${escapeHtml(_ts('catalog_modal.badge_normal'))}</span>`}</div>
                ` : '<span class="text-muted">—</span>';
                const description = fieldModeConfig[currentKeyfield].showExtra ? `
                    <div><strong>${escapeHtml(_ts('catalog_modal.label_link'))}</strong> ${escapeHtml(shortText(item.link || '—'))}</div>
                    <div><strong>${escapeHtml(_ts('catalog_modal.label_article'))}</strong> ${escapeHtml(getNewsTitle(item.news_catalog_id))}</div>
                    <div><strong>${escapeHtml(_ts('catalog_modal.label_file'))}</strong> ${item.image_url ? `<a href="${escapeHtml(item.image_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(_ts('catalog_modal.open_file'))}</a>` : '—'}</div>
                    <div>${escapeHtml(shortText(item.description_ru || '—'))}</div>
                    <div class="catalog-meta">UA: ${escapeHtml(shortText(item.description_ua || '—'))}</div>
                    <div class="catalog-meta">EN: ${escapeHtml(shortText(item.description_en || '—'))}</div>
                ` : '<span class="text-muted">—</span>';
                const openButton = currentKeyfield === 'catalog'
                    ? `<button class="btn btn-sm btn-outline-secondary action-btn" data-action="open" data-id="${item.id}">📂</button>`
                    : `<button class="btn btn-sm btn-outline-secondary action-btn" data-action="cities" data-id="${item.id}" data-name="${escapeHtml(item.name_ua || item.name_ru || `#${item.id}`)}">${escapeHtml(_ts('catalog_modal.cities'))}</button>`;
                tr.innerHTML = `
                    <td >${item.id}</td>
                    <td>
                        <div><strong>RU:</strong> ${escapeHtml(item.name_ru || '')}</div>
                        <div class="catalog-meta"><strong>UA:</strong> ${escapeHtml(item.name_ua || '—')}</div>
                        <div class="catalog-meta"><strong>EN:</strong> ${escapeHtml(item.name_en || '—')}</div>
                    </td>
                    <td >${description}</td>
                    <td >${flags}</td>
                    <td >${childLabel}</td>
                    <td >
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

        function fieldCrudUrl(id = '') {
            const path = id ? `/settings/fields/${encodeURIComponent(id)}` : '/settings/fields';
            const url = new URL(path, window.location.origin);
            if (id) {
                url.searchParams.set('keyfield', currentKeyfield);
            }
            if (currentKeyfield === 'city') {
                url.searchParams.set('ignore_firma', '1');
            }

            return url.toString();
        }

        function editCategory(id) {
            fetch(fieldCrudUrl(id))
                .then(async (r) => {
                    const data = await r.json();
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        alert(data.message || _ts('js.category_not_found'));
                        return;
                    }

                    document.getElementById('catalog-id').value = data.id || '';
                    document.getElementById('catalog-keyfield').value = data.keyfield || currentKeyfield;
                    document.getElementById('catalog-parent-id').value = data.parent_id || '0';
                    document.getElementById('catalog-name-ru').value = data.name_ru || '';
                    document.getElementById('catalog-name-ua').value = data.name_ua || '';
                    document.getElementById('catalog-name-en').value = data.name_en || '';
                    document.getElementById('catalog-link').value = data.link || '';
                    document.getElementById('catalog-news-catalog').value = data.news_catalog_id ? String(data.news_catalog_id) : '';
                    document.getElementById('catalog-file-path').value = data.foto1 || '';
                    document.getElementById('catalog-file-upload').value = '';
                    document.getElementById('catalog-file-current').innerHTML = data.image_url
                        ? `${escapeHtml(_ts('catalog_modal.file_current'))} <a href="${escapeHtml(data.image_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(_ts('catalog_modal.open_file'))}</a>`
                        : escapeHtml(_ts('catalog_modal.file_not_uploaded'));
                    currentCatalogFilePreviewUrl = data.image_url || '';
                    setCatalogFilePreview(data.image_url || '');
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
                .catch(() => alert(_ts('js.load_error_category')));
        }

        function deleteCategory(id) {
            if (!confirm(_ts('js.delete_category_confirm'))) return;

            fetch(fieldCrudUrl(id), {
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
                    alert(data.message || _ts('js.delete_error'));
                    return;
                }

                loadCatalog(currentParentId);
                updateCatalogBadge();
            })
            .catch(() => alert(_ts('js.network_error')));
        }

        function updateCatalogBadge() {
            Promise.all([
                fetch('/settings/fields?keyfield=catalog&parent_id=0').then((r) => r.json()),
                fetch('/settings/fields?keyfield=city&parent_id=0&ignore_firma=1').then((r) => r.json()),
            ]).then(([catalogData, cityData]) => {
                const catalogCount = (catalogData.items || []).length;
                const cityCount = (cityData.items || []).length;

                if (badgeCatalog) {
                    badgeCatalog.textContent = String(catalogCount);
                }
                if (badgeCity) {
                    badgeCity.textContent = String(cityCount);
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
            document.getElementById('catalog-news-catalog').value = '';
            document.getElementById('catalog-file-path').value = '';
            document.getElementById('catalog-file-upload').value = '';
            document.getElementById('catalog-file-current').textContent = _ts('catalog_modal.file_not_uploaded');
            currentCatalogFilePreviewUrl = '';
            setCatalogFilePreview('');
            document.getElementById('catalog-num').value = '0';
            document.getElementById('catalog-visible').checked = true;
            document.getElementById('catalog-firstpage').checked = false;
            parentLabel.textContent = getCurrentParentName();
        }

        function hydrateNewsSelect(select, items) {
            if (!select) return;

            select.innerHTML = `<option value="">${escapeHtml(_ts('catalog_modal.news_none'))}</option>`;
            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = `#${item.id} ${item.title}`;
                select.appendChild(option);
            });
        }

        function getNewsTitle(newsId) {
            if (!newsId) {
                return '—';
            }

            return newsMap.get(String(newsId)) || `#${newsId}`;
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
            return value.length > 64 ? `${value.slice(0, 61)}...` : value;
        }

        function switchFieldMode(mode) {
            currentKeyfield = mode;
            currentParentId = '0';
            selectedRegion = null;
            breadcrumb = [{ id: 0, name: fieldModeConfig[mode].root }];
            hideRegionCities();
            hideCatalogForm();
            resetCatalogForm();
            applyFieldMode();
            loadCatalog('0');
        }

        function applyFieldMode() {
            const config = fieldModeConfig[currentKeyfield];
            const catalogModalTexts = window.SettingsI18n.catalog_modal || {};
            if (modalTitle) {
                modalTitle.textContent = currentKeyfield === 'city'
                    ? `📍 ${catalogModalTexts.title_city || config.current}`
                    : `🌐 ${catalogModalTexts.title_catalog || config.current}`;
            }
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
            if (childrenHead) childrenHead.textContent = config.allowChildren ? _ts('crud.column_subcategories') : _ts('crud.column_records');
            if (breadcrumbBox) breadcrumbBox.style.display = config.allowChildren ? '' : 'none';
            if (backBtn) backBtn.style.display = 'none';
        }

        async function openRegionCities(region) {
            selectedRegion = region;
            hideCatalogForm();
            listArea.style.display = 'none';
            regionCitiesArea.style.display = 'block';
            regionCityFormArea.style.display = 'none';
            regionCitiesTitle.textContent = `${_ts('catalog_modal.cities_of_region')}: ${region.name}`;
            currentLevel.textContent = regionCitiesTitle.textContent;
            addBtn.style.display = 'none';
            backBtn.style.display = 'inline-block';
            await loadRegionCities();
        }

        function hideRegionCities() {
            selectedRegion = null;
            regionCitiesArea.style.display = 'none';
            regionCityFormArea.style.display = 'none';
            addBtn.style.display = '';
            listArea.style.display = 'block';
        }

        async function loadRegionCities() {
            if (!selectedRegion) return;

            regionCitiesTbody.innerHTML = `<tr><td colspan="6" class="text-muted">${escapeHtml(_ts('js.loading'))}</td></tr>`;
            const response = await fetch(`/settings/region-cities?region_id=${encodeURIComponent(selectedRegion.id)}&ignore_firma=1`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                alert(data.message || _ts('js.error_generic'));
                return;
            }

            const items = data.items || [];
            regionCitiesTbody.innerHTML = '';
            regionCitiesEmpty.style.display = items.length ? 'none' : 'block';
            items.forEach((city) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${city.id}</td>
                    <td>${escapeHtml(city.val)}</td>
                    <td>${escapeHtml(city.valru || '—')}</td>
                    <td>${escapeHtml(city.valen || '—')}</td>
                    <td>${city.num}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary region-city-edit" data-id="${city.id}">✏</button>
                        <button type="button" class="btn btn-sm btn-outline-danger region-city-delete" data-id="${city.id}">🗑</button>
                    </td>
                `;
                regionCitiesTbody.appendChild(tr);
            });
        }

        regionCityAddBtn.addEventListener('click', () => {
            resetRegionCityForm();
            regionCityFormArea.style.display = 'block';
        });

        regionCityCancelBtn.addEventListener('click', () => {
            resetRegionCityForm();
            regionCityFormArea.style.display = 'none';
        });

        regionCitiesTbody.addEventListener('click', async (event) => {
            const editButton = event.target.closest('.region-city-edit');
            const deleteButton = event.target.closest('.region-city-delete');

            if (editButton) {
                const response = await fetch(`/settings/region-cities/${encodeURIComponent(editButton.dataset.id)}?ignore_firma=1`, {
                    headers: { Accept: 'application/json' },
                });
                const city = await response.json().catch(() => ({}));
                if (!response.ok) {
                    alert(city.message || _ts('js.error_generic'));
                    return;
                }
                document.getElementById('region-city-id').value = city.id;
                document.getElementById('region-city-val').value = city.val || '';
                document.getElementById('region-city-valru').value = city.valru || '';
                document.getElementById('region-city-valen').value = city.valen || '';
                document.getElementById('region-city-num').value = city.num ?? 0;
                regionCityFormArea.style.display = 'block';
            }

            if (deleteButton) {
                if (!confirm(_ts('catalog_modal.delete_city_confirm'))) return;
                const response = await fetch(`/settings/region-cities/${encodeURIComponent(deleteButton.dataset.id)}?ignore_firma=1`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    alert(data.message || _ts('js.delete_error'));
                    return;
                }
                await loadRegionCities();
            }
        });

        regionCityForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!selectedRegion) return;

            const id = document.getElementById('region-city-id').value;
            const payload = {
                region_id: Number(selectedRegion.id),
                val: document.getElementById('region-city-val').value.trim(),
                valru: document.getElementById('region-city-valru').value.trim(),
                valen: document.getElementById('region-city-valen').value.trim(),
                num: Number(document.getElementById('region-city-num').value) || 0,
            };
            if (!payload.val) {
                alert(_ts('js.name_ua_required'));
                return;
            }

            const response = await fetch(id
                ? `/settings/region-cities/${encodeURIComponent(id)}?ignore_firma=1`
                : '/settings/region-cities?ignore_firma=1', {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                alert(data.message || _ts('js.save_error'));
                return;
            }

            resetRegionCityForm();
            regionCityFormArea.style.display = 'none';
            await loadRegionCities();
        });

        function resetRegionCityForm() {
            regionCityForm.reset();
            document.getElementById('region-city-id').value = '';
            document.getElementById('region-city-num').value = '0';
        }
    }

    function initCatalogFiltersCrud(csrfToken) {
        const modal = document.getElementById('modalCatalogFilters');
        if (!modal) {
            return;
        }

        const catSelect = document.getElementById('catalog-filters-category');
        const listHost = document.getElementById('catalog-filters-list');
        const emptyMsg = document.getElementById('catalog-filters-empty');
        const mainArea = document.getElementById('catalog-filters-main-area');
        const formArea = document.getElementById('catalog-filters-form-area');
        const form = document.getElementById('catalog-filters-form');
        const formHint = document.getElementById('catalog-filters-form-hint');
        const btnLoad = document.getElementById('btn-catalog-filters-load');
        const btnAddGroup = document.getElementById('btn-catalog-filters-add-group');
        const btnCancel = document.getElementById('btn-catalog-filters-form-cancel');

        const routes = {
            categories: @json(route('settings.catalogFilters.categories')),
            index: @json(route('settings.catalogFilters.index')),
            store: @json(route('settings.catalogFilters.store')),
        };
        const itemBase = @json(url('/settings/catalog-filters'));
        const itemUrl = (id) => `${itemBase}/${encodeURIComponent(id)}`;

        function bumpBadge(delta) {
            const el = document.getElementById('badge-catalog-filters');
            if (!el) {
                return;
            }
            const cur = parseInt(el.textContent, 10) || 0;
            el.textContent = String(Math.max(0, cur + delta));
        }

        function showMain() {
            mainArea.style.display = 'block';
            formArea.style.display = 'none';
        }

        function showForm(hint) {
            formHint.textContent = hint || '';
            mainArea.style.display = 'none';
            formArea.style.display = 'block';
        }

        function resetForm() {
            document.getElementById('catalog-filters-record-id').value = '';
            document.getElementById('catalog-filters-val').value = '';
            document.getElementById('catalog-filters-valru').value = '';
            document.getElementById('catalog-filters-valen').value = '';
            document.getElementById('catalog-filters-num').value = '0';
        }

        async function loadCategories() {
            const r = await fetch(routes.categories, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!r.ok) {
                const txt = await r.text();
                console.error('catalog-filters categories', r.status, txt.slice(0, 500));
                alert(_ts('js.http_categories') + r.status + ')');
                return;
            }
            let data;
            try {
                data = await r.json();
            } catch (e) {
                alert(_ts('js.invalid_server_list'));
                return;
            }
            const items = data.categories || [];
            catSelect.innerHTML = `<option value="">${escapeHtml(_ts('js.select_prompt'))}</option>`;
            items.forEach((c) => {
                const o = document.createElement('option');
                o.value = String(c.id);
                o.textContent = `#${c.id} ${c.label}`;
                catSelect.appendChild(o);
            });
        }

        function selectedCatalogId() {
            return catSelect.value || '';
        }

        async function loadFilters() {
            const cid = selectedCatalogId();
            if (!cid) {
                alert(_ts('js.choose_category'));
                return;
            }
            listHost.innerHTML = '<p class="text-muted">' + escapeHtml(_ts('js.loading')) + '</p>';
            const r = await fetch(`${routes.index}?catalog_id=${encodeURIComponent(cid)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            let data;
            try {
                data = await r.json();
            } catch (e) {
                alert(_ts('js.invalid_server_response'));
                listHost.innerHTML = '';
                return;
            }
            if (!r.ok) {
                alert(data.message || _ts('js.error_generic'));
                listHost.innerHTML = '';
                return;
            }
            renderTree(data.groups || []);
        }

        function renderTree(groups) {
            listHost.innerHTML = '';
            if (!groups.length) {
                emptyMsg.style.display = 'block';
                return;
            }
            emptyMsg.style.display = 'none';

            groups.forEach(({ group, values }) => {
                const wrap = document.createElement('div');
                wrap.className = 'glass-card mb-3 p-3';
                const g = group;
                wrap.innerHTML = `
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div>
                            <strong>${escapeHtml(_ts('js.cf_group'))} #${g.id}</strong>
                            <div class="small text-muted">UA: ${escapeHtml(g.val)} | RU: ${escapeHtml(g.valru)} | EN: ${escapeHtml(g.valen)} | num: ${g.num}</div>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary cf-edit" data-id="${g.id}">${escapeHtml(_ts('js.cf_change'))}</button>
                            <button type="button" class="btn btn-outline-danger cf-del-group" data-id="${g.id}">${escapeHtml(_ts('js.cf_delete'))}</button>
                            <button type="button" class="btn btn-outline-success cf-add-val" data-group-id="${g.id}">${escapeHtml(_ts('js.cf_add_value'))}</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead><tr><th>ID</th><th>UA</th><th>RU</th><th>EN</th><th>num</th><th></th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                `;
                const tbody = wrap.querySelector('tbody');
                (values || []).forEach((v) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${v.id}</td>
                        <td>${escapeHtml(v.val)}</td>
                        <td>${escapeHtml(v.valru)}</td>
                        <td>${escapeHtml(v.valen)}</td>
                        <td>${v.num}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary cf-edit" data-id="${v.id}">${escapeHtml(_ts('js.cf_change'))}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger cf-del-val" data-id="${v.id}">${escapeHtml(_ts('js.cf_delete'))}</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                listHost.appendChild(wrap);
            });

            listHost.querySelectorAll('.cf-edit').forEach((b) => {
                b.addEventListener('click', () => startEdit(b.dataset.id));
            });
            listHost.querySelectorAll('.cf-del-group').forEach((b) => {
                b.addEventListener('click', () => destroyRow(b.dataset.id, true));
            });
            listHost.querySelectorAll('.cf-add-val').forEach((b) => {
                b.addEventListener('click', () => startAddValue(b.dataset.groupId));
            });
            listHost.querySelectorAll('.cf-del-val').forEach((b) => {
                b.addEventListener('click', () => destroyRow(b.dataset.id, false));
            });
        }

        modal.addEventListener('show.bs.modal', () => {
            showMain();
            resetForm();
            void loadCategories();
        });

        btnLoad.addEventListener('click', () => void loadFilters());
        btnAddGroup.addEventListener('click', () => startAddGroup());
        btnCancel.addEventListener('click', () => {
            showMain();
            resetForm();
        });

        function startAddGroup() {
            const cid = selectedCatalogId();
            if (!cid) {
                alert(_ts('js.choose_category'));
                return;
            }
            resetForm();
            document.getElementById('catalog-filters-record-id').value = '';
            document.getElementById('catalog-filters-catalog-id').value = cid;
            document.getElementById('catalog-filters-is-group').value = '1';
            document.getElementById('catalog-filters-parent-group-id').value = '';
            showForm(_ts('js.cf_new_group'));
        }

        function startAddValue(groupId) {
            const cid = selectedCatalogId();
            if (!cid) {
                alert(_ts('js.choose_category'));
                return;
            }
            resetForm();
            document.getElementById('catalog-filters-record-id').value = '';
            document.getElementById('catalog-filters-catalog-id').value = cid;
            document.getElementById('catalog-filters-is-group').value = '0';
            document.getElementById('catalog-filters-parent-group-id').value = String(groupId);
            showForm(`${_ts('js.cf_new_value_prefix')}${groupId}`);
        }

        async function startEdit(id) {
            const r = await fetch(itemUrl(id), { headers: { Accept: 'application/json' } });
            const row = await r.json();
            if (!r.ok) {
                alert(row.message || _ts('js.error_generic'));
                return;
            }
            const isGroup = Number(row.idfilter) === 0;
            document.getElementById('catalog-filters-record-id').value = String(row.id);
            document.getElementById('catalog-filters-catalog-id').value = String(row.idkeyfield);
            document.getElementById('catalog-filters-is-group').value = isGroup ? '1' : '0';
            document.getElementById('catalog-filters-parent-group-id').value = isGroup ? '' : String(row.idfilter);
            document.getElementById('catalog-filters-val').value = row.val || '';
            document.getElementById('catalog-filters-valru').value = row.valru || '';
            document.getElementById('catalog-filters-valen').value = row.valen || '';
            document.getElementById('catalog-filters-num').value = String(row.num ?? 0);
            showForm(isGroup ? `${_ts('js.cf_edit_group_prefix')}${id}` : `${_ts('js.cf_edit_value_prefix')}${id}`);
        }

        async function destroyRow(id, isGroup) {
            if (!confirm(isGroup ? _ts('js.delete_filter_group_confirm') : _ts('js.delete_filter_value_confirm'))) {
                return;
            }
            const r = await fetch(itemUrl(id), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok || !data.success) {
                alert(data.message || _ts('js.delete_error'));
                return;
            }
            if (isGroup) {
                bumpBadge(-1);
            }
            void loadFilters();
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const recordId = document.getElementById('catalog-filters-record-id').value;
            const cid = document.getElementById('catalog-filters-catalog-id').value;
            const isGroup = document.getElementById('catalog-filters-is-group').value === '1';
            const body = {
                val: document.getElementById('catalog-filters-val').value.trim(),
                valru: document.getElementById('catalog-filters-valru').value.trim(),
                valen: document.getElementById('catalog-filters-valen').value.trim(),
                num: parseInt(document.getElementById('catalog-filters-num').value, 10) || 0,
            };
            if (!body.val) {
                alert(_ts('js.name_ua_required'));
                return;
            }

            if (recordId) {
                const r = await fetch(itemUrl(recordId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(body),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    alert(data.message || _ts('js.save_error'));
                    return;
                }
            } else {
                const payload = {
                    catalog_id: parseInt(cid, 10),
                    is_group: isGroup,
                    parent_group_id: isGroup ? null : parseInt(document.getElementById('catalog-filters-parent-group-id').value, 10),
                    ...body,
                };
                const r = await fetch(routes.store, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    alert(data.message || _ts('js.save_error'));
                    return;
                }
                if (isGroup) {
                    bumpBadge(1);
                }
            }
            showMain();
            resetForm();
            void loadFilters();
        });
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
                alert(_ts('js.company_name_required'));
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
                    alert(data.message || _ts('js.save_error'));
                    return;
                }
                hideFirmForm();
                resetFirmForm();
                loadFirms();
            })
            .catch(() => alert(_ts('js.network_error')));
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
                    tbody.innerHTML = `<tr><td colspan="7" class="text-danger">${escapeHtml(_ts('js.load_error_companies'))}</td></tr>`;
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
                        alert(data.message || _ts('js.company_not_found'));
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
                .catch(() => alert(_ts('js.load_error_company')));
        }

        function deleteFirm(id, btn) {
            if (!confirm(_ts('js.delete_company_confirm'))) return;

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
                    alert(data.message || _ts('js.delete_error'));
                    return;
                }

                btn.closest('tr').remove();
                const rest = tbody.querySelectorAll('tr').length;
                document.getElementById('badge-firms').textContent = rest;
                if (!rest) {
                    emptyMsg.style.display = 'block';
                }
            })
            .catch(() => alert(_ts('js.network_error')));
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
                alert(_ts('js.banner_title_required'));
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
                    alert(data.message || _ts('js.save_error'));
                    return;
                }

                hideBannerForm();
                resetBannerForm();
                loadBanners();
            })
            .catch(() => alert(_ts('js.network_error')));
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
                    tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
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
                    <td>${String(item.vision) === '1' ? `<span class="badge bg-success">${escapeHtml(_ts('js.banner_visible'))}</span>` : `<span class="badge bg-secondary">${escapeHtml(_ts('js.banner_hidden'))}</span>`}</td>
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
                        alert(data.message || _ts('js.banner_not_found'));
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
                .catch(() => alert(_ts('js.load_error_banner')));
        }

        function deleteBanner(id, btn) {
            if (!confirm(_ts('js.delete_banner_confirm'))) return;

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
                    alert(data.message || _ts('js.delete_error'));
                    return;
                }

                btn.closest('tr').remove();
                const rest = tbody.querySelectorAll('tr').length;
                document.getElementById('badge-banners').textContent = rest;
                if (!rest) {
                    emptyMsg.style.display = 'block';
                }
            })
            .catch(() => alert(_ts('js.network_error')));
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
    (() => {
        const sitemapStatusUrl = @json(route('settings.sitemap.status'));
        const sitemapGenerateUrl = @json(route('settings.sitemap.generate'));
        const modal = document.getElementById('modalSitemap');
        const btnGenerate = document.getElementById('btn-sitemap-generate');
        const publicLink = document.getElementById('sitemap-public-link');
        const openBtn = document.getElementById('btn-sitemap-open');
        const statusText = document.getElementById('sitemap-status-text');
        const lastmodText = document.getElementById('sitemap-lastmod-text');
        const feedback = document.getElementById('sitemap-feedback');
        const badge = document.getElementById('badge-sitemap');

        if (!modal || !btnGenerate) return;

        modal.addEventListener('show.bs.modal', loadSitemapStatus);
        btnGenerate.addEventListener('click', generateSitemap);

        async function parseSitemapResponse(response) {
            const raw = await response.text().catch(() => '');

            if (!raw) {
                return {};
            }

            try {
                return JSON.parse(raw);
            } catch (_) {
                const trimmed = raw.trim();
                const isHtml = trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html') || trimmed.startsWith('<');

                return {
                    message: isHtml ? _ts('sitemap.parse_html_error') : trimmed,
                };
            }
        }

        function loadSitemapStatus() {
            feedback.textContent = '';

            fetch(sitemapStatusUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async (response) => {
                    const data = await parseSitemapResponse(response);

                    if (!response.ok) {
                        throw new Error(data.message || _ts('sitemap.load_status_failed'));
                    }

                    return data;
                })
                .then((data) => updateSitemapUi(data))
                .catch((error) => {
                    feedback.textContent = error.message || _ts('sitemap.load_status_failed');
                });
        }

        function generateSitemap() {
            btnGenerate.disabled = true;
            feedback.textContent = _ts('sitemap.generating');

            fetch(sitemapGenerateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
                .then(async (response) => {
                    const data = await parseSitemapResponse(response);
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok || !data.success) {
                        throw new Error(data.message || data.error || _ts('sitemap.generate_failed'));
                    }

                    updateSitemapUi(data);
                    feedback.textContent = data.message || _ts('sitemap.generated');
                })
                .catch((error) => {
                    feedback.textContent = error.message || _ts('sitemap.generate_failed');
                })
                .finally(() => {
                    btnGenerate.disabled = false;
                });
        }

        function updateSitemapUi(data) {
            const exists = Boolean(data.exists ?? data.public_url);
            const publicUrl = data.public_url || '#';

            statusText.textContent = exists ? _ts('sitemap.file_ready') : _ts('sitemap.file_missing');
            badge.textContent = exists ? 'XML' : '—';
            publicLink.href = publicUrl;
            publicLink.textContent = publicUrl;
            openBtn.href = publicUrl;
            lastmodText.textContent = formatTimestamp(data.last_modified_at);
        }

        function formatTimestamp(value) {
            if (!value) return '—';

            const date = new Date(Number(value) * 1000);
            return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString();
        }
    })();
});

// === База знаний (Knowledge Base) ===
document.addEventListener('DOMContentLoaded', function () {
    (() => {
        const modal = document.getElementById('modalKnowledgeBase');
        const formArea = document.getElementById('kb-form-area');
        const listArea = document.getElementById('kb-list-area');
        const catArea = document.getElementById('kb-category-area');
        const form = document.getElementById('kb-form');
        const tbody = document.getElementById('kb-tbody');
        const emptyMsg = document.getElementById('kb-empty-msg');
        const pagination = document.getElementById('kb-pagination');
        const badge = document.getElementById('badge-knowledge-base');
        const btnAdd = document.getElementById('btn-kb-add');
        const btnManageCat = document.getElementById('btn-kb-manage-categories');
        const btnCancel = document.getElementById('btn-kb-cancel');
        const btnRefresh = document.getElementById('btn-kb-refresh');
        const btnSearch = document.getElementById('btn-kb-search');
        const searchInput = document.getElementById('kb-search-input');
        const filterCategory = document.getElementById('kb-filter-category');

        const kbId = document.getElementById('kb-id');
        const kbTitle = document.getElementById('kb-title');
        const kbCategory = document.getElementById('kb-category');
        const kbContent = document.getElementById('kb-content');
        const kbActive = document.getElementById('kb-active');
        const kbTools = document.getElementById('kb-tools');

        // Category management elements
        const catFormArea = document.getElementById('kb-category-form-area');
        const catForm = document.getElementById('kb-category-form');
        const catTbody = document.getElementById('kb-category-tbody');
        const catEmpty = document.getElementById('kb-category-empty');
        const btnCatAdd = document.getElementById('btn-kb-category-add');
        const btnCatBack = document.getElementById('btn-kb-category-back');
        const btnCatFormCancel = document.getElementById('btn-kb-category-form-cancel');
        const catId = document.getElementById('kb-category-id');
        const catKey = document.getElementById('kb-category-key');
        const catName = document.getElementById('kb-category-name');
        const catSort = document.getElementById('kb-category-sort');
        const catActive = document.getElementById('kb-category-active');

        // Tools management elements
        const toolsArea = document.getElementById('kb-tools-area');
        const toolFormArea = document.getElementById('kb-tool-form-area');
        const toolForm = document.getElementById('kb-tool-form');
        const toolTbody = document.getElementById('kb-tool-tbody');
        const toolEmpty = document.getElementById('kb-tool-empty');
        const btnToolAdd = document.getElementById('btn-kb-tool-add');
        const btnToolBack = document.getElementById('btn-kb-tool-back');
        const btnToolFormCancel = document.getElementById('btn-kb-tool-form-cancel');
        const btnManageTools = document.getElementById('btn-kb-manage-tools');
        const toolId = document.getElementById('kb-tool-id');
        const toolKey = document.getElementById('kb-tool-key');
        const toolName = document.getElementById('kb-tool-name');
        const toolSort = document.getElementById('kb-tool-sort');
        const toolDescription = document.getElementById('kb-tool-description');
        const toolSchema = document.getElementById('kb-tool-schema');
        const toolActive = document.getElementById('kb-tool-active');

        const API_BASE = '/api/ai/knowledge-base';
        const CATEGORY_API_BASE = '/api/ai/knowledge-base/categories';
        const TOOLS_API_BASE = '/api/ai/tools';
        const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
        const FID = () => document.getElementById('kb-fid')?.value || '';

        // Category key → name map, populated from API
        window._kbCategoryMap = window._kbCategoryMap || {};

        let currentPage = 1;
        let lastSearchQuery = '';
        let lastCategory = '';

        if (!modal) return;

        // Inject hidden fid input for reference
        if (!document.getElementById('kb-fid')) {
            const hf = document.createElement('input');
            hf.type = 'hidden'; hf.id = 'kb-fid';
            document.body.appendChild(hf);
        }

        function _kbs(path) {
            const v = String(path || '').split('.').reduce(function (acc, key) {
                return acc && acc[key] !== undefined ? acc[key] : undefined;
            }, window.SettingsI18n?.knowledge_base || {});
            return v !== undefined && v !== null ? v : path;
        }

        function _kbt(path) {
            const v = String(path || '').split('.').reduce(function (acc, key) {
                return acc && acc[key] !== undefined ? acc[key] : undefined;
            }, window.SettingsI18n?.tools || {});
            return v !== undefined && v !== null ? v : path;
        }

        /**
         * Load active categories from API and populate selects + category map.
         */
        function loadCategories() {
            var fidParam = FID() ? '?fid=' + FID() : '';
            return fetch(CATEGORY_API_BASE + fidParam, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(function (data) {
                    const cats = data.data || [];
                    // Build category map
                    const map = {};
                    cats.forEach(function (c) { map[c.key] = c.name; });
                    window._kbCategoryMap = map;

                    // Populate form select (#kb-category)
                    var formSelect = kbCategory;
                    var currentVal = formSelect.value;
                    formSelect.innerHTML = '<option value="">' + _kbs('select_category') + '</option>';
                    cats.forEach(function (c) {
                        var opt = document.createElement('option');
                        opt.value = c.key;
                        opt.textContent = c.name;
                        formSelect.appendChild(opt);
                    });
                    // Restore value if still valid
                    if (currentVal && map[currentVal]) {
                        formSelect.value = currentVal;
                    }

                    // Populate filter select (#kb-filter-category)
                    var filterSelect = filterCategory;
                    var filterVal = filterSelect.value;
                    filterSelect.innerHTML = '<option value="">' + _kbs('all_categories') + '</option>';
                    cats.forEach(function (c) {
                        var opt = document.createElement('option');
                        opt.value = c.key;
                        opt.textContent = c.name;
                        filterSelect.appendChild(opt);
                    });
                    if (filterVal && map[filterVal]) {
                        filterSelect.value = filterVal;
                    }

                    return cats;
                })
                .catch(function () {
                    console.error('Failed to load categories');
                });
        }

        /**
         * Load available tools into the KB form multi-select.
         */
        function loadToolsSelect() {
            if (!kbTools) return;
            kbTools.innerHTML = '<option value="">' + _kbs('tools_loading') + '</option>';
            var fidParam = FID() ? '?fid=' + FID() : '';
            fetch(TOOLS_API_BASE + '/all' + fidParam, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var tools = data.data || [];
                    kbTools.innerHTML = '';
                    tools.forEach(function (t) {
                        if (!t.active) return;
                        var opt = document.createElement('option');
                        opt.value = t.key;
                        opt.textContent = t.key + ' — ' + t.name;
                        kbTools.appendChild(opt);
                    });
                })
                .catch(function () {
                    kbTools.innerHTML = '<option value="">' + _kbs('load_error') + '</option>';
                });
        }

        /**
         * Load all categories (including inactive) into the management table.
         */
        function loadCategoryList() {
            if (!catTbody) return;
            catTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">' + _kbs('loading') + '</td></tr>';

            var fidParam = FID() ? '?fid=' + FID() : '';
            fetch(CATEGORY_API_BASE + '/all' + fidParam, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(function (data) {
                    const cats = data.data || [];
                    catTbody.innerHTML = '';

                    if (cats.length === 0) {
                        catEmpty.style.display = 'block';
                        return;
                    }
                    catEmpty.style.display = 'none';

                    cats.forEach(function (c) {
                        var tr = document.createElement('tr');
                        var activeIcon = c.active ? '✅' : '❌';
                        var toggleIcon = c.active ? '❌' : '✅';

                        tr.innerHTML =
                            '<td><code>' + escapeHtml(c.key) + '</code></td>' +
                            '<td>' + escapeHtml(c.name) + '</td>' +
                            '<td class="text-center">' + (c.sort_order || 0) + '</td>' +
                            '<td class="text-center">' + activeIcon + '</td>' +
                            '<td class="text-end">' +
                                '<button class="btn btn-sm btn-outline-primary me-1 btn-cat-edit" data-id="' + c.id + '">✏️</button>' +
                                '<button class="btn btn-sm btn-outline-warning me-1 btn-cat-toggle" data-id="' + c.id + '" data-active="' + (c.active ? '1' : '0') + '">' + toggleIcon + '</button>' +
                                '<button class="btn btn-sm btn-outline-danger btn-cat-delete" data-id="' + c.id + '">🗑</button>' +
                            '</td>';

                        catTbody.appendChild(tr);
                    });

                    // Edit button handlers
                    catTbody.querySelectorAll('.btn-cat-edit').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var id = parseInt(this.dataset.id);
                            fetch(CATEGORY_API_BASE + '/' + id, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.data) showCategoryForm(data.data);
                                })
                                .catch(function () { alert(_kbs('load_error')); });
                        });
                    });

                    // Toggle active handler
                    catTbody.querySelectorAll('.btn-cat-toggle').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm(_kbs('toggle_active_confirm'))) return;
                            var id = parseInt(this.dataset.id);
                            var newActive = this.dataset.active === '1' ? '0' : '1';
                            fetch(CATEGORY_API_BASE + '/' + id, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ active: newActive === '1' }),
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.data) {
                                        loadCategoryList();
                                        loadCategories();
                                    } else {
                                        alert(_kbs('save_error'));
                                    }
                                })
                                .catch(function () { alert(_kbs('save_error')); });
                        });
                    });

                    // Delete handler
                    catTbody.querySelectorAll('.btn-cat-delete').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm(_kbs('delete_confirm'))) return;
                            var id = parseInt(this.dataset.id);
                            fetch(CATEGORY_API_BASE + '/' + id, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.message) {
                                        loadCategoryList();
                                        loadCategories();
                                    } else {
                                        alert(_kbs('delete_error'));
                                    }
                                })
                                .catch(function () { alert(_kbs('delete_error')); });
                        });
                    });
                })
                .catch(function () {
                    catTbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + _kbs('load_error') + '</td></tr>';
                });
        }

        /**
         * Show/hide category management area.
         */
        function showCategoryArea() {
            if (catArea) {
                listArea.style.display = 'none';
                formArea.style.display = 'none';
                catArea.style.display = 'block';
                btnAdd.style.display = 'none';
                hideCategoryForm();
                loadCategoryList();
            }
        }

        function hideCategoryArea() {
            if (catArea) {
                catArea.style.display = 'none';
                listArea.style.display = 'block';
                btnAdd.style.display = 'inline-block';
                hideCategoryForm();
            }
        }

        function showCategoryForm(record) {
            if (!catFormArea) return;
            catFormArea.style.display = 'block';

            if (record) {
                catId.value = record.id;
                catKey.value = record.key || '';
                catName.value = record.name || '';
                catSort.value = record.sort_order || 0;
                catActive.checked = record.active !== false;
                catKey.readOnly = true;
            } else {
                catId.value = '';
                catKey.value = '';
                catName.value = '';
                catSort.value = '0';
                catActive.checked = true;
                catKey.readOnly = false;
            }
        }

        function hideCategoryForm() {
            if (!catFormArea) return;
            catFormArea.style.display = 'none';
            catForm.reset();
            catId.value = '';
            catKey.readOnly = false;
        }

        // ── Tools CRUD ──

        function showToolArea() {
            if (toolsArea) {
                listArea.style.display = 'none';
                formArea.style.display = 'none';
                catArea.style.display = 'none';
                toolsArea.style.display = 'block';
                btnAdd.style.display = 'none';
                hideToolForm();
                loadTools();
            }
        }

        function hideToolArea() {
            if (toolsArea) {
                toolsArea.style.display = 'none';
                listArea.style.display = 'block';
                btnAdd.style.display = 'inline-block';
                hideToolForm();
            }
        }

        function showToolForm(record) {
            if (!toolFormArea) return;
            toolFormArea.style.display = 'block';

            if (record) {
                toolId.value = record.id;
                toolKey.value = record.key || '';
                toolName.value = record.name || '';
                toolDescription.value = record.description || '';
                toolSchema.value = JSON.stringify(record.schema || {}, null, 4);
                toolActive.checked = record.active !== false;
                toolSort.value = record.sort_order ?? 0;
                toolKey.readOnly = true;
            } else {
                toolId.value = '';
                toolKey.value = '';
                toolName.value = '';
                toolDescription.value = '';
                toolSchema.value = '';
                toolActive.checked = true;
                toolSort.value = 0;
                toolKey.readOnly = false;
            }
        }

        function hideToolForm() {
            if (!toolFormArea) return;
            toolFormArea.style.display = 'none';
            toolForm.reset();
            toolId.value = '';
            toolKey.readOnly = false;
        }

        function loadTools() {
            if (!toolTbody) return;
            toolTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">' + _kbt('loading') + '</td></tr>';

            var fidParam = FID() ? '?fid=' + FID() : '';
            fetch(TOOLS_API_BASE + '/all' + fidParam, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    const tools = data.data || [];
                    toolTbody.innerHTML = '';

                    if (tools.length === 0) {
                        toolEmpty.style.display = 'block';
                        return;
                    }
                    toolEmpty.style.display = 'none';

                    tools.forEach(function (t) {
                        var tr = document.createElement('tr');
                        var activeIcon = t.active ? '✅' : '❌';
                        var toggleIcon = t.active ? '❌' : '✅';
                        var descPreview = (t.description || '').substring(0, 60) + ((t.description || '').length > 60 ? '...' : '');

                        tr.innerHTML =
                            '<td><code>' + escapeHtml(t.key) + '</code></td>' +
                            '<td>' + escapeHtml(t.name) + '</td>' +
                            '<td><small class="text-muted">' + escapeHtml(descPreview || _kbt('no_schema')) + '</small></td>' +
                            '<td class="text-center"><small class="text-muted">' + (t.sort_order ?? 0) + '</small></td>' +
                            '<td class="text-center">' + activeIcon + '</td>' +
                            '<td class="text-end">' +
                                '<button class="btn btn-sm btn-outline-primary me-1 btn-tool-edit" data-id="' + t.id + '">✏️</button>' +
                                '<button class="btn btn-sm btn-outline-warning me-1 btn-tool-toggle" data-id="' + t.id + '" data-active="' + (t.active ? '1' : '0') + '">' + toggleIcon + '</button>' +
                                '<button class="btn btn-sm btn-outline-danger btn-tool-delete" data-id="' + t.id + '">🗑</button>' +
                            '</td>';

                        toolTbody.appendChild(tr);
                    });

                    // Edit button handlers
                    toolTbody.querySelectorAll('.btn-tool-edit').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var id = parseInt(this.dataset.id);
                            fetch(TOOLS_API_BASE + '/' + id, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.data) showToolForm(data.data);
                                })
                                .catch(function () { alert(_kbt('load_error')); });
                        });
                    });

                    // Toggle active handler
                    toolTbody.querySelectorAll('.btn-tool-toggle').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm(_kbt('toggle_active_confirm'))) return;
                            var id = parseInt(this.dataset.id);
                            var newActive = this.dataset.active === '1' ? '0' : '1';
                            fetch(TOOLS_API_BASE + '/' + id, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ active: newActive === '1' }),
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.data) {
                                        loadTools();
                                    } else {
                                        alert(_kbt('save_error'));
                                    }
                                })
                                .catch(function () { alert(_kbt('save_error')); });
                        });
                    });

                    // Delete handler
                    toolTbody.querySelectorAll('.btn-tool-delete').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm(_kbt('delete_confirm'))) return;
                            var id = parseInt(this.dataset.id);
                            fetch(TOOLS_API_BASE + '/' + id, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.message) {
                                        loadTools();
                                    } else {
                                        alert(_kbt('delete_error'));
                                    }
                                })
                                .catch(function () { alert(_kbt('delete_error')); });
                        });
                    });
                })
                .catch(function () {
                    toolTbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + _kbt('load_error') + '</td></tr>';
                });
        }

        // ── Knowledge Base CRUD ──

        function showForm(record) {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
            btnAdd.style.display = 'none';

            // Determine default category key
            var defaultCat = 'general';
            var keys = Object.keys(window._kbCategoryMap);
            if (keys.length > 0) {
                defaultCat = keys[0];
            }

            // Load available tools into multi-select
            loadToolsSelect();

            if (record) {
                kbId.value = record.id;
                kbTitle.value = record.title || '';
                kbCategory.value = record.category || defaultCat;
                kbContent.value = record.content || '';
                kbActive.checked = record.active !== false;
                // Select linked tools
                var toolKeys = record.tool_keys || [];
                if (kbTools && toolKeys.length > 0) {
                    // Wait for tools to load, then select
                    var checkLoaded = setInterval(function () {
                        if (kbTools.options.length > 0 && kbTools.options[0].value !== '') {
                            clearInterval(checkLoaded);
                            Array.from(kbTools.options).forEach(function (opt) {
                                if (toolKeys.indexOf(opt.value) !== -1) {
                                    opt.selected = true;
                                }
                            });
                        }
                    }, 50);
                }
            } else {
                kbId.value = '';
                kbTitle.value = '';
                kbCategory.value = defaultCat;
                kbContent.value = '';
                kbActive.checked = true;
                if (kbTools) kbTools.selectedIndex = -1;
            }
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
            btnAdd.style.display = 'inline-block';
            form.reset();
            kbId.value = '';
        }

        function loadRecords(page) {
            page = page || currentPage;
            const params = new URLSearchParams();
            params.set('fid', FID() || '0');
            params.set('per_page', '20');
            params.set('page', String(page));

            if (lastCategory) {
                params.set('category', lastCategory);
            }

            fetch(API_BASE + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(data => {
                    renderRecords(data.data || [], data.meta || {});
                    updateBadge(data.meta?.total || 0);
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + _kbs('load_error') + '</td></tr>';
                });
        }

        function renderRecords(records, meta) {
            tbody.innerHTML = '';
            if (!records || records.length === 0) {
                emptyMsg.style.display = 'block';
                pagination.innerHTML = '';
                return;
            }
            emptyMsg.style.display = 'none';

            records.forEach(function (rec) {
                const tr = document.createElement('tr');
                const title = rec.title || '—';
                const contentPreview = (rec.content || '').substring(0, 80) + ((rec.content || '').length > 80 ? '...' : '');
                const catKey = rec.category || 'general';
                // Use category map for display name, fall back to key
                const catLabel = (window._kbCategoryMap && window._kbCategoryMap[catKey]) || catKey;
                const activeLabel = rec.active ? '✅' : '❌';
                const activeBtnLabel = rec.active ? '❌' : '✅';

                // Build tool keys badges
                var toolKeys = rec.tool_keys || [];
                var toolsHtml = '';
                if (toolKeys.length > 0) {
                    toolsHtml = toolKeys.map(function (k) {
                        return '<span class="badge bg-info me-1" style="font-size:0.7rem;">' + escapeHtml(k) + '</span>';
                    }).join('');
                } else {
                    toolsHtml = '<span class="text-muted" style="font-size:0.75rem;">—</span>';
                }

                tr.innerHTML =
                    '<td>' + escapeHtml(title) + '</td>' +
                    '<td><span class="badge" style="background:#a5b4fc;color:#020617;">' + escapeHtml(catLabel) + '</span></td>' +
                    '<td><small class="text-muted">' + escapeHtml(contentPreview) + '</small></td>' +
                    '<td style="max-width:140px;">' + toolsHtml + '</td>' +
                    '<td>' + activeLabel + '</td>' +
                    '<td class="text-end">' +
                        '<button class="btn btn-sm btn-outline-primary me-1 btn-kb-edit" data-id="' + rec.id + '">✏️</button>' +
                        '<button class="btn btn-sm btn-outline-warning me-1 btn-kb-toggle" data-id="' + rec.id + '" data-active="' + (rec.active ? '1' : '0') + '">' + activeBtnLabel + '</button>' +
                        '<button class="btn btn-sm btn-outline-danger btn-kb-delete" data-id="' + rec.id + '">🗑</button>' +
                    '</td>';

                tbody.appendChild(tr);
            });

            // Add event listeners
            tbody.querySelectorAll('.btn-kb-edit').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = parseInt(this.dataset.id);
                    fetch(API_BASE + '/' + id, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.data) showForm(data.data);
                        })
                        .catch(() => alert(_kbs('load_error')));
                });
            });

            tbody.querySelectorAll('.btn-kb-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm(_kbs('toggle_active_confirm'))) return;
                    const id = parseInt(this.dataset.id);
                    const newActive = this.dataset.active === '1' ? '0' : '1';
                    fetch(API_BASE + '/' + id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ active: newActive === '1' }),
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.data) {
                                loadRecords(currentPage);
                                loadTotalCount();
                            } else {
                                alert(_kbs('save_error'));
                            }
                        })
                        .catch(() => alert(_kbs('save_error')));
                });
            });

            tbody.querySelectorAll('.btn-kb-delete').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm(_kbs('delete_confirm'))) return;
                    const id = parseInt(this.dataset.id);
                    fetch(API_BASE + '/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.message) {
                                loadRecords(currentPage);
                                loadTotalCount();
                            } else {
                                alert(_kbs('delete_error'));
                            }
                        })
                        .catch(() => alert(_kbs('delete_error')));
                });
            });

            // Pagination
            renderPagination(meta);
        }

        function renderPagination(meta) {
            pagination.innerHTML = '';
            if (!meta || meta.last_page <= 1) return;

            const prev = document.createElement('button');
            prev.className = 'btn btn-sm btn-outline-secondary' + (meta.current_page <= 1 ? ' disabled' : '');
            prev.textContent = '←';
            prev.addEventListener('click', function () {
                if (meta.current_page > 1) {
                    currentPage = meta.current_page - 1;
                    loadRecords(currentPage);
                }
            });
            pagination.appendChild(prev);

            const pageInfo = document.createElement('span');
            pageInfo.className = 'btn btn-sm disabled';
            pageInfo.textContent = meta.current_page + ' / ' + meta.last_page;
            pagination.appendChild(pageInfo);

            const next = document.createElement('button');
            next.className = 'btn btn-sm btn-outline-secondary' + (meta.current_page >= meta.last_page ? ' disabled' : '');
            next.textContent = '→';
            next.addEventListener('click', function () {
                if (meta.current_page < meta.last_page) {
                    currentPage = meta.current_page + 1;
                    loadRecords(currentPage);
                }
            });
            pagination.appendChild(next);
        }

        function searchRecords() {
            const query = searchInput.value.trim();
            lastCategory = filterCategory.value;
            lastSearchQuery = query;

            if (!query && !lastCategory) {
                currentPage = 1;
                loadRecords(1);
                return;
            }

            if (query.length < 2 && query.length > 0) return;

            const params = {
                fid: FID() || '0',
                query: query || '',
                limit: 50,
            };

            fetch(API_BASE + '/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(params),
            })
                .then(r => r.json())
                .then(data => {
                    const items = data.data || [];
                    // Apply category filter client-side if set
                    let filtered = items;
                    if (lastCategory) {
                        filtered = items.filter(function (r) { return r.category === lastCategory; });
                    }
                    renderRecords(filtered, { total: filtered.length, current_page: 1, last_page: 1 });
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + _kbs('load_error') + '</td></tr>';
                });
        }

        function loadTotalCount() {
            fetch(API_BASE + '?fid=' + (FID() || '0') + '&per_page=1', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(data => {
                    const total = data.meta?.total || 0;
                    updateBadge(total);
                })
                .catch(() => {});
        }

        function updateBadge(count) {
            if (badge) badge.textContent = String(count);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        // ── Event Listeners ──

        // Modal events
        modal.addEventListener('show.bs.modal', function () {
            // Set fid from session (available in page context)
            const fidEl = document.getElementById('kb-fid');
            // Try to find fid from page context
            const pageFid = document.querySelector('[data-session-fid]')?.dataset?.sessionFid ||
                           window._pageFid ||
                           document.querySelector('input[name="fid"]')?.value ||
                           '';

            if (fidEl) fidEl.value = pageFid;

            hideForm();
            if (catArea) catArea.style.display = 'none';
            if (toolsArea) toolsArea.style.display = 'none';
            currentPage = 1;
            lastSearchQuery = '';
            lastCategory = '';
            searchInput.value = '';
            filterCategory.value = '';
            // Reset tab buttons
            document.querySelectorAll('.btn-kb-tab').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.category === '');
            });
            loadRecords(1);
            loadCategories();
        });

        btnAdd.addEventListener('click', function () {
            showForm(null);
        });

        // Manage categories button
        if (btnManageCat) {
            btnManageCat.addEventListener('click', function () {
                if (catArea && catArea.style.display === 'block') {
                    hideCategoryArea();
                } else {
                    if (toolsArea) toolsArea.style.display = 'none';
                    showCategoryArea();
                }
            });
        }

        // Manage tools button
        if (btnManageTools) {
            btnManageTools.addEventListener('click', function () {
                if (toolsArea && toolsArea.style.display === 'block') {
                    hideToolArea();
                } else {
                    if (catArea) catArea.style.display = 'none';
                    showToolArea();
                }
            });
        }

        btnCancel.addEventListener('click', hideForm);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = kbId.value;
            const isUpdate = id !== '';
            const method = isUpdate ? 'PUT' : 'POST';
            const url = isUpdate ? API_BASE + '/' + id : API_BASE;

            // Collect selected tool keys
            var selectedTools = [];
            if (kbTools) {
                Array.from(kbTools.selectedOptions).forEach(function (opt) {
                    if (opt.value) selectedTools.push(opt.value);
                });
            }

            const body = {
                fid: parseInt(FID()) || 0,
                title: kbTitle.value.trim(),
                category: kbCategory.value,
                content: kbContent.value.trim(),
                tool_keys: selectedTools,
                active: kbActive.checked,
            };

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.data || data.message) {
                        hideForm();
                        loadRecords(1);
                        loadTotalCount();
                    } else {
                        alert(data.message || _kbs('save_error'));
                    }
                })
                .catch(() => alert(_kbs('save_error')));
        });

        btnRefresh.addEventListener('click', function () {
            currentPage = 1;
            lastSearchQuery = '';
            lastCategory = '';
            searchInput.value = '';
            filterCategory.value = '';
            loadRecords(1);
        });

        btnSearch.addEventListener('click', function () {
            currentPage = 1;
            searchRecords();
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                currentPage = 1;
                searchRecords();
            }
        });

        filterCategory.addEventListener('change', function () {
            currentPage = 1;
            lastCategory = this.value;
            if (lastSearchQuery) {
                searchRecords();
            } else {
                loadRecords(1);
            }
        });

        // ── Tab click handlers ──
        document.querySelectorAll('.btn-kb-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cat = this.dataset.category || '';
                // Update active state on tabs
                document.querySelectorAll('.btn-kb-tab').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                // Update filter dropdown to match
                filterCategory.value = cat;
                currentPage = 1;
                lastCategory = cat;
                if (lastSearchQuery) {
                    searchRecords();
                } else {
                    loadRecords(1);
                }
            });
        });

        // ── Category Management Event Listeners ──

        if (btnCatAdd) {
            btnCatAdd.addEventListener('click', function () {
                showCategoryForm(null);
            });
        }

        if (btnCatBack) {
            btnCatBack.addEventListener('click', hideCategoryArea);
        }

        if (btnCatFormCancel) {
            btnCatFormCancel.addEventListener('click', hideCategoryForm);
        }

        if (catForm) {
            catForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const id = catId.value;
                const isUpdate = id !== '';
                const method = isUpdate ? 'PUT' : 'POST';
                const url = isUpdate ? CATEGORY_API_BASE + '/' + id : CATEGORY_API_BASE;

                const body = {
                    fid: parseInt(FID()) || null,
                    key: catKey.value.trim(),
                    name: catName.value.trim(),
                    sort_order: parseInt(catSort.value) || 0,
                    active: catActive.checked,
                };

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.data || data.message) {
                            hideCategoryForm();
                            loadCategoryList();
                            loadCategories();
                        } else {
                            alert(data.message || _kbs('save_error'));
                        }
                    })
                    .catch(() => alert(_kbs('save_error')));
            });
        }

        // ── Tools Event Listeners ──

        if (btnToolAdd) {
            btnToolAdd.addEventListener('click', function () {
                showToolForm(null);
            });
        }

        if (btnToolBack) {
            btnToolBack.addEventListener('click', hideToolArea);
        }

        if (btnToolFormCancel) {
            btnToolFormCancel.addEventListener('click', hideToolForm);
        }

        if (toolForm) {
            toolForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const id = toolId.value;
                const isUpdate = id !== '';
                const method = isUpdate ? 'PUT' : 'POST';
                const url = isUpdate ? TOOLS_API_BASE + '/' + id : TOOLS_API_BASE;

                // Parse schema JSON
                var schemaRaw = toolSchema.value.trim();
                var schemaObj;
                try {
                    schemaObj = JSON.parse(schemaRaw);
                } catch (err) {
                    alert(_kbt('save_error') + ': ' + _kbt('invalid_json'));
                    return;
                }

                const body = {
                    fid: parseInt(FID()) || null,
                    key: toolKey.value.trim(),
                    name: toolName.value.trim(),
                    description: toolDescription.value.trim(),
                    schema: schemaObj,
                    active: toolActive.checked,
                    sort_order: parseInt(toolSort.value) || 0,
                };

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.data || data.message) {
                            hideToolForm();
                            loadTools();
                            alert(_kbt('saved'));
                        } else {
                            alert(data.message || _kbt('save_error'));
                        }
                    })
                    .catch(function () { alert(_kbt('save_error')); });
            });
        }
    })();
});
</script>
@endsection
