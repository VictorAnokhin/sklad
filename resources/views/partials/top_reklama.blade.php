{{-- top_reklama.blade.php --}}
{{-- Replace with actual logo/header HTML from your top_reklama.php --}}

<div class="d-flex flex-column flex-md-row align-items-center pb-3 mb-4 border-bottom">
  <a href="/" class="d-flex align-items-center link-body-emphasis text-decoration-none">
    <span class="fs-4">{{ config('app.name') }}: {{ session('name1') ?? '' }}
  </a>
  <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
    @if(session('name1'))
    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('document.index') }}">Всі документи</a>
    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('client.index') }}">Клієнти</a>
    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('goods.index') }}">Товари</a>
    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('money.index') }}">Каси</a>
    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('settings.index') }}">Налаштування</a>
    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('money.index') }}">Каси</a>


    <form method="POST" action="{{ route('logout') }}" id="logout-form">
      @csrf
      <a href="#" onclick="document.getElementById('logout-form').submit(); return false;"
        class="link-body-emphasis d-inline-flex text-decoration-none rounded w-100">Вийти</a>
    </form>
    @else
    <a class="py-2 link-body-emphasis text-decoration-none" id="btn_login" style='cursor:pointer'>Увійти</a>
    @endif


  </nav>
</div>