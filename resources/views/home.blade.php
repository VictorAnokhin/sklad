<!DOCTYPE html>
<html lang="{{ $currentBackendLocale ?? 'ru' }}" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>@yield('title')</title>
    <link href="{{ asset('css/dark-theme.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet">
</head>

<body class="dark-theme">
    <div class="align_center">
        <div class="wrapper">
            <header>
                @include('partials.top_reklama')
            </header>
            @include('partials.wallet_connect_app')
            <div class="main">
                <div class="page-heading">
                    <h1 class="text-white page-heading__title">@yield('title')</h1>
                    <div class="page-heading__actions">
                        @yield('header_actions')
                    </div>
                </div>
                @yield('content')
            </div>
            @include('partials.site_footer')
        </div>
    </div>

    <style>
        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .page-heading__title {
            margin: 0;
        }

        .page-heading__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex: 1 1 auto;
            min-width: 240px;
        }

        @media (max-width: 768px) {
            .page-heading {
                align-items: flex-start;
            }

            .page-heading__actions {
                width: 100%;
                min-width: 0;
                justify-content: flex-start;
            }
        }
    </style>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" crossorigin="anonymous"></script>
    <script src="{{ asset('js/javascript.js') }}"></script>
    @stack('scripts')

    {{--
      AI Chat Widget — fid определяется автоматически из session('fid').
      Если нужно явно указать проект, передайте параметр:
      @include('partials.ai_chat_widget', ['fid' => 12])
    --}}
    @include('partials.ai_chat_widget')
    @include('partials.ai_knowledge_base', ['fid' => 1])
</body>

</html>
