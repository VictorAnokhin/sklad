@extends('home')

@section('title', 'Узнай себя')

@section('header_actions')
<button type="button" class="btn btn-warning" id="create-know-test-button" @disabled($migrationRequired ?? false)>Создать</button>
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

    @forelse($tests as $test)
        @php
            $questions = $test->quest_data['questions'] ?? [];
            $intro = $test->quest_data['intro'] ?? '';
            $testAttempts = $attempts->get($test->id);
            $lastAttempt = $testAttempts ? $testAttempts->first() : null;
        @endphp
        <section class="card bg-dark border-secondary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <h2 class="h4 text-white mb-1">{{ $test->title }}</h2>
                        <div class="text-secondary">Самостоятельная профильная анкета · сумма баллов</div>
                    </div>
                    <div class="d-flex align-items-start gap-2 flex-wrap">
                        @if($lastAttempt)
                            <span class="badge text-bg-success">
                                Последний результат: {{ $lastAttempt->total_score ?? $lastAttempt->score }} / {{ $lastAttempt->max_score ?? '—' }} баллов
                            </span>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-light edit-know-test-button"
                                data-test-id="{{ $test->id }}">Изменить</button>
                    </div>
                </div>

                @if($intro)
                    <div class="alert alert-secondary bg-black border-secondary text-light">{{ $intro }}</div>
                @endif

                <form method="POST" action="{{ route('education.tests.submit', $test) }}">
                    @csrf
                    @foreach($questions as $questionIndex => $question)
                        <fieldset class="mb-4">
                            <legend class="fs-6 text-light">{{ $questionIndex + 1 }}. {{ $question['text'] ?? '' }}</legend>
                            @foreach(($question['options'] ?? []) as $optionIndex => $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                           name="answers[{{ $questionIndex }}]"
                                           id="know-test-{{ $test->id }}-q-{{ $questionIndex }}-a-{{ $optionIndex }}"
                                           value="{{ $optionIndex }}" required>
                                    <label class="form-check-label text-light"
                                           for="know-test-{{ $test->id }}-q-{{ $questionIndex }}-a-{{ $optionIndex }}">
                                        {{ is_array($option) ? ($option['text'] ?? $option['label'] ?? '') : $option }}
                                    </label>
                                </div>
                            @endforeach
                        </fieldset>
                    @endforeach
                    <button class="btn btn-warning" type="submit" @disabled(count($questions) === 0)>
                        Завершить тест
                    </button>
                </form>
            </div>
        </section>
    @empty
        <div class="alert alert-info">Тесты для страницы «Узнай себя» пока не созданы.</div>
    @endforelse
</div>

<style>
    #know-test-modal .modal-dialog {
        max-height: calc(100vh - 1.75rem);
    }
    #know-test-modal .modal-content {
        max-height: calc(100vh - 1.75rem);
    }
    #know-test-form {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #know-test-modal .modal-body {
        overflow-y: auto;
        max-height: calc(100vh - 13rem);
    }
    #know-test-modal textarea {
        resize: vertical;
    }
    .know-answer-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 110px;
        gap: 0.75rem;
        align-items: start;
    }
    @media (max-width: 576px) {
        .know-answer-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="modal fade" id="know-test-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5" id="know-test-modal-title">Создать тест</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="know-test-form" method="POST" action="{{ route('education.know-yourself.store') }}">
                @csrf
                <input type="hidden" name="_method" id="know-test-method" value="POST">
                <input type="hidden" id="know-test-quest-data" name="quest_data" required>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="know-test-title">Название теста</label>
                        <input class="form-control" id="know-test-title" name="title" required maxlength="255">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="know-test-public-featured">
                        <label class="form-check-label" for="know-test-public-featured">Показывать первым на публичной странице «Узнай себя»</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="know-test-intro">Описание перед вопросами</label>
                        <textarea class="form-control" id="know-test-intro" rows="3"
                                  placeholder="Кратко объясните, как проходить тест"></textarea>
                    </div>

                    <ul class="nav nav-tabs border-secondary mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="know-questions-tab" data-bs-toggle="tab"
                                    data-bs-target="#know-questions-pane" type="button" role="tab">
                                Вопросы
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="know-results-tab" data-bs-toggle="tab"
                                    data-bs-target="#know-results-pane" type="button" role="tab">
                                Результат
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="know-questions-pane" role="tabpanel" aria-labelledby="know-questions-tab">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <div class="form-text">Кнопка добавляет вопрос и 3 варианта ответа. У каждого варианта укажите балл.</div>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="know-add-question-button">
                                    Добавить
                                </button>
                            </div>
                            <div class="accordion" id="know-questions-editor"></div>
                        </div>

                        <div class="tab-pane fade" id="know-results-pane" role="tabpanel" aria-labelledby="know-results-tab">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <div class="form-text">Результат выбирается по диапазону: минимум ≤ сумма баллов ≤ максимум.</div>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="know-add-result-button">
                                    Добавить результат
                                </button>
                            </div>
                            <div class="vstack gap-3" id="know-results-editor"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-secondary justify-content-between">
                    <button type="button" class="btn btn-outline-danger d-none" id="delete-know-test-button">Удалить</button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning">Сохранить</button>
                    </div>
                </div>
            </form>
            <form id="delete-know-test-form" method="POST" class="d-none">
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
    const modalElement = document.getElementById('know-test-modal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('know-test-form');
    const deleteForm = document.getElementById('delete-know-test-form');
    const deleteButton = document.getElementById('delete-know-test-button');
    const fields = {
        title: document.getElementById('know-test-title'),
        questData: document.getElementById('know-test-quest-data'),
        publicFeatured: document.getElementById('know-test-public-featured'),
        intro: document.getElementById('know-test-intro'),
    };
    const questionsEditor = document.getElementById('know-questions-editor');
    const resultsEditor = document.getElementById('know-results-editor');
    const tests = @json($testEditorItems ?? []);
    const storeUrl = @json(route('education.know-yourself.store'));
    const updateUrl = @json(route('education.know-yourself.update', ['test' => '__ID__']));
    const deleteUrl = @json(route('education.know-yourself.destroy', ['test' => '__ID__']));
    const example = {
        public_featured: true,
        scoring: 'points',
        intro: 'Ответьте честно: здесь нет правильных и неправильных вариантов. По сумме баллов определяется ваш профиль.',
        questions: [
            {
                text: 'Ваш портфель упал на 10% за одну неделю из-за общих рыночных новостей. Ваша первая реакция?',
                options: [
                    { text: 'Тревога. Начинаю всерьез задумываться о закрытии позиций, чтобы сохранить остатки.', score: 1 },
                    { text: 'Беспокойство. Буду чаще проверять котировки, но пока ничего не предприму.', score: 2 },
                    { text: 'Спокойствие. Это обычные рыночные колебания, ничего страшного.', score: 3 },
                ],
            },
        ],
        results: [
            { min: 1, max: 13, title: 'Консервативный инвестор', description: 'Для вас сохранность капитала важнее его приумножения.', recommendation: 'Основу портфеля лучше строить из защитных инструментов.' },
            { min: 14, max: 19, title: 'Умеренный инвестор', description: 'Вы готовы терпеть временную волатильность, но глубокие кризисы могут выбить из колеи.', recommendation: 'Подойдёт сбалансированный портфель и заранее прописанный план действий.' },
            { min: 20, max: 24, title: 'Агрессивный инвестор', description: 'Падение рынка для вас скорее возможность, чем трагедия.', recommendation: 'Можно рассматривать более рискованные инструменты, контролируя самоуверенность и плечи.' },
        ],
    };

    function optionText(option) {
        return typeof option === 'string' ? option : (option?.text || option?.label || '');
    }

    function optionScore(option, fallback) {
        return typeof option === 'object' && option !== null ? (option.score ?? fallback) : fallback;
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
            heading.id = `know-question-heading-${number}`;
            collapse.id = `know-question-collapse-${number}`;
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
            <div class="accordion-collapse collapse" data-question-collapse data-bs-parent="#know-questions-editor">
                <div class="accordion-body">
                <div class="d-flex justify-content-between gap-3 mb-3">
                    <div class="flex-grow-1">
                        <label class="form-label">Вопрос</label>
                        <textarea class="form-control" data-question-text rows="2" required></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger align-self-start" data-remove-question>Удалить</button>
                </div>
                <div class="vstack gap-2" data-options></div>
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

        const optionsWrap = questionElement.querySelector('[data-options]');
        const options = Array.isArray(question.options) ? question.options.slice(0, 3) : [];
        while (options.length < 3) {
            options.push({ text: '', score: options.length + 1 });
        }

        options.forEach((option, index) => {
            const row = document.createElement('div');
            row.className = 'know-answer-row';
            row.innerHTML = `
                <div>
                    <label class="form-label small text-secondary mb-1">Вариант ответа ${index + 1}</label>
                    <input class="form-control" data-option-text required>
                </div>
                <div>
                    <label class="form-label small text-secondary mb-1">Балл</label>
                    <input class="form-control" data-option-score type="number" min="0" required>
                </div>
            `;
            row.querySelector('[data-option-text]').value = optionText(option);
            row.querySelector('[data-option-score]').value = optionScore(option, index + 1);
            optionsWrap.appendChild(row);
        });

        questionsEditor.appendChild(questionElement);
        refreshQuestionAccordion();
    }

    function addResult(result = {}) {
        const resultElement = document.createElement('div');
        resultElement.className = 'card bg-black border-secondary';
        resultElement.innerHTML = `
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
                        <input class="form-control" data-result-title required>
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
        resultElement.querySelector('[data-result-min]').value = result.min ?? 0;
        resultElement.querySelector('[data-result-max]').value = result.max ?? 0;
        resultElement.querySelector('[data-result-title]').value = result.title || '';
        resultElement.querySelector('[data-result-description]').value = result.description || '';
        resultElement.querySelector('[data-result-recommendation]').value = result.recommendation || '';
        resultElement.querySelector('[data-remove-result]').addEventListener('click', () => resultElement.remove());
        resultsEditor.appendChild(resultElement);
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
        const questions = Array.from(questionsEditor.children).map((questionElement) => ({
            text: questionElement.querySelector('[data-question-text]').value.trim(),
            options: Array.from(questionElement.querySelectorAll('.know-answer-row')).map((row) => ({
                text: row.querySelector('[data-option-text]').value.trim(),
                score: Number(row.querySelector('[data-option-score]').value || 0),
            })),
        }));

        const results = Array.from(resultsEditor.children).map((resultElement) => ({
            min: Number(resultElement.querySelector('[data-result-min]').value || 0),
            max: Number(resultElement.querySelector('[data-result-max]').value || 0),
            title: resultElement.querySelector('[data-result-title]').value.trim(),
            description: resultElement.querySelector('[data-result-description]').value.trim(),
            recommendation: resultElement.querySelector('[data-result-recommendation]').value.trim(),
        }));

        return {
            public_featured: fields.publicFeatured.checked,
            scoring: 'points',
            intro: fields.intro.value.trim(),
            questions,
            results,
        };
    }

    function activateQuestionsTab() {
        const trigger = document.getElementById('know-questions-tab');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }

    function openCreate() {
        form.reset();
        form.action = storeUrl;
        document.getElementById('know-test-method').value = 'POST';
        document.getElementById('know-test-modal-title').textContent = 'Создать тест';
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
        document.getElementById('know-test-method').value = 'PUT';
        document.getElementById('know-test-modal-title').textContent = 'Изменить тест';
        fields.title.value = item.title || '';
        loadQuestData(item.quest_data || example);
        deleteForm.action = deleteUrl.replace('__ID__', id);
        deleteButton.classList.remove('d-none');
        activateQuestionsTab();
        modal.show();
    }

    form.addEventListener('submit', () => {
        fields.questData.value = JSON.stringify(collectQuestData());
    });
    document.getElementById('know-add-question-button').addEventListener('click', () => addQuestion());
    document.getElementById('know-add-result-button').addEventListener('click', () => addResult());
    document.getElementById('create-know-test-button').addEventListener('click', openCreate);
    document.querySelectorAll('.edit-know-test-button').forEach((button) => {
        button.addEventListener('click', () => openEdit(button.dataset.testId));
    });
    deleteButton.addEventListener('click', () => {
        if (confirm('Удалить тест и историю попыток по нему?')) deleteForm.submit();
    });
});
</script>
@endpush
