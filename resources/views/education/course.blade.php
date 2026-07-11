@extends('home')

@section('title', 'Курс обучения')

@section('header_actions')
<button type="button" class="btn btn-warning" id="create-course-button" @disabled($migrationRequired ?? false)>Создать</button>
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
        {{ $project->name }}
    </div>

    @php
        $levelLabels = ['beginner' => 'Начальный', 'intermediate' => 'Средний', 'advanced' => 'Продвинутый'];
        $contentTypeLabels = [
            'markdown' => 'HTML / Markdown',
            'video_link' => 'Видео',
            'interactive_scenario' => 'Интерактивный JSON',
        ];
    @endphp

    <ul class="nav nav-tabs border-secondary mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active bg-dark text-warning border-secondary" id="lessons-tab" data-bs-toggle="tab"
                    data-bs-target="#lessons-pane" type="button" role="tab" aria-controls="lessons-pane"
                    aria-selected="true">
                Уроки
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="lessons-pane" role="tabpanel" aria-labelledby="lessons-tab" tabindex="0">
            @forelse($topics as $topic)
                @php
                    $progress = $topic->relationLoaded('studentProgress') ? $topic->getRelation('studentProgress') : null;
                    $accordionId = 'course-lessons-' . $topic->id;
                    $requiredRating = (int) $topic->position;
                @endphp

                <div class="accordion mb-3" id="course-accordion-{{ $topic->id }}">
                    <div class="accordion-item bg-dark border-secondary text-light">
                        <h2 class="accordion-header" id="{{ $accordionId }}-heading">
                            <button class="accordion-button collapsed bg-dark text-light" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}-body"
                                    aria-expanded="false" aria-controls="{{ $accordionId }}-body">
                                <span class="me-3 fw-semibold">{{ $topic->title }}</span>
                                <span class="badge text-bg-warning me-2">Рейтинг {{ $requiredRating }}</span>
                                <span class="badge text-bg-secondary">{{ $topic->materials->count() }} урок(ов)</span>
                            </button>
                        </h2>
                        <div id="{{ $accordionId }}-body" class="accordion-collapse collapse"
                             aria-labelledby="{{ $accordionId }}-heading">
                            <div class="accordion-body">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                    <div>
                                        @if($topic->description)
                                            <p class="text-secondary mb-2">{{ $topic->description }}</p>
                                        @endif
                                        @if($progress)
                                            <div class="small text-secondary">
                                                Успешных попыток: {{ $progress->passed_attempts }} · неуспешных: {{ $progress->failed_attempts }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-light edit-topic-button"
                                                data-topic-id="{{ $topic->id }}">
                                            Изменить курс
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning create-material-button"
                                                data-topic-id="{{ $topic->id }}">
                                            Добавить урок
                                        </button>
                                    </div>
                                </div>

                                @if($topic->materials->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-dark table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 72px;">№</th>
                                                    <th class="w-50">Наименование</th>
                                                    <th>Уровень</th>
                                                    <th>Тип</th>
                                                    <th>Версия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topic->materials as $topicMaterial)
                                                    <tr class="lesson-row" role="button" tabindex="0"
                                                        data-material-id="{{ $topicMaterial->id }}">
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $topicMaterial->title ?: 'Урок #' . $topicMaterial->id }}</div>
                                                        </td>
                                                        <td>{{ $levelLabels[$topicMaterial->level] ?? $topicMaterial->level }}</td>
                                                        <td>{{ $contentTypeLabels[$topicMaterial->content_type] ?? $topicMaterial->content_type }}</td>
                                                        <td>v{{ $topicMaterial->version }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-secondary mb-0">В этом курсе пока нет уроков.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">Курсы ещё не добавлены.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="modal fade" id="topic-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5" id="topic-modal-title">Создать курс</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="topic-form" method="POST" action="{{ route('education.topics.store') }}">
                @csrf
                <input type="hidden" name="_method" id="topic-method" value="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="topic-title">Название</label>
                        <input class="form-control" id="topic-title" name="title" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="topic-description">Описание</label>
                        <textarea class="form-control" id="topic-description" name="description" rows="5"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="topic-position">Рейтинг</label>
                        <input class="form-control" id="topic-position" name="position" type="number" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer border-secondary justify-content-between">
                    <button type="button" class="btn btn-outline-danger d-none" id="delete-topic-button">Удалить</button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning">Сохранить</button>
                    </div>
                </div>
            </form>
            <form id="delete-topic-form" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="material-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5" id="material-modal-title">Создать материал</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="material-form" method="POST" action="{{ route('education.materials.store') }}" style="display:flex; flex-direction:column; min-height:0; max-height:calc(100vh - 3.5rem);">
                @csrf
                <input type="hidden" name="_method" id="material-method" value="POST">
                <div class="modal-body" style="overflow-y:auto; min-height:0; max-height:calc(100vh - 12rem);">
                    <div class="mb-3" id="topic-select-wrap">
                        <label class="form-label" for="material-topic-id">Курс</label>
                        <select class="form-select" id="material-topic-id" name="topic_id" required>
                            <option value="">Выберите курс</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="material-title">Название</label>
                        <input class="form-control" id="material-title" name="title" required maxlength="255">
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
                                <option value="markdown">HTML / Markdown</option>
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
                        <label class="form-label" for="material-body">Содержание</label>
                        <ul class="nav nav-tabs border-secondary mb-2" id="material-body-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active bg-dark text-warning border-secondary" id="material-body-edit-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-body-edit-pane" type="button"
                                        role="tab" aria-controls="material-body-edit-pane" aria-selected="true">
                                    Редактирование HTML
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="material-body-preview-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-body-preview-pane" type="button"
                                        role="tab" aria-controls="material-body-preview-pane" aria-selected="false">
                                    Просмотр
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content border border-secondary rounded-bottom p-2">
                            <div class="tab-pane fade show active" id="material-body-edit-pane" role="tabpanel"
                                 aria-labelledby="material-body-edit-tab" tabindex="0">
                                <textarea class="form-control font-monospace" id="material-body" name="body" rows="12" required style="max-height:45vh; overflow-y:auto; resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade" id="material-body-preview-pane" role="tabpanel"
                                 aria-labelledby="material-body-preview-tab" tabindex="0">
                                <iframe id="material-body-preview" class="w-100 rounded border border-secondary bg-white"
                                        style="min-height:320px;" sandbox=""></iframe>
                                <pre id="material-body-json-preview" class="p-3 rounded bg-black text-light mb-0 d-none" style="max-height:45vh; overflow:auto;"></pre>
                                <div id="material-body-link-preview" class="d-none"></div>
                            </div>
                        </div>
                        <div class="form-text">HTML/Markdown-текст, URL видео или JSON сценария — в зависимости от выбранного типа.</div>
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
    const topicModalElement = document.getElementById('topic-modal');
    const topicModal = new bootstrap.Modal(topicModalElement);
    const topicForm = document.getElementById('topic-form');
    const topicMethod = document.getElementById('topic-method');
    const topicDeleteForm = document.getElementById('delete-topic-form');
    const topicDeleteButton = document.getElementById('delete-topic-button');
    const topicFields = {
        title: document.getElementById('topic-title'),
        description: document.getElementById('topic-description'),
        position: document.getElementById('topic-position'),
    };

    const materialModalElement = document.getElementById('material-modal');
    const materialModal = new bootstrap.Modal(materialModalElement);
    const materialForm = document.getElementById('material-form');
    const materialMethod = document.getElementById('material-method');
    const materialDeleteForm = document.getElementById('delete-material-form');
    const materialDeleteButton = document.getElementById('delete-material-button');
    const previewTab = document.getElementById('material-body-preview-tab');
    const editTab = document.getElementById('material-body-edit-tab');
    const htmlPreview = document.getElementById('material-body-preview');
    const jsonPreview = document.getElementById('material-body-json-preview');
    const linkPreview = document.getElementById('material-body-link-preview');
    const fields = {
        topicId: document.getElementById('material-topic-id'),
        title: document.getElementById('material-title'),
        level: document.getElementById('material-level'),
        contentType: document.getElementById('material-content-type'),
        version: document.getElementById('material-version'),
        body: document.getElementById('material-body'),
    };
    const materials = @json($materialEditorItems ?? []);
    const topics = @json($topicEditorItems ?? []);
    const topicStoreUrl = @json(route('education.topics.store'));
    const topicUpdateUrl = @json(route('education.topics.update', ['topic' => '__ID__']));
    const topicDeleteUrl = @json(route('education.topics.destroy', ['topic' => '__ID__']));
    const materialStoreUrl = @json(route('education.materials.store'));
    const materialUpdateUrl = @json(route('education.materials.update', ['material' => '__ID__']));
    const materialDeleteUrl = @json(route('education.materials.destroy', ['material' => '__ID__']));
    let currentTopicId = null;
    let currentMaterialId = null;

    function resetPreview() {
        htmlPreview.srcdoc = '';
        jsonPreview.textContent = '';
        linkPreview.innerHTML = '';
        htmlPreview.classList.remove('d-none');
        jsonPreview.classList.add('d-none');
        linkPreview.classList.add('d-none');
    }

    function updatePreview() {
        resetPreview();
        const body = fields.body.value || '';

        if (fields.contentType.value === 'interactive_scenario') {
            htmlPreview.classList.add('d-none');
            jsonPreview.classList.remove('d-none');
            try {
                jsonPreview.textContent = JSON.stringify(JSON.parse(body), null, 2);
            } catch (error) {
                jsonPreview.textContent = body || 'JSON пока не заполнен.';
            }
            return;
        }

        if (fields.contentType.value === 'video_link') {
            htmlPreview.classList.add('d-none');
            linkPreview.classList.remove('d-none');
            const safeUrl = body.trim();
            linkPreview.innerHTML = '';

            if (safeUrl) {
                const link = document.createElement('a');
                link.className = 'btn btn-outline-warning';
                link.href = safeUrl;
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = 'Открыть видео';
                linkPreview.appendChild(link);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'text-secondary';
                placeholder.textContent = 'Ссылка на видео пока не заполнена.';
                linkPreview.appendChild(placeholder);
            }
            return;
        }

        htmlPreview.srcdoc = body || '<div style="font-family:Arial,sans-serif;color:#6c757d;padding:16px;">Содержимое пока не заполнено.</div>';
    }

    function showEditTab() {
        bootstrap.Tab.getOrCreateInstance(editTab).show();
    }

    function openCreateTopic() {
        topicForm.reset();
        currentTopicId = null;
        topicForm.action = topicStoreUrl;
        topicMethod.value = 'POST';
        document.getElementById('topic-modal-title').textContent = 'Создать курс';
        topicDeleteButton.classList.add('d-none');
        topicFields.position.value = '0';
        topicModal.show();
    }

    function openEditTopic(id) {
        const topic = topics[id];
        if (!topic) return;

        topicForm.reset();
        currentTopicId = id;
        topicForm.action = topicUpdateUrl.replace('__ID__', id);
        topicMethod.value = 'PUT';
        document.getElementById('topic-modal-title').textContent = 'Изменить курс';
        topicFields.title.value = topic.title || '';
        topicFields.description.value = topic.description || '';
        topicFields.position.value = topic.position || 0;
        topicDeleteForm.action = topicDeleteUrl.replace('__ID__', id);
        topicDeleteButton.classList.remove('d-none');
        topicModal.show();
    }

    function deleteTopic(id) {
        topicDeleteForm.action = topicDeleteUrl.replace('__ID__', id);
        if (confirm('Удалить курс? Все уроки, связанные тесты и попытки также будут удалены.')) topicDeleteForm.submit();
    }

    function openCreateLesson(topicId = '') {
        materialForm.reset();
        materialForm.action = materialStoreUrl;
        materialMethod.value = 'POST';
        document.getElementById('material-modal-title').textContent = 'Создать урок';
        materialDeleteButton.classList.add('d-none');
        currentMaterialId = null;
        fields.title.value = '';
        fields.version.value = '1.0';
        fields.topicId.value = topicId;
        resetPreview();
        showEditTab();
        materialModal.show();
    }

    function openEditLesson(id) {
        const item = materials[id];
        if (!item) return;
        materialForm.reset();
        currentMaterialId = id;
        materialForm.action = materialUpdateUrl.replace('__ID__', id);
        materialMethod.value = 'PUT';
        document.getElementById('material-modal-title').textContent = 'Изменить урок';
        fields.topicId.value = item.topic_id;
        fields.title.value = item.title || `Урок #${id}`;
        fields.level.value = item.level;
        fields.contentType.value = item.content_type;
        fields.version.value = item.version;
        fields.body.value = item.body;
        materialDeleteForm.action = materialDeleteUrl.replace('__ID__', id);
        materialDeleteButton.classList.remove('d-none');
        resetPreview();
        showEditTab();
        materialModal.show();
    }

    function deleteMaterial(id) {
        materialDeleteForm.action = materialDeleteUrl.replace('__ID__', id);
        if (confirm('Удалить урок? Связанные тесты и попытки также будут удалены.')) materialDeleteForm.submit();
    }

    fields.body.addEventListener('input', updatePreview);
    fields.contentType.addEventListener('change', updatePreview);
    previewTab.addEventListener('shown.bs.tab', updatePreview);

    document.getElementById('create-course-button').addEventListener('click', openCreateTopic);
    document.querySelectorAll('.edit-topic-button').forEach(button =>
        button.addEventListener('click', () => openEditTopic(button.dataset.topicId))
    );
    document.querySelectorAll('.create-material-button').forEach(button =>
        button.addEventListener('click', () => openCreateLesson(button.dataset.topicId || ''))
    );
    document.querySelectorAll('.lesson-row').forEach(row =>
        row.addEventListener('click', () => openEditLesson(row.dataset.materialId))
    );
    document.querySelectorAll('.lesson-row').forEach(row =>
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openEditLesson(row.dataset.materialId);
            }
        })
    );
    topicDeleteButton.addEventListener('click', () => {
        if (currentTopicId) deleteTopic(currentTopicId);
    });
    materialDeleteButton.addEventListener('click', () => {
        if (currentMaterialId) deleteMaterial(currentMaterialId);
    });
});
</script>
@endpush
