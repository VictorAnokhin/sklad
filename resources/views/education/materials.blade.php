@extends('home')

@section('title', 'Материалы')

@section('content')
<div class="container pb-5">
    @foreach(['success', 'warning'] as $messageType)
        @if(session($messageType))
            <div class="alert alert-{{ $messageType }}">{{ session($messageType) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('education.material-files.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-md-6 col-lg-5">
                    <label class="form-label" for="education-material-image">Фото</label>
                    <input class="form-control" id="education-material-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    <div class="form-text">Файлы сохраняются в <code>storage/app/public/{{ $storageDirectory }}</code>.</div>
                </div>
                <div class="col-12 col-md-4 col-lg-5">
                    <label class="form-label" for="education-material-alt">Alt / подпись</label>
                    <input class="form-control" id="education-material-alt" name="alt" maxlength="255" placeholder="example">
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-warning w-100" type="submit">Загрузить</button>
                </div>
            </form>
        </div>
    </div>

    @if($materials === [])
        <div class="card bg-dark border-secondary">
            <div class="card-body text-secondary">Фото материалов пока не загружены.</div>
        </div>
    @else
        <div class="education-materials-grid">
            @foreach($materials as $material)
                <div class="card bg-dark border-secondary education-material-card">
                    <a href="{{ $material['url'] }}" target="_blank" rel="noreferrer" class="education-material-thumb">
                        <img src="{{ $material['url'] }}" alt="{{ $material['alt'] }}" loading="lazy">
                    </a>
                    <div class="card-body">
                        <div class="education-material-name" title="{{ $material['file'] }}">{{ $material['file'] }}</div>
                        <details class="mt-3">
                            <summary class="text-warning">Подсказка для вставки</summary>
                            <textarea class="form-control font-monospace mt-2 education-material-hint" rows="5" readonly>{!! $material['hint'] !!}</textarea>
                        </details>
                    </div>
                    <div class="card-footer border-secondary d-flex justify-content-between gap-2">
                        <a class="btn btn-outline-light btn-sm" href="{{ $material['url'] }}" target="_blank" rel="noreferrer">Открыть</a>
                        <form method="POST" action="{{ route('education.material-files.destroy') }}" onsubmit="return confirm('Удалить фото материала?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="file" value="{{ $material['file'] }}">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Удалить</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .education-materials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }

    .education-material-card {
        overflow: hidden;
    }

    .education-material-thumb {
        display: block;
        aspect-ratio: 4 / 3;
        background: rgba(255, 255, 255, 0.04);
        border-bottom: 1px solid rgba(108, 117, 125, 0.45);
    }

    .education-material-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .education-material-name {
        color: #f8f9fa;
        font-size: 0.9rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .education-material-card summary {
        cursor: pointer;
        font-size: 0.9rem;
    }

    .education-material-hint {
        min-height: 8.5rem;
        resize: vertical;
        font-size: 0.8rem;
    }
</style>
@endsection
