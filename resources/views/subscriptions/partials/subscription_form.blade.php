@php
    $isEdit = !empty($subscription);
    $clientName = trim((string) ($subscription->client_name ?? ''));
    $clientDetails = trim(implode(' | ', array_filter([
        $subscription->client_phone ?? '',
        $subscription->client_region ?? '',
        $subscription->client_city ?? '',
        $subscription->client_poshta ?? '',
    ])));
    $accessesMap = $subscription->accesses_map ?? [];
@endphp
<form method="POST" action="{{ $isEdit ? route('subscriptions.update', ['subscription' => $subscription->id]) : route('subscriptions.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="subscription-grid">
        <label class="span-2">Клиент
            <div class="subscription-client-search" data-subscription-client-search>
                <input type="text" class="form-control" value="{{ old('client_search', $clientName) }}" placeholder="Поиск клиента..." autocomplete="off" data-client-search-input>
                <div class="list-group subscription-client-results d-none" data-client-search-results></div>
                <input type="hidden" name="client_id" value="{{ old('client_id', $subscription->client_id ?? '') }}" required data-client-id>
                <div class="alert {{ ($subscription->client_id ?? null) ? 'alert-secondary' : 'alert-warning' }} py-1 mt-1 mb-0 selected-client-details" data-client-details>
                    @if($subscription->client_id ?? null)
                        <strong>{{ $clientName }}</strong>
                        @if($clientDetails !== '')
                            <br><small>{{ $clientDetails }}</small>
                        @endif
                    @else
                        Клиент не выбран
                    @endif
                </div>
            </div>
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

    <section class="subscription-accesses mt-4">
        <div class="subscription-card__head">
            <div>
                <h3>Доступы</h3>
                <p>Включайте доступ к разделам и задавайте лимит. Значение 0 означает без ограничения.</p>
            </div>
        </div>

        <div class="subscription-accesses-grid">
            @foreach(($accessGroups ?? []) as $group)
                <section class="subscription-access-group">
                    <h4>{{ $group['label'] }}</h4>
                    @foreach($group['items'] as $accessKey => $label)
                        @php
                            $oldEnabled = old('accesses', null);
                            $enabled = is_array($oldEnabled)
                                ? in_array($accessKey, $oldEnabled, true)
                                : (bool) ($accessesMap[$accessKey]['enabled'] ?? false);
                            $limit = old("access_limits.{$accessKey}", $accessesMap[$accessKey]['limit'] ?? 0);
                        @endphp
                        <div class="subscription-access-row">
                            <label class="subscription-access-check">
                                <input
                                    type="checkbox"
                                    name="accesses[]"
                                    value="{{ $accessKey }}"
                                    {{ $enabled ? 'checked' : '' }}
                                >
                                <span>{{ $label }}</span>
                            </label>
                            <label class="subscription-access-limit">
                                <span>Лимит</span>
                                <input
                                    type="number"
                                    name="access_limits[{{ $accessKey }}]"
                                    class="form-control"
                                    value="{{ (int) $limit }}"
                                    min="0"
                                    max="999999999"
                                >
                            </label>
                        </div>
                    @endforeach
                </section>
            @endforeach
        </div>
    </section>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-success">{{ $isEdit ? 'Сохранить подписку' : 'Создать подписку' }}</button>
        @if($isEdit)
            <button
                class="btn btn-outline-danger"
                type="submit"
                form="subscription-delete-form-{{ $subscription->id }}"
                onclick="return confirm('Удалить подписку и связанные начисления?');"
            >Удалить</button>
        @endif
    </div>
</form>
@if($isEdit)
    <form method="POST" action="{{ route('subscriptions.destroy', ['subscription' => $subscription->id]) }}" id="subscription-delete-form-{{ $subscription->id }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
