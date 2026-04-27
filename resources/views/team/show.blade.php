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
        <form method="POST" action="{{ route('team.save') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{ $member->id ?? '0' }}">
            @php
                $photoPreview = \App\Support\MediaUrl::image(old('foto1', $member->foto1 ?? ''));
            @endphp

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
                    <label class="form-label">Підрозділ</label>
                    @php
                        $selectedDepartmentRaw = old('status', $member->status ?? '');
                        $selectedDepartment = is_numeric($selectedDepartmentRaw)
                            ? (string) (int) $selectedDepartmentRaw
                            : trim((string) $selectedDepartmentRaw);
                        $departments = [
                            '1' => 'Администрация',
                            '2' => 'Финансы',
                            '3' => 'Продажи',
                            '4' => 'Разработка',
                            '5' => 'Маркетинг',
                        ];
                    @endphp
                    <select name="status" id="teamDepartmentSelect" class="form-control" data-current-value="{{ $selectedDepartment }}">
                        <option value="" {{ $selectedDepartment === '' ? 'selected' : '' }}>— Оберіть підрозділ —</option>
                        @foreach($departments as $value => $label)
                            <option value="{{ $value }}" {{ $selectedDepartment === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $member->email ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Новый пароль</label>
                    <input type="password" name="pass" class="form-control" value="">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Телефон</label>
                    <input type="tel" name="phone" id="team-phone-input" class="form-control" value="{{ old('phone', $member->phone ?? '') }}" placeholder="+38 (0XX) XXX-XX-XX" maxlength="19">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Город</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $member->city ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Регион</label>
                    <input type="text" name="region" class="form-control" value="{{ old('region', $member->region ?? '') }}">
                </div>
            </div>

            <div class="row mb-3 align-items-start">
                <div class="col-md-7">
                    <label class="form-label">Фото</label>
                    <input type="hidden" name="foto1" value="{{ old('foto1', $member->foto1 ?? '') }}">
                    <input type="file" name="foto1_file" id="team-photo-input" class="form-control" accept="image/*">
                    <div class="form-text">Завантажте зображення для профілю.</div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Предпросмотр фото</label>
                    <div id="team-photo-preview-wrap" style="width:100%;max-width:220px;min-height:140px;border:1px solid rgba(255,255,255,.12);border-radius:16px;background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        @if($photoPreview)
                            <img id="team-photo-preview" src="{{ $photoPreview }}" alt="Фото учасника" style="width:100%;height:140px;object-fit:cover;">
                        @else
                            <div id="team-photo-preview-empty" style="padding:16px;color:var(--muted-foreground);text-align:center;">Фото не завантажено</div>
                            <img id="team-photo-preview" src="" alt="Фото учасника" style="display:none;width:100%;height:140px;object-fit:cover;">
                        @endif
                    </div>
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
    const departmentSelect = document.getElementById('teamDepartmentSelect');
    if (departmentSelect) {
        const currentDepartment = (departmentSelect.dataset.currentValue || '').trim();
        departmentSelect.value = currentDepartment;

        if (departmentSelect.value !== currentDepartment) {
            departmentSelect.value = '';
        }
    }

    const phoneInput = document.getElementById('team-phone-input');

    function formatPhone(value) {
        let digits = value.replace(/\D/g, '');

        if (digits.startsWith('380') && digits.length > 3) {
            digits = digits.slice(0, 12);
        } else if (digits.startsWith('0') && digits.length > 0) {
            digits = `38${digits}`.slice(0, 12);
        } else if (digits.startsWith('38') && digits.length > 2) {
            digits = digits.slice(0, 12);
        } else if (digits.length === 0) {
            digits = '38';
        } else {
            digits = `38${digits}`.slice(0, 12);
        }

        const local = digits.slice(2);
        let formatted = '+38';
        if (local.length > 0) {
            formatted += ` (${local.slice(0, 3)}`;
            if (local.length >= 3) formatted += ')';
            if (local.length > 3) formatted += ` ${local.slice(3, 6)}`;
            if (local.length > 6) formatted += `-${local.slice(6, 8)}`;
            if (local.length > 8) formatted += `-${local.slice(8, 10)}`;
        }

        return formatted;
    }

    if (phoneInput) {
        phoneInput.value = formatPhone(phoneInput.value || '');

        phoneInput.addEventListener('input', function () {
            phoneInput.value = formatPhone(phoneInput.value);
        });

        phoneInput.addEventListener('focus', function () {
            if (!phoneInput.value.trim()) {
                phoneInput.value = '+38';
            }
        });
    }

    const photoInput = document.getElementById('team-photo-input');
    const photoPreview = document.getElementById('team-photo-preview');
    const photoPreviewWrap = document.getElementById('team-photo-preview-wrap');
    const photoPreviewEmpty = document.getElementById('team-photo-preview-empty');

    if (photoInput && photoPreview && photoPreviewWrap) {
        photoInput.addEventListener('change', function () {
            const file = photoInput.files && photoInput.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                photoPreview.src = event.target?.result || '';
                photoPreview.style.display = 'block';
                if (photoPreviewEmpty) {
                    photoPreviewEmpty.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        });
    }

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
