{{-- top_reklama.blade.php --}}
@php
  $authUser = \Illuminate\Support\Facades\Auth::user();
  $isAuthenticated = \Illuminate\Support\Facades\Auth::check();
  $userProjectId = (int) (($authUser->firma ?? 0) ?: ($authUser->fid ?? 0));
  $identityUserIds = $isAuthenticated && !empty($authUser?->id)
    ? collect([(int) $authUser->id])
    : collect();
  $userEmail = mb_strtolower(trim((string) ($authUser?->email ?? '')));

  if (
    $isAuthenticated
    && $userEmail !== ''
    && \App\Models\User::hasUsersColumn('email')
  ) {
    $identityUserIds = $identityUserIds
      ->merge(\App\Models\User::query()
        ->whereRaw('LOWER(TRIM(email)) = ?', [$userEmail])
        ->pluck('id')
        ->map(fn ($id) => (int) $id))
      ->unique()
      ->values();
  }

  $hasProjectTable = \Illuminate\Support\Facades\Schema::hasTable('project');
  $hasProjectUserId = $hasProjectTable && \Illuminate\Support\Facades\Schema::hasColumn('project', 'userid');
  $hasProjectEmail = $hasProjectTable && \Illuminate\Support\Facades\Schema::hasColumn('project', 'email');
  $creatorProjectIds = collect();
  $employeeProjectIds = collect();

  if ($isAuthenticated && $hasProjectTable) {
    $creatorProjectIds = \App\Models\Project::query()
      ->where(function ($query) use ($hasProjectUserId, $hasProjectEmail, $identityUserIds, $userEmail) {
        $hasCondition = false;
        if ($hasProjectUserId && $identityUserIds->isNotEmpty()) {
          $query->whereIn('userid', $identityUserIds->all());
          $hasCondition = true;
        }
        if ($hasProjectEmail && $userEmail !== '') {
          $method = $hasCondition ? 'orWhereRaw' : 'whereRaw';
          $query->{$method}('LOWER(TRIM(email)) = ?', [$userEmail]);
          $hasCondition = true;
        }
        if (!$hasCondition) {
          $query->whereRaw('1 = 0');
        }
      })
      ->pluck('id')
      ->map(fn ($id) => (int) $id)
      ->unique()
      ->values();

    if (\Illuminate\Support\Facades\Schema::hasTable('team_memberships') && $identityUserIds->isNotEmpty()) {
      $employeeProjectIds = \Illuminate\Support\Facades\DB::table('team_memberships')
        ->whereIn('user_id', $identityUserIds->all())
        ->pluck('project_id')
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->diff($creatorProjectIds)
        ->values();
    }
  }

  $userProjectIds = $creatorProjectIds->merge($employeeProjectIds)->unique()->values();

  $projectSelectColumns = ['id', 'num', 'name'];
  if (\Illuminate\Support\Facades\Schema::hasTable('project') && \Illuminate\Support\Facades\Schema::hasColumn('project', 'project_type')) {
    $projectSelectColumns[] = 'project_type';
  }

  $headerProjects = $hasProjectTable && $userProjectIds->isNotEmpty()
    ? \App\Models\Project::query()
        ->whereIn('id', $userProjectIds->all())
        ->orderBy('num')
        ->orderBy('name')
        ->get($projectSelectColumns)
    : collect();
  $creatorHeaderProjects = $headerProjects->whereIn('id', $creatorProjectIds)->values();
  $employeeHeaderProjects = $headerProjects->whereIn('id', $employeeProjectIds)->values();
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
  $isEducationProject = $activeProjectType === 'education';
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
            @if($creatorHeaderProjects->isNotEmpty())
              <optgroup label="{{ __('nav.creator') }}">
                @foreach($creatorHeaderProjects as $project)
                  @php $projectNewOrdersCount = (int) ($newOrdersByProject->get((int) $project->id, 0)); @endphp
                  <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
                    {{ $project->name }} #{{ $project->id }}{{ $projectNewOrdersCount > 0 ? ' | new: ' . $projectNewOrdersCount : '' }}
                  </option>
                @endforeach
              </optgroup>
            @endif
            @if($employeeHeaderProjects->isNotEmpty())
              <optgroup label="{{ __('nav.employee') }}">
                @foreach($employeeHeaderProjects as $project)
                  @php $projectNewOrdersCount = (int) ($newOrdersByProject->get((int) $project->id, 0)); @endphp
                  <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
                    {{ $project->name }} #{{ $project->id }}{{ $projectNewOrdersCount > 0 ? ' | new: ' . $projectNewOrdersCount : '' }}
                  </option>
                @endforeach
              </optgroup>
            @endif
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
    
    <a href="{{ route('micro-business') }}" class="header-public-link">{{ __('nav.business_management') }}</a>
    <a href="{{ route('education.public') }}" class="header-public-link">{{ __('nav.education') }}</a>
    <a href="{{ route('price') }}" class="header-public-link">{{ __('nav.prices') }}</a>
    <a href="{{ route('about') }}" class="header-public-link">{{ __('nav.about') }}</a>
    <a href="{{ route('login') }}" class="header-public-link header-btn-login">{{ __('nav.login') }}</a>
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
  <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="{{ __('nav.open_menu') }}">
    <span></span>
    <span></span>
    <span></span>
  </button>


  

  <nav class="header-nav-menu{{ $isAuthenticated ? ' header-nav-menu--auth' : ' header-nav-menu--public' }}" id="header-nav-menu">
    @if($isAuthenticated)
    @if(!$isBankProject)
      <div class="header-nav-menu__section-label">{{ __('nav.business') }}</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link header-nav-menu__link--with-badge" href="{{ route('document.index', ['doc' => 'ZOUT']) }}">
          {{ __('nav.orders') }}
          @if($newOrdersCount > 0)
            <span class="header-new-orders-badge" title="{{ __('nav.new_orders_badge') }}">{{ $newOrdersCount }}</span>
          @endif
        </a>
        <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'ZIN']) }}">{{ __('nav.purchases') }}</a>
        <a class="header-nav-menu__link" href="{{ route('money.transfers') }}">{{ __('nav.transfers') }}</a>
        <a class="header-nav-menu__link" href="{{ route('goods.index') }}">{{ __('nav.goods') }}</a>
        <a class="header-nav-menu__link" href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a>
      </div>

      <div class="header-nav-menu__section-label">{{ __('nav.production') }}</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'WO1']) }}">{{ __('nav.work_orders') }}</a>
        <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'SP']) }}">{{ __('nav.specifications') }}</a>
      </div>

      <div class="header-nav-menu__section-label">{{ __('nav.investing') }}</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('document.assets.index') }}">{{ __('nav.assets') }}</a>
        <a class="header-nav-menu__link" href="{{ route('document.financing.index') }}">{{ __('nav.financing') }}</a>
      </div>
    @endif

    <div class="header-nav-menu__section-label">{{ __('nav.private') }}</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('money.index') }}">{{ __('nav.money') }}</a>
      <a class="header-nav-menu__link" href="{{ route('deposit.index') }}">{{ __('nav.deposits') }}</a>
    </div>

    <div class="header-nav-menu__section-label">{{ __('nav.management') }}</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('team') }}">{{ __('nav.team') }}</a>
      <a class="header-nav-menu__link" href="{{ route('settings.employeeRoles.index') }}">{{ __('nav.employee_roles') }}</a>
      <a class="header-nav-menu__link" href="{{ route('client.index') }}">{{ __('nav.clients') }}</a>
      <a class="header-nav-menu__link" href="{{ route('reports.index') }}">{{ __('nav.reports') }}</a>
    </div>

    @if($isBankProject)
      <div class="header-nav-menu__section-label">{{ __('nav.bank') }}</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('bank.cash-accounts') }}">{{ __('nav.cash_accounts') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.deposit') }}">{{ __('nav.deposits') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.loanDocs.index') }}">{{ __('nav.credits') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.pools') }}">{{ __('nav.pools') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.pool-movements') }}">{{ __('nav.fund_movements') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.invest') }}">{{ __('nav.investments') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.assets') }}">{{ __('nav.assets') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.stock-analysis') }}">{{ __('nav.stocks') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.exchange') }}">{{ __('nav.fiat_crypto_exchange') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.clearing') }}">{{ __('nav.project_clearing') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.payments') }}">{{ __('nav.payments') }}</a>
        <a class="header-nav-menu__link" href="{{ route('bank.reconciliation') }}">{{ __('nav.reconciliation') }}</a>
        <a class="header-nav-menu__link" href="{{ route('blockchain-monitor.index') }}">Blockchain Monitor</a>
      </div>
    @endif

    @if($isEducationProject)
      <div class="header-nav-menu__section-label">{{ __('nav.education') }}</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('education.course') }}">{{ __('nav.course') }}</a>
        <a class="header-nav-menu__link" href="{{ route('education.material-files.index') }}">{{ __('nav.materials') }}</a>
        <a class="header-nav-menu__link" href="{{ route('education.utilities') }}">{{ __('nav.utilities') }}</a>
        <a class="header-nav-menu__link" href="{{ route('education.tests') }}">{{ __('nav.tests') }}</a>
        <a class="header-nav-menu__link" href="{{ route('education.know-yourself') }}">{{ __('nav.know_yourself') }}</a>
      </div>
    @endif

    <div class="header-nav-menu__section-label">{{ __('nav.other') }}</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('price') }}">{{ __('nav.pay') }}</a>
      <a class="header-nav-menu__link" href="{{ route('news.index') }}">{{ __('nav.news') }}</a>
      <a class="header-nav-menu__link" href="{{ route('settings.index') }}">{{ __('nav.settings') }}</a>
      <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: contents;">
        @csrf
        <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
          class="header-nav-menu__link" id="main-logout-btn">{{ __('nav.logout') }}</a>
      </form>
    </div>
    @else
    <div id="mobile-public-links" class="public-picker-menu">
      <a class="header-nav-menu__link mobile-only-link is-active" href="{{ route('micro-business') }}">{{ __('nav.business_management') }}</a>
      <a class="header-nav-menu__link mobile-only-link is-next-1" href="{{ route('education.public') }}">{{ __('nav.education') }}</a>
      <a class="header-nav-menu__link mobile-only-link is-next-2" href="{{ route('price') }}">{{ __('nav.prices') }}</a>
      <a class="header-nav-menu__link mobile-only-link is-next-3" href="{{ route('team') }}">{{ __('nav.team') }}</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('about') }}">{{ __('nav.about') }}</a>
      <a class="header-nav-menu__link" href="{{ route('login') }}">{{ __('nav.login') }}</a>
    </div>
    @endif
  </nav>
