{{-- top_reklama.blade.php --}}
@php
  $authUser = \Illuminate\Support\Facades\Auth::user();
  $isAuthenticated = \Illuminate\Support\Facades\Auth::check();
  $userProjectId = (int) (($authUser->firma ?? 0) ?: ($authUser->idfirma ?? 0) ?: ($authUser->fid ?? 0));
  $userProjectIds = collect();

  if (
    $isAuthenticated
    && !empty($authUser?->email)
    && \App\Models\User::hasUsersColumn('email')
    && \App\Models\User::hasUsersColumn('firma')
  ) {
    $userProjectIds = \App\Models\User::query()
      ->where('email', (string) $authUser->email)
      ->pluck('firma')
      ->map(fn ($firma) => (int) $firma)
      ->filter(fn ($firma) => $firma > 0)
      ->unique()
      ->values();
  }

  if ($userProjectIds->isEmpty() && $userProjectId > 0) {
    $userProjectIds = collect([$userProjectId]);
  }

  $headerProjects = \Illuminate\Support\Facades\Schema::hasTable('project')
    ? \App\Models\Project::query()
        ->when($userProjectIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $userProjectIds->all()))
        ->orderBy('num')
        ->orderBy('name')
        ->get(['id', 'num', 'name'])
    : collect();
  $activeFid = (int) session('fid', $userProjectIds->first() ?: $userProjectId);
  $activeLang = \App\Models\Field::normalizeLocale(app()->getLocale());
  $headerLangOptions = ['ru' => 'RU', 'ua' => 'UA', 'en' => 'EN'];
  $activeProject = $headerProjects->firstWhere('id', $activeFid);
  $headerUserName = trim(implode(' ', array_filter([
    $authUser->name ?? session('name1', ''),
    $authUser->secondname ?? '',
  ])));
  $headerTitle = $isAuthenticated
    ? ($headerUserName !== '' ? $headerUserName : (string) ($authUser->login ?? session('login', config('app.name'))))
    : config('app.name');
  $headerSubtitle = $isAuthenticated ? (session('name1') ?? '') : '';
@endphp

<div class="header-bar">
  

  <div class="header-bar__brand-wrap" style="display: flex; align-items: center; gap: 1rem;">
    <!-- Desktop: single row -->
    @if($isAuthenticated && $headerProjects->isNotEmpty())
      <div class="header-project-switch">
        <form method="POST" action="{{ route('settings.switchProject') }}" class="header-project-switch__form">
          @csrf
          <select id="header-project-select" name="fid" class="header-bar__title" onchange="this.form.submit()">
            @foreach($headerProjects as $project)
              <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
                 {{ $project->name }} #{{ $project->id }}
              </option>
            @endforeach
          </select>
        </form>
      </div>
    @else
    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 0.25rem;">
      <a href="/" class="header-bar__logo text-decoration-none">
        <span class="header-bar__title">{{ $headerTitle }}</span>
        
      </a>
    </div>
    @endif
  </div>

  @if(!$isAuthenticated)
  <div class="header-public-links" id="desktop-public-links">
    
    <a href="{{ route('micro-business') }}" class="header-public-link">Для микро-бизнеса</a>
    <a href="{{ route('individuals') }}" class="header-public-link">Для физических лиц</a>
    <a href="{{ route('team') }}" class="header-public-link">Команда</a>
    <a href="{{ route('about') }}" class="header-public-link">О проекте</a>
    <a href="{{ route('login') }}" class="header-public-link header-btn-login">Войти</a>
  </div>
  @endif

  <div class="header-lang-switch" aria-label="{{ __('nav.language') }}">
    <select
      id="header-lang-select"
      class="header-lang-switch__select"
      onchange="if (this.value) { window.location.href = this.value; }"
    >
      @foreach($headerLangOptions as $langCode => $langLabel)
        <option
          value="{{ request()->fullUrlWithQuery(['lang' => $langCode]) }}"
          {{ $activeLang === $langCode ? 'selected' : '' }}
        >{{ $langLabel }}</option>
      @endforeach
    </select>
  </div>
  <div class="header-wallet-controls">
    <div class="header-wallet-dropdown" id="header-wallet-dropdown">
      <button
        type="button"
        class="header-wallet-trigger"
        id="header-wallet-trigger"
        aria-expanded="false"
        aria-controls="header-wallet-menu"
      >Web3</button>
      <div class="header-wallet-menu" id="header-wallet-menu" hidden>
        <button type="button" id="menu-connect-wallet" class="header-wallet-menu__action">Подключить кошелек</button>
        <button type="button" id="menu-disconnect-wallet" class="header-wallet-menu__action is-disconnect" style="display: none;">
          Отключить <span id="menu-wallet-address"></span>
        </button>
      </div>
    </div>
  </div>
  <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="Відкрити меню">
    <span></span>
    <span></span>
    <span></span>
  </button>


  

  <nav class="header-nav-menu" id="header-nav-menu">
    @if($isAuthenticated)
    <div class="header-nav-menu__section-label">Бизнес</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a>
      <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'ZOUT']) }}">{{ __('nav.orders') }}</a>
      <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'ZIN']) }}">{{ __('nav.purchases') }}</a>
      <a class="header-nav-menu__link" href="{{ route('money.transfers') }}">Трансферы</a>
      <a class="header-nav-menu__link" href="{{ route('client.index') }}">{{ __('nav.clients') }}</a>
      <a class="header-nav-menu__link" href="{{ route('team') }}">Команда</a>
      <a class="header-nav-menu__link" href="{{ route('goods.index') }}">{{ __('nav.goods') }}</a>
      <a class="header-nav-menu__link" href="{{ route('reports.index') }}">{{ __('nav.reports') }}</a>
      <a class="header-nav-menu__link" href="{{ route('news.index') }}">{{ __('nav.news') }}</a>
      <a class="header-nav-menu__link" href="{{ route('wallet') }}">Кошелек</a>
    </div>

    <div class="header-nav-menu__section-label">Частный</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('money.index') }}">{{ __('nav.money') }}</a>
      <a class="header-nav-menu__link" href="{{ route('deposit.index') }}">{{ __('nav.deposits') }}</a>
    </div>

    <div class="header-nav-menu__section-label">Прочее</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('settings.index') }}">{{ __('nav.settings') }}</a>
      <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: contents;">
        @csrf
        <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
          class="header-nav-menu__link" id="main-logout-btn">{{ __('nav.logout') }}</a>
      </form>
    </div>
    @else
    <div id="mobile-public-links">
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('micro-business') }}">Для микро-бизнеса</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('individuals') }}">Для физических лиц</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('team') }}">Команда</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('about') }}">О проекте</a>
      <a class="header-nav-menu__link" href="{{ route('login') }}">Войти</a>
    </div>
    @endif
  </nav>
