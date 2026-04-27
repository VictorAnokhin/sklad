@extends('home')

@section('title')
Команда
@endsection

@section('header_actions')
    @auth
        <a href="{{ route('team.show', ['id' => 0]) }}" class="btn btn-primary">Добавить</a>
    @endauth
@endsection

@section('content')
<style>
    .team-page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .team-hero {
        padding: 2.5rem;
        border-radius: 20px;
        background:
            radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 30%),
            linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border: 1px solid rgba(255,255,255,0.06);
        margin-bottom: 1.75rem;
    }

    .team-hero__eyebrow {
        color: #fbbf24;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .team-hero__title {
        color: #fff;
        font-size: clamp(2rem, 4vw, 3.4rem);
        line-height: 1.05;
        margin: 0 0 0.9rem;
        font-weight: 700;
    }

    .team-hero__text {
        color: rgba(255,255,255,0.72);
        max-width: 720px;
        font-size: 1.05rem;
        line-height: 1.65;
        margin: 0;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
        gap: 1.25rem;
    }

    .team-card {
        height: 100%;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.06);
        background:
            linear-gradient(180deg, rgba(255,255,255,0.045), rgba(255,255,255,0.02)),
            rgba(10, 10, 10, 0.72);
        box-shadow: 0 18px 40px rgba(0,0,0,0.22);
    }

    .team-card__photo {
        width: 100%;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        background:
            radial-gradient(circle at 30% 20%, rgba(251, 191, 36, 0.2), transparent 35%),
            linear-gradient(135deg, #202020, #101010);
        display: block;
    }

    .team-card__photo--fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fbbf24;
        font-size: 2rem;
        font-weight: 700;
        letter-spacing: 0.08em;
    }

    .team-card__body {
        padding: 1.15rem 1.15rem 1.25rem;
    }

    .team-card__name {
        color: #fff;
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .team-card__role {
        color: #fbbf24;
        font-size: 0.88rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        min-height: 1.2rem;
    }

    .team-card__description {
        color: rgba(255,255,255,0.72);
        font-size: 0.94rem;
        line-height: 1.6;
        margin-bottom: 0.9rem;
    }

    .team-card__meta {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .team-card__meta a,
    .team-card__meta span {
        color: rgba(255,255,255,0.78);
        text-decoration: none;
        word-break: break-word;
        font-size: 0.9rem;
    }

    .team-card__meta a:hover {
        color: #fbbf24;
    }

    .team-empty {
        padding: 2rem;
        border-radius: 18px;
        text-align: center;
        color: rgba(255,255,255,0.65);
        border: 1px dashed rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.02);
    }
</style>

<div class="team-page">
    <section class="team-hero">
        <div class="team-hero__eyebrow">AV8 Capital</div>
        <h2 class="team-hero__title">Команда</h2>
        <p class="team-hero__text">
            На этой странице отображаются пользователи из таблицы <code>users</code>, у которых <code>firmuser=1</code>.
        </p>
    </section>

    @if($teamMembers->isEmpty())
        <div class="team-empty">
            В команде пока нет участников с <code>firmuser=1</code>.
        </div>
    @else
        <section class="team-grid">
            @foreach($teamMembers as $member)
                <article class="team-card">
                    @if($member->photo)
                        <img src="{{ $member->photo }}" alt="{{ $member->full_name }}" class="team-card__photo">
                    @else
                        <div class="team-card__photo team-card__photo--fallback">
                            {{ mb_substr($member->full_name, 0, 1) }}
                        </div>
                    @endif

                    <div class="team-card__body">
                        <div class="team-card__name">{{ $member->full_name }}</div>
                        <div class="team-card__role">{{ $member->position ?: 'Участник команды' }}</div>

                        @if($member->description)
                            <div class="team-card__description">
                                {!! nl2br(e($member->description)) !!}
                            </div>
                        @endif

                        <div class="team-card__meta">
                            @if($member->location)
                                <span>{{ $member->location }}</span>
                            @endif
                            @if($member->email)
                                <a href="mailto:{{ $member->email }}">{{ $member->email }}</a>
                            @endif
                            @if($member->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $member->phone) }}">{{ $member->phone }}</a>
                            @endif
                            @if($member->website)
                                <a href="{{ str_starts_with($member->website, 'http://') || str_starts_with($member->website, 'https://') ? $member->website : 'https://' . $member->website }}" target="_blank" rel="noreferrer">
                                    {{ $member->website }}
                                </a>
                            @endif
                        </div>

                        @auth
                            <div style="margin-top: 1rem;">
                                <a href="{{ route('team.show', ['id' => $member->id]) }}" class="btn btn-sm btn-outline-warning">Редактировать</a>
                            </div>
                        @endauth
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</div>
@endsection
