@php
    $isEdit = !empty($plan);
    $accessesMap = $plan->accesses_map ?? [];
@endphp
<form method="POST" action="{{ $isEdit ? route('subscriptions.plans.update', ['plan' => $plan->id]) : route('subscriptions.plans.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="subscription-grid">
        <label class="span-2">Название<input name="name" class="form-control" value="{{ old('name', $plan->name ?? '') }}" required maxlength="160"></label>
        <label>Порядок<input name="sort_order" class="form-control" type="number" value="{{ old('sort_order', $plan->sort_order ?? 100) }}" min="0" max="999999"></label>
        <label>Подпись<input name="subtitle" class="form-control" value="{{ old('subtitle', $plan->subtitle ?? '') }}" maxlength="255"></label>
        <label class="span-4">Описание<textarea name="description" class="form-control" rows="3">{{ old('description', $plan->description ?? '') }}</textarea></label>
        <label>Цена<input name="price" class="form-control" type="number" step="0.01" value="{{ old('price', $plan->price ?? 0) }}" min="0"></label>
        <label>Валюта<input name="currency" class="form-control" value="{{ old('currency', $plan->currency ?? 'UAH') }}" maxlength="10"></label>
        <label>Период
            <select name="billing_period" class="form-select">
                @foreach(['week' => 'Неделя', 'month' => 'Месяц', 'quarter' => 'Квартал', 'year' => 'Год'] as $value => $label)
                    <option value="{{ $value }}" {{ old('billing_period', $plan->billing_period ?? 'month') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Интервал<input name="interval_count" class="form-control" type="number" value="{{ old('interval_count', $plan->interval_count ?? 1) }}" min="1" max="60"></label>
        <label>Дней на оплату<input name="payment_due_days" class="form-control" type="number" value="{{ old('payment_due_days', $plan->payment_due_days ?? 5) }}" min="0"></label>
        <label>Grace дней<input name="grace_days" class="form-control" type="number" value="{{ old('grace_days', $plan->grace_days ?? 3) }}" min="0"></label>
        <label class="form-check"><span><input type="checkbox" name="active" value="1" {{ old('active', $plan->active ?? true) ? 'checked' : '' }}> Активен</span></label>
        <label class="form-check"><span><input type="checkbox" name="block_on_overdue" value="1" {{ old('block_on_overdue', $plan->block_on_overdue ?? true) ? 'checked' : '' }}> Блокировать при неоплате</span></label>
    </div>

    <section class="subscription-accesses mt-4">
        <div class="subscription-card__head">
            <div>
                <h3>Доступы тарифа</h3>
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

    <button class="btn btn-success mt-3">{{ $isEdit ? 'Сохранить тариф' : 'Создать тариф' }}</button>
</form>
@if($isEdit)
    <form method="POST" action="{{ route('subscriptions.plans.destroy', ['plan' => $plan->id]) }}" class="mt-2">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger" onclick="return confirm('Удалить тариф?');">Удалить тариф</button>
    </form>
@endif
