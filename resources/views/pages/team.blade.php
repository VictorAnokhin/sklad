@extends('home')

@section('title')
Команда
@endsection

@section('header_actions')
    @auth
        <a href="{{ route('team.report') }}" class="btn btn-outline-warning me-2">{{ __('team.payroll_title') }}</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teamMemberModal">{{ __('team.payroll_add_member') }}</button>
    @endauth
@endsection

@section('content')
<style>
    .team-page {
        max-width: 1180px;
        margin: 0 auto;
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

    .team-user-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .team-user-row:last-child { border-bottom: 0; }
    .team-user-row__name { color: #fff; font-weight: 600; }
    .team-user-row__meta { color: rgba(255,255,255,.62); font-size: .86rem; }
    .team-photo-preview { width: 100%; max-width: 220px; min-height: 140px; border: 1px solid rgba(255,255,255,.12); border-radius: 16px; background: rgba(255,255,255,.03); display: flex; align-items: center; justify-content: center; overflow: hidden; color: var(--muted-foreground); text-align: center; }
    .team-photo-preview img { width: 100%; height: 140px; object-fit: cover; }
</style>

<div class="team-page">
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @if($teamMembers->isEmpty())
        <div class="team-empty">
            В команде этой компании пока нет сотрудников.
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

@auth
<div class="modal fade" id="teamMemberModal" tabindex="-1" aria-labelledby="teamMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="teamMemberModalLabel">Добавить сотрудника</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $errors->any() ? '' : 'active' }}" id="team-users-tab" data-bs-toggle="tab" data-bs-target="#team-users-pane" type="button" role="tab">Пользователи</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $errors->any() ? 'active' : '' }}" id="team-new-tab" data-bs-toggle="tab" data-bs-target="#team-new-pane" type="button" role="tab">Новый</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ $errors->any() ? '' : 'show active' }}" id="team-users-pane" role="tabpanel">
                        <p class="text-muted">Выберите существующего пользователя компании или холдинга. Его данные и клиентская запись будут сохранены.</p>
                        <div class="mb-3">
                            <label class="form-label d-block">Добавить сотрудником в компании</label>
                            <div class="d-flex flex-wrap gap-3 rounded border p-3" id="team-existing-projects">
                                @foreach($companyOptions as $company)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="{{ $company->id }}" id="existing-project-{{ $company->id }}" {{ in_array((string) $company->id, $selectedCompanyIds, true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="existing-project-{{ $company->id }}">{{ $company->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="search" class="form-control mb-3" id="team-user-search" placeholder="Поиск по имени, email или телефону" autocomplete="off">
                        <div class="rounded border" id="team-users-list"></div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="team-users-prev" disabled>Назад</button>
                            <span class="text-muted small" id="team-users-page"></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="team-users-next" disabled>Далее</button>
                        </div>
                    </div>

                    <div class="tab-pane fade {{ $errors->any() ? 'show active' : '' }}" id="team-new-pane" role="tabpanel">
                        @include('team._form', ['member' => null, 'inModal' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endauth
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('teamMemberModal');
    const list = document.getElementById('team-users-list');
    const search = document.getElementById('team-user-search');
    const prev = document.getElementById('team-users-prev');
    const next = document.getElementById('team-users-next');
    const pageLabel = document.getElementById('team-users-page');
    if (!modalElement || !list || !search || !prev || !next || !pageLabel) return;

    let page = 1;
    let lastPage = 1;
    let timer = null;
    let controller = null;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value || '');
        return div.innerHTML;
    }

    async function loadUsers(requestedPage = 1) {
        controller?.abort();
        controller = new AbortController();
        list.innerHTML = '<div class="p-3 text-muted">Загрузка...</div>';
        const params = new URLSearchParams({ page: String(requestedPage) });
        if (search.value.trim()) params.set('search', search.value.trim());

        try {
            const response = await fetch(`{{ route('team.users') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Не удалось загрузить пользователей.');

            page = Number(payload.current_page) || 1;
            lastPage = Number(payload.last_page) || 1;
            prev.disabled = page <= 1;
            next.disabled = page >= lastPage;
            pageLabel.textContent = `Страница ${page} из ${lastPage}`;

            const users = Array.isArray(payload.data) ? payload.data : [];
            if (!users.length) {
                list.innerHTML = '<div class="p-3 text-muted">Пользователи не найдены.</div>';
                return;
            }

            list.innerHTML = users.map(user => {
                const contacts = [user.email, user.phone].filter(Boolean).map(escapeHtml).join(' · ');
                const memberships = Array.isArray(user.company_names) && user.company_names.length
                    ? `<div class="team-user-row__meta">Уже сотрудник: ${user.company_names.map(escapeHtml).join(', ')}</div>`
                    : '';
                return `<div class="team-user-row">
                    <div>
                        <div class="team-user-row__name">${escapeHtml(user.name)}</div>
                        <div class="team-user-row__meta">${contacts}</div>
                        ${memberships}
                    </div>
                    <button type="button" class="btn btn-sm btn-success" data-team-user-id="${user.id}">Добавить</button>
                </div>`;
            }).join('');
        } catch (error) {
            if (error?.name !== 'AbortError') list.innerHTML = `<div class="p-3 text-danger">${escapeHtml(error?.message || 'Ошибка загрузки.')}</div>`;
        }
    }

    async function attachUser(button) {
        const projectIds = Array.from(document.querySelectorAll('#team-existing-projects input:checked')).map(input => input.value);
        if (!projectIds.length) {
            alert('Выберите хотя бы одну компанию.');
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch(`{{ route('team.attach') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ user_id: button.dataset.teamUserId, project_ids: projectIds }),
            });
            const payload = await response.json();
            if (!response.ok || payload.success === false) throw new Error(payload.message || 'Не удалось добавить сотрудника.');
            window.location.reload();
        } catch (error) {
            alert(error?.message || 'Не удалось добавить сотрудника.');
            button.disabled = false;
        }
    }

    modalElement.addEventListener('show.bs.modal', () => loadUsers(1));
    list.addEventListener('click', event => {
        const button = event.target.closest('[data-team-user-id]');
        if (button) attachUser(button);
    });
    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadUsers(1), 300);
    });
    prev.addEventListener('click', () => { if (page > 1) loadUsers(page - 1); });
    next.addEventListener('click', () => { if (page < lastPage) loadUsers(page + 1); });

    @if($errors->any())
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    @endif
});
</script>
@endpush
