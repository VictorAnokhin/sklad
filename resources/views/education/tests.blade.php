@extends('home')

@section('title', 'Тесты')

@section('content')
<div class="container pb-5">
    @foreach(['success', 'warning'] as $messageType)
        @if(session($messageType))
            <div class="alert alert-{{ $messageType }}">{{ session($messageType) }}</div>
        @endif
    @endforeach

    @forelse($tests as $test)
        @php
            $questions = $test->quest_data['questions'] ?? [];
            $lastAttempt = $attempts->get($test->id)?->first();
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
                                        {{ $option }}
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
@endsection
