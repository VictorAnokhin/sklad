<div class="modal fade" id="modalTrafficLearning" tabindex="-1" aria-labelledby="modalTrafficLearningLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrafficLearningLabel">🚦 Правила дорожнього руху України</h5>
                <button type="button" class="btn btn-sm btn-primary ms-3" id="traffic-add">+ Додати</button>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><button class="nav-link active traffic-tab" type="button" data-type="rules">ПДР</button></li>
                    <li class="nav-item"><button class="nav-link traffic-tab" type="button" data-type="signs">Знаки</button></li>
                </ul>

                <div id="traffic-form-wrap" class="card border-0 bg-light mb-4" style="display:none">
                    <div class="card-body">
                        <form id="traffic-form">
                            <input type="hidden" id="traffic-id">
                            <div class="row g-3" id="traffic-fields"></div>
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-success" type="submit">💾 Зберегти</button>
                                <button class="btn btn-secondary" type="button" id="traffic-cancel">Скасувати</button>
                                <button class="btn btn-outline-danger ms-auto" type="button" id="traffic-delete" style="display:none">Видалити</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="traffic-loading" class="text-center text-muted py-5">Завантаження…</div>
                <div id="traffic-list" class="row g-3"></div>
            </div>
        </div>
    </div>
</div>

<style>
    #modalTrafficLearning .traffic-admin-card { height: 100%; border: 1px solid rgba(148,163,184,.25); cursor: pointer; }
    #modalTrafficLearning .traffic-admin-card:hover { border-color: #f59e0b; }
    #modalTrafficLearning .traffic-sign-image { width: 92px; height: 92px; object-fit: contain; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalTrafficLearning');
    if (!modal) return;

    const base = @json(route('settings.trafficLearning.index'));
    const fid = @json((int) ($fid ?? 2));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const list = document.getElementById('traffic-list');
    const loading = document.getElementById('traffic-loading');
    const formWrap = document.getElementById('traffic-form-wrap');
    const form = document.getElementById('traffic-form');
    const fields = document.getElementById('traffic-fields');
    const idInput = document.getElementById('traffic-id');
    const deleteButton = document.getElementById('traffic-delete');
    let type = 'rules';
    let records = { rules: [], signs: [] };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);

    function input(name, label, value = '', options = {}) {
        const col = options.full ? 'col-12' : 'col-md-6';
        const control = options.textarea
            ? `<textarea class="form-control" name="${name}" rows="${options.rows || 3}" ${options.required ? 'required' : ''}>${escapeHtml(value)}</textarea>`
            : `<input class="form-control" name="${name}" type="${options.type || 'text'}" value="${escapeHtml(value)}" ${options.required ? 'required' : ''}>`;
        return `<div class="${col}"><label class="form-label">${label}</label>${control}</div>`;
    }

    function showForm(item = null) {
        idInput.value = item?.id || '';
        deleteButton.style.display = item ? '' : 'none';
        fields.innerHTML = type === 'rules'
            ? input('section_number', 'Розділ', item?.section_number, { required: true })
                + input('title', 'Назва', item?.title, { required: true })
                + input('summary', 'Короткий опис', item?.summary, { textarea: true, full: true, required: true })
                + input('content', 'Текст правила', item?.content, { textarea: true, rows: 5, full: true })
            : input('code', 'Номер знака', item?.code, { required: true })
                + input('title', 'Назва', item?.title, { required: true })
                + input('category', 'Категорія', item?.category)
                + input('image_url', 'URL зображення', item?.image_url, { required: true })
                + input('description', 'Опис', item?.description, { textarea: true, full: true });
        fields.insertAdjacentHTML('beforeend',
            input('sort_order', 'Порядок', item?.sort_order ?? 0, { type: 'number' })
            + `<div class="col-md-6 d-flex align-items-end pb-2"><div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_published" id="traffic-published" ${item?.is_published !== false ? 'checked' : ''}>
                <label class="form-check-label" for="traffic-published">Опубліковано</label>
              </div></div>`
        );
        formWrap.style.display = '';
        formWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideForm() {
        form.reset();
        idInput.value = '';
        formWrap.style.display = 'none';
    }

    function render() {
        list.innerHTML = records[type].map(item => `
            <div class="col-md-6 col-lg-4">
                <div class="card traffic-admin-card" data-id="${item.id}">
                    <div class="card-body">
                        ${type === 'signs' ? `<img class="traffic-sign-image float-end ms-3" src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}">` : ''}
                        <div class="small text-warning fw-bold mb-1">${escapeHtml(type === 'rules' ? `Розділ ${item.section_number}` : `${item.category || 'Знак'} · ${item.code}`)}</div>
                        <h6>${escapeHtml(item.title)}</h6>
                        <p class="small text-muted mb-2">${escapeHtml(type === 'rules' ? item.summary : item.description)}</p>
                        <span class="badge ${item.is_published ? 'bg-success' : 'bg-secondary'}">${item.is_published ? 'Опубліковано' : 'Приховано'}</span>
                    </div>
                </div>
            </div>`).join('') || '<p class="text-muted">Записів ще немає.</p>';
        list.querySelectorAll('[data-id]').forEach(card => card.addEventListener('click', () => {
            showForm(records[type].find(item => item.id === Number(card.dataset.id)));
        }));
    }

    async function load() {
        loading.style.display = '';
        list.innerHTML = '';
        try {
            const response = await fetch(`${base}?fid=${fid}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Не вдалося завантажити дані');
            records = await response.json();
            render();
        } catch (error) {
            list.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        } finally {
            loading.style.display = 'none';
        }
    }

    modal.addEventListener('show.bs.modal', load);
    document.getElementById('traffic-add').addEventListener('click', () => showForm());
    document.getElementById('traffic-cancel').addEventListener('click', hideForm);
    document.querySelectorAll('.traffic-tab').forEach(tab => tab.addEventListener('click', function () {
        type = this.dataset.type;
        document.querySelectorAll('.traffic-tab').forEach(item => item.classList.toggle('active', item === this));
        hideForm();
        render();
    }));

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const id = idInput.value;
        const data = Object.fromEntries(new FormData(form).entries());
        data.fid = fid;
        data.sort_order = Number(data.sort_order || 0);
        data.is_published = form.elements.is_published.checked;
        const response = await fetch(id ? `${base}/${type}/${id}` : `${base}/${type}`, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(data)
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            alert(payload.message || 'Не вдалося зберегти');
            return;
        }
        hideForm();
        await load();
    });

    deleteButton.addEventListener('click', async function () {
        if (!idInput.value || !confirm('Видалити картку?')) return;
        const response = await fetch(`${base}/${type}/${idInput.value}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }
        });
        if (!response.ok) {
            alert('Не вдалося видалити');
            return;
        }
        hideForm();
        await load();
    });
});
</script>
