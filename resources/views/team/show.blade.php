@extends('home')

@section('title')
{{ $member ? 'Редактирование участника команды' : 'Добавление участника команды' }}
@endsection

@section('content')
<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="glass-card" style="max-width: 980px;">
        <form method="POST" action="{{ route('team.save') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $member->id ?? '0' }}">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Фамилия</label>
                    <input type="text" name="secondname" class="form-control" value="{{ old('secondname', $member->secondname ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Имя</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $member->name ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Отчество</label>
                    <input type="text" name="fathername" class="form-control" value="{{ old('fathername', $member->fathername ?? '') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Должность</label>
                    <input type="text" name="name2" class="form-control" value="{{ old('name2', $member->name2 ?? '') }}" placeholder="CEO, Analyst, Partner">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Компания / подпись</label>
                    <input type="text" name="orgname" class="form-control" value="{{ old('orgname', $member->orgname ?? '') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $member->email ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $member->phone ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Сайт</label>
                    <input type="text" name="website" class="form-control" value="{{ old('website', $member->website ?? '') }}" placeholder="example.com">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Город</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $member->city ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Регион</label>
                    <input type="text" name="region" class="form-control" value="{{ old('region', $member->region ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Підрозділ</label>
                    <select name="top" class="form-control">
                        <option value="">— Оберіть підрозділ —</option>
                            <option value="1">Администрация</option>
                            <option value="2">Финансы</option>
                            <option value="3">Продажи</option>
                            <option value="4">Разработка</option>
                            <option value="5">Маркетинг</option>
                      
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Фото</label>
                    <input type="text" name="foto1" class="form-control" value="{{ old('foto1', $member->foto1 ?? '') }}" placeholder="files/... или https://...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Новый пароль</label>
                    <input type="password" name="pass" class="form-control" value="">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" rows="6" placeholder="Краткая информация об участнике команды">{{ old('description', $member->description ?? '') }}</textarea>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-success" style="min-width: 140px;">Сохранить</button>
                <a href="{{ route('team') }}" class="btn btn-outline-secondary" style="min-width: 120px;">Назад</a>
                @if($member && !empty($member->id))
                    <button
                        type="submit"
                        class="btn btn-outline-danger ms-auto"
                        formaction="{{ route('team.destroy') }}"
                        formmethod="POST"
                        formnovalidate
                        onclick="return confirm('Удалить этого участника команды?');"
                    >
                        Удалить
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('counterpartySearchInput');
    const searchBtn = document.getElementById('searchCounterpartyBtn');
    const resultsContainer = document.getElementById('counterpartySearchResults');
    const counterpartyId = document.getElementById('userid');
    const counterpartyDetails = document.getElementById('selectedCounterpartyDetails');
    const counterpartyName = document.getElementById('selectedCounterpartyName');

    if (!searchInput || !searchBtn || !resultsContainer || !counterpartyId || !counterpartyDetails || !counterpartyName) {
        return;
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function performSearch() {
        const q = searchInput.value.trim();
        if (q.length < 2) {
            resultsContainer.style.display = 'none';
            return;
        }

        fetch("{{ route('client.search') }}?q=" + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                resultsContainer.innerHTML = '';

                if (!data.length) {
                    resultsContainer.innerHTML = '<div class="list-group-item text-muted">Ничего не найдено</div>';
                } else {
                    data.forEach(user => {
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        a.innerHTML = `
                            <strong>${escapeHtml(user.orgname || '')}</strong> |
                            ${escapeHtml(user.name2 || '')} ${escapeHtml(user.name || '')} ${escapeHtml(user.secondname || '')}
                            <br>
                            <small>${escapeHtml(user.phone || '')} | ${user.region ? escapeHtml(user.region) + ' | ' : ''}${escapeHtml(user.city || '')}${user.poshta ? ' | ' + escapeHtml(user.poshta) : ''}</small>
                        `;

                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            const selectedLabel = [
                                user.orgname || '',
                                user.secondname || '',
                                user.name || ''
                            ].filter(Boolean).join(' ').trim();

                            counterpartyId.value = user.id;
                            counterpartyName.value = selectedLabel;
                            counterpartyDetails.className = 'alert alert-secondary py-2 mt-2';
                            counterpartyDetails.style.border = '1px solid var(--border)';
                            counterpartyDetails.innerHTML = a.innerHTML;
                            resultsContainer.style.display = 'none';
                            searchInput.value = selectedLabel;
                        });

                        resultsContainer.appendChild(a);
                    });
                }

                resultsContainer.style.display = 'block';
            });
    }

    searchBtn.addEventListener('click', performSearch);

    let debounceTimer = null;
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 300);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target) && !searchBtn.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
});
</script>
@endpush
