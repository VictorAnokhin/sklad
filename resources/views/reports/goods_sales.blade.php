<div class="card shadow-sm mt-4 bg-transparent border-secondary">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h4 class="card-title mb-0 text-light">Продажи товаров по проведенным RN</h4>
            <div class="text-muted small">{{ $monthLabel }}</div>
        </div>

        @if(($salesByGoods ?? collect())->isEmpty())
        <div class="text-muted">За выбранный период проведенных RN с товарами не найдено.</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                <thead class="table-dark">
                    <tr>
                        <th>Товар</th>
                        <th>Код</th>
                        <th class="text-end">К-ть продаж</th>
                        <th class="text-end">Документов RN</th>
                        <th class="text-end">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesByGoods as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->pnum }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $item->sold_qty, 3, '.', ' ') }}</td>
                        <td class="text-end">{{ (int) $item->documents_count }}</td>
                        <td class="text-end">{{ number_format((float) $item->sold_sum, 2, '.', ' ') }} грн</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
