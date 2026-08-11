@extends('home')

@section('title', (int)($item->id ?? 0) > 0 ? 'Редагування новини' : 'Нова новина')

@section('content')
<div class="ttable news-edit-wrap">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php($photoPreview = trim((string) ($item->photo_view ?? '')))

    <form action="{{ route('news.save') }}" method="post" enctype="multipart/form-data" id="news-edit-form">
        @csrf
        <input type="hidden" name="id" value="{{ $item->id ?? 0 }}">
        <input type="hidden" name="foto" value="{{ old('foto', $item->foto ?? '') }}">

        <div class="news-edit-actions-top">
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">💾 Зберегти</button>
                <a href="{{ route('news.index') }}" class="btn btn-secondary">← Назад</a>
                @if((int)($item->id ?? 0) > 0)
                <a href="{{ route('news.show', ['id' => $item->id]) }}" class="btn btn-outline-info">👁 Перегляд</a>
                @endif
            </div>

            @if((int)($item->id ?? 0) > 0)
            <button type="submit" form="news-delete-form" class="btn btn-danger" onclick="return confirm('Видалити новину?');">🗑 Видалити</button>
            @endif
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Дата</label>
                <input type="text" name="dt" class="form-control" value="{{ old('dt', $item->dt ?? date('d-m-Y')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Час</label>
                <input type="time" name="time" class="form-control" value="{{ old('time', !empty($item->time) ? substr((string)$item->time, 0, 5) : date('H:i')) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Фото</label>
                <div class="news-photo-preview">
                    @if($photoPreview !== '')
                    <img src="{{ $photoPreview }}" alt="preview">
                    @else
                    <div class="news-photo-preview__empty">Фото не завантажено</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Людиночитний URL (`url`)</label>
            <div class="input-group">
                <span class="input-group-text">/articles/</span>
                <input
                    type="text"
                    name="url"
                    class="form-control news-safe-line"
                    value="{{ old('url', $item->url ?? '') }}"
                    placeholder="finansovyi-plan"
                    maxlength="100"
                >
            </div>
            <div class="form-text">Якщо залишити поле порожнім, URL автоматично сформується транслітом із назви RU.</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Завантажити фото</label>
            <input type="file" name="foto_upload" class="form-control @error('foto_upload') is-invalid @enderror" accept="image/*">
            @error('foto_upload')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Новий файл буде збережений у `storage/files/news` і оновить `foto`.</div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="view" value="1" id="news-view" {{ old('view', (string)($item->view ?? 1)) === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="news-view">Показувати</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="hot" value="1" id="news-hot" {{ old('hot', (string)($item->hot ?? 0)) === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="news-hot">Топ</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="always" value="1" id="news-always" {{ old('always', (string)($item->always ?? 0)) === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="news-always">Always</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="article" value="1" id="news-article" {{ old('article', (string)($item->article ?? 0)) === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="news-article">Article</label>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="newsLangTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="news-ru-tab" data-bs-toggle="tab" data-bs-target="#news-ru" type="button" role="tab">RU</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="news-ua-tab" data-bs-toggle="tab" data-bs-target="#news-ua" type="button" role="tab">UA</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="news-en-tab" data-bs-toggle="tab" data-bs-target="#news-en" type="button" role="tab">EN</button>
            </li>
        </ul>

        <div class="tab-content news-tab-content">
            <div class="tab-pane fade show active" id="news-ru" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label">Назва RU (`title`)</label>
                    <input type="text" name="title" class="form-control news-safe-line" value="{{ old('title', $item->title ?? '') }}" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Короткий текст RU (`kratko`)</label>
                    <textarea name="kratko" class="form-control news-safe-text" rows="4" maxlength="2000">{{ old('kratko', $item->kratko ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Повний текст RU (`txt`)</label>
                    <textarea name="txt" class="form-control news-safe-text" rows="12" maxlength="2000">{{ old('txt', $item->txt ?? '') }}</textarea>
                </div>
            </div>

            <div class="tab-pane fade" id="news-ua" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label">Назва UA (`title_ua`)</label>
                    <input type="text" name="title_ua" class="form-control news-safe-line" value="{{ old('title_ua', $item->title_ua ?? '') }}" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Короткий текст UA (`kratko_ua`)</label>
                    <textarea name="kratko_ua" class="form-control news-safe-text" rows="4" maxlength="2000">{{ old('kratko_ua', $item->kratko_ua ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Повний текст UA (`txt_ua`)</label>
                    <textarea name="txt_ua" class="form-control news-safe-text" rows="12" maxlength="2000">{{ old('txt_ua', $item->txt_ua ?? '') }}</textarea>
                </div>
            </div>

            <div class="tab-pane fade" id="news-en" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label">Назва EN (`title_en`)</label>
                    <input type="text" name="title_en" class="form-control news-safe-line" value="{{ old('title_en', $item->title_en ?? '') }}" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Короткий текст EN (`kratko_en`)</label>
                    <textarea name="kratko_en" class="form-control news-safe-text" rows="4" maxlength="2000">{{ old('kratko_en', $item->kratko_en ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Повний текст EN (`txt_en`)</label>
                    <textarea name="txt_en" class="form-control news-safe-text" rows="12" maxlength="2000">{{ old('txt_en', $item->txt_en ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Tags</label>
                <input type="text" name="tags" class="form-control news-safe-keywords" value="{{ old('tags', $item->tags ?? '') }}" maxlength="30">
            </div>
            <div class="col-md-6">
                <label class="form-label">HTML keys</label>
                <input type="text" name="htmlkeys" class="form-control news-safe-keywords" value="{{ old('htmlkeys', $item->htmlkeys ?? '') }}" maxlength="30">
            </div>
        </div>
    </form>

    @if((int)($item->id ?? 0) > 0)
    <form id="news-delete-form" action="{{ route('news.destroy') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $item->id }}">
    </form>
    @endif
</div>

<style>
    .news-edit-wrap {
        padding: 18px;
        max-width: 1100px;
        margin: 0 auto;
    }

    .news-edit-actions-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .news-tab-content {
        padding: 18px;
        border: 1px solid #dee2e6;
        border-top: 0;
        border-radius: 0 0 10px 10px;
        background: rgba(255,255,255,0.02);
    }

    .news-photo-preview {
        min-height: 168px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgba(255, 255, 255, 0.18);
        border-radius: 12px;
        padding: 10px;
        background: rgba(255,255,255,0.03);
    }

    .news-photo-preview img {
        max-width: 100%;
        max-height: 220px;
        border-radius: 10px;
        display: block;
    }

    .news-photo-preview__empty {
        color: #94a3b8;
        font-size: 0.95rem;
    }

    .news-field-counter {
        color: #94a3b8;
        font-size: 0.78rem;
        line-height: 1.2;
        margin-top: 4px;
        text-align: right;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('news-edit-form');
    if (!form) return;

    const safeLine = value => String(value || '')
        .replace(/[\x00-\x1F\x7F<>{}\[\]\\=;:*|~^$#@!?%&+]/g, '')
        .replace(/[^\p{L}\p{M}\p{N}\s.,'"’`()_-]/gu, '')
        .replace(/\s+/g, ' ')
        .slice(0, 100);
    const safeKeywords = value => String(value || '')
        .replace(/[<>{}\[\]\\=;:*|~^$#@!?%&+]/g, '')
        .replace(/[^\p{L}\p{M}\p{N}\s,._-]/gu, '')
        .replace(/\s+/g, ' ')
        .slice(0, 30);
    const safeText = value => String(value || '')
        .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
        .replace(/\son\w+\s*=\s*(['"]).*?\1/gi, '')
        .replace(/\s(href|src)\s*=\s*(['"])\s*javascript:.*?\2/gi, '')
        .slice(0, 2000);
    const ensureCounter = input => {
        const maxLength = Number(input.getAttribute('maxlength') || 0);
        if (!maxLength) return null;

        const anchor = input.closest('.input-group') || input;
        let counter = anchor.nextElementSibling;
        if (!counter || !counter.classList.contains('news-field-counter')) {
            counter = document.createElement('div');
            counter.className = 'news-field-counter';
            anchor.insertAdjacentElement('afterend', counter);
        }

        return counter;
    };
    const updateCounter = input => {
        const counter = ensureCounter(input);
        if (!counter) return;
        const maxLength = Number(input.getAttribute('maxlength') || 0);
        counter.textContent = `${String(input.value || '').length}/${maxLength}`;
    };
    const bindProtectedInput = (input, sanitizer) => {
        input.value = sanitizer(input.value);
        updateCounter(input);
        input.addEventListener('input', () => {
            input.value = sanitizer(input.value);
            updateCounter(input);
        });
    };

    form.querySelectorAll('.news-safe-line').forEach(input => {
        bindProtectedInput(input, safeLine);
    });
    form.querySelectorAll('.news-safe-keywords').forEach(input => {
        bindProtectedInput(input, safeKeywords);
    });
    form.querySelectorAll('.news-safe-text').forEach(input => {
        bindProtectedInput(input, safeText);
    });
});
</script>
@endsection
