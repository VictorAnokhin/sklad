@extends('partials.home')

@section('content')
<div class="menu_content_login" id="menu_content_login">
  <form action="{{ route('login.post') }}" method="post">
    @csrf

    @error('login')
    <div class="alert-error" style="color:red;margin-bottom:8px">{{ $message }}</div>
    @enderror

    <div>
      <label for="login">Логін або телефон</label>
      <input type="text" name="login" value="{{ old('login') }}" placeholder="Ваш логін або телефон" required
        autocomplete="username" class="name1">
    </div>

    <div>
      <label for="pass">Пароль</label>
      <input type="password" name="pass" placeholder="Пароль" autocomplete="current-password" required class="name1">
    </div>

    <div>
      <button type="submit">Увійти</button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
  const btnMenu = document.getElementById('btn_login');
  const Menu = document.getElementById('menu_content_login');
  btnMenu.addEventListener('click', function () {
    if (Menu.style.display == 'block')
      Menu.style.display = 'none';
    else
      Menu.style.display = 'block';
  });
</script>
@endpush