@extends('home')

@section('title', 'Курс обучения')

@section('header_actions')
<button type="button" class="btn btn-warning" id="create-material-button" @disabled($migrationRequired ?? false)>Создать</button>
@endsection

@section('content')
<div class="container pb-5">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if($migrationRequired ?? false)
        <div class="alert alert-warning">
            Таблицы образовательного модуля ещё не созданы. Выполните миграции Laravel:
            <code>php artisan migrate --force</code>.
        </div>
    @endif

    <div class="mb-4 text-secondary">
        {{ $project->name }} · материал адаптируется к текущему уровню и результатам тестов.
    </div>

    @forelse($topics as $topic)
        @php
            $material = $topic->relationLoaded('selectedMaterial') ? $topic->getRelation('selectedMaterial') : null;
            $progress = $topic->relationLoaded('studentProgress') ? $topic->getRelation('studentProgress') : null;
            $levelLabels = ['beginner' => 'Начальный', 'intermediate' => 'Средний', 'advanced' => 'Продвинутый'];
        @endphp
        <article class="card bg-dark border-secondary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h2 class="h4 text-white mb-2">{{ $topic->title }}</h2>
                        @if($topic->description)
                            <p class="text-secondary mb-3">{{ $topic->description }}</p>
                        @endif
                    </div>
                    @if($material)
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge text-bg-warning">
                                {{ $levelLabels[$material->level] ?? $material->level }} · v{{ $material->version }}
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-light edit-material-button"
                                    data-material-id="{{ $material->id }}">Изменить</button>
                        </div>
                    @endif
                </div>

                @if(!$material)
                    <div class="alert alert-secondary mb-0">Для этой темы пока нет активного материала.</div>
                @elseif($material->content_type === 'video_link')
                    <a class="btn btn-outline-warning" href="{{ $material->body }}" target="_blank" rel="noopener">
                        Открыть видео
                    </a>
                @elseif($material->content_type === 'interactive_scenario')
                    <pre class="p-3 rounded bg-black text-light mb-0"><code>{{ json_encode(json_decode($material->body, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                @else
                    <div class="education-content text-light">{!! \Illuminate\Support\Str::markdown($material->body, [
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                    ]) !!}</div>
                @endif

                @if($progress)
                    <div class="small text-secondary mt-3">
                        Успешных попыток: {{ $progress->passed_attempts }} · неуспешных: {{ $progress->failed_attempts }}
                    </div>
                @endif

                @if($topic->materials->isNotEmpty())
                    <div class="border-top border-secondary mt-3 pt-3">
                        <div class="small text-secondary mb-2">Все версии материала</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($topic->materials as $topicMaterial)
                                <button type="button"
                                        class="btn btn-sm {{ $material?->id === $topicMaterial->id ? 'btn-warning' : 'btn-outline-secondary' }} edit-material-button"
                                        data-material-id="{{ $topicMaterial->id }}">
                                    {{ $levelLabels[$topicMaterial->level] ?? $topicMaterial->level }} · v{{ $topicMaterial->version }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="alert alert-info">Темы курса ещё не добавлены.</div>
    @endforelse
</div>

<div class="modal fade" id="material-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5" id="material-modal-title">Создать материал</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="material-form" method="POST" action="{{ route('education.materials.store') }}">
                @csrf
                <input type="hidden" name="_method" id="material-method" value="POST">
                <div class="modal-body">
                    <div class="mb-3" id="topic-select-wrap">
                        <label class="form-label" for="material-topic-id">Тема</label>
                        <select class="form-select" id="material-topic-id" name="topic_id">
                            <option value="">Создать новую тему</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label" for="material-topic-title">Название темы</label>
                            <input class="form-control" id="material-topic-title" name="topic_title" required maxlength="255">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="material-position">Порядок</label>
                            <input class="form-control" id="material-position" name="position" type="number" min="0" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="material-topic-description">Описание темы</label>
                        <textarea class="form-control" id="material-topic-description" name="topic_description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="material-level">Уровень</label>
                            <select class="form-select" id="material-level" name="level" required>
                                <option value="beginner">Начальный</option>
                                <option value="intermediate">Средний</option>
                                <option value="advanced">Продвинутый</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="material-content-type">Тип контента</label>
                            <select class="form-select" id="material-content-type" name="content_type" required>
                                <option value="markdown">Markdown</option>
                                <option value="video_link">Ссылка на видео</option>
                                <option value="interactive_scenario">Интерактивный JSON</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="material-version">Версия</label>
                            <input class="form-control" id="material-version" name="version" value="1.0" required maxlength="32">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="material-body">Содержимое</label>
                        <textarea class="form-control font-monospace" id="material-body" name="body" rows="12" required></textarea>
                        <div class="form-text">Markdown-текст, URL видео или JSON сценария — в зависимости от выбранного типа.</div>
                    </div>
                </div>
                <div class="modal-footer border-secondary justify-content-between">
                    <button type="button" class="btn btn-outline-danger d-none" id="delete-material-button">Удалить</button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning">Сохранить</button>
                    </div>
                </div>
            </form>
            <form id="delete-material-form" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('material-modal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('material-form');
    const deleteForm = document.getElementById('delete-material-form');
    const deleteButton = document.getElementById('delete-material-button');
    const fields = {
        topicId: document.getElementById('material-topic-id'),
        title: document.getElementById('material-topic-title'),
        description: document.getElementById('material-topic-description'),
        position: document.getElementById('material-position'),
        level: document.getElementById('material-level'),
        contentType: document.getElementById('material-content-type'),
        version: document.getElementById('material-version'),
        body: document.getElementById('material-body'),
    };
    const materials = @json($topics->flatMap(fn ($topic) => $topic->materials->map(fn ($material) => [
        'id' => $material->id,
        'topic_id' => $topic->id,
        'topic_title' => $topic->title,
        'topic_description' => $topic->description,
        'position' => $topic->position,
        'level' => $material->level,
        'content_type' => $material->content_type,
        'version' => $material->version,
        'body' => $material->body,
    ]))->keyBy('id'));
    const topics = @json($topics->keyBy('id')->map(fn ($topic) => [
        'title' => $topic->title,
        'description' => $topic->description,
        'position' => $topic->position,
    ]));
    const storeUrl = @json(route('education.materials.store'));
    const updateUrl = @json(route('education.materials.update', ['material' => '__ID__']));
    const deleteUrl = @json(route('education.materials.destroy', ['material' => '__ID__']));

    function openCreate() {
        form.reset();
        form.action = storeUrl;
        document.getElementById('material-method').value = 'POST';
        document.getElementById('material-modal-title').textContent = 'Создать материал курса';
        deleteButton.classList.add('d-none');
        fields.version.value = '1.0';
        modal.show();
    }

    function openEdit(id) {
        const item = materials[id];
        if (!item) return;
        form.reset();
        form.action = updateUrl.replace('__ID__', id);
        document.getElementById('material-method').value = 'PUT';
        document.getElementById('material-modal-title').textContent = 'Изменить материал курса';
        fields.topicId.value = item.topic_id;
        fields.title.value = item.topic_title;
        fields.description.value = item.topic_description || '';
        fields.position.value = item.position || 0;
        fields.level.value = item.level;
        fields.contentType.value = item.content_type;
        fields.version.value = item.version;
        fields.body.value = item.body;
        deleteForm.action = deleteUrl.replace('__ID__', id);
        deleteButton.classList.remove('d-none');
        modal.show();
    }

    fields.topicId.addEventListener('change', () => {
        const topic = topics[fields.topicId.value];
        if (!topic) {
            fields.title.value = '';
            fields.description.value = '';
            fields.position.value = 0;
            return;
        }
        fields.title.value = topic.title;
        fields.description.value = topic.description || '';
        fields.position.value = topic.position || 0;
    });
    document.getElementById('create-material-button').addEventListener('click', openCreate);
    document.querySelectorAll('.edit-material-button').forEach(button =>
        button.addEventListener('click', () => openEdit(button.dataset.materialId))
    );
    deleteButton.addEventListener('click', () => {
        if (confirm('Удалить материал? Связанные тесты и попытки также будут удалены.')) deleteForm.submit();
    });
});
</script>
@endpush
