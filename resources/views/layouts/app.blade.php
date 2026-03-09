<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name', 'av8fund'))</title>
  <link href="{{ asset('css/styles.css') }}" rel="stylesheet">
  <link href="{{ asset('css/styles-menu-d.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body>
<div class="align_center">
<div class="wrapper">

  <header class="header">
    @include('partials.top_reklama')
    <section>{{ session('menu_txt', '') }}</section>
  </header>

  <div class="main">

    <div class="document">
      <section style="width:15%;max-width:60px">
        @include('partials.filter')
      </section>
      <section style="width:85%">
        @include('partials.panel')
      </section>
    </div>

    @if(session('success'))
      <div class="alert alert-success" style="color:green;padding:6px">
        {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="alert-error" style="color:red;padding:6px">
        @foreach($errors->all() as $err)
          <div>{{ $err }}</div>
        @endforeach
      </div>
    @endif

    @yield('content')

  </div>

</div>
</div>
<script src="{{ asset('js/javascript.js') }}"></script>
@stack('scripts')
</body>
</html>
