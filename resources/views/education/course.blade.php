@extends('home')

@section('title', 'Курс обучения')

@section('content')
<div class="container pb-5">
    <div class="mb-4 text-secondary">
        {{ $project->name }} · материал адаптируется к текущему уровню и результатам тестов.
    </div>

    @forelse($topics as $topic)
        @php
            $material = $topic->getRelation('selectedMaterial');
            $progress = $topic->getRelation('studentProgress');
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
                        <span class="badge text-bg-warning">
                            {{ $levelLabels[$material->level] ?? $material->level }} · v{{ $material->version }}
                        </span>
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
            </div>
        </article>
    @empty
        <div class="alert alert-info">Темы курса ещё не добавлены.</div>
    @endforelse
</div>
@endsection
