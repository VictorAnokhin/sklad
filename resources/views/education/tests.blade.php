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

    @forelse($tests as $test)
        @php
            $questions = $test->quest_data['questions'] ?? [];
            $testAttempts = $attempts->get($test->id);
            $lastAttempt = $testAttempts ? $testAttempts->first() : null;
        @endphp
        <section class="card bg-dark border-secondary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <h2 class="h4 text-white mb-1">{{ $test->title }}</h2>
                        <div class="text-secondary">
                            {{ $test->material->topic->title }} · {{ $test->material->level }} · проходной балл {{ $test->passing_score }}%
                        </div>
                    </div>
                    @if($lastAttempt)
                        <span class="badge {{ $lastAttempt->passed ? 'text-bg-success' : 'text-bg-danger' }}">
                            Последний результат: {{ $lastAttempt->score }}%
                        </span>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-light edit-test-button"
                            data-test-id="{{ $test->id }}">Изменить</button>
                </div>

                <form method="POST" action="{{ route('education.tests.submit', $test) }}">
                    @csrf
                    @foreach($questions as $questionIndex => $question)
                        <fieldset class="mb-4">
                            <legend class="fs-6 text-light">{{ $questionIndex + 1 }}. {{ $question['text'] ?? '' }}</legend>
                            @foreach(($question['options'] ?? []) as $optionIndex => $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                           name="answers[{{ $questionIndex }}]"
                                           id="test-{{ $test->id }}-q-{{ $questionIndex }}-a-{{ $optionIndex }}"
                                           value="{{ $optionIndex }}" required>
                                    <label class="form-check-label text-light"
                                           for="test-{{ $test->id }}-q-{{ $questionIndex }}-a-{{ $optionIndex }}">
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
        <div class="alert alert-info">Для текущего уровня пока нет активных тестов.</div>
    @endforelse
</div>

<div class="modal fade" id="test-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                        <div class="col-md-9 mb-3">
                            <label class="form-label" for="test-material-id">Материал курса</label>
                            <select class="form-select" id="test-material-id" name="material_id" required>
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
                                    Для сохранения теста нужен хотя бы один материал на странице «Курс обучения».
                                </div>
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="test-passing-score">Проходной балл, %</label>
                            <input class="form-control" id="test-passing-score" name="passing_score"
                                   type="number" min="1" max="100" value="80" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="test-quest-data">Вопросы в формате JSON</label>
                        <textarea class="form-control font-monospace" id="test-quest-data" name="quest_data"
                                  rows="16" required></textarea>
                        <div class="form-text">
                            Проверка знаний: <code>text</code>, <code>options</code>, <code>correct_index</code>.
                            Анкета: варианты <code>{"text":"...","score":1}</code> и диапазоны <code>results</code>.
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
        materialId: document.getElementById('test-material-id'),
        passingScore: document.getElementById('test-passing-score'),
        questData: document.getElementById('test-quest-data'),
    };
    const tests = @json($testEditorItems ?? []);
    const storeUrl = @json(route('education.tests.store'));
    const updateUrl = @json(route('education.tests.update', ['test' => '__ID__']));
    const deleteUrl = @json(route('education.tests.destroy', ['test' => '__ID__']));
    const example = {
        public_featured: false,
        questions: [
            {
                text: 'Какой вариант является правильным?',
                options: ['Первый', 'Второй', 'Третий'],
                correct_index: 0
            }
        ],
        results: []
    };

    function openCreate() {
        form.reset();
        form.action = storeUrl;
        document.getElementById('test-method').value = 'POST';
        document.getElementById('test-modal-title').textContent = 'Создать тест';
        fields.passingScore.value = 80;
        fields.questData.value = JSON.stringify(example, null, 2);
        deleteButton.classList.add('d-none');
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
        fields.materialId.value = item.material_id;
        fields.passingScore.value = item.passing_score;
        fields.questData.value = JSON.stringify(item.quest_data, null, 2);
        deleteForm.action = deleteUrl.replace('__ID__', id);
        deleteButton.classList.remove('d-none');
        modal.show();
    }

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
