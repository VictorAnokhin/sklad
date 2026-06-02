@extends('home')

@section('title', 'WebChat активність')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
@php
    $formatDuration = static function (int|float|null $ms): string {
        $seconds = (int) floor(((int) $ms) / 1000);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $restSeconds = $seconds % 60;

        if ($hours > 0) {
            return $hours . 'г ' . $minutes . 'хв';
        }

        if ($minutes > 0) {
            return $minutes . 'хв ' . $restSeconds . 'с';
        }

        return $restSeconds . 'с';
    };
@endphp

<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.webchatactivity'),
        'periodResetUrl' => route('reports.webchatactivity'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">WebChat: відвідування та активності</h3>
                    <div class="text-muted small">Поточний проект: fid {{ session('fid') }} · Період: {{ $periodLabel }}</div>
                </div>
                <div class="text-muted small">Сесії, події, шлях користувача і зафіксовані потреби</div>
            </div>

            @if(!$tablesReady)
                <div class="alert alert-warning mb-0">
                    Таблиці webchat_visitors або webchat_events ще не створені. Запустіть міграції Laravel API.
                </div>
            @else
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Відвідувачів у періоді</div>
                        <div class="fs-4 fw-bold text-primary">{{ (int) $uniqueVisitors }}</div>
                        <div class="text-muted small mt-1">Активних по подіях: {{ (int) $activeVisitors }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Подій WebChat</div>
                        <div class="fs-4 fw-bold text-light">{{ (int) $eventsCount }}</div>
                        <div class="text-muted small mt-1">Переглядів сторінок: {{ (int) $pageViews }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Сумарний час</div>
                        <div class="fs-4 fw-bold text-success">{{ $formatDuration($totalDurationMs) }}</div>
                        <div class="text-muted small mt-1">Середній на відвідувача: {{ $formatDuration($avgVisitorTimeMs) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Ідентифіковано</div>
                        <div class="fs-4 fw-bold text-warning">{{ (int) $identifiedVisitors }}</div>
                        <div class="text-muted small mt-1">Чат-активностей: {{ (int) $chatMessages }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($tablesReady)
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">Топ сторінок</h4>
                        <div class="text-muted small">За кількістю подій</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Сторінка</th>
                                    <th class="text-end">Подій</th>
                                    <th class="text-end">Відвідувачів</th>
                                    <th class="text-end">Час</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPages as $row)
                                <tr>
                                    <td class="text-break">{{ $row->page_path }}</td>
                                    <td class="text-end">{{ (int) $row->events_count }}</td>
                                    <td class="text-end">{{ (int) $row->visitors_count }}</td>
                                    <td class="text-end">{{ $formatDuration($row->duration_ms) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted">Даних за період немає.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">Типи активностей</h4>
                        <div class="text-muted small">Події від скрипта чату</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Подія</th>
                                    <th class="text-end">Кількість</th>
                                    <th class="text-end">Відвідувачів</th>
                                    <th class="text-end">Час</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topEvents as $row)
                                <tr>
                                    <td>{{ $row->event_type }}</td>
                                    <td class="text-end">{{ (int) $row->events_count }}</td>
                                    <td class="text-end">{{ (int) $row->visitors_count }}</td>
                                    <td class="text-end">{{ $formatDuration($row->duration_ms) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted">Подій за період немає.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Динаміка по днях</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>День</th>
                                    <th class="text-end">Подій</th>
                                    <th class="text-end">Відвідувачів</th>
                                    <th class="text-end">Час</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailyStats as $row)
                                <tr>
                                    <td>{{ $row->day }}</td>
                                    <td class="text-end">{{ (int) $row->events_count }}</td>
                                    <td class="text-end">{{ (int) $row->visitors_count }}</td>
                                    <td class="text-end">{{ $formatDuration($row->duration_ms) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted">Немає денної статистики.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Домени</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Домен</th>
                                    <th class="text-end">Подій</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topDomains as $row)
                                <tr>
                                    <td class="text-break">{{ $row->site_domain }}</td>
                                    <td class="text-end">{{ (int) $row->events_count }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-muted">Доменів немає.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Зафіксовані потреби</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Потреба</th>
                                    <th>Останній шлях</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($needsRows as $row)
                                <tr>
                                    <td class="text-break">{{ $row->needs_text }}</td>
                                    <td class="text-break text-muted small">{{ $row->last_seen_path ?: '/' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-muted">Потреби ще не сформовані агентом.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm bg-transparent border-secondary">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Останні відвідувачі</h4>
                <div class="text-muted small">visitor_uid, останній шлях, compact journey, needs_summary</div>
            </div>
            <div class="table-responsive reports-sticky-first-col">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Відвідувач</th>
                            <th>Домен</th>
                            <th>Остання сторінка</th>
                            <th>Journey</th>
                            <th>Потреба</th>
                            <th class="text-end">Час</th>
                            <th>Останній візит</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentVisitors as $visitor)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $visitor->visitor_uid }}</div>
                                <div class="text-muted small">
                                    {{ $visitor->identified_user_id ? 'user #' . $visitor->identified_user_id : 'anonymous' }}
                                </div>
                            </td>
                            <td class="text-break">{{ $visitor->site_domain ?: 'unknown' }}</td>
                            <td class="text-break">{{ $visitor->last_seen_path ?: '/' }}</td>
                            <td class="text-break small">
                                @if(!empty($visitor->journey_preview))
                                    {{ implode(' → ', $visitor->journey_preview) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-break small">{{ $visitor->needs_text ?: '-' }}</td>
                            <td class="text-end">{{ $formatDuration($visitor->total_time_ms) }}</td>
                            <td class="small">{{ $visitor->last_seen_at }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-muted">Відвідувачів за період не знайдено.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
