{{-- top_reklama.blade.php --}}

<div class="header-bar">
  <a href="/" class="d-flex align-items-center text-decoration-none">
    <span class="fs-4" style="color: #ffffff; font-weight: 600;">{{ config('app.name') }}: {{ session('name1') ?? '' }}</span>
  </a>

  <button type="button" class="header-burger" id="header-burger" aria-expanded="false" aria-controls="header-nav-menu" aria-label="Відкрити меню">
    <span></span>
    <span></span>
    <span></span>
  </button>

  <nav class="header-nav-menu" id="header-nav-menu">
    @if(session('name1'))
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('document.index', ['doc' => 'ZOUT']) }}">Замовлення</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('document.index', ['doc' => 'ZIN']) }}">Закупки</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('client.index') }}">Клієнти</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('goods.index') }}">Товари</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('news.index') }}">Новини</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('money.index') }}">Гроші</a>
    <a class="py-2 text-decoration-none" style="color: #fbbf24; font-weight: 500;" href="{{ route('settings.index') }}">Налаштування</a>

    <form method="POST" action="{{ route('logout') }}" id="logout-form">
      @csrf
      <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
        class="text-decoration-none py-2" style="color: #fbbf24; font-weight: 500;">Вийти</a>
    </form>
    @else
    <a class="py-2 text-decoration-none" id="btn_login" style="cursor:pointer; color: #fbbf24; font-weight: 500;">Увійти</a>
    @endif
  </nav>
</div>

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