</div>

<div class="modal fade" id="wallet-connect-modal" tabindex="-1" aria-labelledby="wallet-connect-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered wallet-modal-dialog">
    <div class="modal-content wallet-modal-content">
      <div class="modal-header wallet-modal-header border-0">
        <div>
          <p class="wallet-modal-eyebrow">WEB3 ACCESS</p>
          <h5 class="modal-title" id="wallet-connect-modal-title">Подключение кошелька</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="wallet-modal-copy">
          Выберите EVM или Solana кошелек. Если адрес уже привязан, откроется dashboard.
        </p>
        <div id="wallet-modal-provider-list" class="wallet-modal-provider-list"></div>
        <div id="wallet-modal-install-list" class="wallet-modal-install-list" style="display:none;"></div>
        <div id="wallet-modal-status" class="wallet-modal-status" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<style>
  /* Desktop: project selector is compact and on the right */
  @media (min-width: 901px) {
    .mobile-only-link {
      display: none !important;
    }

    .header-bar {
      flex-wrap: nowrap;
    }

    .header-bar__brand-wrap {
      min-width: 0;
    }

    .header-bar__bottom {
      flex-wrap: nowrap;
    }

    .header-public-links {
      display: flex;
      gap: 1.5rem;
      margin-left: 1.5rem;
      align-items: center;
      flex: 1 1 auto;
    }

    .header-public-link {
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
      font-size: 0.95rem;
      transition: color 0.2s;
      white-space: nowrap;
    }

    .header-public-link:hover {
      color: #fff;
    }

    .header-bar__logo {
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .header-bar__subtitle {
      color: rgba(255, 255, 255, 0.58);
      font-size: 0.8rem;
      line-height: 1.1;
      white-space: nowrap;
    }

    .header-btn-login, .header-btn-register {
      color: #fbbf24;
      font-weight: 600;
    }

    .header-project-switch {
      order: 1;
    }

    .header-project-switch__select {
      min-width: 140px;
      max-width: 260px;
    }

    .header-project-switch__label {
      display: block;
    }

    .header-project-name {
      display: none;
    }

    .header-lang-switch {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      order: 2;
      margin-left: 0;
    }

    .header-lang-switch__label {
      display: none;
    }

    .header-lang-switch__select {
      width: 64px;
      min-width: 54px;
      height: 44px;
      padding: 0;
      border-radius: 10px;
      border: 1px solid rgba(251, 191, 36, 0.35);
      background: rgba(0, 0, 0, 0.2);
      color: #fbbf24;
      font-size: 1rem;
      font-weight: 600;
      text-align: center;
      text-align-last: center;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      outline: none;
      cursor: pointer;
    }

    .header-lang-switch__select:focus {
      border-color: rgba(251, 191, 36, 0.55);
      box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.12);
    }

    .header-lang-switch__select:hover {
      background: rgba(251, 191, 36, 0.12);
      border-color: rgba(251, 191, 36, 0.55);
    }

    .header-lang-switch__select option {
      color: #111827;
    }

    .header-wallet-controls {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-left: auto;
    }

    .header-wallet-dropdown {
      position: relative;
    }

    .header-wallet-trigger {
      border: 1px solid rgba(251, 191, 36, 0.35);
      border-radius: 10px;
      background: rgba(0, 0, 0, 0.2);
      color: #fbbf24;
      font-size: 0.82rem;
      line-height: 1;
      font-weight: 700;
      min-width: 44px;
      height: 44px;
      padding: 0 0.8rem;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      white-space: nowrap;
    }

    .header-wallet-trigger:hover,
    .header-wallet-trigger[aria-expanded="true"] {
      background: rgba(251, 191, 36, 0.12);
      border-color: rgba(251, 191, 36, 0.55);
    }

    .header-wallet-menu {
      position: absolute;
      top: calc(100% + 0.5rem);
      right: 0;
      min-width: 220px;
      padding: 0.4rem;
      border-radius: 12px;
      border: 1px solid rgba(251, 191, 36, 0.18);
      background:
        radial-gradient(circle at top left, rgba(251, 191, 36, 0.12), transparent 40%),
        linear-gradient(180deg, rgba(19, 24, 33, 0.98), rgba(10, 13, 18, 0.98));
      box-shadow: 0 18px 42px rgba(0, 0, 0, 0.38);
      display: none;
      z-index: 1050;
    }

    .header-wallet-menu[hidden] {
      display: none !important;
    }

    .header-wallet-dropdown.is-open .header-wallet-menu {
      display: grid;
      gap: 0.35rem;
    }

    .header-wallet-menu__action {
      width: 100%;
      border: 1px solid rgba(251, 191, 36, 0.16);
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.04);
      color: #f8fafc;
      font-size: 0.88rem;
      line-height: 1.2;
      text-align: left;
      padding: 0.75rem 0.85rem;
      transition: all 0.2s ease;
    }

    .header-wallet-menu__action:hover {
      background: rgba(251, 191, 36, 0.1);
      border-color: rgba(251, 191, 36, 0.35);
      color: #fff;
    }

    .header-wallet-menu__action.is-disconnect {
      color: #fecaca;
      border-color: rgba(248, 113, 113, 0.18);
    }

    .header-burger {
      order: 4;
    }
  }

  /* Mobile overrides */
  @media (max-width: 900px) {
    .header-bar {
      padding: 0.25rem 0.5rem;
      flex-wrap: nowrap;
      align-items: center;
      gap: 0.35rem;
    }

    .header-bar__brand-wrap {
      order: 1;
      flex: 1 1 auto;
      min-width: 0;
    }

    .header-bar__logo {
      flex: 1 1 auto;
      min-width: 0;
    }

    .header-bar__logo .header-bar__title {
      font-size: 0.95rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .header-bar__logo .header-bar__subtitle {
      max-width: 180px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .header-public-links {
      display: none;
    }

    .header-public-links::-webkit-scrollbar {
      display: none; /* Chrome/Safari */
    }

    .header-public-link {
      font-size: 0.85rem;
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
    }

    .header-btn-login, .header-btn-register {
      color: #fbbf24;
      font-weight: 600;
    }

    /* Project selector on mobile - compact dropdown */
    .header-project-switch {
      order: 2;
      display: flex;
      align-items: center;
      gap: 0;
      margin-left: 0;
    }

    .header-project-switch__select {
      min-width: 0;
      width: auto;
      max-width: 170px;
      height: 36px;
      padding: 0 1.5rem 0 0.5rem;
      font-size: 0.75rem;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.08);
    }

    .header-project-switch__label {
      display: none;
    }

    .header-project-name {
      display: none;
    }

    /* Language switcher - consistent sizing */
    .header-lang-switch {
      order: 3;
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      margin-left: 0;
    }

    .header-lang-switch__select {
    order:3;
      width: 75px;
      min-width: 70px;
      height: 40px;
      font-size: 0.9rem;
      border-radius: 10px;
    }

    .header-wallet-controls {
      order: 4;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      margin-left: 0.35rem;
      
    }

    .header-wallet-trigger {
      min-width: 70px;
      height: 40px;
      padding: 0 0.9rem;
      font-size: 0.9rem;
      border-radius: 10px;
    }

    .header-wallet-menu {
      right: -0.35rem;
      min-width: 200px;
    }

    .header-wallet-menu__action {
      font-size: 0.84rem;
      padding: 0.7rem 0.8rem;
    }

    /* Burger button */
    .header-burger {
      order: 5;
      margin-left: 0.15rem;
      width: 40px;
      height: 40px;
      min-width: 40px;
      padding: 8px;
      border-radius: 10px;
    }

    .header-burger span {
      width: 20px;
      height: 3.5px;
      margin: 2.5px 0;
    }

    .header-nav-menu {
      position: absolute;
      top: 100%;
      right: 1rem;
      left: 1rem;
      min-width: 0;
      padding: 0.75rem;
      background: linear-gradient(180deg, rgba(19, 24, 33, 0.96), rgba(10, 13, 18, 0.98));
      backdrop-filter: blur(15px);
      border: 1px solid rgba(251, 191, 36, 0.25);
      border-radius: 20px;
      box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.7);
      margin-top: 0.5rem;
      display: none; /* Скрыто по умолчанию, показывается через .is-open */
      z-index: 1000;
    }

    .header-nav-menu.is-open {
      display: block;
      animation: menuFadeIn 0.2s ease-out;
    }

    @keyframes menuFadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .header-nav-menu__link {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0.85rem 0.5rem;
      font-size: 0.9rem;
      font-weight: 600;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      text-align: center;
    }

    .header-nav-menu__link:hover {
      background: rgba(251, 191, 36, 0.15);
      border-color: rgba(251, 191, 36, 0.45);
      color: #fbbf24;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .header-nav-menu__link:active {
      transform: scale(0.94);
      background: rgba(251, 191, 36, 0.25);
      transition: transform 0.1s;
    }

    .header-nav-menu__section {
      padding: 0.5rem 0.75rem;
      margin-bottom: 0.45rem;
    }

    .header-nav-menu__label {
      font-size: 1rem;
      margin-bottom: 0.45rem;
    }

    .header-nav-menu__project-select {
      height: 38px;
      font-size: 0.95rem;
    }
  }

  .header-nav-menu__section-label {
    padding: 1.25rem 0.85rem 0.5rem;
    color: #fbbf24;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .header-nav-menu__section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, rgba(251, 191, 36, 0.3), transparent);
  }

  .header-nav-menu__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.6rem;
    padding: 0.4rem;
  }

  /* Project selector in mobile menu */
  .header-nav-menu__section {
    display: grid;
    gap: 0.35rem;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.45rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .header-nav-menu__label {
    display: block;
    color: var(--muted-foreground);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.45rem;
  }

  .header-nav-menu__project-form {
    margin: 0;
  }

  .header-nav-menu__project-select {
    width: 100%;
    height: 42px;
    padding: 0 2.25rem 0 0.85rem;
    border-radius: var(--radius);
    border: 1px solid var(--glass-border);
    background: rgba(255, 255, 255, 0.06);
    color: var(--foreground);
    font: inherit;
    font-size: 0.9rem;
    outline: none;
  }

  .header-nav-menu__project-select:focus {
    border-color: var(--accent-amber-border);
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.12);
  }

  .header-nav-menu__project-select option {
    color: #111827;
  }

  .wallet-modal-dialog {
    max-width: 400px;
    margin: 1rem auto;
  }

  .wallet-modal-content {
    color: #fff;
    border: 1px solid rgba(251, 191, 36, 0.18);
    border-radius: 16px;
    background:
      radial-gradient(circle at top left, rgba(251, 191, 36, 0.18), transparent 38%),
      linear-gradient(180deg, rgba(19, 24, 33, 0.98), rgba(10, 13, 18, 0.98));
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.42);
  }

  .wallet-modal-header {
    padding: 1rem 1rem 0.35rem;
  }

  .wallet-modal-content .modal-body {
    padding: 0.6rem 1rem 1rem;
  }

  .wallet-modal-eyebrow {
    margin: 0 0 0.2rem;
    color: #fbbf24;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
  }

  #wallet-connect-modal-title {
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.2;
  }

  .wallet-modal-copy {
    margin-bottom: 0.75rem;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.9rem;
    line-height: 1.35;
  }

  .wallet-modal-provider-list,
  .wallet-modal-install-list {
    display: grid;
    gap: 0.5rem;
  }

  .wallet-modal-provider {
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.75rem 0.85rem;
    transition: all 0.2s ease;
    text-align: left;
  }

  .wallet-modal-provider:hover:not(:disabled) {
    border-color: rgba(251, 191, 36, 0.4);
    background: rgba(255, 255, 255, 0.05);
  }

  .wallet-modal-provider:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }

  .wallet-modal-provider__name {
    font-weight: 600;
    font-size: 0.92rem;
  }

  .wallet-modal-provider__meta {
    display: block;
    margin-top: 0.15rem;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.76rem;
    line-height: 1.25;
  }

  .wallet-modal-provider__badge {
    color: #fbbf24;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
  }

  .wallet-modal-status {
    margin-top: 0.75rem;
    padding: 0.7rem 0.85rem;
    border-radius: 12px;
    font-size: 0.84rem;
    line-height: 1.35;
    background: rgba(255, 255, 255, 0.04);
  }

  @media (max-width: 576px) {
    .wallet-modal-dialog {
      max-width: none;
      margin: 0.75rem;
    }

    .wallet-modal-header {
      padding: 0.9rem 0.9rem 0.25rem;
    }

    .wallet-modal-content .modal-body {
      padding: 0.5rem 0.9rem 0.9rem;
    }
  }

  .wallet-modal-status.is-error {
    color: #fecaca;
    border: 1px solid rgba(248, 113, 113, 0.24);
    background: rgba(127, 29, 29, 0.22);
  }

  .wallet-modal-status.is-success {
    color: #bbf7d0;
    border: 1px solid rgba(74, 222, 128, 0.24);
    background: rgba(20, 83, 45, 0.22);
  }

  .wallet-modal-status.is-info {
    color: #fde68a;
    border: 1px solid rgba(251, 191, 36, 0.24);
    background: rgba(120, 53, 15, 0.22);
  }

  /* Общий стиль для таблиц в отчетах */
  .table th:first-child,
  .table td:first-child {
    vertical-align: middle;
  }
