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
                    $material = $topic->relationLoaded('selectedMaterial') ? $topic->getRelation('selectedMaterial') : null;
                    $progress = $topic->relationLoaded('studentProgress') ? $topic->getRelation('studentProgress') : null;
                    $accordionId = 'course-lessons-' . $topic->id;
                @endphp

                <div class="accordion mb-3" id="course-accordion-{{ $topic->id }}">
                    <div class="accordion-item bg-dark border-secondary text-light">
                        <h2 class="accordion-header" id="{{ $accordionId }}-heading">
                            <button class="accordion-button collapsed bg-dark text-light" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}-body"
                                    aria-expanded="false" aria-controls="{{ $accordionId }}-body">
                                <span class="me-3 fw-semibold">{{ $topic->title }}</span>
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
                                    <button type="button" class="btn btn-sm btn-warning create-material-button"
                                            data-topic-id="{{ $topic->id }}">
                                        Добавить урок
                                    </button>
                                </div>

                                @if($topic->materials->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-dark table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Урок</th>
                                                    <th>Уровень</th>
                                                    <th>Тип</th>
                                                    <th>Версия</th>
                                                    <th class="text-end">Действия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topic->materials as $topicMaterial)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold">{{ $topic->title }}</div>
                                                            <div class="small text-secondary">
                                                                {{ \Illuminate\Support\Str::limit(strip_tags($topicMaterial->body), 120) }}
                                                            </div>
                                                        </td>
                                                        <td>{{ $levelLabels[$topicMaterial->level] ?? $topicMaterial->level }}</td>
                                                        <td>{{ $contentTypeLabels[$topicMaterial->content_type] ?? $topicMaterial->content_type }}</td>
                                                        <td>v{{ $topicMaterial->version }}</td>
                                                        <td>
                                                            <div class="d-flex justify-content-end gap-2">
                                                                <button type="button" class="btn btn-sm btn-outline-light edit-material-button"
                                                                        data-material-id="{{ $topicMaterial->id }}">
                                                                    Изменить
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-material-inline-button"
                                                                        data-material-id="{{ $topicMaterial->id }}">
                                                                    Удалить
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-secondary mb-0">В этом курсе пока нет уроков.</div>
                                @endif

                                @if($material)
                                    <div class="border-top border-secondary mt-4 pt-3">
                                        <div class="small text-secondary mb-2">Текущий материал для студента</div>
                                        @if($material->content_type === 'video_link')
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
                                    </div>
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
                        <select class="form-select" id="material-topic-id" name="topic_id">
                            <option value="">Создать новый курс</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label" for="material-topic-title">Название курса</label>
                            <input class="form-control" id="material-topic-title" name="topic_title" required maxlength="255">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="material-position">Порядок</label>
                            <input class="form-control" id="material-position" name="position" type="number" min="0" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="material-topic-description">Описание курса</label>
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
    const modalElement = document.getElementById('material-modal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('material-form');
    const deleteForm = document.getElementById('delete-material-form');
    const deleteButton = document.getElementById('delete-material-button');
    const previewTab = document.getElementById('material-body-preview-tab');
    const editTab = document.getElementById('material-body-edit-tab');
    const htmlPreview = document.getElementById('material-body-preview');
    const jsonPreview = document.getElementById('material-body-json-preview');
    const linkPreview = document.getElementById('material-body-link-preview');
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
    const materials = @json($materialEditorItems ?? []);
    const topics = @json($topicEditorItems ?? []);
    const storeUrl = @json(route('education.materials.store'));
    const updateUrl = @json(route('education.materials.update', ['material' => '__ID__']));
    const deleteUrl = @json(route('education.materials.destroy', ['material' => '__ID__']));
    let currentMaterialId = null;

    function applyTopic(topicId) {
        const topic = topics[topicId];
        if (!topic) {
            fields.title.value = '';
            fields.description.value = '';
            fields.position.value = 0;
            return;
        }

        fields.title.value = topic.title;
        fields.description.value = topic.description || '';
        fields.position.value = topic.position || 0;
    }

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

    function openCreate(topicId = '') {
        form.reset();
        form.action = storeUrl;
        document.getElementById('material-method').value = 'POST';
        document.getElementById('material-modal-title').textContent = 'Создать урок';
        deleteButton.classList.add('d-none');
        currentMaterialId = null;
        fields.version.value = '1.0';
        fields.topicId.value = topicId;
        if (topicId) applyTopic(topicId);
        resetPreview();
        showEditTab();
        modal.show();
    }

    function openEdit(id) {
        const item = materials[id];
        if (!item) return;
        form.reset();
        currentMaterialId = id;
        form.action = updateUrl.replace('__ID__', id);
        document.getElementById('material-method').value = 'PUT';
        document.getElementById('material-modal-title').textContent = 'Изменить урок';
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
        resetPreview();
        showEditTab();
        modal.show();
    }

    function deleteMaterial(id) {
        deleteForm.action = deleteUrl.replace('__ID__', id);
        if (confirm('Удалить урок? Связанные тесты и попытки также будут удалены.')) deleteForm.submit();
    }

    fields.topicId.addEventListener('change', () => applyTopic(fields.topicId.value));
    fields.body.addEventListener('input', updatePreview);
    fields.contentType.addEventListener('change', updatePreview);
    previewTab.addEventListener('shown.bs.tab', updatePreview);

    document.getElementById('create-material-button').addEventListener('click', () => openCreate());
    document.querySelectorAll('.create-material-button').forEach(button =>
        button.addEventListener('click', () => openCreate(button.dataset.topicId || ''))
    );
    document.querySelectorAll('.edit-material-button').forEach(button =>
        button.addEventListener('click', () => openEdit(button.dataset.materialId))
    );
    document.querySelectorAll('.delete-material-inline-button').forEach(button =>
        button.addEventListener('click', () => deleteMaterial(button.dataset.materialId))
    );
    deleteButton.addEventListener('click', () => {
        if (currentMaterialId) deleteMaterial(currentMaterialId);
    });
});
</script>
@endpush
