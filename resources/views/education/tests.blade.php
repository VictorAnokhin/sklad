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
        grid-template-columns: minmax(0, 1fr) 90px 90px 52px;
        gap: 0.75rem;
        align-items: end;
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
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="test-title">Название теста</label>
                        <input class="form-control" id="test-title" name="title" required maxlength="255">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="test-type">Тип теста</label>
                            <select class="form-select" id="test-type" name="test_type" required>
                                <option value="profile_assessment">Профильная анкета</option>
                                <option value="knowledge_check">Проверка после материала</option>
                            </select>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="test-material-id">Материал курса</label>
                            <select class="form-select" id="test-material-id" name="material_id">
                                <option value="">Без материала — самостоятельный тест</option>
                                @if($materials->isEmpty())
                                    <option value="">Сначала создайте материал курса</option>
                                @endif
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">
                                        {{ $material->topic->title }} · {{ $material->level }} · v{{ $material->version }}
                                    </option>
                                @endforeach
                            </select>
                            @if($materials->isEmpty())
                                <div class="form-text text-warning">
                                    Для профильной анкеты материал не нужен. Для проверки после урока сначала создайте материал на странице «Курс обучения».
                                </div>
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="test-passing-score">Проходной балл, %</label>
                            <input class="form-control" id="test-passing-score" name="passing_score"
                                   type="number" min="1" max="100" value="80" required>
                        </div>
                    </div>
                    <input type="hidden" id="test-quest-data" name="quest_data" required>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="test-public-featured">
                        <label class="form-check-label" for="test-public-featured">Показывать первым на публичной странице «Узнай себя»</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="test-intro">Описание перед вопросами</label>
                        <textarea class="form-control" id="test-intro" rows="3"
                                  placeholder="Кратко объясните, как проходить тест"></textarea>
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
        testType: document.getElementById('test-type'),
        materialId: document.getElementById('test-material-id'),
        passingScore: document.getElementById('test-passing-score'),
        questData: document.getElementById('test-quest-data'),
        publicFeatured: document.getElementById('test-public-featured'),
        intro: document.getElementById('test-intro'),
    };
    const questionsEditor = document.getElementById('questions-editor');
    const resultsEditor = document.getElementById('results-editor');
    const tests = @json($testEditorItems ?? []);
    const storeUrl = @json(route('education.tests.store'));
    const updateUrl = @json(route('education.tests.update', ['test' => '__ID__']));
    const deleteUrl = @json(route('education.tests.destroy', ['test' => '__ID__']));
    const example = {
        public_featured: true,
        scoring: 'points',
        intro: 'Ответьте честно: здесь нет правильных и неправильных вариантов. Каждый ответ добавляет баллы, по сумме определяется профиль.',
        questions: [
            {
                text: 'Ваш портфель упал на 10% за одну неделю из-за общих рыночных новостей. Ваша первая реакция?',
                options: [
                    { text: 'Тревога. Начинаю всерьез задумываться о закрытии позиций, чтобы сохранить остатки.', score: 1 },
                    { text: 'Беспокойство. Буду чаще проверять котировки, но пока ничего не предприму.', score: 2 },
                    { text: 'Спокойствие. Это обычные рыночные колебания, ничего страшного.', score: 3 }
                ]
            }
        ],
        results: [
            { min: 1, max: 1, title: 'Низкая стрессоустойчивость', description: 'Просадка вызывает сильный стресс.', recommendation: 'Начните с консервативных инструментов и небольших сумм.' },
            { min: 2, max: 2, title: 'Средняя стрессоустойчивость', description: 'Есть тревога, но без импульсивных действий.', recommendation: 'Подойдёт сбалансированный портфель и заранее прописанный план.' },
            { min: 3, max: 3, title: 'Высокая стрессоустойчивость', description: 'Вы воспринимаете волатильность как нормальную часть рынка.', recommendation: 'Можно рассматривать более рискованные инструменты, не забывая про диверсификацию.' }
        ]
    };

    function optionValue(option, key, fallback = '') {
        if (typeof option === 'string') {
            return key === 'text' ? option : fallback;
        }
        return option?.[key] ?? fallback;
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
            <div>
                <label class="form-label small text-secondary mb-1">Балл</label>
                <input class="form-control" data-option-score type="number" min="0" value="0">
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
        row.querySelector('[data-option-score]').value = optionValue(option, 'score', 0);
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
        questionElement.querySelector('[data-add-option]').addEventListener('click', () => addOption(questionElement, { text: '', score: 0 }));

        const options = Array.isArray(question.options) && question.options.length > 0
            ? question.options
            : [{ text: '', score: 1 }, { text: '', score: 2 }, { text: '', score: 3 }];
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
        fields.publicFeatured.checked = Boolean(data.public_featured);
        fields.intro.value = data.intro || '';

        const questions = Array.isArray(data.questions) && data.questions.length > 0 ? data.questions : example.questions;
        questions.forEach(addQuestion);

        const results = Array.isArray(data.results) && data.results.length > 0 ? data.results : example.results;
        results.forEach(addResult);
    }

    function collectQuestData() {
        const questions = Array.from(questionsEditor.children).map((questionElement) => {
            const optionRows = Array.from(questionElement.querySelectorAll('[data-options] .test-answer-row'));
            const correctIndex = optionRows.findIndex((row) => row.querySelector('[data-option-correct]').checked);
            const question = {
                text: questionElement.querySelector('[data-question-text]').value.trim(),
                options: optionRows.map((row) => ({
                    text: row.querySelector('[data-option-text]').value.trim(),
                    score: Number(row.querySelector('[data-option-score]').value || 0),
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
            public_featured: fields.publicFeatured.checked,
            scoring: fields.testType.value === 'profile_assessment' ? 'points' : 'correct_answers',
            intro: fields.intro.value.trim(),
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
        fields.testType.value = 'profile_assessment';
        fields.materialId.value = '';
        fields.passingScore.value = 1;
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
        fields.title.value = item.title;
        fields.testType.value = item.test_type || 'knowledge_check';
        fields.materialId.value = item.material_id || '';
        fields.passingScore.value = item.passing_score;
        loadQuestData(item.quest_data || example);
        deleteForm.action = deleteUrl.replace('__ID__', id);
        deleteButton.classList.remove('d-none');
        activateQuestionsTab();
        modal.show();
    }

    form.addEventListener('submit', () => {
        fields.questData.value = JSON.stringify(collectQuestData());
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
