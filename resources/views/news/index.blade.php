@extends('home')

@section('title', __('news.title'))

@section('content')
@php
    $currentPage = (int) floor(($pos ?? 0) / ($perPage ?? 20)) + 1;
    $totalPages = max(1, (int) ceil(($total ?? 0) / ($perPage ?? 20)));
    $firstPos = 0;
    $prevPos = max(0, ($pos ?? 0) - ($perPage ?? 20));
    $nextPos = min(max(0, ($totalPages - 1) * ($perPage ?? 20)), ($pos ?? 0) + ($perPage ?? 20));
    $lastPos = max(0, ($totalPages - 1) * ($perPage ?? 20));
    $from = ($total ?? 0) > 0 ? (($pos ?? 0) + 1) : 0;
    $to = min(($pos ?? 0) + ($perPage ?? 20), $total ?? 0);
@endphp

<div class="ttable news-wrap">
    <div class="news-toolbar">
        <a href="{{ route('news.edit', ['id' => 0]) }}" class="btn btn-success">{{ __('news.add') }}</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(($items ?? collect())->isEmpty())
    <div class="news-empty">{{ __('news.empty') }}</div>
    @else
    <div class="news-list">
        @foreach($items as $item)
        <div class="news-row">
            <div class="news-row__title">
                <a href="{{ route('news.show', ['id' => $item->id]) }}">{{ $item->title_view }}</a>
                <div class="news-row__meta">
                    <span>#{{ $item->id }}</span>
                    @if(!empty($item->dt))
                    <span>{{ $item->dt }}</span>
                    @endif
                    @if((int)($item->hot ?? 0) === 1)
                    <span class="news-badge">{{ __('news.top') }}</span>
                    @endif
                    @if((int)($item->view ?? 0) !== 1)
                    <span class="news-hidden">{{ __('news.hidden') }}</span>
                    @endif
                </div>
            </div>

            <div class="news-row__actions">
                <a href="{{ route('news.edit', ['id' => $item->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('news.edit') }}</a>
                <form action="{{ route('news.destroy') }}" method="post" onsubmit="return confirm('{{ __('news.delete_confirm') }}');">
                    @csrf
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('news.delete') }}</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    @if(($total ?? 0) > ($perPage ?? 20))
    <div class="news-pagination">
        @if($currentPage <= 1)
        <span class="button news-pagination__btn is-disabled">« 1</span>
        <span class="button news-pagination__btn is-disabled">←</span>
        @else
        <a href="{{ route('news.index', ['pos' => $firstPos]) }}" class="button news-pagination__btn">« 1</a>
        <a href="{{ route('news.index', ['pos' => $prevPos]) }}" class="button news-pagination__btn">←</a>
        @endif

        @if($currentPage >= $totalPages)
        <span class="button news-pagination__btn is-disabled">→</span>
        <span class="button news-pagination__btn is-disabled">{{ $totalPages }} »</span>
        @else
        <a href="{{ route('news.index', ['pos' => $nextPos]) }}" class="button news-pagination__btn">→</a>
        <a href="{{ route('news.index', ['pos' => $lastPos]) }}" class="button news-pagination__btn">{{ $totalPages }} »</a>
        @endif

        <span class="news-pagination__meta">{{ $from }}-{{ $to }} з {{ $total }} | стор. {{ $currentPage }} / {{ $totalPages }}</span>
    </div>
    @endif
    @endif
</div>

<style>
    .news-wrap {
        padding: 16px;
    }

    .news-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
    }

    .news-empty {
        text-align: center;
        padding: 28px 12px;
        color: #cc0000;
        font-size: 1.1rem;
    }

    .news-list {
        display: grid;
        gap: 12px;
    }

    .news-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 14px 16px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.03);
    }

    .news-row__meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .news-badge {
        color: #fbbf24;
        font-weight: 700;
    }

    .news-hidden {
        color: #f87171;
        font-weight: 700;
    }

    .news-row__title a {
        color: #fff;
        font-weight: 600;
        text-decoration: none;
    }

    .news-row__title a:hover {
        color: #fbbf24;
    }

    .news-row__actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .news-row__actions form {
        margin: 0;
    }

    .news-pagination {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .news-pagination__btn {
        min-width: 56px;
        text-align: center;
    }

    .news-pagination__btn.is-disabled {
        pointer-events: none;
        opacity: 0.55;
    }

    .news-pagination__meta {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .news-row {
            grid-template-columns: 1fr;
        }

        .news-row__actions {
            justify-content: flex-start;
            flex-wrap: wrap;
        }
    }
</style>
@endsection
