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
                    <li class="nav-item"><button class="nav-link traffic-tab" type="button" data-type="tests">Тести</button></li>
                </ul>
                <p class="small text-muted">
                    Джерело каталогу:
                    <a href="https://pdr.infotech.gov.ua/" target="_blank" rel="noopener noreferrer">Офіційні тести ПДР України</a>.
                    Правильні відповіді демонстраційний API не розкриває — їх можна вказати під час редагування.
                </p>

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
    #modalTrafficLearning .traffic-answer-list { padding-left: 1.25rem; }
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
    let records = { rules: [], signs: [], tests: [] };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);

    function input(name, label, value = '', options = {}) {
        const col = options.full ? 'col-12' : 'col-md-6';
        const control = options.file
            ? `<input class="form-control" name="${name}" type="file" accept="${options.accept || 'image/*'}">`
            : options.textarea
            ? `<textarea class="form-control" name="${name}" rows="${options.rows || 3}" ${options.required ? 'required' : ''}>${escapeHtml(value)}</textarea>`
            : `<input class="form-control" name="${name}" type="${options.type || 'text'}" value="${escapeHtml(value)}" ${options.required ? 'required' : ''}>`;
        return `<div class="${col}"><label class="form-label">${label}</label>${control}</div>`;
    }

    function showForm(item = null) {
        idInput.value = item?.id || '';
        deleteButton.style.display = item ? '' : 'none';
        if (type === 'rules') {
            fields.innerHTML = input('section_number', 'Розділ', item?.section_number, { required: true })
                + input('title', 'Назва', item?.title, { required: true })
                + input('summary', 'Короткий опис', item?.summary, { textarea: true, full: true, required: true })
                + input('content', 'Текст правила', item?.content, { textarea: true, rows: 5, full: true });
        } else if (type === 'signs') {
            fields.innerHTML = input('code', 'Номер знака', item?.code, { required: true })
                + input('title', 'Назва', item?.title, { required: true })
                + input('category', 'Категорія', item?.category)
                + input('image_url', 'Поточний файл або URL', item?.image_url)
                + input('image_upload', 'Завантажити нове зображення', '', { file: true, accept: '.svg,.png,.jpg,.jpeg,.webp,image/*' })
                + (item?.image_url
                    ? `<div class="col-12"><img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}" class="traffic-sign-image"><div class="form-text">Поточне зображення</div></div>`
                    : '')
                + input('description', 'Опис', item?.description, { textarea: true, full: true });
        } else {
            fields.innerHTML = input('source_external_id', 'ID джерела', item?.source_external_id, { type: 'number' })
                + input('topic_external_id', 'ID теми', item?.topic_external_id, { type: 'number' })
                + input('question', 'Питання', item?.question, { textarea: true, full: true, required: true })
                + input('answers_text', 'Варіанти відповіді — кожен з нового рядка', (item?.answers || []).join('\n'), { textarea: true, rows: 5, full: true, required: true })
                + input('correct_answer', 'Правильний варіант (номер від 1)', item?.correct_answer == null ? '' : Number(item.correct_answer) + 1, { type: 'number' })
                + input('image_url', 'Зображення питання', item?.image_url)
                + input('explanation', 'Пояснення', item?.explanation, { textarea: true, rows: 5, full: true })
                + input('source_url', 'Джерело', item?.source_url, { full: true });
        }
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
                        ${type === 'tests' && item.image_url ? `<img class="img-fluid rounded mb-3" src="${escapeHtml(item.image_url)}" alt="">` : ''}
                        <div class="small text-warning fw-bold mb-1">${escapeHtml(
                            type === 'rules'
                                ? `Розділ ${item.section_number}`
                                : type === 'signs'
                                    ? `${item.category || 'Знак'} · ${item.code}`
                                    : `Тест · ID ${item.source_external_id || item.id}`
                        )}</div>
                        <h6>${escapeHtml(type === 'tests' ? item.question : item.title)}</h6>
                        ${type === 'tests'
                            ? `<ol class="traffic-answer-list small text-muted">${(item.answers || []).map(answer => `<li>${escapeHtml(answer)}</li>`).join('')}</ol>`
                            : `<p class="small text-muted mb-2">${escapeHtml(String(type === 'rules' ? item.summary : item.description || '').slice(0, 320))}</p>`}
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
        if (type === 'tests') {
            data.answers = String(data.answers_text || '').split(/\n+/).map(value => value.trim()).filter(Boolean);
            delete data.answers_text;
            data.correct_answer = data.correct_answer === '' ? null : Math.max(0, Number(data.correct_answer) - 1);
            data.source_external_id = data.source_external_id === '' ? null : Number(data.source_external_id);
            data.topic_external_id = data.topic_external_id === '' ? null : Number(data.topic_external_id);
        }
        let requestOptions;
        if (type === 'signs') {
            const multipart = new FormData(form);
            multipart.set('fid', String(fid));
            multipart.set('sort_order', String(data.sort_order));
            multipart.set('is_published', data.is_published ? '1' : '0');
            if (id) multipart.set('_method', 'PUT');
            requestOptions = {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: multipart
            };
        } else {
            requestOptions = {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data)
            };
        }
        const response = await fetch(id ? `${base}/${type}/${id}` : `${base}/${type}`, requestOptions);
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
