<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width">
  <title>Вхід — {{ config('app.name', 'av8fund') }}</title>
  <link href="{{ asset('css/styles.css') }}" rel="stylesheet">
</head>
<body>
<div class="align_center">
<div class="wrapper">

  @include('partials.top_reklama')

  <div class="main">
    <form action="{{ route('login.post') }}" method="post" class="login-form">
      @csrf

      @error('login')
        <div class="alert-error" style="color:red;margin-bottom:8px">{{ $message }}</div>
      @enderror

      <div class="txtbox_startpage_str">
        <input type="text"
               name="login"
               value="{{ old('login') }}"
               placeholder="Ваш логін або телефон"
               autocomplete="username"
               class="name1">
      </div>

      <div class="txtbox_startpage_str">
        <input type="password"
               name="pass"
               placeholder="Пароль"
               autocomplete="current-password"
               class="name1">
      </div>

      <div class="txtbox_startpage_str">
        <button type="submit" class="button" style="width:140px">Увійти</button>
      </div>
    </form>
  </div>

</div>
</div>
</body>
</html>
