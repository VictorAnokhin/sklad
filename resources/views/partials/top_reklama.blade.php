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
              <optgroup label="Создатель">
                @foreach($creatorHeaderProjects as $project)
                  @php $projectNewOrdersCount = (int) ($newOrdersByProject->get((int) $project->id, 0)); @endphp
                  <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
                    {{ $project->name }} #{{ $project->id }}{{ $projectNewOrdersCount > 0 ? ' | new: ' . $projectNewOrdersCount : '' }}
                  </option>
                @endforeach
              </optgroup>
            @endif
            @if($employeeHeaderProjects->isNotEmpty())
              <optgroup label="Сотрудник">
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
    
    <a href="{{ route('micro-business') }}" class="header-public-link">Управление бизнесом</a>
    <a href="{{ route('education.public') }}" class="header-public-link">Обучение</a>
    <a href="{{ route('price') }}" class="header-public-link">Цены</a>
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


  

  <nav class="header-nav-menu{{ !$isAuthenticated ? ' header-nav-menu--public' : '' }}" id="header-nav-menu">
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
        <a class="header-nav-menu__link" href="{{ route('document.assets.index') }}">Активы</a>
        <a class="header-nav-menu__link" href="{{ route('document.financing.index') }}">Финансирование</a>
        <a class="header-nav-menu__link" href="{{ route('goods.index') }}">{{ __('nav.goods') }}</a>
      </div>

      <div class="header-nav-menu__section-label">Производство</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'WO1']) }}">Наряды WO1</a>
        <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'SP']) }}">Спецификации SP</a>
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
    </div>

    @if($isBankProject)
      <div class="header-nav-menu__section-label">Банк</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('bank.cash-accounts') }}">Кассы/Счета</a>
        <a class="header-nav-menu__link" href="{{ route('bank.deposit') }}">Депозиты</a>
        <a class="header-nav-menu__link" href="{{ route('bank.loanDocs.index') }}">Кредиты</a>
        <a class="header-nav-menu__link" href="{{ route('bank.pools') }}">Пулы</a>
        <a class="header-nav-menu__link" href="{{ route('bank.pool-movements') }}">Движение средств</a>
        <a class="header-nav-menu__link" href="{{ route('bank.invest') }}">Инвестиции</a>
        <a class="header-nav-menu__link" href="{{ route('bank.assets') }}">Активы</a>
        <a class="header-nav-menu__link" href="{{ route('bank.stock-analysis') }}">Акции</a>
        <a class="header-nav-menu__link" href="{{ route('bank.exchange') }}">Обмен фиат/крипта</a>
        <a class="header-nav-menu__link" href="{{ route('bank.clearing') }}">Клиринг проектов</a>
        <a class="header-nav-menu__link" href="{{ route('bank.payments') }}">Платежи</a>
        <a class="header-nav-menu__link" href="{{ route('bank.reconciliation') }}">Сверка</a>
        <a class="header-nav-menu__link" href="{{ route('blockchain-monitor.index') }}">Blockchain Monitor</a>
      </div>
    @endif

    @if($isEducationProject)
      <div class="header-nav-menu__section-label">Образование</div>
      <div class="header-nav-menu__grid">
        <a class="header-nav-menu__link" href="{{ route('education.course') }}">Курс обучения</a>
        <a class="header-nav-menu__link" href="{{ route('education.material-files.index') }}">Материалы</a>
        <a class="header-nav-menu__link" href="{{ route('education.utilities') }}">Утилиты</a>
        <a class="header-nav-menu__link" href="{{ route('education.tests') }}">Тесты</a>
        <a class="header-nav-menu__link" href="{{ route('education.know-yourself') }}">Узнай себя</a>
      </div>
    @endif

    <div class="header-nav-menu__section-label">Прочее</div>
    <div class="header-nav-menu__grid">
      <a class="header-nav-menu__link" href="{{ route('price') }}">Оплатить</a>
      <a class="header-nav-menu__link" href="{{ route('settings.index') }}">{{ __('nav.settings') }}</a>
      <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: contents;">
        @csrf
        <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
          class="header-nav-menu__link" id="main-logout-btn">{{ __('nav.logout') }}</a>
      </form>
    </div>
    @else
    <div id="mobile-public-links" class="public-picker-menu">
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('micro-business') }}">Управление бизнесом</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('education.public') }}">Обучение</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('price') }}">Цены</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('team') }}">Команда</a>
      <a class="header-nav-menu__link mobile-only-link" href="{{ route('about') }}">О проекте</a>
      <a class="header-nav-menu__link" href="{{ route('login') }}">Войти</a>
    </div>
    @endif
  </nav>
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

    .header-nav-menu--public {
      left: 50%;
      right: auto;
      width: min(92vw, 430px);
      min-height: 460px;
      padding: 1.2rem;
      border-radius: 24px;
      transform: translateX(-50%);
      background:
        linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.16), transparent) center/100% 92px no-repeat,
        linear-gradient(180deg, rgba(19, 24, 33, 0.97), rgba(8, 11, 16, 0.99));
      overflow: hidden;
    }

    .header-nav-menu--public.is-open {
      display: grid;
      place-items: center;
      animation: publicPickerIn 0.22s ease-out;
    }

    @keyframes publicPickerIn {
      from { opacity: 0; transform: translateX(-50%) translateY(-8px) scale(0.96); }
      to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
    }

    .public-picker-menu {
      position: relative;
      width: 100%;
      height: 390px;
      perspective: 720px;
      touch-action: pan-y;
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
      --picker-offset: -96px;
      --picker-tilt: -18deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-1 {
      --picker-offset: 96px;
      --picker-tilt: 18deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-2 {
      --picker-offset: -164px;
      --picker-tilt: -28deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-2 {
      --picker-offset: 164px;
      --picker-tilt: 28deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-3 {
      --picker-offset: -216px;
      --picker-tilt: -34deg;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-3 {
      --picker-offset: 216px;
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
      width: min(96vw, 360px);
      min-height: 420px;
      padding: 0.75rem;
    }

    .public-picker-menu {
      height: 360px;
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
      --picker-offset: -84px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-1 {
      --picker-offset: 84px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-2 {
      --picker-offset: -142px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-2 {
      --picker-offset: 142px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-prev-3 {
      --picker-offset: -188px;
    }

    .header-nav-menu--public .header-nav-menu__link.is-next-3 {
      --picker-offset: 188px;
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
    const publicPicker = document.getElementById('mobile-public-links');
    const publicLinks = publicPicker ? Array.from(publicPicker.querySelectorAll('a')) : [];
    let activePublicIndex = 0;
    let publicTouchStartY = null;
    let publicTouchLastY = null;
    let publicTouchMoved = false;

    function closeHeaderMenu() {
      if (!burger || !menu) {
        return;
      }

      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.classList.remove('header-menu-open');
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

      syncPublicPicker();

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
        }, { passive: true });

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
