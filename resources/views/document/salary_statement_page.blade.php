@extends('home')

@section('title', 'Платежная ведомость')

@section('header_actions')
    <a href="{{ route('document.index', ['doc' => 'ZV']) }}" class="btn btn-outline-light btn-sm">К списку ведомостей</a>
@endsection

@section('content')
<div class="salary-statement-page">
    <form id="salaryStatementForm" class="salary-statement-card glass-card">
        <div class="salary-statement-card__header">
            <div>
                <div class="text-muted small">Документ ZV</div>
                <h2 class="salary-statement-title" id="salaryStatementTitle">Новая платежная ведомость</h2>
            </div>
        </div>

        <input type="hidden" id="salaryStatementId">
        <input type="hidden" name="doc" value="ZV">

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-3">
                <label class="form-label" for="salaryStatementDate">Дата</label>
                <input type="date" class="form-control" id="salaryStatementDate" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="salaryStatementPaymentType">Вид платежа</label>
                <select class="form-select" id="salaryStatementPaymentType" required>
                    <option value="">— Выберите вид платежа —</option>
                    @foreach($salaryPaymentTypes as $paymentType)
                        <option value="{{ $paymentType->id }}">{{ $paymentType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label" for="salaryStatementContent">Примечание</label>
                <input type="text" class="form-control" id="salaryStatementContent" maxlength="65535">
            </div>
        </div>

        <div class="mb-3" id="salaryStatementDocumentsBlock" hidden>
            <div class="form-label">Связанные документы ZP</div>
            <div class="d-flex flex-wrap gap-2" id="salaryStatementDocuments"></div>
        </div>

        <div class="salary-statement-table-wrap">
            <table class="table table-hover align-middle salary-statement-table">
                <thead>
                    <tr>
                        <th class="salary-statement-col-employee">Сотрудник</th>
                        <th class="salary-statement-col-amount">Размер зарплаты</th>
                        <th class="salary-statement-col-doc">Документ ZP</th>
                        <th class="salary-statement-col-payout">Выплата</th>
                        <th class="text-end salary-statement-col-actions"></th>
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

        <div class="salary-statement-actions">
            <button type="button" class="btn btn-outline-danger" id="salaryStatementDelete" hidden>Удалить</button>
            <a href="{{ route('document.index', ['doc' => 'ZV']) }}" class="btn btn-secondary">К списку</a>
            <button type="submit" class="btn btn-success">Сохранить ведомость</button>
        </div>
    </form>
</div>

<div class="modal fade" id="salaryPayoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
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
@endsection

@push('scripts')
<style>
    .salary-statement-page {
        padding-bottom: 24px;
    }

    .salary-statement-card {
        padding: 20px;
        border-radius: 14px;
    }

    .salary-statement-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .salary-statement-title {
        margin: 0;
        color: #fff;
        font-size: 1.5rem;
        line-height: 1.25;
    }

    .salary-statement-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 10px;
    }

    .salary-statement-table {
        min-width: 760px;
        margin-bottom: 0;
    }

    .salary-statement-col-employee {
        width: 45%;
    }

    .salary-statement-col-amount {
        width: 170px;
    }

    .salary-statement-col-doc {
        width: 120px;
    }

    .salary-statement-col-payout {
        width: 86px;
    }

    .salary-statement-col-actions {
        width: 70px;
    }

    .salary-statement-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    #salaryStatementDelete {
        margin-right: auto;
    }

    @media (max-width: 768px) {
        .salary-statement-card {
            padding: 10px;
            border-radius: 10px;
        }

        .salary-statement-title {
            font-size: 1.15rem;
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
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 12px;
            background: rgba(15, 23, 42, .82);
        }

        .salary-statement-table td {
            padding: 8px 0;
            border: 0;
        }

        .salary-statement-table tbody td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 5px;
            color: #94a3b8;
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .salary-statement-table td.salary-statement-row-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 4px;
        }

        .salary-statement-table td.salary-statement-row-actions::before {
            display: none;
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

        .salary-statement-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .salary-statement-actions .btn {
            width: 100%;
        }

        #salaryStatementDelete {
            margin-right: 0;
        }

        #salaryPayoutModal .modal-dialog {
            margin: .5rem;
        }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = @json(csrf_token());
    const initialEmployees = @json($salaryEmployees);
    const initialStatementId = @json((int) $initialStatementId);
    const salaryStatementsBaseUrl = @json(route('document.salaryStatements.store'));
    const salaryStatementShowUrl = @json(route('document.salaryStatements.show', ['id' => '__ID__']));
    const listUrl = @json(route('document.index', ['doc' => 'ZV']));
    const payoutModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('salaryPayoutModal'));
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

    if (initialStatementId > 0) {
        loadStatement(initialStatementId).catch((error) => {
            showError(statementError, error.message || 'Не удалось открыть ведомость.');
        });
    } else {
        initNewStatement();
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

    function initNewStatement() {
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
    }

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
        const wasNew = !statement;
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
        if (wasNew && data.statement?.id) {
            window.history.replaceState({}, '', salaryStatementShowUrl.replace('__ID__', data.statement.id));
        }
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

        window.location.href = listUrl;
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
        payoutModal.show();
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
                    <div class="fw-semibold">${escapeHtml(line.employee_name || '')}</div>
                    <div class="small text-muted">${escapeHtml(line.email || '')}</div>
                </td>
                <td data-label="Размер зарплаты">
                    <div class="input-group">
                        <input type="number" min="0" step="0.01" class="form-control"
                            data-salary-input data-employee-id="${line.employee_id}"
                            value="${Number(line.salary_amount || 0).toFixed(2)}" ${paid ? 'disabled' : ''}>
                        <span class="input-group-text">грн</span>
                    </div>
                </td>
                <td data-label="Документ ZP">
                    ${paid
                        ? `<a href="${escapeHtml(line.zp_url || '#')}" class="btn btn-sm ${Number(line.zp_posted) === 1 ? 'btn-outline-success' : 'btn-outline-warning'}">ZP №${escapeHtml(line.zp_num || '')}</a>`
                        : '<span class="text-muted">—</span>'}
                </td>
                <td data-label="Выплата">
                    <button type="button" class="btn btn-sm btn-success" data-payout-line
                        data-employee-id="${line.employee_id}" ${!statement || paid || Number(line.salary_amount) <= 0 ? 'disabled' : ''}>
                        Выпл.
                    </button>
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
