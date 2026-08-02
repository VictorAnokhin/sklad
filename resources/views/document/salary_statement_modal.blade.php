<div class="modal fade" id="salaryStatementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="salaryStatementTitle">Платежная ведомость</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="salaryStatementForm">
                <div class="modal-body">
                    <input type="hidden" id="salaryStatementId">
                    <input type="hidden" name="doc" value="ZV">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label" for="salaryStatementDate">Дата</label>
                            <input type="date" class="form-control" id="salaryStatementDate" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="salaryStatementPaymentType">Вид платежа</label>
                            <select class="form-select" id="salaryStatementPaymentType" required>
                                <option value="">— Выберите вид платежа —</option>
                                @foreach($salaryPaymentTypes as $paymentType)
                                    <option value="{{ $paymentType->id }}">{{ $paymentType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="salaryStatementContent">Примечание</label>
                            <input type="text" class="form-control" id="salaryStatementContent" maxlength="65535">
                        </div>
                    </div>
                    <div class="mb-3" id="salaryStatementDocumentsBlock" hidden>
                        <div class="form-label">Связанные документы ZP</div>
                        <div class="d-flex flex-wrap gap-2" id="salaryStatementDocuments"></div>
                    </div>
                    <div class="table-responsive salary-statement-table-wrap">
                        <table class="table table-hover align-middle salary-statement-table">
                            <thead>
                                <tr>
                                    <th style="width:45%">Сотрудник</th>
                                    <th style="width:170px">Размер зарплаты</th>
                                    <th style="width:120px">Документ ZP</th>
                                    <th style="width:86px">Выплата</th>
                                    <th class="text-end" style="width:70px"></th>
                                </tr>
                            </thead>
                            <tbody id="salaryStatementRows"></tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td>Итого</td>
                                    <td id="salaryStatementTotal">0.00 грн</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="text-muted text-center py-3" id="salaryStatementEmpty" hidden>
                        В текущем проекте нет сотрудников.
                    </div>
                    <div class="alert alert-danger mt-3 mb-0" id="salaryStatementError" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto" id="salaryStatementDelete" hidden>Удалить</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-success">Сохранить ведомость</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="salaryPayoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title">Выдача зарплаты (ZP)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="salaryPayoutForm">
                <div class="modal-body">
                    <input type="hidden" name="doc" value="ZP">
                    <input type="hidden" id="salaryPayoutLineId">
                    <div class="mb-3">
                        <label class="form-label">Сотрудник</label>
                        <input type="text" class="form-control" id="salaryPayoutEmployee" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="salaryPayoutAmount">Сумма</label>
                        <input type="number" min="0.01" step="0.01" class="form-control" id="salaryPayoutAmount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="salaryPayoutDate">Дата</label>
                        <input type="date" class="form-control" id="salaryPayoutDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="salaryPayoutCashbox">Касса / банковский счет</label>
                        <select class="form-select" id="salaryPayoutCashbox" required>
                            <option value="">— Выберите счет —</option>
                            @foreach($salaryCashboxes as $cashbox)
                                <option value="{{ $cashbox->id }}">{{ $cashbox->name }} ({{ $cashbox->currency ?: 'UAH' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="salaryPayoutPaymentType">Вид платежа</label>
                        <select class="form-select" id="salaryPayoutPaymentType" required>
                            <option value="">— Выберите вид платежа —</option>
                            @foreach($salaryPaymentTypes as $paymentType)
                                <option value="{{ $paymentType->id }}">{{ $paymentType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="salaryPayoutContent">Примечание</label>
                        <textarea class="form-control" id="salaryPayoutContent" rows="2"></textarea>
                    </div>
                    <div class="alert alert-danger mb-0" id="salaryPayoutError" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Выдать и провести</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<style>
    @media (max-width: 768px) {
        #salaryStatementModal .modal-dialog {
            margin: .5rem;
        }

        #salaryStatementModal .modal-body {
            padding: .75rem;
        }

        #salaryStatementModal .modal-footer {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        #salaryStatementModal .modal-footer .btn {
            width: 100%;
        }

        #salaryStatementDelete {
            margin-right: 0 !important;
        }

        .salary-statement-table-wrap {
            overflow: visible;
        }

        .salary-statement-table,
        .salary-statement-table thead,
        .salary-statement-table tbody,
        .salary-statement-table tfoot,
        .salary-statement-table tr,
        .salary-statement-table td {
            display: block;
            width: 100%;
        }

        .salary-statement-table {
            min-width: 0;
            border-collapse: separate;
            border-spacing: 0 10px;
            font-size: .875rem;
        }

        .salary-statement-table thead {
            display: none;
        }

        .salary-statement-table tbody tr {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 12px;
            background: rgba(15, 23, 42, .82);
        }

        .salary-statement-table td {
            display: block;
            width: 100% !important;
            max-width: none !important;
            padding: 8px 0;
            border: 0;
        }

        .salary-statement-table tbody td:not(.salary-statement-row-actions) {
            display: grid !important;
            grid-template-columns: minmax(116px, 34%) minmax(0, 1fr);
            align-items: center;
            column-gap: 12px;
        }

        .salary-statement-table tbody td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 0;
            color: #94a3b8;
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .salary-statement-cell-value,
        .salary-statement-table .input-group {
            display: block;
            width: 100%;
            max-width: none;
            min-width: 0;
        }

        .salary-statement-table .input-group {
            display: flex;
            flex-wrap: nowrap;
        }

        .salary-statement-table .input-group .form-control,
        .salary-statement-table .salary-statement-amount-input {
            min-width: 0;
            width: 100% !important;
            max-width: none !important;
            flex: 1 1 auto !important;
        }

        .salary-statement-table .input-group .input-group-text {
            flex: 0 0 auto;
        }

        .salary-statement-table .btn,
        .salary-statement-table .salary-statement-full-action {
            display: block;
            width: 100% !important;
            max-width: none !important;
            white-space: normal;
        }

        .salary-statement-table td.salary-statement-row-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 4px;
        }

        .salary-statement-table td.salary-statement-row-actions::before {
            display: none;
        }

        .salary-statement-table td.salary-statement-row-actions .btn {
            width: 100% !important;
            max-width: 120px;
        }

        .salary-statement-table tfoot tr {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
            padding: 10px;
            border-radius: 12px;
            background: rgba(255, 193, 7, .12);
        }

        .salary-statement-table tfoot td {
            padding: 0;
        }

        .salary-statement-table tfoot td[colspan] {
            display: none;
        }

        .salary-statement-table .form-control,
        .salary-statement-table .input-group-text,
        .salary-statement-table .btn {
            font-size: .82rem;
            padding: .35rem .45rem;
        }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = @json(csrf_token());
    const initialEmployees = @json($salaryEmployees);
    const initialStatementId = @json((int) request()->query('statement_id', 0));
    const salaryStatementsBaseUrl = @json(route('document.salaryStatements.store'));
    const statementModalElement = document.getElementById('salaryStatementModal');
    const payoutModalElement = document.getElementById('salaryPayoutModal');
    const statementModal = bootstrap.Modal.getOrCreateInstance(statementModalElement);
    const payoutModal = bootstrap.Modal.getOrCreateInstance(payoutModalElement);
    const statementForm = document.getElementById('salaryStatementForm');
    const payoutForm = document.getElementById('salaryPayoutForm');
    const rowsElement = document.getElementById('salaryStatementRows');
    const emptyElement = document.getElementById('salaryStatementEmpty');
    const totalElement = document.getElementById('salaryStatementTotal');
    const documentsBlock = document.getElementById('salaryStatementDocumentsBlock');
    const documentsElement = document.getElementById('salaryStatementDocuments');
    const statementError = document.getElementById('salaryStatementError');
    const payoutError = document.getElementById('salaryPayoutError');
    const deleteStatementButton = document.getElementById('salaryStatementDelete');
    let statement = null;
    let lines = [];
    let reopenStatementAfterPayout = false;
    let listChanged = false;

    payoutModalElement.addEventListener('hidden.bs.modal', () => {
        if (reopenStatementAfterPayout) {
            reopenStatementAfterPayout = false;
            statementModal.show();
        }
    });
    statementModalElement.addEventListener('hidden.bs.modal', () => {
        if (!reopenStatementAfterPayout && listChanged) {
            window.location.reload();
        }
    });

    document.querySelector('[data-zv-create]')?.addEventListener('click', () => {
        statement = null;
        lines = initialEmployees.map((employee) => ({
            id: null,
            employee_id: Number(employee.id),
            employee_name: employee.display_name,
            email: employee.email || '',
            salary_amount: 0,
            zp_document_id: null,
        }));
        document.getElementById('salaryStatementId').value = '';
        document.getElementById('salaryStatementTitle').textContent = 'Новая платежная ведомость';
        document.getElementById('salaryStatementDate').value = localDate();
        document.getElementById('salaryStatementPaymentType').value = '';
        document.getElementById('salaryStatementContent').value = '';
        if (deleteStatementButton) {
            deleteStatementButton.hidden = true;
        }
        hideError(statementError);
        renderLinkedDocuments([]);
        renderLines();
        statementModal.show();
    });

    document.querySelectorAll('[data-zv-open]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                await loadStatement(link.dataset.statementId);
                statementModal.show();
            } catch (error) {
                window.alert(error.message || 'Не удалось открыть ведомость.');
            }
        });
    });

    if (initialStatementId > 0) {
        loadStatement(initialStatementId)
            .then(() => statementModal.show())
            .catch((error) => window.alert(error.message || 'Не удалось открыть ведомость.'));
    }

    rowsElement.addEventListener('input', (event) => {
        const input = event.target.closest('[data-salary-input]');
        if (!input) return;
        const line = lines.find((item) => String(item.employee_id) === input.dataset.employeeId);
        if (line) line.salary_amount = Number(input.value || 0);
        updateTotal();
    });

    rowsElement.addEventListener('click', async (event) => {
        const removeButton = event.target.closest('[data-remove-line]');
        const payoutButton = event.target.closest('[data-payout-line]');
        if (removeButton) {
            await removeLine(removeButton.dataset.employeeId);
        }
        if (payoutButton) {
            openPayout(payoutButton.dataset.employeeId);
        }
    });

    statementForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await saveStatement();
    });

    deleteStatementButton?.addEventListener('click', async () => {
        await deleteStatement();
    });

    payoutForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitPayout();
    });

    async function loadStatement(id) {
        hideError(statementError);
        const response = await fetch(`${salaryStatementsBaseUrl}/${id}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Не удалось открыть ведомость.');
        applyStatement(data);
    }

    function applyStatement(data) {
        statement = data;
        lines = (data.lines || []).map((line) => ({ ...line, salary_amount: Number(line.salary_amount || 0) }));
        document.getElementById('salaryStatementId').value = data.id;
        document.getElementById('salaryStatementTitle').textContent = `Платежная ведомость №${data.num}`;
        document.getElementById('salaryStatementDate').value = inputDate(data.data);
        document.getElementById('salaryStatementPaymentType').value = data.reestr || '';
        document.getElementById('salaryStatementContent').value = data.content || '';
        if (deleteStatementButton) {
            deleteStatementButton.hidden = false;
        }
        renderLinkedDocuments(data.zp_documents || []);
        renderLines();
    }

    function renderLinkedDocuments(documents) {
        documentsElement.innerHTML = '';
        documentsBlock.hidden = documents.length === 0;
        documents.forEach((salaryDocument) => {
            const link = document.createElement('a');
            link.href = salaryDocument.url;
            link.className = `btn btn-sm ${salaryDocument.posted ? 'btn-outline-success' : 'btn-outline-warning'}`;
            link.textContent = `Открыть ZP №${salaryDocument.num} · ${Number(salaryDocument.summa || 0).toFixed(2)} грн`;
            link.title = `${salaryDocument.employee_name || ''} · ${salaryDocument.data || ''}`;
            documentsElement.appendChild(link);
        });
    }

    async function saveStatement() {
        hideError(statementError);
        const payload = statementPayload();
        const url = statement ? `${salaryStatementsBaseUrl}/${statement.id}` : salaryStatementsBaseUrl;
        const response = await fetch(url, {
            method: statement ? 'PUT' : 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) {
            showError(statementError, validationMessage(data));
            return;
        }
        applyStatement(data.statement);
        listChanged = true;
    }

    async function deleteStatement() {
        if (!statement) return;
        hideError(statementError);
        if (!window.confirm(`Удалить платежную ведомость №${statement.num}?`)) {
            return;
        }

        const response = await fetch(`${salaryStatementsBaseUrl}/${statement.id}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
        });
        const data = await response.json();
        if (!response.ok) {
            showError(statementError, validationMessage(data));
            return;
        }

        listChanged = true;
        statement = null;
        statementModal.hide();
    }

    async function removeLine(employeeId) {
        const line = lines.find((item) => String(item.employee_id) === String(employeeId));
        if (!line || line.zp_document_id) return;
        if (statement && line.id) {
            const response = await fetch(`${salaryStatementsBaseUrl}/${statement.id}/employees/${line.id}`, {
                method: 'DELETE',
                headers: jsonHeaders(),
            });
            const data = await response.json();
            if (!response.ok) {
                showError(statementError, validationMessage(data));
                return;
            }
        }
        lines = lines.filter((item) => String(item.employee_id) !== String(employeeId));
        listChanged = Boolean(statement);
        renderLines();
    }

    function openPayout(employeeId) {
        const line = lines.find((item) => String(item.employee_id) === String(employeeId));
        if (!statement || !line || line.zp_document_id || Number(line.salary_amount) <= 0) return;
        document.getElementById('salaryPayoutLineId').value = line.id;
        document.getElementById('salaryPayoutEmployee').value = line.employee_name;
        document.getElementById('salaryPayoutAmount').value = Number(line.salary_amount).toFixed(2);
        document.getElementById('salaryPayoutDate').value = document.getElementById('salaryStatementDate').value || localDate();
        document.getElementById('salaryPayoutPaymentType').value =
            document.getElementById('salaryStatementPaymentType').value;
        document.getElementById('salaryPayoutContent').value = `Выплата по ведомости ZV №${statement.num}`;
        hideError(payoutError);
        reopenStatementAfterPayout = true;
        statementModalElement.addEventListener('hidden.bs.modal', () => payoutModal.show(), { once: true });
        statementModal.hide();
    }

    async function submitPayout() {
        hideError(payoutError);
        const lineId = document.getElementById('salaryPayoutLineId').value;
        const response = await fetch(`${salaryStatementsBaseUrl}/${statement.id}/employees/${lineId}/payout`, {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({
                salary_amount: Number(document.getElementById('salaryPayoutAmount').value || 0),
                data: document.getElementById('salaryPayoutDate').value,
                oplata: document.getElementById('salaryPayoutCashbox').value,
                reestr: document.getElementById('salaryPayoutPaymentType').value,
                content: document.getElementById('salaryPayoutContent').value,
            }),
        });
        const data = await response.json();
        if (!response.ok) {
            showError(payoutError, validationMessage(data));
            return;
        }
        applyStatement(data.statement);
        listChanged = true;
        payoutModal.hide();
    }

    function renderLines() {
        rowsElement.innerHTML = '';
        emptyElement.hidden = lines.length > 0;
        lines.forEach((line) => {
            const paid = Boolean(line.zp_document_id);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td data-label="Сотрудник">
                    <div class="salary-statement-cell-value">
                        <div class="fw-semibold">${escapeHtml(line.employee_name || '')}</div>
                        <div class="small text-muted">${escapeHtml(line.email || '')}</div>
                    </div>
                </td>
                <td data-label="Размер зарплаты">
                    <div class="salary-statement-cell-value">
                        <div class="input-group">
                            <input type="number" min="0" step="0.01" class="form-control salary-statement-amount-input"
                                data-salary-input data-employee-id="${line.employee_id}"
                                value="${Number(line.salary_amount || 0).toFixed(2)}" ${paid ? 'disabled' : ''}>
                            <span class="input-group-text">грн</span>
                        </div>
                    </div>
                </td>
                <td data-label="Документ ZP">
                    <div class="salary-statement-cell-value">
                        ${paid
                            ? `<a href="${escapeHtml(line.zp_url || '#')}" class="btn btn-sm salary-statement-full-action ${Number(line.zp_posted) === 1 ? 'btn-outline-success' : 'btn-outline-warning'}">ZP №${escapeHtml(line.zp_num || '')}</a>`
                            : '<span class="text-muted">—</span>'}
                    </div>
                </td>
                <td data-label="Выплата">
                    <div class="salary-statement-cell-value">
                        <button type="button" class="btn btn-sm btn-success salary-statement-full-action" data-payout-line
                            data-employee-id="${line.employee_id}" ${!statement || paid || Number(line.salary_amount) <= 0 ? 'disabled' : ''}>
                            Выпл.
                        </button>
                    </div>
                </td>
                <td class="text-end salary-statement-row-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-line
                        data-employee-id="${line.employee_id}" ${paid ? 'disabled' : ''} title="Удалить сотрудника">×</button>
                </td>
            `;
            rowsElement.appendChild(row);
        });
        updateTotal();
    }

    function updateTotal() {
        const total = lines.reduce((sum, line) => sum + Number(line.salary_amount || 0), 0);
        totalElement.textContent = `${total.toFixed(2)} грн`;
    }

    function statementPayload() {
        return {
            data: document.getElementById('salaryStatementDate').value,
            reestr: document.getElementById('salaryStatementPaymentType').value,
            content: document.getElementById('salaryStatementContent').value,
            employees: lines.map((line) => ({
                employee_id: line.employee_id,
                salary_amount: Number(line.salary_amount || 0),
            })),
        };
    }

    function jsonHeaders() {
        return {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        };
    }

    function validationMessage(data) {
        if (data.errors) return Object.values(data.errors).flat().join(' ');
        return data.message || 'Операция не выполнена.';
    }

    function showError(element, message) {
        element.textContent = message;
        element.hidden = false;
    }

    function hideError(element) {
        element.textContent = '';
        element.hidden = true;
    }

    function inputDate(value) {
        const match = String(value || '').match(/^(\d{2})-(\d{2})-(\d{4})$/);
        return match ? `${match[3]}-${match[2]}-${match[1]}` : String(value || '').slice(0, 10);
    }

    function localDate() {
        const date = new Date();
        const offset = date.getTimezoneOffset();
        return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
