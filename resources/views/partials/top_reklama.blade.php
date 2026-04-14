{{-- top_reklama.blade.php --}}
@php
  $headerProjects = \Illuminate\Support\Facades\Schema::hasTable('project')
    ? \App\Models\Project::query()->orderBy('num')->orderBy('name')->get(['id', 'num', 'name'])
    : collect();
  $activeFid = (int) session('fid', 0);
  $activeLang = \App\Models\Field::normalizeLocale(app()->getLocale());
@endphp

<div class="header-bar">
  <!-- Desktop: single row -->
  <a href="/" class="header-bar__logo text-decoration-none">
    <span class="header-bar__title">{{ config('app.name') }}: {{ session('name1') ?? '' }}</span>
  </a>

  <div class="header-lang-switch" aria-label="{{ __('nav.language') }}">
    @foreach(['ru' => 'RU', 'ua' => 'UA', 'en' => 'EN'] as $langCode => $langLabel)
      <a
        href="{{ request()->fullUrlWithQuery(['lang' => $langCode]) }}"
        class="header-lang-switch__link {{ $activeLang === $langCode ? 'is-active' : '' }}"
      >{{ $langLabel }}</a>
    @endforeach

    <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="Відкрити меню">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>


  

  <nav class="header-nav-menu" id="header-nav-menu">
    @if(session('name1'))
    @if($headerProjects->isNotEmpty())
    <div class="header-nav-menu__section">
      <label class="header-nav-menu__label">{{ __('nav.project') }}</label>
      <form method="POST" action="{{ route('settings.switchProject') }}" class="header-nav-menu__project-form">
        @csrf
        <select name="fid" class="header-nav-menu__project-select" onchange="this.form.submit()">
          @foreach($headerProjects as $project)
          <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
            #{{ $project->id }}{{ !empty($project->num) ? ' / ' . $project->num : '' }} {{ $project->name }}
          </option>
          @endforeach
        </select>
      </form>
    </div>
    @endif

    <a class="header-nav-menu__link" href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a>
    <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'ZOUT']) }}">{{ __('nav.orders') }}</a>
    <a class="header-nav-menu__link" href="{{ route('document.index', ['doc' => 'ZIN']) }}">{{ __('nav.purchases') }}</a>
    <a class="header-nav-menu__link" href="{{ route('client.index') }}">{{ __('nav.clients') }}</a>
    <a class="header-nav-menu__link" href="{{ route('goods.index') }}">{{ __('nav.goods') }}</a>
    <a class="header-nav-menu__link" href="{{ route('reports.index') }}">{{ __('nav.reports') }}</a>
    <a class="header-nav-menu__link" href="{{ route('news.index') }}">{{ __('nav.news') }}</a>
    <a class="header-nav-menu__link" href="{{ route('money.index') }}">{{ __('nav.money') }}</a>
    <a class="header-nav-menu__link" href="{{ route('deposit.index') }}">{{ __('nav.deposits') }}</a>
    <a class="header-nav-menu__link" href="{{ route('settings.index') }}">{{ __('nav.settings') }}</a>

    <form method="POST" action="{{ route('logout') }}" id="logout-form">
      @csrf
      <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
        class="header-nav-menu__link">{{ __('nav.logout') }}</a>
    </form>
    @else
    <a class="header-nav-menu__link" id="btn_login" style="cursor:pointer;">{{ __('nav.login') }}</a>
    @endif
  </nav>
</div>

<style>
  /* Desktop: project selector is compact and on the right */
  @media (min-width: 901px) {
    .header-bar {
      flex-wrap: nowrap;
    }

    .header-bar__bottom {
      flex-wrap: nowrap;
    }

    .header-project-switch {
      margin-left: auto;
      order: 3;
    }

    .header-project-switch__select {
      min-width: 140px;
      max-width: 200px;
    }

    .header-project-switch__label {
      display: block;
    }

    .header-project-name {
      display: none;
    }

    .header-lang-switch {
      order: 2;
      margin-left: 0;
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

    .header-bar__logo {
      order: 1;
      flex: 1 1 auto;
      min-width: 0;
    }

    .header-bar__logo .header-bar__title {
      font-size: 0.95rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Project selector on mobile - compact dropdown */
    .header-project-switch {
      order: 2;
      display: flex;
      align-items: center;
      gap: 0;
      margin-left: auto;
    }

    .header-project-switch__select {
      min-width: 0;
      width: auto;
      max-width: 150px;
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
      margin-left: 0;
    }

    .header-lang-switch__link {
      min-width: 32px;
      height: 30px;
      padding: 0 0.5rem;
      font-size: 0.7rem;
      border-radius: 8px;
      border-width: 1px;
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
      height: 2.5px;
      margin: 2.5px 0;
    }

    .header-nav-menu {
      right: 0.5rem;
      left: 0.5rem;
      min-width: 0;
      padding: 0.4rem;
    }

    .header-nav-menu__link {
      padding: 0.55rem 0.75rem;
      font-size: 1.1rem;
    }

    .header-nav-menu__section {
      padding: 0.5rem 0.75rem;
      margin-bottom: 0.35rem;
    }

    .header-nav-menu__label {
      font-size: 0.7rem;
      margin-bottom: 0.35rem;
    }

    .header-nav-menu__project-select {
      height: 38px;
      font-size: 0.95rem;
    }
  }

  /* Project selector in mobile menu */
  .header-nav-menu__section {
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.35rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .header-nav-menu__label {
    display: block;
    color: var(--muted-foreground);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.35rem;
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
</style>

@push('scripts')
<script>
  (function () {
    const burger = document.getElementById('header-burger');
    const menu = document.getElementById('header-nav-menu');

    if (!burger || !menu) {
      return;
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

      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.classList.remove('header-menu-open');
    });
  })();
</script>
@endpush
