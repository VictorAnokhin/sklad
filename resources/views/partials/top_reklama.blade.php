{{-- top_reklama.blade.php --}}
@php
  $headerProjects = \Illuminate\Support\Facades\Schema::hasTable('project')
    ? \App\Models\Project::query()->orderBy('num')->orderBy('name')->get(['id', 'num', 'name'])
    : collect();
  $activeFid = (int) session('fid', 0);
  $activeLang = \App\Models\Field::normalizeLocale(app()->getLocale());
@endphp

<div class="header-bar">
  <a href="/" class="d-flex align-items-center text-decoration-none">
    <span class="fs-4" style="color: #ffffff; font-weight: 600;">{{ config('app.name') }}: {{ session('name1') ?? '' }}</span>
  </a>

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

  <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="Відкрити меню">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <nav class="header-nav-menu" id="header-nav-menu">
    @if(session('name1'))
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('document.index', ['doc' => 'ZOUT']) }}">{{ __('nav.orders') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('document.index', ['doc' => 'ZIN']) }}">{{ __('nav.purchases') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('client.index') }}">{{ __('nav.clients') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('goods.index') }}">{{ __('nav.goods') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('reports.index') }}">{{ __('nav.reports') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('news.index') }}">{{ __('nav.news') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('money.index') }}">{{ __('nav.money') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('deposit.index') }}">{{ __('nav.deposits') }}</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('settings.index') }}">{{ __('nav.settings') }}</a>

    <form method="POST" action="{{ route('logout') }}" id="logout-form">
      @csrf
      <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
        class="text-decoration-none py-2" style="color: #fbbf24; font-weight: 500;">{{ __('nav.logout') }}</a>
    </form>
    @else
    <a class="py-2 text-decoration-none" id="btn_login" style="cursor:pointer; color: #fbbf24; font-weight: 500;">{{ __('nav.login') }}</a>
    @endif
  </nav>
</div>

<style>
  .header-project-switch {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
    margin-right: 0.35rem;
    min-width: 0;
  }

  .header-lang-switch {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-left: 0.25rem;
    margin-right: 0.5rem;
  }

  .header-lang-switch__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 34px;
    padding: 0 0.7rem;
    border-radius: 999px;
    border: 1px solid var(--glass-border);
    color: var(--muted-foreground);
    text-decoration: none;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    background: rgba(255, 255, 255, 0.04);
  }

  .header-lang-switch__link.is-active {
    border-color: rgba(251, 191, 36, 0.45);
    color: #fbbf24;
    background: rgba(251, 191, 36, 0.1);
  }

  .header-project-switch__label {
    color: var(--muted-foreground);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    white-space: nowrap;
  }

  .header-project-switch__select {
    min-width: 220px;
    max-width: 320px;
    height: 40px;
    padding: 0 2.25rem 0 0.85rem;
    border-radius: var(--radius);
    border: 1px solid var(--glass-border);
    background: rgba(255, 255, 255, 0.06);
    color: var(--foreground);
    font: inherit;
    outline: none;
  }

  .header-project-switch__select:focus {
    border-color: var(--accent-amber-border);
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.12);
  }

  .header-project-switch__select option {
    color: #111827;
  }

  @media (max-width: 900px) {
    .header-project-switch {
      order: 3;
      width: 100%;
      margin: 0;
    }

    .header-lang-switch {
      order: 2;
      margin-left: auto;
    }

    .header-project-switch__select {
      min-width: 0;
      max-width: none;
      width: 100%;
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
