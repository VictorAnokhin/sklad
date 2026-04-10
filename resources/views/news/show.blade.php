@extends('home')

@section('title', $item->title_view ?? __('news.title'))

@section('content')
<div class="ttable news-show-wrap">
    <div class="news-show-toolbar">
        <a href="{{ route('news.index') }}" class="btn btn-outline-secondary">← {{ __('news.list_title') }}</a>
        <a href="{{ route('news.edit', ['id' => $item->id]) }}" class="btn btn-outline-primary">{{ __('news.edit') }}</a>
    </div>

    <article class="news-show-card">
        <div class="news-show-meta">
            @if(!empty($item->dt))
            <span>{{ $item->dt }}</span>
            @endif
            @if(!empty($item->time))
            <span>{{ substr((string) $item->time, 0, 5) }}</span>
            @endif
            @if((int)($item->hot ?? 0) === 1)
            <span class="news-badge">{{ __('news.top') }}</span>
            @endif
        </div>

        <h1 class="news-show-title">{{ $item->title_view }}</h1>

        @if(!empty($item->excerpt_view))
        <p class="news-show-excerpt">{{ $item->excerpt_view }}</p>
        @endif

        @if(!empty($item->photo_view))
        <div class="news-show-image">
            <img src="{{ $item->photo_view }}" alt="{{ $item->title_view }}">
        </div>
        @endif

        <div class="news-show-body">{!! $item->body_view !!}</div>
    </article>
</div>

<style>
    .news-show-wrap {
        padding: 16px;
    }

    .news-show-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .news-show-card {
        padding: 20px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.03);
    }

    .news-show-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        color: #94a3b8;
        margin-bottom: 10px;
    }

    .news-show-title {
        margin: 0 0 14px 0;
        color: #fff;
    }

    .news-show-excerpt {
        color: #e2e8f0;
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 16px;
    }

    .news-show-image img {
        max-width: 100%;
        border-radius: 12px;
        display: block;
        margin-bottom: 16px;
    }

    .news-show-body {
        color: #cbd5e1;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .news-show-body img {
        max-width: 100%;
        height: auto;
    }
</style>
@endsection