</style>

@push('scripts')
<script>
  (function () {
    const burger = document.getElementById('header-burger');
    const menu = document.getElementById('header-nav-menu');
    const walletDropdown = document.getElementById('header-wallet-dropdown');
    const walletTrigger = document.getElementById('header-wallet-trigger');
    const walletMenu = document.getElementById('header-wallet-menu');
    const connectWalletBtn = document.getElementById('menu-connect-wallet');
    const disconnectWalletBtn = document.getElementById('menu-disconnect-wallet');
    const walletAddressNode = document.getElementById('menu-wallet-address');
    const walletModalNode = document.getElementById('wallet-connect-modal');
    const walletModalStatus = document.getElementById('wallet-modal-status');
    const walletProviderList = document.getElementById('wallet-modal-provider-list');
    const walletInstallList = document.getElementById('wallet-modal-install-list');
    const isAuthenticated = @json($isAuthenticated);
    const web3ChallengeUrl = '{{ route('web3.challenge') }}';
    const web3LoginUrl = '{{ route('web3.login') }}';
    const walletLinkChallengeUrl = '{{ route('wallet.challenge') }}';
    const walletLinkUrl = '{{ route('wallet.link') }}';
    const walletPageUrl = '{{ route('wallet') }}';
    const dashboardUrl = '{{ route('dashboard') }}';
    const stateListeners = new Set();
    const KNOWN_WALLETS = [
      { id: 'metamask', type: 'evm', name: 'MetaMask', installUrl: 'https://metamask.io/download/', matches(provider) { return provider && provider.isMetaMask === true; } },
      { id: 'rabby', type: 'evm', name: 'Rabby', installUrl: 'https://rabby.io/', matches(provider) { return provider && provider.isRabby === true; } },
      { id: 'coinbase', type: 'evm', name: 'Coinbase Wallet', installUrl: 'https://www.coinbase.com/wallet/downloads', matches(provider) { return provider && provider.isCoinbaseWallet === true; } },
      { id: 'trust', type: 'evm', name: 'Trust Wallet', installUrl: 'https://trustwallet.com/browser-extension', matches(provider) { return provider && provider.isTrust === true; } },
      { id: 'okx', type: 'evm', name: 'OKX Wallet', installUrl: 'https://www.okx.com/web3', matches(provider) { return provider && (provider.isOKExWallet === true || provider.isOKXWallet === true); } },
      { id: 'phantom', type: 'solana', name: 'Phantom', installUrl: 'https://phantom.com/download', matches(provider) { return provider && provider.isPhantom === true; } },
      { id: 'solflare', type: 'solana', name: 'Solflare', installUrl: 'https://solflare.com/download', matches(provider) { return provider && provider.isSolflare === true; } },
      { id: 'backpack', type: 'solana', name: 'Backpack', installUrl: 'https://backpack.app/', matches(provider) { return provider && provider.isBackpack === true; } },
    ];
    const walletState = {
      provider: null,
      address: null,
      chainId: null,
      walletType: null,
      linked: false,
      connected: false,
    };
    let pendingModalOptions = null;
    let walletModal = null;

    if (walletModalNode && walletModalNode.parentElement !== document.body) {
      document.body.appendChild(walletModalNode);
    }

    if (walletModalNode && window.bootstrap) {
      walletModal = window.bootstrap.Modal.getOrCreateInstance(walletModalNode);
    }

    function closeWalletDropdown() {
      if (!walletDropdown || !walletTrigger || !walletMenu) {
        return;
      }

      walletDropdown.classList.remove('is-open');
      walletTrigger.setAttribute('aria-expanded', 'false');
      walletMenu.hidden = true;
    }

    function toggleWalletDropdown() {
      if (!walletDropdown || !walletTrigger || !walletMenu) {
        return;
      }

      const expanded = walletTrigger.getAttribute('aria-expanded') === 'true';
      const willOpen = !expanded;
      walletDropdown.classList.toggle('is-open', willOpen);
      walletTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      walletMenu.hidden = !willOpen;
    }

    function closeHeaderMenu() {
      if (!burger || !menu) {
        return;
      }

      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.classList.remove('header-menu-open');
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
        const parsed = parseInt(raw, 16);
        return Number.isFinite(parsed) ? '0x' + parsed.toString(16) : null;
      }

      if (/^\d+$/.test(raw)) {
        const parsed = parseInt(raw, 10);
        return Number.isFinite(parsed) ? '0x' + parsed.toString(16) : null;
      }

      return null;
    }

    function getInjectedProviders() {
      if (!window.ethereum) {
        return [];
      }

      if (Array.isArray(window.ethereum.providers) && window.ethereum.providers.length > 0) {
        return window.ethereum.providers;
      }

      return [window.ethereum];
    }

    function getSolanaProviders() {
      const candidates = [
        window.phantom?.solana,
        window.solflare,
        window.backpack?.solana,
        window.solana,
      ];
      const seen = new Set();

      return candidates.filter((provider) => {
        if (!provider || typeof provider !== 'object' || seen.has(provider)) {
          return false;
        }

        seen.add(provider);
        return true;
      });
    }

    function listWalletOptions() {
      const mapped = new Map();

      getInjectedProviders().forEach((provider) => {
        const known = KNOWN_WALLETS.find((wallet) => wallet.matches(provider));
        const id = known ? known.id : `evm-${mapped.size + 1}`;

        if (!mapped.has(id)) {
          mapped.set(id, {
            id,
            type: known ? known.type : 'evm',
            name: known ? known.name : 'Browser Wallet',
            installUrl: known ? known.installUrl : 'https://ethereum.org/wallets/',
            provider,
            installed: true,
          });
        }
      });

      getSolanaProviders().forEach((provider) => {
        const known = KNOWN_WALLETS.find((wallet) => wallet.type === 'solana' && wallet.matches(provider));
        const id = known ? known.id : `solana-${mapped.size + 1}`;

        if (!mapped.has(id)) {
          mapped.set(id, {
            id,
            type: known ? known.type : 'solana',
            name: known ? known.name : 'Solana Wallet',
            installUrl: known ? known.installUrl : 'https://solana.com/ecosystem/explore?categories=wallet',
            provider,
            installed: true,
          });
        }
      });

      KNOWN_WALLETS.forEach((wallet) => {
        if (!mapped.has(wallet.id)) {
          mapped.set(wallet.id, {
            id: wallet.id,
            type: wallet.type,
            name: wallet.name,
            installUrl: wallet.installUrl,
            provider: null,
            installed: false,
          });
        }
      });

      return Array.from(mapped.values());
    }

    function shortenAddress(address) {
      if (!address || address.length < 10) {
        return address || '';
      }

      return address.slice(0, 6) + '...' + address.slice(-4);
    }

    function walletTriggerLabel() {
      if (!walletState.connected || !walletState.address) {
        return 'Web3';
      }

      return `Выход ${walletState.address.slice(-4)}`;
    }

    function syncWalletButtons() {
      if (walletTrigger) {
        const label = walletTriggerLabel();
        walletTrigger.textContent = label;
        walletTrigger.setAttribute('aria-label', label);
        walletTrigger.title = walletState.address
          ? `Отключить ${walletState.address}`
          : 'Подключить Web3 кошелек';
      }

      if (connectWalletBtn) {
        connectWalletBtn.style.display = walletState.connected ? 'none' : 'inline-flex';
      }

      if (disconnectWalletBtn) {
        disconnectWalletBtn.style.display = walletState.connected ? 'inline-flex' : 'none';
      }

      if (walletAddressNode) {
        walletAddressNode.textContent = walletState.address ? ` ${shortenAddress(walletState.address)}` : '';
      }
    }

    function emitWalletState() {
      syncWalletButtons();
      stateListeners.forEach((listener) => {
        try {
          listener({ ...walletState });
        } catch (error) {
          console.error('Wallet state listener error:', error);
        }
      });
    }

    function setWalletState(patch) {
      Object.assign(walletState, patch);
      emitWalletState();
    }

    function syncWalletStateFromExternal(detail) {
      if (!detail || typeof detail !== 'object') {
        return;
      }

      setWalletState({
        provider: detail.provider || walletState.provider || window.ethereum || null,
        address: detail.connected ? (detail.address || null) : null,
        chainId: detail.connected ? (normalizeChainId(detail.chainId) || detail.chainId || null) : null,
        walletType: detail.walletType === 'solana' ? 'solana' : (detail.walletType || (detail.connected ? 'evm' : null)),
        linked: Boolean(detail.linked),
        connected: Boolean(detail.connected && detail.address),
      });

      if (detail.connected) {
        localStorage.removeItem('walletDisconnectedExplicitly');
      } else {
        localStorage.setItem('walletDisconnectedExplicitly', 'true');
      }
    }

    function setWalletModalStatus(message, type) {
      if (!walletModalStatus) {
        return;
      }

      if (!message) {
        walletModalStatus.style.display = 'none';
        walletModalStatus.textContent = '';
        walletModalStatus.className = 'wallet-modal-status';
        return;
      }

      walletModalStatus.style.display = 'block';
      walletModalStatus.textContent = message;
      walletModalStatus.className = `wallet-modal-status is-${type || 'info'}`;
    }

    function renderWalletModalProviders(activeWalletId) {
      if (!walletProviderList || !walletInstallList) {
        return;
      }

      const options = listWalletOptions();
      const installed = options.filter((wallet) => wallet.installed);
      const missing = options.filter((wallet) => !wallet.installed);

      walletProviderList.innerHTML = installed.map((wallet) => `
        <button type="button" class="wallet-modal-provider" data-wallet-id="${wallet.id}" ${activeWalletId ? 'disabled' : ''}>
          <span>
            <span class="wallet-modal-provider__name">${wallet.name}</span>
            <span class="wallet-modal-provider__meta">Подключить ${wallet.type === 'solana' ? 'Solana' : 'EVM'}-адрес и проверить привязку</span>
          </span>
          <span class="wallet-modal-provider__badge">${activeWalletId === wallet.id ? 'Подключаем...' : 'Установлен'}</span>
        </button>
      `).join('');

      if (missing.length > 0) {
        walletInstallList.style.display = 'grid';
        walletInstallList.innerHTML = missing.slice(0, 4).map((wallet) => `
          <a href="${wallet.installUrl}" target="_blank" rel="noreferrer" class="wallet-modal-provider">
            <span>
              <span class="wallet-modal-provider__name">${wallet.name}</span>
              <span class="wallet-modal-provider__meta">Установить расширение</span>
            </span>
            <span class="wallet-modal-provider__badge">Скачать</span>
          </a>
        `).join('');
      } else {
        walletInstallList.style.display = 'none';
        walletInstallList.innerHTML = '';
      }

      if (installed.length === 0) {
        setWalletModalStatus('В браузере не найден Web3-кошелек. Установите MetaMask, Phantom, Solflare или другой совместимый кошелек.', 'error');
      }
    }

    function uint8ArrayToBase64(value) {
      const bytes = value instanceof Uint8Array ? value : new Uint8Array(value || []);
      let binary = '';

      bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
      });

      return window.btoa(binary);
    }

    async function signWalletMessage(provider, walletType, address, message) {
      if (walletType === 'solana') {
        if (!provider || typeof provider.signMessage !== 'function') {
          throw new Error('Solana-кошелек не поддерживает подпись сообщений.');
        }

        const encoded = new TextEncoder().encode(message);
        const signed = await provider.signMessage(encoded, 'utf8');
        const signatureBytes = signed?.signature || signed;

        if (!signatureBytes) {
          throw new Error('Solana-кошелек не вернул подпись.');
        }

        return uint8ArrayToBase64(signatureBytes);
      }

      return provider.request({
        method: 'personal_sign',
        params: [message, address],
      });
    }

    async function postJson(url, payload) {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
      });

      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        const error = new Error(data.message || 'Web3 request failed.');
        error.status = response.status;
        error.payload = data;
        throw error;
      }

      return data;
    }

    async function attemptWalletLogin(address, provider, options) {
      const walletType = options?.walletType === 'solana' ? 'solana' : 'evm';

      if (isAuthenticated || options?.autoLogin === false) {
        return { linked: false, skipped: true };
      }

      const challenge = await postJson(web3ChallengeUrl, { address, wallet_type: walletType });
      const signature = await signWalletMessage(provider, walletType, address, challenge.message);

      try {
        await postJson(web3LoginUrl, { address, signature, wallet_type: walletType });
        return { linked: true };
      } catch (error) {
        if (error && error.status === 404) {
          return { linked: false };
        }

        throw error;
      }
    }

    async function ensureWalletLinked(address, provider, options) {
      const walletType = options?.walletType === 'solana' ? 'solana' : 'evm';
      const chainId = walletType === 'solana'
        ? 'solana'
        : (normalizeChainId(options?.chainId) || '0x1');

      if (!isAuthenticated) {
        return { linked: false, skipped: true };
      }

      const challenge = await postJson(walletLinkChallengeUrl, {
        address,
        wallet_type: walletType,
      });

      const signature = await signWalletMessage(provider, walletType, address, challenge.message);
      await postJson(walletLinkUrl, {
        address,
        signature,
        network: chainId,
        wallet_type: walletType,
      });

      return { linked: true };
    }

    async function connectWallet(walletId, options) {
      const selected = listWalletOptions().find((wallet) => wallet.id === walletId);

      if (!selected || !selected.provider) {
        throw new Error('Кошелек недоступен в этом браузере.');
      }

      renderWalletModalProviders(walletId);
      setWalletModalStatus('Подтвердите подключение кошелька во всплывающем окне расширения.', 'info');

      let address = null;
      let chainId = null;

      if (selected.type === 'solana') {
        const response = await selected.provider.connect();
        address = response?.publicKey?.toString?.() || selected.provider.publicKey?.toString?.() || null;
        chainId = 'solana';
      } else {
        const accounts = await selected.provider.request({ method: 'eth_requestAccounts' });
        address = Array.isArray(accounts) ? accounts[0] : null;

        try {
          chainId = normalizeChainId(await selected.provider.request({ method: 'eth_chainId' }));
        } catch (error) {
          console.error('Failed to detect wallet chain id:', error);
        }
      }

      if (!address) {
        throw new Error('Кошелек не вернул адрес.');
      }

      setWalletState({
        provider: selected.provider,
        address,
        chainId,
        walletType: selected.type,
        connected: true,
        linked: false,
      });

      localStorage.removeItem('walletDisconnectedExplicitly');

      const linkResult = await ensureWalletLinked(address, selected.provider, { ...(options || {}), walletType: selected.type, chainId });
      const loginResult = await attemptWalletLogin(address, selected.provider, { ...(options || {}), walletType: selected.type });
      setWalletState({ linked: Boolean(linkResult.linked || loginResult.linked) });

      if (loginResult.linked) {
        setWalletModalStatus('Кошелек привязан к аккаунту. Открываем dashboard...', 'success');
        setTimeout(() => {
          window.location.href = dashboardUrl;
        }, 250);
      } else if (!loginResult.skipped) {
        setWalletModalStatus('Кошелек подключен, но адрес пока не привязан к аккаунту. Можно продолжить работу на странице кошелька.', 'info');
      } else {
        setWalletModalStatus(linkResult.linked ? 'Кошелек подключен и добавлен к аккаунту.' : 'Кошелек подключен.', 'success');
      }

      if (walletModal && !loginResult.linked) {
        setTimeout(() => walletModal.hide(), 250);
      }

      if (options && typeof options.onConnected === 'function') {
        await options.onConnected({
          address,
          chainId,
          provider: selected.provider,
          walletType: selected.type,
          linked: Boolean(linkResult.linked || loginResult.linked),
        });
      }

      renderWalletModalProviders(null);
      return { address, chainId, provider: selected.provider, walletType: selected.type, linked: Boolean(linkResult.linked || loginResult.linked) };
    }

    function openWalletModal(options = {}) {
      pendingModalOptions = options;
      renderWalletModalProviders(null);
      setWalletModalStatus('', null);
      closeHeaderMenu();

      if (walletModal) {
        walletModal.show();
      } else if (options.redirectToWallet !== false) {
        window.location.href = walletPageUrl;
      }
    }

    function disconnectWallet() {
      localStorage.setItem('walletDisconnectedExplicitly', 'true');
      setWalletState({
        provider: null,
        address: null,
        chainId: null,
        walletType: null,
        linked: false,
        connected: false,
      });
    }

    async function restoreWalletConnection() {
      if (localStorage.getItem('walletDisconnectedExplicitly') === 'true') {
        emitWalletState();
        return;
      }

      try {
        const options = listWalletOptions();
        const solanaEntry = options.find((wallet) => wallet.type === 'solana' && wallet.provider?.isConnected && wallet.provider?.publicKey);

        if (solanaEntry?.provider?.publicKey) {
          setWalletState({
            provider: solanaEntry.provider,
            address: solanaEntry.provider.publicKey.toString(),
            chainId: 'solana',
            walletType: 'solana',
            linked: false,
            connected: true,
          });
          return;
        }

        const providerEntry = options.find((wallet) => wallet.type === 'evm' && wallet.provider) || null;
        if (!providerEntry?.provider) {
          emitWalletState();
          return;
        }

        const accounts = await providerEntry.provider.request({ method: 'eth_accounts' });
        if (!Array.isArray(accounts) || accounts.length === 0) {
          emitWalletState();
          return;
        }

        const chainId = normalizeChainId(await providerEntry.provider.request({ method: 'eth_chainId' }).catch(() => null));

        setWalletState({
          provider: providerEntry.provider,
          address: accounts[0],
          chainId,
          walletType: 'evm',
          linked: false,
          connected: true,
        });
      } catch (error) {
        console.error('Failed to restore wallet connection:', error);
      }
    }

    if (burger && menu) {
      burger.addEventListener('click', function () {
        const expanded = burger.getAttribute('aria-expanded') === 'true';
        burger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        menu.classList.toggle('is-open', !expanded);
        document.body.classList.toggle('header-menu-open', !expanded);
      });

      menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', function () {
          burger.setAttribute('aria-expanded', 'false');
          menu.classList.remove('is-open');
          document.body.classList.remove('header-menu-open');
        });
      });

      document.addEventListener('click', function (event) {
        if (!menu.classList.contains('is-open')) {
          return;
        }

        if (menu.contains(event.target) || burger.contains(event.target)) {
          return;
        }

        closeHeaderMenu();
      });
    }

    if (connectWalletBtn) {
      connectWalletBtn.addEventListener('click', function () {
        closeWalletDropdown();
        openWalletModal();
      });
    }

    if (disconnectWalletBtn) {
      disconnectWalletBtn.addEventListener('click', function (event) {
        event.preventDefault();
        closeWalletDropdown();
        disconnectWallet();
      });
    }

    if (walletTrigger) {
      walletTrigger.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (walletState.connected) {
          closeWalletDropdown();
          disconnectWallet();
          return;
        }

        closeWalletDropdown();
        openWalletModal();
      });
    }

    document.addEventListener('click', function (event) {
      if (!walletDropdown || !walletDropdown.classList.contains('is-open')) {
        return;
      }

      if (walletDropdown.contains(event.target)) {
        return;
      }

      closeWalletDropdown();
    });

    if (walletProviderList) {
      walletProviderList.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-wallet-id]');
        if (!button) {
          return;
        }

        const walletId = button.getAttribute('data-wallet-id');
        if (!walletId) {
          return;
        }

        try {
          await connectWallet(walletId, pendingModalOptions || {});
        } catch (error) {
          console.error('Wallet connection error:', error);
          renderWalletModalProviders(null);
          setWalletModalStatus(error.message || 'Не удалось подключить кошелек.', 'error');
        }
      });
    }

    if (walletModalNode) {
      walletModalNode.addEventListener('hidden.bs.modal', function () {
        pendingModalOptions = null;
        renderWalletModalProviders(null);
        setWalletModalStatus('', null);
      });
    }

    window.appWallet = {
      openModal: openWalletModal,
      disconnect: disconnectWallet,
      subscribe(listener) {
        if (typeof listener !== 'function') {
          return function () {};
        }

        stateListeners.add(listener);
        listener({ ...walletState });

        return function unsubscribe() {
          stateListeners.delete(listener);
        };
      },
      getState() {
        return { ...walletState };
      },
      signMessage({ provider, walletType, address, message }) {
        return signWalletMessage(provider, walletType, address, message);
      },
      connect(options = {}) {
        openWalletModal(options);
      },
    };

    window.addEventListener('app-wallet-state-changed', function (event) {
      syncWalletStateFromExternal(event.detail);
    });

    restoreWalletConnection();

    if (window.ethereum) {
      window.ethereum.on('accountsChanged', async function (accounts) {
        if (!Array.isArray(accounts) || accounts.length === 0) {
          disconnectWallet();
          return;
        }

        const nextChainId = normalizeChainId(await window.ethereum.request({ method: 'eth_chainId' }).catch(() => walletState.chainId));
        localStorage.removeItem('walletDisconnectedExplicitly');
        setWalletState({
          provider: walletState.provider || window.ethereum,
          address: accounts[0],
          chainId: nextChainId,
          connected: true,
        });
      });

      window.ethereum.on('chainChanged', function (chainId) {
        if (!walletState.connected) {
          return;
        }

        setWalletState({
          chainId: normalizeChainId(chainId) || walletState.chainId,
        });
      });
    }
  })();
</script>
@endpush
