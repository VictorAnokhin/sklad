{{-- top_reklama.blade.php --}}
@php
  $authUser = \Illuminate\Support\Facades\Auth::user();
  $isAuthenticated = \Illuminate\Support\Facades\Auth::check();
  $userProjectId = (int) (($authUser->firma ?? 0) ?: ($authUser->fid ?? 0));
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

  $projectSelectColumns = ['id', 'num', 'name'];
  if (\Illuminate\Support\Facades\Schema::hasTable('project') && \Illuminate\Support\Facades\Schema::hasColumn('project', 'project_type')) {
    $projectSelectColumns[] = 'project_type';
  }

  $headerProjects = \Illuminate\Support\Facades\Schema::hasTable('project')
    ? \App\Models\Project::query()
        ->when($userProjectIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $userProjectIds->all()))
        ->orderBy('num')
        ->orderBy('name')
        ->get($projectSelectColumns)
    : collect();
  $newOrdersByProject = collect();
  $newOrdersCount = 0;

  if (
    $isAuthenticated
    && $userProjectIds->isNotEmpty()
    && \Illuminate\Support\Facades\Schema::hasTable('document')
  ) {
    $newOrdersByProject = \Illuminate\Support\Facades\DB::table('document')
      ->select('firma', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))
      ->whereIn('firma', $userProjectIds->map(fn ($id) => (string) $id)->all())
      ->where('type', 'ZOUT')
      ->where(function ($query) {
        $query->whereNull('status')
          ->orWhere('status', '')
          ->orWhere('status', '0');
      })
      ->groupBy('firma')
      ->pluck('cnt', 'firma')
      ->mapWithKeys(fn ($count, $firma) => [(int) $firma => (int) $count]);

    $newOrdersCount = (int) $newOrdersByProject->sum();
  }
  $activeFid = (int) session('fid', $userProjectIds->first() ?: $userProjectId);
  $activeLang = \App\Models\Field::normalizeLocale(app()->getLocale());
  $headerLangOptions = ['ru' => 'RU', 'ua' => 'UA', 'en' => 'EN'];
  $activeProject = $headerProjects->firstWhere('id', $activeFid);
  $activeProjectType = strtolower(trim((string) ($activeProject->project_type ?? '')));
  $isBankProject = $activeProjectType === 'bank';
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
              @php $projectNewOrdersCount = (int) ($newOrdersByProject->get((int) $project->id, 0)); @endphp
              <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
                 {{ $project->name }} #{{ $project->id }}{{ $projectNewOrdersCount > 0 ? ' | new: ' . $projectNewOrdersCount : '' }}
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
  <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="Відкрити меню">
    <span></span>
    <span></span>
    <span></span>
  </button>


  

  <nav class="header-nav-menu" id="header-nav-menu">
    @if($isAuthenticated)
    @if(!$isBankProject)
      <div class="header-nav-menu__section-label">Бизнес</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link header-nav-menu__link--with-badge" href="{{ route('document.index', ['doc' => 'ZOUT']) }}">
          {{ __('nav.orders') }}
          @if($newOrdersCount > 0)
            <span class="header-new-orders-badge" title="Новые заказы без выбранного статуса во всех проектах этого email">{{ $newOrdersCount }}</span>
          @endif
        </a>
        <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'ZIN']) }}">{{ __('nav.purchases') }}</a>
        <a class="header-nav-menu__link" href="{{ route('money.transfers') }}">Трансферы</a>
        <a class="header-nav-menu__link" href="{{ route('goods.index') }}">{{ __('nav.goods') }}</a>
      </div>
    @endif

    <div class="header-nav-menu__section-label">Частный</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('money.index') }}">{{ __('nav.money') }}</a>
      <a class="header-nav-menu__link" href="{{ route('deposit.index') }}">{{ __('nav.deposits') }}</a>
    </div>

    <div class="header-nav-menu__section-label">Менеджмент</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a>
      <a class="header-nav-menu__link" href="{{ route('team') }}">Команда</a>
      <a class="header-nav-menu__link" href="{{ route('client.index') }}">{{ __('nav.clients') }}</a>
      <a class="header-nav-menu__link" href="{{ route('reports.index') }}">{{ __('nav.reports') }}</a>
      <a class="header-nav-menu__link" href="{{ route('news.index') }}">{{ __('nav.news') }}</a>
      <a class="header-nav-menu__link" href="{{ route('wallet') }}">Кошелек</a>
    </div>

    @if($isBankProject)
      <div class="header-nav-menu__section-label">Банк</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('bank.cash-accounts') }}">Кассы/Счета</a>
        <a class="header-nav-menu__link" href="{{ route('bank.operational-accounts') }}">Операционные счета</a>
        <a class="header-nav-menu__link" href="{{ route('bank.deposit') }}">Депозиты</a>
        <a class="header-nav-menu__link" href="{{ route('bank.pools') }}">Пулы</a>
        <a class="header-nav-menu__link" href="{{ route('bank.pool-movements') }}">Движение средств</a>
        <a class="header-nav-menu__link" href="{{ route('bank.invest') }}">Инвестиции</a>
        <a class="header-nav-menu__link" href="{{ route('bank.assets') }}">Активы</a>
        <a class="header-nav-menu__link" href="{{ route('bank.exchange') }}">Обмен фиат/крипта</a>
        <a class="header-nav-menu__link" href="{{ route('bank.clearing') }}">Клиринг проектов</a>
        <a class="header-nav-menu__link" href="{{ route('bank.payments') }}">Платежи</a>
        <a class="header-nav-menu__link" href="{{ route('bank.reconciliation') }}">Сверка</a>
        <a class="header-nav-menu__link" href="{{ route('blockchain-monitor.index') }}">Blockchain Monitor</a>
      </div>
    @endif

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

<style>
  /* Desktop: project selector is compact and on the right */
  .header-nav-menu__link--with-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
  }

  .header-new-orders-badge {
    min-width: 1.45rem;
    height: 1.45rem;
    padding: 0 0.4rem;
    border: 1px solid rgba(251, 191, 36, 0.85);
    border-radius: 999px;
    background: rgba(251, 191, 36, 0.16);
    color: #fbbf24;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.35rem;
    text-align: center;
    box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.08);
  }

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
      margin-left: auto;
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

    /* Burger button */
    .header-burger {
      order: 4;
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
      padding: 0.45rem;
      background: linear-gradient(180deg, rgba(19, 24, 33, 0.96), rgba(10, 13, 18, 0.98));
      backdrop-filter: blur(15px);
      border: 1px solid rgba(251, 191, 36, 0.25);
      border-radius: 14px;
      box-shadow: 0 18px 42px -14px rgba(0, 0, 0, 0.72);
      margin-top: 0.35rem;
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
      min-height: 34px;
      padding: 0.45rem 0.45rem;
      font-size: 0.78rem;
      font-weight: 600;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      text-align: center;
      line-height: 1.15;
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
    padding: 0.65rem 0.45rem 0.25rem;
    color: #fbbf24;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.45rem;
  }

  .header-nav-menu__section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, rgba(251, 191, 36, 0.3), transparent);
  }

  .header-nav-menu__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.35rem;
    padding: 0.2rem;
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

  @media (max-width: 520px) {
    .header-nav-menu__grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .header-nav-menu__link {
      font-size: 0.76rem;
      min-height: 32px;
      padding: 0.4rem 0.35rem;
    }
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

    function closeHeaderMenu() {
      if (!burger || !menu) {
        return;
      }

      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.classList.remove('header-menu-open');
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
  })();
</script>
@endpush