</div>

<div class="site-navigation-spinner" id="site-navigation-spinner" aria-hidden="true">
  <div class="site-navigation-spinner__ring"></div>
</div>

<style>
  /* Desktop: project selector is compact and on the right */
  #header-project-select {
    font-size: 1.5rem;
    font-weight: 600;
  }

  #header-project-select optgroup {
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.7rem;
    font-weight: 600;
  }

  #header-project-select option {
    color: var(--foreground);
    font-size: 1rem;
    font-weight: 400;
  }

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

  .site-navigation-spinner {
    position: fixed;
    inset: 0;
    z-index: 5000;
    display: none;
    place-items: center;
    background: rgba(0, 0, 0, 0.56);
    backdrop-filter: blur(8px);
  }

  .site-navigation-spinner.is-visible {
    display: grid;
  }

  .site-navigation-spinner__ring {
    width: 68px;
    height: 68px;
    border: 4px solid rgba(255, 255, 255, 0.16);
    border-top-color: #fbbf24;
    border-radius: 50%;
    animation: siteSpinnerRotate 0.78s linear infinite;
    box-shadow: 0 0 30px rgba(251, 191, 36, 0.2);
  }

  @keyframes siteSpinnerRotate {
    to {
      transform: rotate(360deg);
    }
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

    .header-nav-menu--public {
      position: absolute;
      top: 100%;
      right: 0;
      left: auto;
      bottom: auto;
      width: min(940px, calc(100vw - 2rem));
      height: auto !important;
      min-height: 420px !important;
      max-height: calc(100vh - 112px) !important;
      padding: 1.15rem;
      overflow: hidden;
      border: 1px solid rgba(251, 191, 36, 0.25);
      border-radius: 16px;
      background:
        linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.16), transparent) center/100% 92px no-repeat,
        linear-gradient(180deg, rgba(19, 24, 33, 0.98), rgba(8, 11, 16, 0.99));
      box-shadow: 0 28px 70px -22px rgba(0, 0, 0, 0.78);
      transform: none;
    }

    .header-nav-menu--public.is-open {
      display: grid;
      place-items: center;
      animation: menuFadeIn 0.2s ease-out;
    }

    .header-nav-menu--public .mobile-only-link {
      display: flex !important;
    }

    .header-nav-menu--public .public-picker-menu {
      position: relative;
      width: 100%;
      height: 420px;
      min-height: 420px;
      perspective: 720px;
      touch-action: none;
      user-select: none;
    }

    .header-nav-menu--auth.desktop-auth-ready {
      position: absolute;
      top: 100%;
      right: 0;
      left: auto;
      width: min(940px, calc(100vw - 2rem));
      max-height: calc(100vh - 112px);
      padding: 0;
      overflow: hidden;
      border: 1px solid rgba(251, 191, 36, 0.25);
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(19, 24, 33, 0.98), rgba(8, 11, 16, 0.99));
      box-shadow: 0 28px 70px -22px rgba(0, 0, 0, 0.78);
    }

    .header-nav-menu--auth.desktop-auth-ready.is-open {
      display: grid;
      animation: menuFadeIn 0.2s ease-out;
    }

    .header-nav-menu--auth.desktop-auth-ready > :not(.desktop-auth-menu) {
      display: none !important;
    }

    .desktop-auth-menu {
      display: grid;
      grid-template-columns: 260px minmax(0, 1fr);
      min-height: 420px;
      max-height: calc(100vh - 112px);
    }

    .desktop-auth-menu__sections {
      display: grid;
      align-content: start;
      gap: 0.4rem;
      padding: 1rem;
      overflow-y: auto;
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(0, 0, 0, 0.18);
    }

    .desktop-auth-menu__section {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      min-height: 54px;
      padding: 0.85rem 1rem;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.045);
      color: rgba(255, 255, 255, 0.82);
      font-size: 1.05rem;
      font-weight: 850;
      text-align: left;
    }

    .desktop-auth-menu__section.is-active {
      border-color: rgba(251, 191, 36, 0.55);
      background: rgba(251, 191, 36, 0.14);
      color: #fbbf24;
      box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.1);
    }

    .desktop-auth-menu__section--logout {
      margin-top: 0.65rem;
      border-color: rgba(248, 113, 113, 0.24);
      color: #fecaca;
    }

    .desktop-auth-menu__section--logout:hover {
      border-color: rgba(248, 113, 113, 0.46);
      background: rgba(248, 113, 113, 0.12);
      color: #fff;
    }

    .desktop-auth-menu__items-wrap {
      display: grid;
      grid-template-rows: auto 1fr;
      min-width: 0;
      min-height: 0;
      padding: 1.15rem;
    }

    .desktop-auth-menu__title {
      margin: 0 0 1rem;
      color: #fbbf24;
      font-size: 1.25rem;
      font-weight: 900;
    }

    .desktop-auth-menu__items {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      align-content: start;
      gap: 0.85rem;
      overflow-y: auto;
      padding-right: 0.25rem;
    }

    .desktop-auth-menu__items.is-switching .desktop-auth-menu__item {
      animation: desktopMenuTileIn 0.28s ease-out both;
    }

    .desktop-auth-menu__items.is-switching .desktop-auth-menu__item:nth-child(2) {
      animation-delay: 0.035s;
    }

    .desktop-auth-menu__items.is-switching .desktop-auth-menu__item:nth-child(3) {
      animation-delay: 0.07s;
    }

    .desktop-auth-menu__items.is-switching .desktop-auth-menu__item:nth-child(4) {
      animation-delay: 0.105s;
    }

    .desktop-auth-menu__items.is-switching .desktop-auth-menu__item:nth-child(n+5) {
      animation-delay: 0.14s;
    }

    @keyframes desktopMenuTileIn {
      from {
        opacity: 0;
        transform: translateY(14px) scale(0.97);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .desktop-auth-menu__item {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 92px;
      padding: 1rem;
      border: 1px solid rgba(251, 191, 36, 0.18);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.055);
      color: #fff;
      font-size: 1.22rem;
      font-weight: 900;
      line-height: 1.18;
      text-align: center;
      transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .desktop-auth-menu__item:hover {
      transform: translateY(-3px);
      border-color: rgba(251, 191, 36, 0.48);
      background: rgba(251, 191, 36, 0.12);
      color: #fbbf24;
    }

    .desktop-auth-menu__item.is-active {
      border-color: rgba(251, 191, 36, 0.72);
      background: rgba(251, 191, 36, 0.16);
      color: #fbbf24;
      box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.14), 0 16px 34px rgba(0, 0, 0, 0.24);
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

    .header-nav-menu--public {
      position: fixed;
      top: 74px;
      right: 12px;
      bottom: 14px;
      left: 12px;
      width: auto;
      min-height: 0;
      padding: 1.35rem;
      border-radius: 24px;
      transform: none;
      background:
        linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.16), transparent) center/100% 92px no-repeat,
        linear-gradient(180deg, rgba(19, 24, 33, 0.97), rgba(8, 11, 16, 0.99));
      overflow: hidden;
    }

    .header-nav-menu--auth.auth-picker-ready {
      position: fixed;
      top: 74px;
      right: 12px;
      bottom: 14px;
      left: 12px;
      width: auto;
      min-height: 0;
      padding: 1.35rem;
      border-radius: 24px;
      background:
        linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.16), transparent) center/100% 92px no-repeat,
        linear-gradient(180deg, rgba(19, 24, 33, 0.97), rgba(8, 11, 16, 0.99));
      overflow: hidden;
    }

    .header-nav-menu--auth.auth-picker-ready.is-open {
      display: grid;
      place-items: center;
      animation: publicPickerIn 0.22s ease-out;
    }

    .header-nav-menu--auth.auth-picker-ready > :not(.auth-picker-menu) {
      display: none !important;
    }

    .header-nav-menu--public.is-open {
      display: grid;
      place-items: center;
      animation: publicPickerIn 0.22s ease-out;
    }

    @keyframes publicPickerIn {
      from { opacity: 0; transform: translateY(-8px) scale(0.96); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .public-picker-menu {
      position: relative;
      width: 100%;
      height: min(100%, 620px);
      min-height: 440px;
      perspective: 720px;
      touch-action: none;
      user-select: none;
    }

    .auth-picker-menu {
      display: grid;
      grid-template-rows: auto 1fr;
      gap: 12px;
      width: 100%;
      height: min(100%, 620px);
      min-height: 440px;
      touch-action: none;
      user-select: none;
    }

    .auth-picker-menu__top {
      display: grid;
      grid-template-columns: 72px 1fr 72px;
      align-items: center;
      gap: 10px;
    }

    .auth-picker-menu__back {
      min-height: 42px;
      border: 1px solid rgba(251, 191, 36, 0.32);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.06);
      color: #fbbf24;
      font-size: 2rem;
      font-weight: 900;
      line-height: 1;
    }

    .auth-picker-menu[data-view="sections"] .auth-picker-menu__back {
      visibility: hidden;
    }

    .auth-picker-menu__title {
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.86rem;
      font-weight: 900;
      letter-spacing: 0.12em;
      text-align: center;
      text-transform: uppercase;
    }

    .auth-picker-menu__stage {
      position: relative;
      perspective: 720px;
    }

    .auth-picker-menu__option {
      position: absolute;
      left: 50%;
      top: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      width: min(86vw, 360px);
      min-height: 84px;
      padding: 0.85rem 1rem;
      border: 1px solid rgba(251, 191, 36, 0.24);
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.075);
      color: #fff;
      font-size: 1.45rem;
      font-weight: 900;
      line-height: 1.08;
      text-align: center;
      opacity: 0;
      pointer-events: none;
      transform: translate(-50%, -50%);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
      transition: opacity 0.24s ease, filter 0.24s ease, transform 0.24s ease, background 0.2s ease, border-color 0.2s ease;
    }

    .auth-picker-menu__option.is-active {
      z-index: 5;
      opacity: 1;
      filter: none;
      pointer-events: auto;
      border-color: rgba(251, 191, 36, 0.72);
      background: rgba(251, 191, 36, 0.14);
      color: #fbbf24;
      font-size: 1.68rem;
      transform: translate(-50%, -50%) scale(1);
      box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.16), 0 18px 42px rgba(0, 0, 0, 0.34);
    }

    .auth-picker-menu__option.is-prev-1,
    .auth-picker-menu__option.is-next-1 {
      z-index: 4;
      opacity: 0.72;
      filter: blur(0.4px);
      pointer-events: auto;
      transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.82) rotateX(var(--picker-tilt));
    }

    .auth-picker-menu__option.is-prev-2,
    .auth-picker-menu__option.is-next-2 {
      z-index: 3;
      opacity: 0.38;
      filter: blur(1.3px);
      pointer-events: auto;
      transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.64) rotateX(var(--picker-tilt));
    }

    .auth-picker-menu__option.is-prev-3,
    .auth-picker-menu__option.is-next-3 {
      z-index: 2;
      opacity: 0.18;
      filter: blur(2.2px);
      transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.5) rotateX(var(--picker-tilt));
    }

    .auth-picker-menu__option.is-prev-1 {
      --picker-offset: -118px;
      --picker-tilt: -18deg;
    }

    .auth-picker-menu__option.is-next-1 {
      --picker-offset: 118px;
      --picker-tilt: 18deg;
    }

    .auth-picker-menu__option.is-prev-2 {
      --picker-offset: -210px;
      --picker-tilt: -28deg;
    }

    .auth-picker-menu__option.is-next-2 {
      --picker-offset: 210px;
      --picker-tilt: 28deg;
    }

    .auth-picker-menu__option.is-prev-3 {
      --picker-offset: -288px;
      --picker-tilt: -34deg;
    }

    .auth-picker-menu__option.is-next-3 {
      --picker-offset: 288px;
      --picker-tilt: 34deg;
    }

    .header-nav-menu--public .header-nav-menu__link {
      position: absolute;
      left: 50%;
      top: 50%;
      width: min(86vw, 360px);
      min-height: 84px;
      padding: 0.85rem 1rem;
      border-radius: 18px;
      border-color: rgba(251, 191, 36, 0.24);
      background: rgba(255, 255, 255, 0.075);
      color: #fff;
      font-size: 1.45rem;
      font-weight: 900;
      line-height: 1.08;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
      opacity: 0;
      pointer-events: none;
      transform: translate(-50%, -50%);
      transition: opacity 0.24s ease, filter 0.24s ease, transform 0.24s ease, background 0.2s ease, border-color 0.2s ease;
    }

    .header-nav-menu--public .header-nav-menu__link:hover {
      color: #111827;
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      border-color: rgba(251, 191, 36, 0.74);
    }

    .header-nav-menu--public .header-nav-menu__link.is-active {
      z-index: 5;
      opacity: 1;
      filter: none;
      pointer-events: auto;
      border-color: rgba(251, 191, 36, 0.72);
      background: rgba(251, 191, 36, 0.14);
      color: #fbbf24;
      font-size: 1.68rem;
      transform: translate(-50%, -50%) scale(1);
      box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.16), 0 18px 42px rgba(0, 0, 0, 0.34);
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-1,
    .header-nav-menu--public .header-nav-menu__link.is-next-1 {
      z-index: 4;
      opacity: 0.72;
      filter: blur(0.4px);
      pointer-events: auto;
      transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.82) rotateX(var(--picker-tilt));
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-2,
    .header-nav-menu--public .header-nav-menu__link.is-next-2 {
      z-index: 3;
      opacity: 0.38;
      filter: blur(1.3px);
      pointer-events: auto;
      transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.64) rotateX(var(--picker-tilt));
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-3,
    .header-nav-menu--public .header-nav-menu__link.is-next-3 {
      z-index: 2;
      opacity: 0.18;
      filter: blur(2.2px);
      transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.5) rotateX(var(--picker-tilt));
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-1 {
      --picker-offset: -118px;
      --picker-tilt: -18deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-1 {
      --picker-offset: 118px;
      --picker-tilt: 18deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-2 {
      --picker-offset: -210px;
      --picker-tilt: -28deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-2 {
      --picker-offset: 210px;
      --picker-tilt: 28deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-3 {
      --picker-offset: -288px;
      --picker-tilt: -34deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-3 {
      --picker-offset: 288px;
      --picker-tilt: 34deg;
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

  @media (max-width: 900px) {
    body.header-menu-open {
      overflow: hidden;
    }
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

    .header-nav-menu--public {
      top: 68px;
      right: 8px;
      bottom: 10px;
      left: 8px;
      width: auto;
      min-height: 0;
      padding: 0.75rem;
    }

    .header-nav-menu--auth.auth-picker-ready {
      top: 5dvh !important;
      right: 8px;
      bottom: auto !important;
      left: 8px;
      height: 90dvh !important;
      min-height: 90dvh !important;
      max-height: 90dvh !important;
      padding: 0.75rem;
    }

    .public-picker-menu {
      height: min(100%, 560px);
      min-height: 400px;
    }

    .auth-picker-menu {
      height: 100%;
      min-height: 0;
    }

    .auth-picker-menu__top {
      grid-template-columns: 58px 1fr 58px;
    }

    .auth-picker-menu__back {
      min-height: 38px;
      font-size: 0.82rem;
    }

    .auth-picker-menu__title {
      font-size: 0.76rem;
    }

    .auth-picker-menu__option {
      width: min(86vw, 304px);
      min-height: 74px;
      font-size: 1.12rem;
      padding: 0.7rem 0.75rem;
    }

    .auth-picker-menu__option.is-active {
      font-size: 1.32rem;
    }

    .auth-picker-menu__option.is-prev-1 {
      --picker-offset: -102px;
    }

    .auth-picker-menu__option.is-next-1 {
      --picker-offset: 102px;
    }

    .auth-picker-menu__option.is-prev-2 {
      --picker-offset: -178px;
    }

    .auth-picker-menu__option.is-next-2 {
      --picker-offset: 178px;
    }

    .auth-picker-menu__option.is-prev-3 {
      --picker-offset: -238px;
    }

    .auth-picker-menu__option.is-next-3 {
      --picker-offset: 238px;
    }

    .header-nav-menu--public .header-nav-menu__link {
      width: min(86vw, 304px);
      min-height: 74px;
      font-size: 1.12rem;
      padding: 0.7rem 0.75rem;
    }

    .header-nav-menu--public .header-nav-menu__link.is-active {
      font-size: 1.32rem;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-1 {
      --picker-offset: -102px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-1 {
      --picker-offset: 102px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-2 {
      --picker-offset: -178px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-2 {
      --picker-offset: 178px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-3 {
      --picker-offset: -238px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-3 {
      --picker-offset: 238px;
    }
  }

  .header-nav-menu--public {
    position: fixed;
    top: 5dvh !important;
    right: 12px;
    bottom: auto !important;
    left: 12px;
    width: auto;
    height: 90dvh !important;
    min-height: 90dvh !important;
    max-height: 90dvh !important;
    padding: 1.35rem;
    border-radius: 24px;
    background:
      linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.16), transparent) center/100% 92px no-repeat,
      linear-gradient(180deg, rgba(19, 24, 33, 0.97), rgba(8, 11, 16, 0.99));
    overflow: hidden;
    transform: none;
  }

  .header-nav-menu--public.is-open {
    display: grid;
    place-items: center;
    animation: publicPickerIn 0.22s ease-out;
  }

  .header-nav-menu--public .mobile-only-link {
    display: flex !important;
  }

  .header-nav-menu--public .public-picker-menu {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 0;
    perspective: 720px;
    touch-action: none;
    user-select: none;
  }

  .header-nav-menu--public .header-nav-menu__link {
    position: absolute;
    left: 50%;
    top: 50%;
    width: min(86vw, 360px);
    min-height: 84px;
    padding: 0.85rem 1rem;
    border-radius: 18px;
    border-color: rgba(251, 191, 36, 0.24);
    background: rgba(255, 255, 255, 0.075);
    color: #fff;
    font-size: 1.45rem;
    font-weight: 900;
    line-height: 1.08;
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, -50%);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
    transition: opacity 0.24s ease, filter 0.24s ease, transform 0.24s ease, background 0.2s ease, border-color 0.2s ease;
  }

  .header-nav-menu--public .header-nav-menu__link.is-active {
    z-index: 5;
    opacity: 1;
    filter: none;
    pointer-events: auto;
    border-color: rgba(251, 191, 36, 0.72);
    background: rgba(251, 191, 36, 0.14);
    color: #fbbf24;
    font-size: 1.68rem;
    transform: translate(-50%, -50%) scale(1);
    box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.16), 0 18px 42px rgba(0, 0, 0, 0.34);
  }

  .header-nav-menu--public .header-nav-menu__link.is-prev-1,
  .header-nav-menu--public .header-nav-menu__link.is-next-1 {
    z-index: 4;
    opacity: 0.72;
    filter: blur(0.4px);
    pointer-events: auto;
    transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.82) rotateX(var(--picker-tilt));
  }

  .header-nav-menu--public .header-nav-menu__link.is-prev-2,
  .header-nav-menu--public .header-nav-menu__link.is-next-2 {
    z-index: 3;
    opacity: 0.38;
    filter: blur(1.3px);
    pointer-events: auto;
    transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.64) rotateX(var(--picker-tilt));
  }

  .header-nav-menu--public .header-nav-menu__link.is-prev-3,
  .header-nav-menu--public .header-nav-menu__link.is-next-3 {
    z-index: 2;
    opacity: 0.18;
    filter: blur(2.2px);
    transform: translate(-50%, calc(-50% + var(--picker-offset))) scale(0.5) rotateX(var(--picker-tilt));
  }

  .header-nav-menu--public .header-nav-menu__link.is-prev-1 {
    --picker-offset: -118px;
    --picker-tilt: -18deg;
  }

  .header-nav-menu--public .header-nav-menu__link.is-next-1 {
    --picker-offset: 118px;
    --picker-tilt: 18deg;
  }

  .header-nav-menu--public .header-nav-menu__link.is-prev-2 {
    --picker-offset: -210px;
    --picker-tilt: -28deg;
  }

  .header-nav-menu--public .header-nav-menu__link.is-next-2 {
    --picker-offset: 210px;
    --picker-tilt: 28deg;
  }

  .header-nav-menu--public .header-nav-menu__link.is-prev-3 {
    --picker-offset: -288px;
    --picker-tilt: -34deg;
  }

  .header-nav-menu--public .header-nav-menu__link.is-next-3 {
    --picker-offset: 288px;
    --picker-tilt: 34deg;
  }

  @media (max-width: 520px) {
    .header-nav-menu--public {
      top: 5dvh !important;
      right: 8px;
      bottom: auto !important;
      left: 8px;
      height: 90dvh !important;
      min-height: 90dvh !important;
      max-height: 90dvh !important;
      padding: 0.75rem;
    }

    .header-nav-menu--public .public-picker-menu {
      height: 100%;
      min-height: 0;
    }

    .header-nav-menu--public .header-nav-menu__link {
      width: min(86vw, 304px);
      min-height: 74px;
      font-size: 1.12rem;
      padding: 0.7rem 0.75rem;
    }

    .header-nav-menu--public .header-nav-menu__link.is-active {
      font-size: 1.32rem;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-1 {
      --picker-offset: -102px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-1 {
      --picker-offset: 102px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-2 {
      --picker-offset: -178px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-2 {
      --picker-offset: 178px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-3 {
      --picker-offset: -238px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-3 {
      --picker-offset: 238px;
    }
  }

  @media (max-width: 900px) {
    .header-nav-menu--public,
    .header-nav-menu--auth.auth-picker-ready {
      top: 5dvh !important;
      right: 8px !important;
      bottom: auto !important;
      left: 8px !important;
      width: auto !important;
      height: 90dvh !important;
      min-height: 90dvh !important;
      max-height: 90dvh !important;
      padding: 0.75rem !important;
    }

    .header-nav-menu--public .public-picker-menu,
    .auth-picker-menu {
      height: 100% !important;
      min-height: 0 !important;
    }
  }

  @media (min-width: 901px) {
    .header-nav-menu--public {
      position: absolute;
      top: 100%;
      right: 0;
      bottom: auto;
      left: auto;
      width: min(940px, calc(100vw - 2rem));
      height: auto !important;
      min-height: 420px !important;
      max-height: calc(100vh - 112px) !important;
      padding: 1.15rem;
      transform: none;
    }

    .header-nav-menu--public .public-picker-menu {
      height: 420px !important;
      min-height: 420px !important;
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
    const navigationSpinner = document.getElementById('site-navigation-spinner');
    const publicPicker = document.getElementById('mobile-public-links');
    const publicLinks = publicPicker ? Array.from(publicPicker.querySelectorAll('a')) : [];
    const authPickerBackLabel = @json(__('nav.back'));
    const authPickerChooseSectionLabel = @json(__('nav.choose_section'));
    const isSettingsPage = window.location.pathname.replace(/\/+$/, '') === '/settings'
      || window.location.pathname.startsWith('/settings/');
    let activePublicIndex = 0;
    let publicTouchStartY = null;
    let publicTouchLastY = null;
    let publicTouchMoved = false;
    let activeAuthIndex = 0;
    let activeAuthSectionIndex = 0;
    let activeAuthView = 'sections';
    let authTouchStartY = null;
    let authTouchLastY = null;
    let authTouchMoved = false;

    function normalizeMenuUrl(url) {
      try {
        const parsedUrl = new URL(url, window.location.origin);
        const pathname = parsedUrl.pathname.replace(/\/+$/, '') || '/';
        parsedUrl.searchParams.delete('lang');
        return pathname + parsedUrl.search;
      } catch (error) {
        return '';
      }
    }

    function isCurrentMenuLink(link) {
      if (!link) {
        return false;
      }

      const href = link.getAttribute('href');

      if (!href || href === '#') {
        return false;
      }

      const currentUrl = normalizeMenuUrl(window.location.href);
      const linkUrl = normalizeMenuUrl(link.href);

      if (!linkUrl) {
        return false;
      }

      return linkUrl === currentUrl;
    }

    function closeHeaderMenu() {
      if (!burger || !menu) {
        return;
      }

      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.classList.remove('header-menu-open');
    }

    function showNavigationSpinner() {
      if (!navigationSpinner) {
        return;
      }

      navigationSpinner.classList.add('is-visible');
      navigationSpinner.setAttribute('aria-hidden', 'false');
    }

    function shouldShowSpinnerForLink(link) {
      if (!link) {
        return false;
      }

      const href = link.getAttribute('href');
      const target = link.getAttribute('target');

      if (!href || href === '#' || target === '_blank') {
        return false;
      }

      return true;
    }

    function shouldShowSpinnerForButton(button) {
      if (!button) {
        return false;
      }

      if (isSettingsPage) {
        return false;
      }

      if (button.disabled || button.getAttribute('aria-disabled') === 'true') {
        return false;
      }

      if (button.id === 'header-burger') {
        return false;
      }

      if (button.closest('.auth-picker-menu') || button.closest('.desktop-auth-menu__sections')) {
        return false;
      }

      return true;
    }

    if (burger && menu) {
      function syncPublicPicker() {
        if (!publicLinks.length) {
          return;
        }

        publicLinks.forEach((link, index) => {
          link.classList.remove(
            'is-active',
            'is-prev-1',
            'is-prev-2',
            'is-prev-3',
            'is-next-1',
            'is-next-2',
            'is-next-3'
          );

          const offset = index - activePublicIndex;

          if (offset === 0) {
            link.classList.add('is-active');
            link.setAttribute('aria-current', 'true');
            return;
          }

          link.removeAttribute('aria-current');

          if (offset < 0 && Math.abs(offset) <= 3) {
            link.classList.add(`is-prev-${Math.abs(offset)}`);
          }

          if (offset > 0 && offset <= 3) {
            link.classList.add(`is-next-${offset}`);
          }
        });
      }

      const currentPublicIndex = publicLinks.findIndex((link) => isCurrentMenuLink(link));

      if (currentPublicIndex >= 0) {
        activePublicIndex = currentPublicIndex;
      }

      syncPublicPicker();

      function createPickerOption(label, index) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'auth-picker-menu__option';
        button.dataset.index = String(index);
        button.textContent = label;
        return button;
      }

      function applyPickerPositionClasses(items, activeIndex) {
        items.forEach((item, index) => {
          item.classList.remove(
            'is-active',
            'is-prev-1',
            'is-prev-2',
            'is-prev-3',
            'is-next-1',
            'is-next-2',
            'is-next-3'
          );

          const offset = index - activeIndex;

          if (offset === 0) {
            item.classList.add('is-active');
            item.setAttribute('aria-current', 'true');
            return;
          }

          item.removeAttribute('aria-current');

          if (offset < 0 && Math.abs(offset) <= 3) {
            item.classList.add(`is-prev-${Math.abs(offset)}`);
          }

          if (offset > 0 && offset <= 3) {
            item.classList.add(`is-next-${offset}`);
          }
        });
      }

      function collectAuthMenuSections() {
        const sections = [];
        let currentSection = null;

        Array.from(menu.children).forEach((child) => {
          if (child.classList.contains('desktop-auth-menu') || child.classList.contains('auth-picker-menu')) {
            return;
          }

          if (child.classList.contains('header-nav-menu__section-label')) {
            currentSection = {
              label: child.textContent.trim(),
              links: [],
            };
            sections.push(currentSection);
            return;
          }

          if (child.classList.contains('header-nav-menu__grid') && currentSection) {
            currentSection.links.push(...Array.from(child.querySelectorAll('a.header-nav-menu__link')));
          }
        });

        return sections.filter((section) => section.links.length > 0);
      }

      function buildDesktopAuthMenu() {
        if (!menu.classList.contains('header-nav-menu--auth')) {
          return;
        }

        const sections = collectAuthMenuSections();

        if (!sections.length) {
          return;
        }

        const desktopLogoutLink = sections
          .flatMap((section) => section.links)
          .find((link) => link.id === 'main-logout-btn');
        const desktopSections = sections
          .map((section) => ({
            label: section.label,
            links: section.links.filter((link) => link.id !== 'main-logout-btn'),
          }))
          .filter((section) => section.links.length > 0);

        if (!desktopSections.length) {
          return;
        }

        let activeDesktopSectionIndex = desktopSections.findIndex((section) => section.links.some((link) => isCurrentMenuLink(link)));

        if (activeDesktopSectionIndex < 0) {
          activeDesktopSectionIndex = 0;
        }

        const desktopMenu = document.createElement('div');
        desktopMenu.className = 'desktop-auth-menu';
        desktopMenu.addEventListener('click', function (event) {
          event.stopPropagation();
        });

        const sectionsColumn = document.createElement('div');
        sectionsColumn.className = 'desktop-auth-menu__sections';

        const itemsWrap = document.createElement('div');
        itemsWrap.className = 'desktop-auth-menu__items-wrap';

        const title = document.createElement('div');
        title.className = 'desktop-auth-menu__title';

        const itemsGrid = document.createElement('div');
        itemsGrid.className = 'desktop-auth-menu__items';

        itemsWrap.append(title, itemsGrid);
        desktopMenu.append(sectionsColumn, itemsWrap);

        function renderDesktopMenu(animateItems = false) {
          const sectionButtons = desktopSections.map((section, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'desktop-auth-menu__section';
            button.textContent = section.label;

            if (index === activeDesktopSectionIndex) {
              button.classList.add('is-active');
            }

            button.addEventListener('click', function (event) {
              event.preventDefault();
              event.stopPropagation();

              if (index === activeDesktopSectionIndex) {
                return;
              }

              activeDesktopSectionIndex = index;
              renderDesktopMenu(true);
            });

            return button;
          });

          if (desktopLogoutLink) {
            const logoutButton = document.createElement('button');
            logoutButton.type = 'button';
            logoutButton.className = 'desktop-auth-menu__section desktop-auth-menu__section--logout';
            logoutButton.textContent = desktopLogoutLink.textContent.trim().replace(/\s+/g, ' ');
            logoutButton.addEventListener('click', function (event) {
              event.preventDefault();
              event.stopPropagation();
              showNavigationSpinner();
              closeHeaderMenu();
              desktopLogoutLink.click();
            });
            sectionButtons.push(logoutButton);
          }

          sectionsColumn.replaceChildren(...sectionButtons);

          const activeSection = desktopSections[activeDesktopSectionIndex];
          title.textContent = activeSection.label;
          itemsGrid.classList.remove('is-switching');
          itemsGrid.replaceChildren(...activeSection.links.map((link) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'desktop-auth-menu__item';
            button.textContent = link.textContent.trim().replace(/\s+/g, ' ');

            if (isCurrentMenuLink(link)) {
              button.classList.add('is-active');
            }

            button.addEventListener('click', function (event) {
              event.preventDefault();
              event.stopPropagation();

              if (shouldShowSpinnerForLink(link)) {
                showNavigationSpinner();
              }

              closeHeaderMenu();
              link.click();
            });

            return button;
          }));

          if (animateItems) {
            window.requestAnimationFrame(function () {
              itemsGrid.classList.add('is-switching');
            });
          }
        }

        renderDesktopMenu();
        menu.prepend(desktopMenu);
        menu.classList.add('desktop-auth-ready');
      }

      function buildAuthPicker() {
        if (!menu.classList.contains('header-nav-menu--auth')) {
          return;
        }

        const availableSections = collectAuthMenuSections();

        if (!availableSections.length) {
          return;
        }

        const matchedSectionIndex = availableSections.findIndex((section) => section.links.some((link) => isCurrentMenuLink(link)));
        const matchedItemIndex = matchedSectionIndex >= 0
          ? availableSections[matchedSectionIndex].links.findIndex((link) => isCurrentMenuLink(link))
          : -1;

        if (matchedSectionIndex >= 0 && matchedItemIndex >= 0) {
          activeAuthSectionIndex = matchedSectionIndex;
          activeAuthIndex = matchedItemIndex;
          activeAuthView = 'items';
        }

        const picker = document.createElement('div');
        picker.className = 'auth-picker-menu';
        picker.dataset.view = 'sections';
        picker.innerHTML = [
          '<div class="auth-picker-menu__top">',
          '<button type="button" class="auth-picker-menu__back" aria-label="' + authPickerBackLabel + '">‹</button>',
          '<div class="auth-picker-menu__title"></div>',
          '<span></span>',
          '</div>',
          '<div class="auth-picker-menu__stage"></div>',
        ].join('');

        const title = picker.querySelector('.auth-picker-menu__title');
        const stage = picker.querySelector('.auth-picker-menu__stage');
        const backButton = picker.querySelector('.auth-picker-menu__back');

        function currentItems() {
          return activeAuthView === 'sections'
            ? availableSections.map((section) => ({ label: section.label, target: section }))
            : availableSections[activeAuthSectionIndex].links.map((link) => ({
                label: link.textContent.trim().replace(/\s+/g, ' '),
                target: link,
              }));
        }

        function syncAuthPicker() {
          const items = currentItems();
          activeAuthIndex = Math.max(0, Math.min(activeAuthIndex, items.length - 1));
          picker.dataset.view = activeAuthView;
          title.textContent = activeAuthView === 'sections'
            ? authPickerChooseSectionLabel
            : availableSections[activeAuthSectionIndex].label;
          stage.replaceChildren(...items.map((item, index) => createPickerOption(item.label, index)));
          applyPickerPositionClasses(Array.from(stage.children), activeAuthIndex);
        }

        function moveAuthPicker(direction) {
          const items = currentItems();
          const nextIndex = activeAuthIndex + direction;

          if (nextIndex < 0 || nextIndex >= items.length) {
            return;
          }

          activeAuthIndex = nextIndex;
          syncAuthPicker();
        }

        stage.addEventListener('click', function (event) {
          const option = event.target.closest('.auth-picker-menu__option');

          if (!option || !stage.contains(option)) {
            return;
          }

          event.preventDefault();
          event.stopPropagation();

          const nextIndex = Number(option.dataset.index);

          if (Number.isNaN(nextIndex)) {
            return;
          }

          if (authTouchMoved) {
            event.stopImmediatePropagation();
            authTouchMoved = false;
            return;
          }

          if (nextIndex !== activeAuthIndex) {
            activeAuthIndex = nextIndex;
            syncAuthPicker();
            return;
          }

          if (activeAuthView === 'sections') {
            activeAuthSectionIndex = activeAuthIndex;
            activeAuthView = 'items';
            activeAuthIndex = 0;
            syncAuthPicker();
            return;
          }

          const originalLink = currentItems()[activeAuthIndex]?.target;

          if (originalLink) {
            if (shouldShowSpinnerForLink(originalLink)) {
              showNavigationSpinner();
            }

            originalLink.click();
          }
        });

        backButton.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();

          if (activeAuthView === 'sections') {
            return;
          }

          activeAuthView = 'sections';
          activeAuthIndex = activeAuthSectionIndex;
          syncAuthPicker();
        });

        picker.addEventListener('touchstart', function (event) {
          if (!event.touches.length) {
            return;
          }

          authTouchStartY = event.touches[0].clientY;
          authTouchLastY = authTouchStartY;
          authTouchMoved = false;
        }, { passive: true });

        picker.addEventListener('touchmove', function (event) {
          if (authTouchStartY === null || !event.touches.length) {
            return;
          }

          event.preventDefault();

          const currentY = event.touches[0].clientY;
          const deltaY = currentY - authTouchLastY;

          if (Math.abs(currentY - authTouchStartY) > 12) {
            authTouchMoved = true;
          }

          if (Math.abs(deltaY) < 46) {
            return;
          }

          moveAuthPicker(deltaY < 0 ? 1 : -1);
          authTouchLastY = currentY;
        }, { passive: false });

        picker.addEventListener('touchend', function () {
          authTouchStartY = null;
          authTouchLastY = null;
          window.setTimeout(function () {
            authTouchMoved = false;
          }, 80);
        });

        syncAuthPicker();
        menu.prepend(picker);
        menu.classList.add('auth-picker-ready');
      }

      buildDesktopAuthMenu();
      buildAuthPicker();

      if (publicPicker && publicLinks.length) {
        function movePublicPicker(direction) {
          const nextIndex = activePublicIndex + direction;

          if (nextIndex < 0 || nextIndex >= publicLinks.length) {
            return;
          }

          activePublicIndex = nextIndex;
          syncPublicPicker();
        }

        publicPicker.addEventListener('click', function (event) {
          const link = event.target.closest('a');

          if (!link || !publicPicker.contains(link)) {
            return;
          }

          const nextIndex = publicLinks.indexOf(link);

          if (nextIndex === -1 || nextIndex === activePublicIndex) {
            return;
          }

          if (publicTouchMoved) {
            event.preventDefault();
            event.stopImmediatePropagation();
            publicTouchMoved = false;
            return;
          }

          event.preventDefault();
          event.stopImmediatePropagation();
          activePublicIndex = nextIndex;
          syncPublicPicker();
        }, true);

        publicPicker.addEventListener('touchstart', function (event) {
          if (!event.touches.length) {
            return;
          }

          publicTouchStartY = event.touches[0].clientY;
          publicTouchLastY = publicTouchStartY;
          publicTouchMoved = false;
        }, { passive: true });

        publicPicker.addEventListener('touchmove', function (event) {
          if (publicTouchStartY === null || !event.touches.length) {
            return;
          }

          event.preventDefault();

          const currentY = event.touches[0].clientY;
          const deltaY = currentY - publicTouchLastY;

          if (Math.abs(currentY - publicTouchStartY) > 12) {
            publicTouchMoved = true;
          }

          if (Math.abs(deltaY) < 46) {
            return;
          }

          movePublicPicker(deltaY < 0 ? 1 : -1);
          publicTouchLastY = currentY;
        }, { passive: false });

        publicPicker.addEventListener('touchend', function () {
          publicTouchStartY = null;
          publicTouchLastY = null;
          window.setTimeout(function () {
            publicTouchMoved = false;
          }, 80);
        });
      }

      burger.addEventListener('click', function () {
        const expanded = burger.getAttribute('aria-expanded') === 'true';
        burger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        menu.classList.toggle('is-open', !expanded);
        document.body.classList.toggle('header-menu-open', !expanded);

        if (!expanded) {
          syncPublicPicker();
        }
      });

      menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', function () {
          if (shouldShowSpinnerForLink(link)) {
            showNavigationSpinner();
          }

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

      document.addEventListener('click', function (event) {
        const button = event.target.closest('button, input[type="button"], input[type="submit"], input[type="reset"], [role="button"], .btn');

        if (!button || !shouldShowSpinnerForButton(button)) {
          return;
        }

        showNavigationSpinner();
      }, true);

      window.addEventListener('pageshow', function () {
        if (!navigationSpinner) {
          return;
        }

        navigationSpinner.classList.remove('is-visible');
        navigationSpinner.setAttribute('aria-hidden', 'true');
      });
    }
  })();
</script>
@endpush
