<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>@yield('title')</title>
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <div class="align_center">
        <div class="wrapper">
            <header>
                @include('partials.top_reklama')
            </header>
            <div class="main">
                <h1>@yield('title')</h1>
                @yield('content')
            </div>

        </div>
    </div>

    <script src="{{ asset('js/javascript.js') }}"></script>
    @stack('scripts')
</body>

</html>