@php($isEdit = !empty($subscription))
<form method="POST" action="{{ $isEdit ? route('subscriptions.update', ['subscription' => $subscription->id]) : route('subscriptions.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="subscription-grid">
        <label>Клиент
            <select name="client_id" class="form-select" required>
                <option value="">Выберите клиента</option>
                @foreach($clients as $client)
                    @php($clientName = trim($client->orgname ?: trim(($client->secondname ?? '') . ' ' . ($client->name ?? ''))) ?: ($client->email ?: 'Клиент #' . $client->id))
                    <option value="{{ $client->id }}" {{ (string) old('client_id', $subscription->client_id ?? '') === (string) $client->id ? 'selected' : '' }}>{{ $clientName }}</option>
                @endforeach
            </select>
        </label>
        <label>Тариф
            <select name="plan_id" class="form-select" required>
                <option value="">Выберите тариф</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ (string) old('plan_id', $subscription->plan_id ?? '') === (string) $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Статус
            <select name="status" class="form-select">
                @foreach(['active' => 'Активна', 'paused' => 'Пауза', 'cancelled' => 'Отменена', 'expired' => 'Истекла', 'blocked' => 'Заблокирована'] as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $subscription->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Способ оплаты<input name="payment_method" class="form-control" value="{{ old('payment_method', $subscription->payment_method ?? '') }}"></label>
        <label>Старт<input name="starts_at" class="form-control" type="date" value="{{ old('starts_at', $subscription->starts_at ?? now()->toDateString()) }}"></label>
        <label>Следующее начисление<input name="next_billing_at" class="form-control" type="date" value="{{ old('next_billing_at', $subscription->next_billing_at ?? now()->toDateString()) }}"></label>
        <label>Окончание<input name="ends_at" class="form-control" type="date" value="{{ old('ends_at', $subscription->ends_at ?? '') }}"></label>
        <label class="form-check"><span><input type="checkbox" name="auto_create_invoice" value="1" {{ old('auto_create_invoice', $subscription->auto_create_invoice ?? true) ? 'checked' : '' }}> Автона начисление</span></label>
        <label class="span-2">Заметки<textarea name="notes" class="form-control" rows="2">{{ old('notes', $subscription->notes ?? '') }}</textarea></label>
    </div>
    <button class="btn btn-success mt-3">{{ $isEdit ? 'Сохранить подписку' : 'Создать подписку' }}</button>
</form>
