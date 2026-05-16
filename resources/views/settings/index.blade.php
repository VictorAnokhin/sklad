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
                    <span class="badge bg-primary" id="badge-projects">{{ count($projects ?? []) }}</span>
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
            <div class="glass-card h-100 border-info setting-card" data-bs-toggle="modal" data-bs-target="#modalCatalog">
                <div class="card-body text-center">
                    <h5 class="card-title">🌐 {{ __('settings.languages_regions') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.languages_regions_desc') }}</p>
                    <span class="badge bg-info text-dark" id="badge-field-total">{{ $fieldTranslationsCount ?? 0 }}</span>
                    <div class="small text-muted mt-2">
                        {{ __('settings.catalog.line_catalog') }} <span id="badge-catalog">{{ $fieldCatalogTopCount ?? 0 }}</span>
                        | {{ __('settings.catalog.line_city') }} <span id="badge-city">{{ $fieldCityCount ?? 0 }}</span>
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
            <div class="glass-card h-100 setting-card" data-bs-toggle="modal" data-bs-target="#modalTaxReceipts" style="border-color: #28a745;">
                <div class="card-body text-center">
                    <h5 class="card-title">🧾 {{ __('settings.cards.tax_receipts.title') }}</h5>
                    <p class="card-text text-muted">{{ __('settings.cards.tax_receipts.description') }}</p>
                    <span class="badge bg-success" id="badge-tax-receipts">0</span>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 {{ __('settings.common.save') }}</button>
                        <button type="button" class="btn btn-secondary" id="btn-account-cancel">{{ __('settings.common.cancel') }}</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="account-list-area">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h6 class="mb-3">{{ __('settings.accounts.accounts_heading') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('settings.accounts.th_code') }}</th>
                                        <th>{{ __('settings.accounts.th_name') }}</th>
                                        <th>{{ __('settings.accounts.th_type') }}</th>
                                        <th>{{ __('settings.accounts.th_parent') }}</th>
                                        <th class="text-end">{{ __('settings.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="accounts-tbody"></tbody>
                            </table>
                        </div>
                        <p class="text-center text-muted" id="accounts-empty-msg" style="display:none">{{ __('settings.accounts.empty_accounts') }}</p>
                    </div>

                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">{{ __('settings.accounts.bindings_heading') }}</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-payment-bindings-reload">{{ __('settings.common.refresh') }}</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
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

<div class="modal fade" id="modalCatalog" tabindex="-1" aria-labelledby="modalCatalogLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="modal-title" id="modalCatalogLabel">🌐 {{ __('settings.languages_regions') }}</h5>
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
                            <input type="file" class="form-control" id="catalog-file-upload">
                            <div class="form-text" id="catalog-file-current">{{ __('settings.catalog_modal.file_not_uploaded') }}</div>
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
                                <th id="" class="catalog-description-cell">{{ __('settings.catalog_modal.th_description') }}</th>
                                <th id="catalog-flags-head" class="catalog-flags-cell">{{ __('settings.catalog_modal.th_flags') }}</th>
                                <th id="catalog-description-head" class="catalog-children-cell">{{ __('settings.catalog_modal.th_subcategories') }}</th>
                                <th class="catalog-actions-cell">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="catalog-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="catalog-empty-msg" style="display:none">{{ __('settings.field_modes.catalog.empty') }}</p>
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
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-outline-danger" id="btn-project-delete" style="display:none;">🗑 Видалити</button>
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
                                <th>Назва</th>
                                <th>Email</th>
                                <th>Телефон</th>
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
                    <input type="hidden" id="form-foto-existing" value="">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="form-name" class="form-label">Назва <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="form-name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="form-color" class="form-label">Колір</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color" id="form-color-picker" value="#ffffff">
                                <input type="text" class="form-control" id="form-color" placeholder="#hex">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" id="form-visibility-row" style="display:none;">
                            <label class="form-label d-block">Видимість</label>
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="form-visibility-checkbox">
                                <label class="form-check-label" for="form-visibility-checkbox" id="form-visibility-label">Видимий</label>
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
                        </div>
                        <div class="form-text">Для яких документів доступний цей вид платежу.</div>
                    </div>
                    <div id="form-office-fields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="form-phone" class="form-label">Телефон</label>
                                <input type="text" class="form-control" id="form-phone" placeholder="+380...">
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
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Назва</th>
                            <th id="crud-color-column">Колір</th>
                            <th id="crud-status-column">Статус</th>
                            <th id="crud-phone-column" style="display:none;">Телефон</th>
                            <th id="crud-address-column" style="display:none;">Адреса</th>
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

<!-- Модальное окно для управления чеками налоговой -->
<div class="modal fade" id="modalTaxReceipts" tabindex="-1" aria-labelledby="modalTaxReceiptsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalTaxReceiptsLabel">🧾 Чеки податкової інспекції України</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="btn-tax-receipt-add">+ Додати чек</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body" id="tax-receipt-form-area" style="display:none;">
                <div class="alert alert-info">
                    <strong>⚙️ Налаштування API:</strong> Для реєстрації чеків потрібно налаштувати доступ до API податкової інспекції Украї (ДПІ). 
                    <a href="#" id="btn-tax-api-settings" class="alert-link">Налаштувати</a>
                </div>
                <form id="tax-receipt-form">
                    <input type="hidden" id="tax-receipt-id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Тип документа <span class="text-danger">*</span></label>
                        <select class="form-select" id="tax-receipt-doc-type" required>
                            <option value="">-- оберіть тип --</option>
                            <option value="PO">Прихід грошей (PO)</option>
                            <option value="RO">Видача грошей (RO)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ID документа <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tax-receipt-document-id" placeholder="ID документа" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ІПН платника <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tax-receipt-taxpayer-id" placeholder="ІПН" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Касир</label>
                            <input type="text" class="form-control" id="tax-receipt-cashier-name" placeholder="ПІБ касира">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Сума (грн) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="tax-receipt-amount" placeholder="0.00" step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Опис товарів/послуг</label>
                        <textarea class="form-control" id="tax-receipt-description" rows="3" placeholder="Опис..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Додати чек</button>
                        <button type="button" class="btn btn-secondary" id="btn-tax-receipt-cancel">Скасувати</button>
                    </div>
                </form>
            </div>

            <div class="modal-body" id="tax-receipt-list-area">
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-success" id="btn-tax-register-pending">📤 Зареєструвати усі</button>
                    <button class="btn btn-sm btn-outline-info" id="btn-tax-reload">🔄 Оновити</button>
                </div>

                <div class="alert alert-secondary" id="tax-receipt-stats" style="display:none;">
                    <strong>Статистика:</strong>
                    Всього: <span id="tax-stat-total">0</span> | 
                    Зареєстровано: <span id="tax-stat-registered">0</span> | 
                    Очікування: <span id="tax-stat-pending">0</span> | 
                    Помилки: <span id="tax-stat-failed">0</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>№ Чека</th>
                                <th>Документ</th>
                                <th>Сума (грн)</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th class="text-end">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="tax-receipts-tbody"></tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="tax-receipts-empty-msg" style="display:none">Чеків ще немає</p>
            </div>
        </div>
    </div>
</div>

<!-- API Settings Modal for Tax Receipts -->
<div class="modal fade" id="modalTaxApiSettings" tabindex="-1" aria-labelledby="modalTaxApiSettingsLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTaxApiSettingsLabel">⚙️ Налаштування API ДПІ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>📌 Важливо:</strong> Отримайте облікові дані від податкової інспекції Украї для API доступу.
                </div>

                <form id="tax-api-settings-form">
                    <div class="mb-3">
                        <label class="form-label">API URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="tax-api-url" placeholder="https://api.tax.gov.ua" value="https://api.tax.gov.ua" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="tax-api-key" placeholder="Введіть API ключ" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Secret Key <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="tax-api-secret" placeholder="Введіть секретний ключ" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 Зберегти</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для управления базой знаний -->
<div class="modal fade" id="modalKnowledgeBase" tabindex="-1" aria-labelledby="modalKnowledgeBaseLabel" aria-hidden="true" data-session-fid="{{ $fid ?? '' }}" data-session-firma="{{ session('firma', '') }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title" id="modalKnowledgeBaseLabel">🧠 {{ __('settings.cards.knowledge_base.modal_title') }}</h5>
                <button type="button" class="btn btn-sm btn-primary" id="btn-kb-add">+ {{ __('settings.common.add') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btn-kb-manage-categories" title="{{ __('settings.knowledge_base.manage_categories') }}">⚙️ <span class="d-none d-md-inline">{{ __('settings.knowledge_base.manage_categories') }}</span></button>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="{{ __('settings.common.close') }}"></button>
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
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">💾 {{ __('settings.common.save') }}</button>
                        <button type="button" class="btn btn-secondary" id="btn-kb-cancel">{{ __('settings.common.cancel') }}</button>
                    </div>
                </form>
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
                                <th>{{ __('settings.knowledge_base.category_key_th') }}</th>
                                <th>{{ __('settings.knowledge_base.category_name_th') }}</th>
                                <th class="text-center">{{ __('settings.knowledge_base.category_sort_th') }}</th>
                                <th class="text-center">{{ __('settings.knowledge_base.th_active') }}</th>
                                <th class="text-end" style="width:140px;">{{ __('settings.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="kb-category-tbody">
                            <tr><td colspan="5" class="text-center text-muted">{{ __('settings.common.loading') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-center text-muted" id="kb-category-empty" style="display:none">{{ __('settings.knowledge_base.no_categories') }}</p>
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
        const addBtn = document.getElementById('btn-project-add');
        const cancelBtn = document.getElementById('btn-project-cancel');
        const deleteBtn = document.getElementById('btn-project-delete');
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
        deleteBtn?.addEventListener('click', () => {
            const id = document.getElementById('project-id').value;
            if (!id) return;
            deleteProject(id);
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
                loadProjects();
            })
            .catch((error) => alert(error?.message || _ts('js.network_error')));
        });

        function loadProjects() {
            fetch('/settings/projects')
                .then(async (response) => {
                    const data = await parseResponseData(response);
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) {
                        throw new Error(extractErrorMessage(data, _ts('js.load_error')));
                    }

                    renderProjects(Array.isArray(data) ? data : []);
                })
                .catch((error) => {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${escapeHtml(error?.message || _ts('js.load_error'))}</td></tr>`;
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
                const projectEmail = item.email
                    ? `<a href="mailto:${escapeHtml(item.email)}">${escapeHtml(item.email)}</a>`
                    : '—';
                const projectPhone = item.phone
                    ? `<a href="tel:${escapeHtml(item.phone)}">${escapeHtml(item.phone)}</a>`
                    : '—';
                const projectUrl = item.url
                    ? `<a href="${escapeHtml(item.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(item.url)}</a>`
                    : '—';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id ?? ''}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(item.name || '')}</div>
                        <div class="company-meta">${escapeHtml(item.description || '')}</div>
                    </td>
                    <td>${projectEmail}</td>
                    <td>
                        <div>${projectPhone}</div>
                        <div class="small text-muted">${projectUrl}</div>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">${escapeHtml(_ts('crud.edit'))}</button>
                        ${item.can_delete ? `<button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">${escapeHtml(_ts('crud.delete'))}</button>` : `<span class="text-muted small">${escapeHtml(_ts('js.no_delete_permission'))}</span>`}
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
        const deleteBtn = document.getElementById('btn-delete');
        const colorPicker = document.getElementById('form-color-picker');
        const colorInput = document.getElementById('form-color');
        const statusRow = document.getElementById('form-status-row');
        const statusLabel = document.getElementById('form-status-label');
        const statusSelect = document.getElementById('form-status');
        const statusHelp = document.getElementById('form-status-help');
        const visibilityRow = document.getElementById('form-visibility-row');
        const visibilityCheckbox = document.getElementById('form-visibility-checkbox');
        const visibilityLabel = document.getElementById('form-visibility-label');
        const statusColumn = document.getElementById('crud-status-column');
        const docRow = document.getElementById('form-doc-row');
        const docColumn = document.getElementById('crud-doc-column');
        const officeFields = document.getElementById('form-office-fields');
        const phoneInput = document.getElementById('form-phone');
        const addressInput = document.getElementById('form-address');
        const googleMapInput = document.getElementById('form-google-map');
        const fotoExistingInput = document.getElementById('form-foto-existing');
        const fotoFileInput = document.getElementById('form-foto-file');
        const colorColumn = document.getElementById('crud-color-column');
        const phoneColumn = document.getElementById('crud-phone-column');
        const addressColumn = document.getElementById('crud-address-column');
        const docCheckboxes = [
            document.getElementById('form-doc-po'),
            document.getElementById('form-doc-ppo'),
            document.getElementById('form-doc-ro'),
            document.getElementById('form-doc-deposit'),
            document.getElementById('form-doc-zp'),
            document.getElementById('form-doc-pro'),
        ];

        let currentType = '';

        fotoFileInput?.addEventListener('change', () => {
            updateImagePreview(fotoFileInput, 'form-foto-preview', 'form-foto-preview-wrap');
        });

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
            resetOfficeFields();
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
                vision: currentType === 'sklads'
                    ? (visibilityCheckbox.checked ? '1' : '0')
                    : '1',
            };

            if (currentType === 'reestr') {
                payload.doc = getDocFlags();
            }

            if (!payload.name) return;

            const request = currentType === 'sklads'
                ? submitOfficeForm(id, payload)
                : fetch(id ? `/settings/api/${id}` : '/settings/api', {
                    method: id ? 'PUT' : 'POST',
                    headers: {
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
                    ? escapeHtml(item.doc_label || _ts('crud.all_documents'))
                    : '';
                const addressHtml = currentType === 'sklads'
                    ? escapeHtml(item.address || '—')
                    : '';
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
                    <td>${item.id}</td>
                    <td>${escapeHtml(item.name || '')}</td>
                    ${currentType === 'sklads' ? '' : `<td>${colorHtml}</td>`}
                    <td>${statusLabel}</td>
                    ${currentType === 'sklads' ? `<td>${addressHtml}</td>` : ''}
                    ${currentType === 'reestr' ? `<td>${docHtml}</td>` : ''}
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-id="${item.id}">${_ts('crud.edit')}</button>
                        <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="${item.id}">${_ts('crud.delete')}</button>
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
                    visibilityCheckbox.checked = String(item.vision ?? '1') === '1';
                    visibilityLabel.textContent = visibilityCheckbox.checked ? _ts('crud.visible') : _ts('crud.hidden');
                    setDocFlags(item.doc || '');
                    phoneInput.value = item.phone || '';
                    addressInput.value = item.address || '';
                    googleMapInput.value = item.google_map || '';
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
                });
        }

        function showForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
            deleteBtn.style.display = document.getElementById('form-id').value ? '' : 'none';
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
            deleteBtn.style.display = 'none';
        }

        function configureStatusField() {
            const isReestr = currentType === 'reestr';
            const isOffice = currentType === 'sklads';
            docRow.style.display = isReestr ? 'block' : 'none';
            docColumn.style.display = isReestr ? '' : 'none';
            officeFields.style.display = isOffice ? 'block' : 'none';
            visibilityRow.style.display = isOffice ? '' : 'none';
            statusRow.style.display = isOffice ? 'none' : 'block';
            colorColumn.style.display = isOffice ? 'none' : '';
            phoneColumn.style.display = 'none';
            addressColumn.style.display = isOffice ? '' : 'none';

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

        function getDocFlags() {
            return docCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value)
                .join(',');
        }

        function submitOfficeForm(id, payload) {
            const formData = new FormData();
            formData.append('type', payload.type);
            formData.append('name', payload.name);
            formData.append('color', payload.color);
            formData.append('status', payload.status);
            formData.append('vision', payload.vision);
            formData.append('phone', phoneInput.value.trim());
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
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });
        }

        function resetOfficeFields() {
            phoneInput.value = '';
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
            if (currentType === 'sklads') {
                count += 1;
            }
            if (currentType === 'reestr') {
                count += 1;
            }
            return count;
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
                    alert(data.message || _ts('js.save_error_account'));
                    return;
                }

                hideAccountForm();
                loadAccounts();
                loadBindings();
            })
            .catch(() => alert(_ts('js.network_error')));
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
                    tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
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
        const newsCatalogSelect = document.getElementById('catalog-news-catalog');
        const newsMap = new Map((newsOptions || []).map((item) => [String(item.id), item.title]));

        hydrateNewsSelect(newsCatalogSelect, newsOptions || []);

        let currentKeyfield = 'catalog';
        let currentParentId = '0';
        const fieldModeConfig = window.SettingsI18n.field_modes || {};
        let breadcrumb = [{ id: 0, name: fieldModeConfig.catalog.root }];

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

            fetch(id ? `/settings/fields/${id}` : '/settings/fields', {
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
            fetch(`/settings/fields?keyfield=${encodeURIComponent(currentKeyfield)}&parent_id=${encodeURIComponent(targetParentId)}`)
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
                const childLabel = currentKeyfield === 'catalog' && item.children_count > 0
                    ? `<span class="badge bg-info text-dark">${item.children_count}</span>`
                    : '<span class="text-muted">0</span>';
                const flags = fieldModeConfig[currentKeyfield].showExtra ? `
                    <div>${item.visible === '1' ? `<span class="badge bg-success">${escapeHtml(_ts('catalog_modal.badge_visible'))}</span>` : `<span class="badge bg-secondary">${escapeHtml(_ts('catalog_modal.badge_hidden'))}</span>`}</div>
                    <div class="mt-1">${item.firstpage === '1' ? `<span class="badge bg-warning text-dark">${escapeHtml(_ts('catalog_modal.badge_firstpage'))}</span>` : `<span class="badge bg-light text-dark">${escapeHtml(_ts('catalog_modal.badge_normal'))}</span>`}</div>
                ` : '<span class="text-muted">—</span>';
                const description = fieldModeConfig[currentKeyfield].showExtra ? `
                    <div><strong>${escapeHtml(_ts('catalog_modal.label_link'))}</strong> ${escapeHtml(shortText(item.link || '—'))}</div>
                    <div><strong>${escapeHtml(_ts('catalog_modal.label_article'))}</strong> ${escapeHtml(getNewsTitle(item.news_catalog_id))}</div>
                    <div><strong>${escapeHtml(_ts('catalog_modal.label_file'))}</strong> ${item.foto1_url ? `<a href="${escapeHtml(item.foto1_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(_ts('catalog_modal.open_file'))}</a>` : '—'}</div>
                    <div>${escapeHtml(shortText(item.description_ru || '—'))}</div>
                    <div class="catalog-meta">UA: ${escapeHtml(shortText(item.description_ua || '—'))}</div>
                    <div class="catalog-meta">EN: ${escapeHtml(shortText(item.description_en || '—'))}</div>
                ` : '<span class="text-muted">—</span>';
                const openButton = currentKeyfield === 'catalog'
                    ? `<button class="btn btn-sm btn-outline-secondary action-btn" data-action="open" data-id="${item.id}">📂</button>`
                    : '';
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

        function editCategory(id) {
            fetch(`/settings/fields/${id}?keyfield=${encodeURIComponent(currentKeyfield)}`)
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
                    document.getElementById('catalog-file-current').innerHTML = data.foto1_url
                        ? `${escapeHtml(_ts('catalog_modal.file_current'))} <a href="${escapeHtml(data.foto1_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(_ts('catalog_modal.open_file'))}</a>`
                        : escapeHtml(_ts('catalog_modal.file_not_uploaded'));
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
            document.getElementById('catalog-news-catalog').value = '';
            document.getElementById('catalog-file-path').value = '';
            document.getElementById('catalog-file-upload').value = '';
            document.getElementById('catalog-file-current').textContent = _ts('catalog_modal.file_not_uploaded');
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
            if (childrenHead) childrenHead.textContent = config.allowChildren ? _ts('crud.column_subcategories') : _ts('crud.column_records');
            if (breadcrumbBox) breadcrumbBox.style.display = config.allowChildren ? '' : 'none';
            if (backBtn) backBtn.style.display = 'none';
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

    // ── Tax Receipts Management ────────────────────────────────────────────

    (() => {
        const modal = document.getElementById('modalTaxReceipts');
        const apiSettingsModal = document.getElementById('modalTaxApiSettings');
        const form = document.getElementById('tax-receipt-form');
        const listArea = document.getElementById('tax-receipt-list-area');
        const formArea = document.getElementById('tax-receipt-form-area');
        const tbody = document.getElementById('tax-receipts-tbody');
        const emptyMsg = document.getElementById('tax-receipts-empty-msg');
        const badge = document.getElementById('badge-tax-receipts');
        const btnAdd = document.getElementById('btn-tax-receipt-add');
        const btnCancel = document.getElementById('btn-tax-receipt-cancel');
        const btnRegisterPending = document.getElementById('btn-tax-register-pending');
        const btnReload = document.getElementById('btn-tax-reload');
        const btnApiSettings = document.getElementById('btn-tax-api-settings');
        const apiForm = document.getElementById('tax-api-settings-form');

        if (!modal) return;

        modal.addEventListener('show.bs.modal', () => {
            hideForm();
            loadTaxReceipts();
            loadStatistics();
        });

        btnAdd.addEventListener('click', () => {
            showForm();
        });

        btnCancel.addEventListener('click', hideForm);
        btnReload.addEventListener('click', loadTaxReceipts);

        btnRegisterPending.addEventListener('click', () => {
            if (!confirm(_ts('js.register_all_receipts_confirm'))) return;
            
            fetch('/settings/tax-receipts/register-pending', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    alert(`${_ts('js.bulk_register_done')} ${data.registered}, ${_ts('js.bulk_register_errors')} ${data.failed}`);
                    loadTaxReceipts();
                    loadStatistics();
                })
                .catch(e => alert(_ts('js.error_with_message') + e.message));
        });

        btnApiSettings.addEventListener('click', () => {
            new (window.bootstrap.Modal)(apiSettingsModal).show();
        });

        apiForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const data = {
                api_key: document.getElementById('tax-api-key').value,
                secret_key: document.getElementById('tax-api-secret').value,
                base_url: document.getElementById('tax-api-url').value,
            };

            fetch('/settings/tax-receipts/settings', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(_ts('js.settings_saved'));
                        new (window.bootstrap.Modal)(apiSettingsModal).hide();
                    }
                })
                .catch(e => alert(_ts('js.error_with_message') + e.message));
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const data = {
                document_type: document.getElementById('tax-receipt-doc-type').value,
                document_id: document.getElementById('tax-receipt-document-id').value,
                taxpayer_id: document.getElementById('tax-receipt-taxpayer-id').value,
                cashier_name: document.getElementById('tax-receipt-cashier-name').value || 'Unknown',
                amount: parseFloat(document.getElementById('tax-receipt-amount').value) || 0,
                goods_description: document.getElementById('tax-receipt-description').value,
            };

            fetch('/settings/tax-receipts', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(_ts('js.receipt_added'));
                        resetForm();
                        hideForm();
                        loadTaxReceipts();
                        loadStatistics();
                    } else {
                        alert(_ts('js.error_with_message') + (data.error || _ts('js.unknown_error')));
                    }
                })
                .catch(e => alert(_ts('js.error_with_message') + e.message));
        });

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;

            const id = btn.dataset.id;
            const action = btn.dataset.action;

            if (action === 'delete') {
                if (!confirm(_ts('js.delete_receipt_confirm'))) return;
                fetch(`/settings/tax-receipts/${id}`, { method: 'DELETE' })
                    .then(r => r.json())
                    .then(() => {
                        loadTaxReceipts();
                        loadStatistics();
                    })
                    .catch(e => alert(_ts('js.error_with_message') + e.message));
            } else if (action === 'register') {
                fetch(`/settings/tax-receipts/${id}/register`, { method: 'POST' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert(_ts('js.receipt_registered') + data.tax_receipt_id);
                        } else {
                            alert(_ts('js.registration_error') + data.error);
                        }
                        loadTaxReceipts();
                        loadStatistics();
                    })
                    .catch(e => alert(_ts('js.error_with_message') + e.message));
            }
        });

        function loadTaxReceipts() {
            fetch('/settings/tax-receipts')
                .then(r => r.json())
                .then(data => {
                    renderReceipts(data.data);
                    badge.textContent = data.total;
                })
                .catch(e => {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-danger">${escapeHtml(_ts('js.load_error'))}</td></tr>`;
                });
        }

        function loadStatistics() {
            fetch('/settings/tax-receipts/statistics')
                .then(r => r.json())
                .then(data => {
                    const stats = data.data;
                    document.getElementById('tax-stat-total').textContent = stats.total;
                    document.getElementById('tax-stat-registered').textContent = stats.registered;
                    document.getElementById('tax-stat-pending').textContent = stats.pending;
                    document.getElementById('tax-stat-failed').textContent = stats.failed;
                    document.getElementById('tax-receipt-stats').style.display = 'block';
                });
        }

        function renderReceipts(items) {
            tbody.innerHTML = '';
            if (!items.length) {
                emptyMsg.style.display = 'block';
                return;
            }

            emptyMsg.style.display = 'none';
            items.forEach(item => {
                const statusBadge =
                    item.status === 'registered' ? `<span class="badge bg-success">${escapeHtml(_ts('js.tax_status_registered'))}</span>` :
                    item.status === 'pending' ? `<span class="badge bg-warning text-dark">${escapeHtml(_ts('js.tax_status_pending'))}</span>` :
                    `<span class="badge bg-danger">${escapeHtml(_ts('js.tax_status_failed'))}</span>`;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-semibold">${escapeHtml(item.receipt_number)}</td>
                    <td>${escapeHtml(item.document_type)} №${escapeHtml(item.document_id)}</td>
                    <td class="text-end">${(item.amount || 0).toFixed(2)}</td>
                    <td>${statusBadge}</td>
                    <td>${item.registered_at ? item.registered_at : '—'}</td>
                    <td class="text-end">
                        ${item.status === 'pending' ? `<button class="btn btn-sm btn-outline-success" data-action="register" data-id="${item.id}">${escapeHtml(_ts('js.tax_btn_register'))}</button>` : ''}
                        <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${item.id}">🗑</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function resetForm() {
            form.reset();
            document.getElementById('tax-receipt-id').value = '';
        }

        function showForm() {
            formArea.style.display = 'block';
            listArea.style.display = 'none';
            resetForm();
        }

        function hideForm() {
            formArea.style.display = 'none';
            listArea.style.display = 'block';
        }
    })();

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

        const API_BASE = '/api/ai/knowledge-base';
        const CATEGORY_API_BASE = '/api/ai/knowledge-base/categories';
        const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
        const FID = () => document.getElementById('kb-fid')?.value || '';
        const FIRMA = () => document.getElementById('kb-firma')?.value || '';

        // Category key → name map, populated from API
        window._kbCategoryMap = window._kbCategoryMap || {};

        let currentPage = 1;
        let lastSearchQuery = '';
        let lastCategory = '';

        if (!modal) return;

        // Inject hidden fid/firma inputs for reference
        if (!document.getElementById('kb-fid')) {
            const hf = document.createElement('input');
            hf.type = 'hidden'; hf.id = 'kb-fid';
            document.body.appendChild(hf);
        }
        if (!document.getElementById('kb-firma')) {
            const hf = document.createElement('input');
            hf.type = 'hidden'; hf.id = 'kb-firma';
            document.body.appendChild(hf);
        }

        function _kbs(path) {
            const v = String(path || '').split('.').reduce(function (acc, key) {
                return acc && acc[key] !== undefined ? acc[key] : undefined;
            }, window.SettingsI18n?.knowledge_base || {});
            return v !== undefined && v !== null ? v : path;
        }

        /**
         * Load active categories from API and populate selects + category map.
         */
        function loadCategories() {
            return fetch(CATEGORY_API_BASE, {
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
         * Load all categories (including inactive) into the management table.
         */
        function loadCategoryList() {
            if (!catTbody) return;
            catTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">' + _kbs('loading') + '</td></tr>';

            fetch(CATEGORY_API_BASE + '/all', {
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

            if (record) {
                kbId.value = record.id;
                kbTitle.value = record.title || '';
                kbCategory.value = record.category || defaultCat;
                kbContent.value = record.content || '';
                kbActive.checked = record.active !== false;
            } else {
                kbId.value = '';
                kbTitle.value = '';
                kbCategory.value = defaultCat;
                kbContent.value = '';
                kbActive.checked = true;
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

                tr.innerHTML =
                    '<td>' + escapeHtml(title) + '</td>' +
                    '<td><span class="badge" style="background:#a5b4fc;color:#020617;">' + escapeHtml(catLabel) + '</span></td>' +
                    '<td><small class="text-muted">' + escapeHtml(contentPreview) + '</small></td>' +
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
            // Set fid/firma from session (available in page context)
            const fidEl = document.getElementById('kb-fid');
            const firmaEl = document.getElementById('kb-firma');
            // Try to find fid from page context
            const pageFid = document.querySelector('[data-session-fid]')?.dataset?.sessionFid ||
                           window._pageFid ||
                           document.querySelector('input[name="fid"]')?.value ||
                           '';

            const pageFirma = document.querySelector('[data-session-firma]')?.dataset?.sessionFirma ||
                             window._pageFirma || '';

            if (fidEl) fidEl.value = pageFid;
            if (firmaEl) firmaEl.value = pageFirma;

            hideForm();
            if (catArea) catArea.style.display = 'none';
            currentPage = 1;
            lastSearchQuery = '';
            lastCategory = '';
            searchInput.value = '';
            filterCategory.value = '';
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
                    showCategoryArea();
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

            const body = {
                fid: parseInt(FID()) || 0,
                title: kbTitle.value.trim(),
                category: kbCategory.value,
                content: kbContent.value.trim(),
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
    })();
});
</script>
@endsection
