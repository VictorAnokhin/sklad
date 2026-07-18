@extends('home')

@section('title', 'Курс обучения')

@section('header_actions')
<button type="button" class="btn btn-outline-warning me-2" id="manage-education-categories-button" @disabled($migrationRequired ?? false)>Категории</button>
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
                                <span class="badge text-bg-info me-2">{{ $topic->category?->title ?? 'Без категории' }}</span>
                                <span class="badge text-bg-warning me-2">Рейтинг {{ $requiredRating }}</span>
                                <span class="badge text-bg-secondary">{{ $topic->materials->count() }} урок(ов)</span>
                            </button>
                        </h2>
                        <div id="{{ $accordionId }}-body" class="accordion-collapse collapse" data-topic-id="{{ $topic->id }}"
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
                                                    <th>Рейтинг</th>
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
                                                        <td>{{ (int) ($topicMaterial->rating ?? $topic->position ?? 0) }}</td>
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
                        <label class="form-label">Название</label>
                        <ul class="nav nav-tabs border-secondary mb-2" id="topic-title-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="topic-title-ua-tab"
                                        data-bs-toggle="tab" data-bs-target="#topic-title-ua-pane" type="button"
                                        role="tab" aria-controls="topic-title-ua-pane" aria-selected="false">
                                    UA
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active bg-dark text-warning border-secondary" id="topic-title-ru-tab"
                                        data-bs-toggle="tab" data-bs-target="#topic-title-ru-pane" type="button"
                                        role="tab" aria-controls="topic-title-ru-pane" aria-selected="true">
                                    RU
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="topic-title-en-tab"
                                        data-bs-toggle="tab" data-bs-target="#topic-title-en-pane" type="button"
                                        role="tab" aria-controls="topic-title-en-pane" aria-selected="false">
                                    EN
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content border border-secondary rounded-bottom p-2">
                            <div class="tab-pane fade" id="topic-title-ua-pane" role="tabpanel"
                                 aria-labelledby="topic-title-ua-tab" tabindex="0">
                                <textarea class="form-control" id="topic-title-ua" name="title_translations[ua]" rows="2" maxlength="255" placeholder="UA" style="resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade show active" id="topic-title-ru-pane" role="tabpanel"
                                 aria-labelledby="topic-title-ru-tab" tabindex="0">
                                <textarea class="form-control" id="topic-title" name="title_translations[ru]" rows="2" maxlength="255" placeholder="RU" style="resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade" id="topic-title-en-pane" role="tabpanel"
                                 aria-labelledby="topic-title-en-tab" tabindex="0">
                                <textarea class="form-control" id="topic-title-en" name="title_translations[en]" rows="2" maxlength="255" placeholder="EN" style="resize:vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <ul class="nav nav-tabs border-secondary mb-2" id="topic-description-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="topic-description-ua-tab"
                                        data-bs-toggle="tab" data-bs-target="#topic-description-ua-pane" type="button"
                                        role="tab" aria-controls="topic-description-ua-pane" aria-selected="false">
                                    UA
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active bg-dark text-warning border-secondary" id="topic-description-ru-tab"
                                        data-bs-toggle="tab" data-bs-target="#topic-description-ru-pane" type="button"
                                        role="tab" aria-controls="topic-description-ru-pane" aria-selected="true">
                                    RU
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="topic-description-en-tab"
                                        data-bs-toggle="tab" data-bs-target="#topic-description-en-pane" type="button"
                                        role="tab" aria-controls="topic-description-en-pane" aria-selected="false">
                                    EN
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content border border-secondary rounded-bottom p-2">
                            <div class="tab-pane fade" id="topic-description-ua-pane" role="tabpanel"
                                 aria-labelledby="topic-description-ua-tab" tabindex="0">
                                <textarea class="form-control" id="topic-description-ua" name="description_translations[ua]" rows="5" placeholder="UA" style="resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade show active" id="topic-description-ru-pane" role="tabpanel"
                                 aria-labelledby="topic-description-ru-tab" tabindex="0">
                                <textarea class="form-control" id="topic-description" name="description_translations[ru]" rows="5" placeholder="RU" style="resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade" id="topic-description-en-pane" role="tabpanel"
                                 aria-labelledby="topic-description-en-tab" tabindex="0">
                                <textarea class="form-control" id="topic-description-en" name="description_translations[en]" rows="5" placeholder="EN" style="resize:vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-0">
                            <label class="form-label" for="topic-category">Категория</label>
                            <select class="form-select" id="topic-category" name="category_id">
                                <option value="">Без категории</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-0">
                            <label class="form-label" for="topic-position">Рейтинг</label>
                            <input class="form-control" id="topic-position" name="position" type="number" min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-0">
                            <label class="form-label" for="topic-cost-av8">Стоимость, AV8</label>
                            <input class="form-control" id="topic-cost-av8" name="cost_av8" type="number" min="0" step="0.000001" value="0">
                        </div>
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
                        <label class="form-label">Название</label>
                        <ul class="nav nav-tabs border-secondary mb-2" id="material-title-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="material-title-ua-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-title-ua-pane" type="button"
                                        role="tab" aria-controls="material-title-ua-pane" aria-selected="false">
                                    UA
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active bg-dark text-warning border-secondary" id="material-title-ru-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-title-ru-pane" type="button"
                                        role="tab" aria-controls="material-title-ru-pane" aria-selected="true">
                                    RU
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="material-title-en-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-title-en-pane" type="button"
                                        role="tab" aria-controls="material-title-en-pane" aria-selected="false">
                                    EN
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content border border-secondary rounded-bottom p-2">
                            <div class="tab-pane fade" id="material-title-ua-pane" role="tabpanel"
                                 aria-labelledby="material-title-ua-tab" tabindex="0">
                                <textarea class="form-control" id="material-title-ua" name="title_translations[ua]" rows="2" maxlength="255" placeholder="UA" style="resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade show active" id="material-title-ru-pane" role="tabpanel"
                                 aria-labelledby="material-title-ru-tab" tabindex="0">
                                <textarea class="form-control" id="material-title" name="title_translations[ru]" rows="2" maxlength="255" placeholder="RU" style="resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade" id="material-title-en-pane" role="tabpanel"
                                 aria-labelledby="material-title-en-tab" tabindex="0">
                                <textarea class="form-control" id="material-title-en" name="title_translations[en]" rows="2" maxlength="255" placeholder="EN" style="resize:vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="material-rating">Рейтинг</label>
                            <input class="form-control" id="material-rating" name="rating" type="number" min="0" value="0" required>
                            <input type="hidden" id="material-level" name="level" value="beginner">
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
                                <button class="nav-link bg-dark text-light border-secondary" id="material-body-ua-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-body-ua-pane" type="button"
                                        role="tab" aria-controls="material-body-ua-pane" aria-selected="false">
                                    UA
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active bg-dark text-warning border-secondary" id="material-body-ru-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-body-ru-pane" type="button"
                                        role="tab" aria-controls="material-body-ru-pane" aria-selected="true">
                                    RU
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link bg-dark text-light border-secondary" id="material-body-en-tab"
                                        data-bs-toggle="tab" data-bs-target="#material-body-en-pane" type="button"
                                        role="tab" aria-controls="material-body-en-pane" aria-selected="false">
                                    EN
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content border border-secondary rounded-bottom p-2">
                            <div class="tab-pane fade" id="material-body-ua-pane" role="tabpanel"
                                 aria-labelledby="material-body-ua-tab" tabindex="0">
                                <textarea class="form-control font-monospace" id="material-body-ua" name="body_translations[ua]" rows="12" placeholder="UA" style="max-height:45vh; overflow-y:auto; resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade show active" id="material-body-ru-pane" role="tabpanel"
                                 aria-labelledby="material-body-ru-tab" tabindex="0">
                                <textarea class="form-control font-monospace" id="material-body" name="body_translations[ru]" rows="12" placeholder="RU" style="max-height:45vh; overflow-y:auto; resize:vertical;"></textarea>
                            </div>
                            <div class="tab-pane fade" id="material-body-en-pane" role="tabpanel"
                                 aria-labelledby="material-body-en-tab" tabindex="0">
                                <textarea class="form-control font-monospace" id="material-body-en" name="body_translations[en]" rows="12" placeholder="EN" style="max-height:45vh; overflow-y:auto; resize:vertical;"></textarea>
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
@include('education.category-modal', ['categoryContext' => \App\Models\EducationCategory::CONTEXT_COURSE])
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
    const topicTitleRuTab = document.getElementById('topic-title-ru-tab');
    const topicDescriptionRuTab = document.getElementById('topic-description-ru-tab');
    const topicFields = {
        title: document.getElementById('topic-title'),
        titleUa: document.getElementById('topic-title-ua'),
        titleEn: document.getElementById('topic-title-en'),
        description: document.getElementById('topic-description'),
        descriptionUa: document.getElementById('topic-description-ua'),
        descriptionEn: document.getElementById('topic-description-en'),
        categoryId: document.getElementById('topic-category'),
        position: document.getElementById('topic-position'),
        costAv8: document.getElementById('topic-cost-av8'),
    };

    const materialModalElement = document.getElementById('material-modal');
    const materialModal = new bootstrap.Modal(materialModalElement);
    const materialForm = document.getElementById('material-form');
    const materialMethod = document.getElementById('material-method');
    const materialDeleteForm = document.getElementById('delete-material-form');
    const materialDeleteButton = document.getElementById('delete-material-button');
    const materialTitleRuTab = document.getElementById('material-title-ru-tab');
    const materialBodyRuTab = document.getElementById('material-body-ru-tab');
    const fields = {
        topicId: document.getElementById('material-topic-id'),
        title: document.getElementById('material-title'),
        titleUa: document.getElementById('material-title-ua'),
        titleEn: document.getElementById('material-title-en'),
        rating: document.getElementById('material-rating'),
        level: document.getElementById('material-level'),
        contentType: document.getElementById('material-content-type'),
        version: document.getElementById('material-version'),
        body: document.getElementById('material-body'),
        bodyUa: document.getElementById('material-body-ua'),
        bodyEn: document.getElementById('material-body-en'),
    };
    const materials = @json($materialEditorItems ?? []);
    const topics = @json($topicEditorItems ?? []);
    const topicStoreUrl = @json(route('education.topics.store'));
    const topicUpdateUrl = @json(route('education.topics.update', ['topic' => '__ID__']));
    const topicDeleteUrl = @json(route('education.topics.destroy', ['topic' => '__ID__']));
    const materialStoreUrl = @json(route('education.materials.store'));
    const materialUpdateUrl = @json(route('education.materials.update', ['material' => '__ID__']));
    const materialDeleteUrl = @json(route('education.materials.destroy', ['material' => '__ID__']));
    const storageKeys = {
        openTopicId: 'education.course.openTopicId',
        materialSelectors: 'education.course.materialSelectors',
    };
    let currentTopicId = null;
    let currentMaterialId = null;

    function getStoredValue(key) {
        try {
            return window.sessionStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function setStoredValue(key, value) {
        try {
            window.sessionStorage.setItem(key, value);
        } catch (error) {
            // Storage may be unavailable in private or restricted browser modes.
        }
    }

    function selectHasValue(select, value) {
        return !!value && Array.from(select.options).some((option) => option.value === String(value));
    }

    function saveOpenTopic(topicId) {
        if (topicId) setStoredValue(storageKeys.openTopicId, String(topicId));
    }

    function readMaterialSelectors() {
        const rawValue = getStoredValue(storageKeys.materialSelectors);
        if (!rawValue) return {};

        try {
            return JSON.parse(rawValue) || {};
        } catch (error) {
            return {};
        }
    }

    function saveMaterialSelectors() {
        setStoredValue(storageKeys.materialSelectors, JSON.stringify({
            topicId: fields.topicId.value || '',
            contentType: fields.contentType.value || '',
        }));
    }

    function applyStoredMaterialSelectors(preferredTopicId = '') {
        const selectors = readMaterialSelectors();
        const topicId = preferredTopicId || selectors.topicId || '';

        if (selectHasValue(fields.topicId, topicId)) fields.topicId.value = String(topicId);
        if (selectHasValue(fields.contentType, selectors.contentType)) fields.contentType.value = selectors.contentType;
    }

    function selectedTopicRating() {
        const topic = topics[fields.topicId.value];
        return topic?.position ?? topic?.rating ?? 0;
    }

    function applyTopicRating() {
        fields.rating.value = selectedTopicRating();
    }

    function restoreOpenTopic() {
        const topicId = getStoredValue(storageKeys.openTopicId);
        if (!topicId) return;

        const collapseElement = Array.from(document.querySelectorAll('.accordion-collapse[data-topic-id]'))
            .find((element) => element.dataset.topicId === String(topicId));
        if (collapseElement) bootstrap.Collapse.getOrCreateInstance(collapseElement, { toggle: false }).show();
    }

    function showDefaultMaterialLanguageTabs() {
        bootstrap.Tab.getOrCreateInstance(materialTitleRuTab).show();
        bootstrap.Tab.getOrCreateInstance(materialBodyRuTab).show();
    }

    function showDefaultTopicLanguageTabs() {
        bootstrap.Tab.getOrCreateInstance(topicTitleRuTab).show();
        bootstrap.Tab.getOrCreateInstance(topicDescriptionRuTab).show();
    }

    function openCreateTopic() {
        topicForm.reset();
        currentTopicId = null;
        topicForm.action = topicStoreUrl;
        topicMethod.value = 'POST';
        document.getElementById('topic-modal-title').textContent = 'Создать курс';
        topicDeleteButton.classList.add('d-none');
        topicFields.position.value = '0';
        topicFields.costAv8.value = '0';
        topicFields.categoryId.value = '';
        showDefaultTopicLanguageTabs();
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
        topicFields.titleUa.value = topic.title_translations?.ua || '';
        topicFields.title.value = topic.title_translations?.ru || topic.title || '';
        topicFields.titleEn.value = topic.title_translations?.en || '';
        topicFields.description.value = topic.description || '';
        topicFields.descriptionUa.value = topic.description_translations?.ua || '';
        topicFields.description.value = topic.description_translations?.ru || topic.description || '';
        topicFields.descriptionEn.value = topic.description_translations?.en || '';
        topicFields.position.value = topic.position || 0;
        topicFields.costAv8.value = topic.cost_av8 || '0';
        topicFields.categoryId.value = topic.category_id || '';
        topicDeleteForm.action = topicDeleteUrl.replace('__ID__', id);
        topicDeleteButton.classList.remove('d-none');
        showDefaultTopicLanguageTabs();
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
        fields.titleUa.value = '';
        fields.titleEn.value = '';
        fields.body.value = '';
        fields.bodyUa.value = '';
        fields.bodyEn.value = '';
        fields.level.value = 'beginner';
        fields.version.value = '1.0';
        applyStoredMaterialSelectors(topicId);
        applyTopicRating();
        saveOpenTopic(fields.topicId.value);
        saveMaterialSelectors();
        showDefaultMaterialLanguageTabs();
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
        fields.title.value = item.title_translations?.ru || item.title || `Урок #${id}`;
        fields.titleUa.value = item.title_translations?.ua || '';
        fields.titleEn.value = item.title_translations?.en || '';
        fields.rating.value = item.rating ?? topics[item.topic_id]?.position ?? 0;
        fields.level.value = item.level || 'beginner';
        fields.contentType.value = item.content_type;
        fields.version.value = item.version;
        fields.body.value = item.body_translations?.ru || item.body || '';
        fields.bodyUa.value = item.body_translations?.ua || '';
        fields.bodyEn.value = item.body_translations?.en || '';
        saveOpenTopic(item.topic_id);
        saveMaterialSelectors();
        materialDeleteForm.action = materialDeleteUrl.replace('__ID__', id);
        materialDeleteButton.classList.remove('d-none');
        showDefaultMaterialLanguageTabs();
        materialModal.show();
    }

    function deleteMaterial(id) {
        materialDeleteForm.action = materialDeleteUrl.replace('__ID__', id);
        if (confirm('Удалить урок? Связанные тесты и попытки также будут удалены.')) materialDeleteForm.submit();
    }

    fields.topicId.addEventListener('change', () => {
        if (!currentMaterialId) applyTopicRating();
        saveOpenTopic(fields.topicId.value);
        saveMaterialSelectors();
    });
    fields.contentType.addEventListener('change', () => {
        saveMaterialSelectors();
    });
    materialForm.addEventListener('submit', () => {
        saveOpenTopic(fields.topicId.value);
        saveMaterialSelectors();
    });
    document.querySelectorAll('.accordion-collapse[data-topic-id]').forEach((collapseElement) => {
        collapseElement.addEventListener('shown.bs.collapse', () => saveOpenTopic(collapseElement.dataset.topicId));
    });

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
    restoreOpenTopic();
});
</script>
@endpush
