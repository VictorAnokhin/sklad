@php
    $inModal = $inModal ?? false;
    $selectedProjects = collect(old('project_ids', $selectedCompanyIds ?? []))->map(fn ($id) => (string) $id)->all();
    $photoPreview = \App\Support\MediaUrl::image(old('foto1', $member->foto1 ?? ''));
    $selectedDepartmentRaw = old('status', $member->status ?? '');
    $selectedDepartment = is_numeric($selectedDepartmentRaw) ? (string) (int) $selectedDepartmentRaw : '';
    $departments = [
        '1' => 'Администрация',
        '2' => 'Финансы',
        '3' => 'Продажи',
        '4' => 'Разработка',
        '5' => 'Маркетинг',
    ];
@endphp

<form method="POST" action="{{ route('team.save') }}" enctype="multipart/form-data" id="team-member-form">
    @csrf
    <input type="hidden" name="id" value="{{ $member->id ?? '0' }}">
    @if($inModal)
        <input type="hidden" name="return_to_team" value="1">
    @endif

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
            <label class="form-label">Подразделение</label>
            <select name="status" id="teamDepartmentSelect" class="form-control">
                <option value="">— Выберите подразделение —</option>
                @foreach($departments as $value => $label)
                    <option value="{{ $value }}" {{ $selectedDepartment === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label d-block">Компании, в которых пользователь будет сотрудником</label>
        <div class="d-flex flex-wrap gap-3 rounded border p-3">
            @foreach($companyOptions as $company)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="project_ids[]" value="{{ $company->id }}" id="team-project-{{ $company->id }}" {{ in_array((string) $company->id, $selectedProjects, true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="team-project-{{ $company->id }}">{{ $company->name }}</label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $member->email ?? '') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Новый пароль</label>
            <input type="password" name="pass" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Телефон</label>
            <input type="text" name="phone" id="team-phone-input" class="form-control" value="{{ old('phone', $member->phone ?? '') }}" placeholder="+38 (000) 00-00-000" maxlength="19" inputmode="tel">
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
        </div>
        <div class="col-md-5">
            <div id="team-photo-preview-wrap" class="team-photo-preview">
                <div id="team-photo-preview-empty" @if($photoPreview) hidden @endif>Фото не загружено</div>
                <img id="team-photo-preview" src="{{ $photoPreview ?: '' }}" alt="Фото сотрудника" @if(!$photoPreview) hidden @endif>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Описание</label>
        <textarea name="description" class="form-control" rows="5" placeholder="Краткая информация о сотруднике">{{ old('description', $member->description ?? '') }}</textarea>
    </div>

    <div class="d-flex gap-3 mt-4">
        <button type="submit" class="btn btn-success">Сохранить</button>
        @if($inModal)
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
        @else
            <a href="{{ route('team') }}" class="btn btn-outline-secondary">Назад</a>
        @endif
        @if($member && !empty($member->id))
            <button type="submit" class="btn btn-outline-danger ms-auto" formaction="{{ route('team.destroy') }}" formmethod="POST" formnovalidate onclick="return confirm('Удалить сотрудника из текущей компании? Пользователь останется в системе.');">Удалить из команды</button>
        @endif
    </div>
</form>

@once
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const phoneInput = document.getElementById('team-phone-input');
            const photoInput = document.getElementById('team-photo-input');
            const photoPreview = document.getElementById('team-photo-preview');
            const photoPreviewEmpty = document.getElementById('team-photo-preview-empty');

            function formatPhone(value) {
                const digits = String(value || '').replace(/\D/g, '').slice(0, 12);
                if (digits.length === 0) {
                    return '';
                }

                if (digits.length <= 3) {
                    return `+${digits}`;
                }

                if (digits.length <= 5) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3)}`;
                }

                if (digits.length <= 8) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5)}`;
                }

                if (digits.length <= 10) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8)}`;
                }

                return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8, 10)}-${digits.slice(10)}`;
            }

            if (phoneInput) {
                if (phoneInput.value.trim()) phoneInput.value = formatPhone(phoneInput.value);
                phoneInput.addEventListener('input', () => { phoneInput.value = formatPhone(phoneInput.value); });
            }

            photoInput?.addEventListener('change', function () {
                const file = photoInput.files?.[0];
                if (!file || !photoPreview) return;
                const reader = new FileReader();
                reader.onload = event => {
                    photoPreview.src = event.target?.result || '';
                    photoPreview.hidden = false;
                    if (photoPreviewEmpty) photoPreviewEmpty.hidden = true;
                };
                reader.readAsDataURL(file);
            });
        });
        </script>
    @endpush
@endonce
