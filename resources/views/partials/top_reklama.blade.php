{{-- top_reklama.blade.php --}}
@php
  $headerProjects = \Illuminate\Support\Facades\Schema::hasTable('project')
    ? \App\Models\Project::query()->orderBy('num')->orderBy('name')->get(['id', 'num', 'name'])
    : collect();
  $activeFid = (int) session('fid', 0);
  $activeLang = \App\Models\Field::normalizeLocale(app()->getLocale());
@endphp

<div class="header-bar">
  <!-- Level 1: App name + burger -->
  <div class="header-bar__top">
    <a href="/" class="header-bar__logo text-decoration-none">
      <span class="header-bar__title">{{ config('app.name') }}: {{ session('name1') ?? '' }}</span>
    </a>

    <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="Відкрити меню">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>

  <!-- Level 2: Projects (left) + Languages (right) -->
  <div class="header-bar__bottom">
    @if(session('name1') && $headerProjects->isNotEmpty())
    <form method="POST" action="{{ route('settings.switchProject') }}" class="header-project-switch" id="header-project-switch-form">
      @csrf
      <label for="header-project-select" class="header-project-switch__label">{{ __('nav.project') }}</label>
      <select name="fid" id="header-project-select" class="header-project-switch__select" onchange="this.form.submit()">
        @foreach($headerProjects as $project)
        <option value="{{ $project->id }}" {{ $activeFid === (int) $project->id ? 'selected' : '' }}>
          #{{ $project->id }}{{ !empty($project->num) ? ' / ' . $project->num : '' }} {{ $project->name }}
        </option>
        @endforeach
      </select>
    </form>
    @endif

    <div class="header-lang-switch" aria-label="{{ __('nav.language') }}">
      @foreach(['ru' => 'RU', 'ua' => 'UA', 'en' => 'EN'] as $langCode => $langLabel)
        <a
          href="{{ request()->fullUrlWithQuery(['lang' => $langCode]) }}"
          class="header-lang-switch__link {{ $activeLang === $langCode ? 'is-active' : '' }}"
        >{{ $langLabel }}</a>
      @endforeach
    </div>
  </div>

  <nav class="header-nav-menu" id="header-nav-menu">
    @if(session('name1'))
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
  /* Mobile overrides */
  @media (max-width: 900px) {
    .header-bar {
      padding: 0 0.5rem;
    }

    .header-bar__bottom {
      flex-wrap: wrap;
    }

    .header-project-switch {
      flex: 1 1 auto;
      min-width: 0;
    }

    .header-project-switch__select {
      min-width: 0;
      max-width: none;
      width: 100%;
    }

    .header-nav-menu {
      right: 0.5rem;
      left: 0.5rem;
      min-width: 0;
    }
  }

  @media (min-width: 901px) {
    .header-bar__bottom {
      flex-wrap: nowrap;
    }
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
