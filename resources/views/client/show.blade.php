@extends('home')

@section('title')
{{ $client ? __('client.edit_title') : __('client.create_title') }}
@endsection

@section('content')
<div class="container mt-4">
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="glass-card" style="max-width: 900px;">
        <form method="POST" action="{{ route('client.save') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $client->id ?? '0' }}">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('client.field_organization') }}</label>
                    <input type="text" name="orgname" class="form-control" value="{{ $client->orgname ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_edrpou') }}</label>
                    <input type="text" name="kod1" class="form-control" value="{{ $client->kod1 ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_contact') }}</label>
                    <input type="text" name="name2" class="form-control" value="{{ $client->name2 ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_lastname') }}</label>
                    <input type="text" name="secondname" class="form-control" value="{{ $client->secondname ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_firstname') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ $client->name ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_middlename') }}</label>
                    <input type="text" name="fathername" class="form-control" value="{{ $client->fathername ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_phone') }}</label>
                    <input type="tel" name="phone" id="phone-input" class="form-control phone-input" value="{{ $client->phone ?? '' }}" placeholder="+38 (0XX) XXX-XX-XX" maxlength="19">
                    <div class="invalid-feedback" id="phone-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_phone2') }}</label>
                    <input type="tel" name="phone1" id="phone1-input" class="form-control phone-input" value="{{ $client->phone1 ?? '' }}" placeholder="+38 (0XX) XXX-XX-XX" maxlength="19">
                    <div class="invalid-feedback" id="phone1-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_birthday') }}</label>
                    <input type="text" name="hbd" class="form-control" value="{{ $client->hbd ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_email') }}</label>
                    <input type="email" name="email" id="email-input" class="form-control" value="{{ $client->email ?? '' }}" required>
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_city') }}</label>
                    <input type="text" name="city" class="form-control" value="{{ $client->city ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_region') }}</label>
                    <input type="text" name="region" class="form-control" value="{{ $client->region ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_nova_poshta') }}</label>
                    <input type="text" name="poshta" class="form-control" value="{{ $client->poshta ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('client.field_password') }}</label>
                    <input type="password" name="pass" class="form-control" value="" placeholder="{{ $client ? __('client.field_password_hint') : '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_status') }}</label>
                    <select name="idstatus" class="form-select">
                        @foreach($statuses as $s)
                            <option value="{{ $s->id }}" {{ (string)($client->idstatus ?? $client->ustype ?? '') === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_rating') }}</label>
                    <input type="number" name="top" class="form-control" value="{{ $client->top ?? 1 }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('client.field_bonus') }}</label>
                    <input type="number" step="0.01" name="bonus" class="form-control" value="{{ $client->bonus ?? 0 }}">
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-success" style="min-width: 120px;">
                    <i class="fas fa-save me-1"></i> {{ __('client.btn_save') }}
                </button>
                <a href="{{ route('client.index') }}" class="btn btn-outline-secondary" style="min-width: 120px;">
                    ← {{ __('client.btn_back') }}
                </a>
                @if($client && !empty($client->id))
                <button
                    type="submit"
                    class="btn btn-outline-danger ms-auto"
                    formaction="{{ route('client.destroy') }}"
                    formmethod="POST"
                    formnovalidate
                    onclick="return confirm('{{ __('client.confirm_delete') }}');"
                >
                    🗑 {{ __('client.btn_delete') }}
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const checkEmailUrl = "{{ route('client.checkEmail') }}";
    const clientId = "{{ $client->id ?? '0' }}";

    // Phone formatting: +38 (0XX) XXX-XX-XX
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

    // Normalize phone to +380XXXXXXXXX
    function normalizePhone(value) {
        const digits = value.replace(/\D/g, '');
        if (digits.startsWith('38') && digits.length === 12) {
            return `+${digits}`;
        }
        const padded = digits.startsWith('38') ? digits.slice(0, 12) : `38${digits}`.slice(0, 12);
        return `+${padded.padEnd(12, '0')}`;
    }

    // Validate Ukrainian phone: +38XXXXXXXXXX (12 digits after +)
    function isValidPhone(value) {
        if (!value || value === '+38' || value === '+38 ()') return true; // empty is ok
        const normalized = normalizePhone(value);
        return /^\+38\d{10}$/.test(normalized);
    }

    // Validate email format
    function isValidEmail(value) {
        if (!value.trim()) return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    // Async email uniqueness check
    let emailCheckTimeout = null;
    let emailCheckPromise = null;

    function checkEmailUniqueness(email) {
        if (emailCheckTimeout) {
            clearTimeout(emailCheckTimeout);
        }

        return new Promise(function(resolve) {
            emailCheckTimeout = setTimeout(function() {
                emailCheckPromise = fetch(checkEmailUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                       document.querySelector('input[name="_token"]')?.value || ''
                    },
                    body: JSON.stringify({ email: email, client_id: clientId })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    resolve(data);
                })
                .catch(function(err) {
                    console.error('Email check error:', err);
                    resolve({ valid: true, message: '' }); // allow on error
                });
            }, 500); // debounce 500ms
        });
    }

    // Apply formatting to phone inputs
    document.querySelectorAll('.phone-input').forEach(function(input) {
        // Format existing value on page load
        if (input.value) {
            input.value = formatPhone(input.value);
        }

        input.addEventListener('input', function(e) {
            const cursorPos = this.selectionStart;
            const oldLength = this.value.length;
            this.value = formatPhone(this.value);
            const newLength = this.value.length;
            // Try to maintain cursor position
            if (cursorPos !== null) {
                const newPos = cursorPos + (newLength - oldLength);
                this.setSelectionRange(newPos, newPos);
            }
            // Clear error on input
            this.classList.remove('is-invalid');
            const errorEl = document.getElementById(this.id.replace('-input', '-error'));
            if (errorEl) errorEl.textContent = '';
        });
    });

    // Email async validation
    const emailInput = document.getElementById('email-input');
    const emailError = document.getElementById('email-error');
    let emailIsValid = false;

    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();

            if (!email) {
                this.classList.remove('is-invalid', 'is-valid');
                if (emailError) emailError.textContent = '';
                emailIsValid = false;
                return;
            }

            if (!isValidEmail(email)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                if (emailError) emailError.textContent = 'Введіть коректну email адресу';
                emailIsValid = false;
                return;
            }

            // Check uniqueness async
            this.classList.remove('is-invalid', 'is-valid');
            if (emailError) emailError.textContent = 'Перевірка...';

            checkEmailUniqueness(email).then(function(result) {
                if (result.valid) {
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    if (emailError) emailError.textContent = '';
                    emailIsValid = true;
                } else {
                    emailInput.classList.add('is-invalid');
                    emailInput.classList.remove('is-valid');
                    if (emailError) emailError.textContent = result.message;
                    emailIsValid = false;
                }
            });
        });

        emailInput.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
            if (emailError) emailError.textContent = '';
            emailIsValid = false;
        });
    }

    // Form validation on submit
    const form = document.querySelector('form[action*="client.save"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;

            // Validate phone
            const phoneInput = document.getElementById('phone-input');
            if (phoneInput && phoneInput.value && !isValidPhone(phoneInput.value)) {
                phoneInput.classList.add('is-invalid');
                const errorEl = document.getElementById('phone-error');
                if (errorEl) errorEl.textContent = 'Введіть коректний номер телефону (наприклад: +380671234567)';
                hasErrors = true;
            }

            // Validate phone2
            const phone1Input = document.getElementById('phone1-input');
            if (phone1Input && phone1Input.value && !isValidPhone(phone1Input.value)) {
                phone1Input.classList.add('is-invalid');
                const errorEl = document.getElementById('phone1-error');
                if (errorEl) errorEl.textContent = 'Введіть коректний номер телефону';
                hasErrors = true;
            }

            // Validate email format
            const emailInput = document.getElementById('email-input');
            if (emailInput && !isValidEmail(emailInput.value)) {
                emailInput.classList.add('is-invalid');
                const errorEl = document.getElementById('email-error');
                if (errorEl) errorEl.textContent = 'Введіть коректну email адресу';
                hasErrors = true;
            }

            // Check if email was validated async
            if (emailInput && emailInput.value && !emailIsValid) {
                // Trigger async check and prevent submit
                emailInput.dispatchEvent(new Event('blur'));
                e.preventDefault();
                return false;
            }

            if (hasErrors) {
                e.preventDefault();
                return false;
            }
        });
    }
})();
</script>
@endpush
@endsection
