@extends('home')

@section('title', 'Тесты')

@section('header_actions')
<button type="button" class="btn btn-warning" id="create-test-button" @disabled($migrationRequired ?? false)>Создать</button>
@endsection

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
    @if($migrationRequired ?? false)
        <div class="alert alert-warning">
            Таблицы образовательного модуля ещё не созданы. Выполните миграции Laravel:
            <code>php artisan migrate --force</code>.
        </div>
    @endif

    <div class="card bg-dark border-secondary">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Название</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tests as $test)
                        <tr>
                            <td>
                                <button type="button"
                                        class="btn btn-link text-light text-decoration-none p-0 edit-test-button"
                                        data-test-id="{{ $test->id }}">
                                    {{ $test->title }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary">Тесты пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    #test-modal .modal-dialog {
        max-height: calc(100vh - 1.75rem);
    }
    #test-modal .modal-content {
        max-height: calc(100vh - 1.75rem);
    }
    #test-form {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #test-modal .modal-body {
        overflow-y: auto;
        max-height: calc(100vh - 13rem);
    }
    #test-modal textarea {
        resize: vertical;
    }
    .test-answer-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 90px 52px;
        gap: 0.75rem;
        align-items: end;
    }
    .material-search-results {
        display: none;
        max-height: 320px;
        overflow-y: auto;
        position: relative;
        z-index: 1060;
    }
    .selected-material-details {
        font-size: 0.85rem;
    }
    @media (max-width: 768px) {
        .test-answer-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="modal fade" id="test-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5" id="test-modal-title">Создать тест</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="test-form" method="POST" action="{{ route('education.tests.store') }}">
                @csrf
                <input type="hidden" name="_method" id="test-method" value="POST">
                <input type="hidden" id="test-type" name="test_type" value="knowledge_check">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название теста</label>
                        <div class="row g-2">
                            <div class="col-md"><input class="form-control" id="test-title-ua" name="title_translations[ua]" maxlength="255" placeholder="UA"></div>
                            <div class="col-md"><input class="form-control" id="test-title" name="title_translations[ru]" maxlength="255" placeholder="RU"></div>
                            <div class="col-md"><input class="form-control" id="test-title-en" name="title_translations[en]" maxlength="255" placeholder="EN"></div>
                            <div class="col-md"><input class="form-control" id="test-title-es" name="title_translations[es]" maxlength="255" placeholder="ES"></div>
                            <div class="col-md"><input class="form-control" id="test-title-fr" name="title_translations[fr]" maxlength="255" placeholder="FR"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="test-material-id">Материал курса</label>
                            <div class="client-search-row d-flex gap-1">
                                <input type="text" id="test-material-search" class="form-control flex-grow-1 bg-dark text-white border-secondary"
                                       placeholder="Поиск урока или курса" autocomplete="off">
                            </div>
                            <div id="test-material-results" class="list-group client-search-results material-search-results"></div>
                            <input type="hidden" id="test-material-id" name="material_id">
                            <div id="selected-material-details"
                                 class="alert alert-warning py-1 mt-1 selected-client-details selected-client-details--empty selected-material-details">
                                Без материала — самостоятельный тест
                            </div>
                            @if($materials->isEmpty())
                                <div class="form-text text-warning">
                                    Для профильной анкеты материал не нужен. Для проверки после урока сначала создайте материал на странице «Курс обучения».
                                </div>
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="test-rating">Рейтинг</label>
                            <input class="form-control" id="test-rating" name="rating"
                                   type="number" min="0" value="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="test-passing-score">Проходной балл, %</label>
                            <input class="form-control" id="test-passing-score" name="passing_score"
                                   type="number" min="1" max="100" value="80" required>
                        </div>
                    </div>
                    <input type="hidden" id="test-quest-data" name="quest_data" required>
                    <input type="hidden" id="test-quest-data-translations" name="quest_data_translations">

                    <div class="btn-group btn-group-sm mb-3" role="group" aria-label="Язык контента теста">
                        <button type="button" class="btn btn-warning" data-test-lang="ru">RU</button>
                        <button type="button" class="btn btn-outline-warning" data-test-lang="ua">UA</button>
                        <button type="button" class="btn btn-outline-warning" data-test-lang="en">EN</button>
                        <button type="button" class="btn btn-outline-warning" data-test-lang="es">ES</button>
                        <button type="button" class="btn btn-outline-warning" data-test-lang="fr">FR</button>
                    </div>

                    <ul class="nav nav-tabs border-secondary mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="test-questions-tab" data-bs-toggle="tab"
                                    data-bs-target="#test-questions-pane" type="button" role="tab">
                                Вопросы
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="test-results-tab" data-bs-toggle="tab"
                                    data-bs-target="#test-results-pane" type="button" role="tab">
                                Результат
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="test-questions-pane" role="tabpanel" aria-labelledby="test-questions-tab">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <div class="form-text">Для проверки после материала отметьте верный ответ. Для профильной анкеты заполните баллы.</div>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="add-question-button">
                                    Добавить
                                </button>
                            </div>
                            <div class="accordion" id="questions-editor"></div>
                        </div>

                        <div class="tab-pane fade" id="test-results-pane" role="tabpanel" aria-labelledby="test-results-tab">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <div class="form-text">Результат выбирается по диапазону: минимум ≤ сумма баллов ≤ максимум.</div>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="add-result-button">
                                    Добавить результат
                                </button>
                            </div>
                            <div class="vstack gap-3" id="results-editor"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary justify-content-between">
                    <button type="button" class="btn btn-outline-danger d-none" id="delete-test-button">Удалить</button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning">Сохранить</button>
                    </div>
                </div>
            </form>
            <form id="delete-test-form" method="POST" class="d-none">
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
    const modal = new bootstrap.Modal(document.getElementById('test-modal'));
    const form = document.getElementById('test-form');
    const deleteForm = document.getElementById('delete-test-form');
    const deleteButton = document.getElementById('delete-test-button');
    const fields = {
        title: document.getElementById('test-title'),
        titleUa: document.getElementById('test-title-ua'),
        titleEn: document.getElementById('test-title-en'),
        titleEs: document.getElementById('test-title-es'),
        titleFr: document.getElementById('test-title-fr'),
        testType: document.getElementById('test-type'),
        materialId: document.getElementById('test-material-id'),
        rating: document.getElementById('test-rating'),
        passingScore: document.getElementById('test-passing-score'),
        questData: document.getElementById('test-quest-data'),
        questDataTranslations: document.getElementById('test-quest-data-translations'),
    };
    const questionsEditor = document.getElementById('questions-editor');
    const resultsEditor = document.getElementById('results-editor');
    const materialSearch = document.getElementById('test-material-search');
    const materialResults = document.getElementById('test-material-results');
    const selectedMaterialDetails = document.getElementById('selected-material-details');
    const tests = @json($testEditorItems ?? []);
    const materialOptions = @json($materialSearchItems ?? []);
    const storeUrl = @json(route('education.tests.store'));
    const updateUrl = @json(route('education.tests.update', ['test' => '__ID__']));
    const deleteUrl = @json(route('education.tests.destroy', ['test' => '__ID__']));
    const example = {
        public_featured: false,
        scoring: 'correct_answers',
        intro: '',
        questions: [
            {
                text: 'Ваш портфель упал на 10% за одну неделю из-за общих рыночных новостей. Ваша первая реакция?',
                options: [
                    { text: 'Тревога. Начинаю всерьез задумываться о закрытии позиций, чтобы сохранить остатки.' },
                    { text: 'Беспокойство. Буду чаще проверять котировки, но пока ничего не предприму.' },
                    { text: 'Спокойствие. Это обычные рыночные колебания, ничего страшного.' }
                ],
                correct_index: 2
            }
        ],
        results: [
            { min: 1, max: 1, title: 'Низкая стрессоустойчивость', description: 'Просадка вызывает сильный стресс.', recommendation: 'Начните с консервативных инструментов и небольших сумм.' },
            { min: 2, max: 2, title: 'Средняя стрессоустойчивость', description: 'Есть тревога, но без импульсивных действий.', recommendation: 'Подойдёт сбалансированный портфель и заранее прописанный план.' },
            { min: 3, max: 3, title: 'Высокая стрессоустойчивость', description: 'Вы воспринимаете волатильность как нормальную часть рынка.', recommendation: 'Можно рассматривать более рискованные инструменты, не забывая про диверсификацию.' }
        ]
    };
    let currentLang = 'ru';
    let questDataByLang = {};

    function optionValue(option, key, fallback = '') {
        if (typeof option === 'string') {
            return key === 'text' ? option : fallback;
        }
        return option?.[key] ?? fallback;
    }

    function setSelectedMaterial(materialId = '') {
        fields.materialId.value = materialId ? String(materialId) : '';
        const material = materialOptions.find((item) => item.id === fields.materialId.value);

        if (!material) {
            selectedMaterialDetails.className = 'alert alert-warning py-1 mt-1 selected-client-details selected-client-details--empty selected-material-details';
            selectedMaterialDetails.innerHTML = 'Без материала — самостоятельный тест';
            materialSearch.value = '';
            return;
        }

        selectedMaterialDetails.className = 'alert alert-secondary py-1 mt-1 selected-client-details selected-client-details--filled selected-material-details';
        selectedMaterialDetails.innerHTML = formatMaterialDetailsHtml(material);
        materialSearch.value = material.title;
    }

    function resetMaterialSearch() {
        materialSearch.value = '';
        materialResults.innerHTML = '';
        materialResults.style.display = 'none';
    }

    function formatMaterialDetailsHtml(material) {
        const meta = [material.topic, material.level ? `Уровень: ${material.level}` : '', material.version ? `v${material.version}` : '']
            .filter(Boolean)
            .join(' | ');

        return `<strong>${escapeHtml(material.title)}</strong><br><small><em>${escapeHtml(meta)}</em></small>`;
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value || '');
        return div.innerHTML;
    }

    function renderMaterialResults(query = materialSearch.value) {
        const normalized = query.trim().toLowerCase();
        const filtered = materialOptions.filter((material) => {
            if (normalized === '') return true;
            return `${material.title} ${material.topic} ${material.level} ${material.version}`.toLowerCase().includes(normalized);
        });

        materialResults.innerHTML = '';

        const standalone = document.createElement('a');
        standalone.href = '#';
        standalone.className = 'list-group-item list-group-item-action bg-white text-dark';
        standalone.innerHTML = 'Без материала — самостоятельный тест';
        standalone.addEventListener('click', (event) => {
            event.preventDefault();
            setSelectedMaterial('');
            materialResults.style.display = 'none';
        });
        materialResults.appendChild(standalone);

        if (filtered.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item text-dark bg-white';
            empty.textContent = 'Уроки не найдены';
            materialResults.appendChild(empty);
        } else {
            filtered.forEach((material) => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action bg-white text-dark';
                item.innerHTML = formatMaterialDetailsHtml(material);
                item.addEventListener('click', (event) => {
                    event.preventDefault();
                    setSelectedMaterial(material.id);
                    materialResults.style.display = 'none';
                });
                materialResults.appendChild(item);
            });
        }

        materialResults.style.display = 'block';
    }

    function addOption(questionElement, option = {}, isCorrect = false) {
        const optionsWrap = questionElement.querySelector('[data-options]');
        const row = document.createElement('div');
        row.className = 'test-answer-row';
        row.innerHTML = `
            <div>
                <label class="form-label small text-secondary mb-1">Вариант ответа</label>
                <input class="form-control" data-option-text required>
            </div>
            <div class="text-center">
                <label class="form-label small text-secondary mb-1 d-block">Верный</label>
                <input class="form-check-input" type="radio" data-option-correct>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-outline-danger" data-remove-option>×</button>
            </div>
        `;
        row.querySelector('[data-option-text]').value = optionValue(option, 'text');
        row.querySelector('[data-option-correct]').checked = isCorrect;
        row.querySelector('[data-option-correct]').addEventListener('change', () => {
            if (!row.querySelector('[data-option-correct]').checked) return;
            optionsWrap.querySelectorAll('[data-option-correct]').forEach((input) => {
                if (input !== row.querySelector('[data-option-correct]')) input.checked = false;
            });
        });
        row.querySelector('[data-remove-option]').addEventListener('click', () => row.remove());
        optionsWrap.appendChild(row);
    }

    function refreshQuestionAccordion() {
        Array.from(questionsEditor.children).forEach((questionElement, index) => {
            const number = index + 1;
            const heading = questionElement.querySelector('[data-question-heading]');
            const collapse = questionElement.querySelector('[data-question-collapse]');
            const button = questionElement.querySelector('[data-question-toggle]');
            const titleInput = questionElement.querySelector('[data-question-text]');
            const title = titleInput?.value.trim() || `Вопрос ${number}`;

            questionElement.dataset.questionIndex = String(index);
            heading.id = `test-question-heading-${number}`;
            collapse.id = `test-question-collapse-${number}`;
            collapse.setAttribute('aria-labelledby', heading.id);
            button.setAttribute('data-bs-target', `#${collapse.id}`);
            button.setAttribute('aria-controls', collapse.id);
            button.textContent = `${number}. ${title}`;
        });
    }

    function addQuestion(question = {}) {
        const questionElement = document.createElement('div');
        questionElement.className = 'accordion-item bg-black border-secondary text-light';
        questionElement.innerHTML = `
            <h2 class="accordion-header" data-question-heading>
                <button class="accordion-button collapsed bg-black text-light border-secondary" type="button"
                        data-bs-toggle="collapse" data-question-toggle>
                    Вопрос
                </button>
            </h2>
            <div class="accordion-collapse collapse" data-question-collapse data-bs-parent="#questions-editor">
                <div class="accordion-body">
                <div class="d-flex justify-content-between gap-3 mb-3">
                    <div class="flex-grow-1">
                        <label class="form-label">Вопрос</label>
                        <textarea class="form-control" data-question-text rows="2" required></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger align-self-start" data-remove-question>Удалить</button>
                </div>
                <div class="vstack gap-2 mb-2" data-options></div>
                <button type="button" class="btn btn-sm btn-outline-light" data-add-option>Добавить вариант</button>
                </div>
            </div>
        `;
        const questionText = questionElement.querySelector('[data-question-text]');
        questionText.value = question.text || '';
        questionText.addEventListener('input', refreshQuestionAccordion);
        questionElement.querySelector('[data-remove-question]').addEventListener('click', () => {
            questionElement.remove();
            refreshQuestionAccordion();
        });
        questionElement.querySelector('[data-add-option]').addEventListener('click', () => addOption(questionElement, { text: '' }));

        const options = Array.isArray(question.options) && question.options.length > 0
            ? question.options
            : [{ text: '' }, { text: '' }, { text: '' }];
        options.forEach((option, index) => addOption(questionElement, option, Number(question.correct_index) === index));
        questionsEditor.appendChild(questionElement);
        refreshQuestionAccordion();
    }

    function addResult(result = {}) {
        const row = document.createElement('div');
        row.className = 'card bg-black border-secondary';
        row.innerHTML = `
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Мин.</label>
                        <input class="form-control" data-result-min type="number" min="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Макс.</label>
                        <input class="form-control" data-result-max type="number" min="0" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Название результата</label>
                        <input class="form-control mb-2" data-result-title required>
                        <input class="form-control" data-result-subtitle placeholder="Подзаголовок">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Описание</label>
                        <textarea class="form-control" data-result-description rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Рекомендация</label>
                        <textarea class="form-control" data-result-recommendation rows="3"></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-result>Удалить</button>
                    </div>
                </div>
            </div>
        `;
        row.querySelector('[data-result-min]').value = result.min ?? 0;
        row.querySelector('[data-result-max]').value = result.max ?? 0;
        row.querySelector('[data-result-title]').value = result.title || '';
        row.querySelector('[data-result-subtitle]').value = result.subtitle || '';
        row.querySelector('[data-result-description]').value = result.description || '';
        row.querySelector('[data-result-recommendation]').value = result.recommendation || '';
        row.querySelector('[data-remove-result]').addEventListener('click', () => row.remove());
        resultsEditor.appendChild(row);
    }

    function loadQuestData(data) {
        questionsEditor.innerHTML = '';
        resultsEditor.innerHTML = '';

        const questions = Array.isArray(data.questions) && data.questions.length > 0 ? data.questions : example.questions;
        questions.forEach(addQuestion);

        const results = Array.isArray(data.results) && data.results.length > 0 ? data.results : example.results;
        results.forEach(addResult);
    }

    function setLanguage(lang) {
        questDataByLang[currentLang] = collectQuestData();
        currentLang = lang;
        document.querySelectorAll('[data-test-lang]').forEach((button) => {
            const active = button.dataset.testLang === lang;
            button.classList.toggle('btn-warning', active);
            button.classList.toggle('btn-outline-warning', !active);
        });
        loadQuestData(questDataByLang[lang] || example);
    }

    function activateLanguageButton(lang) {
        document.querySelectorAll('[data-test-lang]').forEach((button) => {
            const active = button.dataset.testLang === lang;
            button.classList.toggle('btn-warning', active);
            button.classList.toggle('btn-outline-warning', !active);
        });
    }

    function collectQuestData() {
        const questions = Array.from(questionsEditor.children).map((questionElement) => {
            const optionRows = Array.from(questionElement.querySelectorAll('[data-options] .test-answer-row'));
            const correctIndex = optionRows.findIndex((row) => row.querySelector('[data-option-correct]').checked);
            const question = {
                text: questionElement.querySelector('[data-question-text]').value.trim(),
                options: optionRows.map((row) => ({
                    text: row.querySelector('[data-option-text]').value.trim(),
                })),
            };

            if (fields.testType.value === 'knowledge_check') {
                question.correct_index = correctIndex >= 0 ? correctIndex : 0;
            }

            return question;
        });

        const results = Array.from(resultsEditor.children).map((row) => ({
            min: Number(row.querySelector('[data-result-min]').value || 0),
            max: Number(row.querySelector('[data-result-max]').value || 0),
            title: row.querySelector('[data-result-title]').value.trim(),
            subtitle: row.querySelector('[data-result-subtitle]').value.trim(),
            description: row.querySelector('[data-result-description]').value.trim(),
            recommendation: row.querySelector('[data-result-recommendation]').value.trim(),
        }));

        return {
            public_featured: false,
            scoring: 'correct_answers',
            rating: Number(fields.rating.value || 0),
            intro: '',
            questions,
            results,
        };
    }

    function activateQuestionsTab() {
        const trigger = document.getElementById('test-questions-tab');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }

    function openCreate() {
        form.reset();
        form.action = storeUrl;
        document.getElementById('test-method').value = 'POST';
        document.getElementById('test-modal-title').textContent = 'Создать тест';
        fields.testType.value = 'knowledge_check';
        fields.titleUa.value = '';
        fields.title.value = '';
        fields.titleEn.value = '';
        fields.titleEs.value = '';
        fields.titleFr.value = '';
        resetMaterialSearch();
        setSelectedMaterial('');
        fields.rating.value = 0;
        fields.passingScore.value = 80;
        currentLang = 'ru';
        activateLanguageButton('ru');
        questDataByLang = { ru: JSON.parse(JSON.stringify(example)) };
        loadQuestData(example);
        deleteButton.classList.add('d-none');
        activateQuestionsTab();
        modal.show();
    }

    function openEdit(id) {
        const item = tests[id];
        if (!item) return;
        form.reset();
        form.action = updateUrl.replace('__ID__', id);
        document.getElementById('test-method').value = 'PUT';
        document.getElementById('test-modal-title').textContent = 'Изменить тест';
        fields.title.value = item.title_translations?.ru || item.title || '';
        fields.titleUa.value = item.title_translations?.ua || '';
        fields.titleEn.value = item.title_translations?.en || '';
        fields.titleEs.value = item.title_translations?.es || '';
        fields.titleFr.value = item.title_translations?.fr || '';
        fields.testType.value = item.test_type || 'knowledge_check';
        resetMaterialSearch();
        setSelectedMaterial(item.material_id || '');
        fields.rating.value = item.quest_data?.rating ?? 0;
        fields.passingScore.value = item.passing_score;
        currentLang = 'ru';
        activateLanguageButton('ru');
        questDataByLang = {
            ru: item.quest_data_translations?.ru || item.quest_data || example,
            ua: item.quest_data_translations?.ua || item.quest_data || example,
            en: item.quest_data_translations?.en || item.quest_data || example,
            es: item.quest_data_translations?.es || item.quest_data || example,
            fr: item.quest_data_translations?.fr || item.quest_data || example,
        };
        loadQuestData(questDataByLang.ru || example);
        deleteForm.action = deleteUrl.replace('__ID__', id);
        deleteButton.classList.remove('d-none');
        activateQuestionsTab();
        modal.show();
    }

    form.addEventListener('submit', () => {
        questDataByLang[currentLang] = collectQuestData();
        fields.questData.value = JSON.stringify(questDataByLang.ru || questDataByLang.ua || questDataByLang.en || questDataByLang.es || questDataByLang.fr || example);
        fields.questDataTranslations.value = JSON.stringify(questDataByLang);
    });
    document.querySelectorAll('[data-test-lang]').forEach((button) => {
        button.addEventListener('click', () => setLanguage(button.dataset.testLang));
    });
    materialSearch.addEventListener('focus', () => renderMaterialResults());
    materialSearch.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault();
    });
    materialSearch.addEventListener('input', () => renderMaterialResults());
    document.addEventListener('click', (event) => {
        if (event.target === materialSearch || materialResults.contains(event.target)) return;
        materialResults.style.display = 'none';
    });
    document.getElementById('add-question-button').addEventListener('click', () => addQuestion());
    document.getElementById('add-result-button').addEventListener('click', () => addResult());
    document.getElementById('create-test-button').addEventListener('click', openCreate);
    document.querySelectorAll('.edit-test-button').forEach(button =>
        button.addEventListener('click', () => openEdit(button.dataset.testId))
    );
    deleteButton.addEventListener('click', () => {
        if (confirm('Удалить тест и историю попыток по нему?')) deleteForm.submit();
    });
});
</script>
@endpush
