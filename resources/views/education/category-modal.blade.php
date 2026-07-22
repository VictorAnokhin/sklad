<div class="modal fade" id="education-category-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5">Категории</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="list-group mb-4" id="education-category-list">
                    @forelse($categories as $category)
                        <button type="button" class="list-group-item list-group-item-action bg-dark text-light border-secondary edit-education-category"
                                data-category-id="{{ $category->id }}">
                            <span class="fw-semibold">{{ $category->title }}</span>
                            <span class="badge text-bg-secondary ms-2">Позиция {{ $category->position }}</span>
                        </button>
                    @empty
                        <div class="text-secondary">Категории ещё не добавлены.</div>
                    @endforelse
                </div>

                <form id="education-category-form" method="POST" action="{{ route('education.categories.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="education-category-method" value="POST">
                    <input type="hidden" name="context" value="{{ $categoryContext }}">
                    <h3 class="fs-6 mb-3" id="education-category-form-title">Новая категория</h3>
                    <div class="row g-2 mb-3">
                        <div class="col-md">
                            <label class="form-label" for="education-category-title-ua">Название UA</label>
                            <input class="form-control" id="education-category-title-ua" name="title_translations[ua]" maxlength="255">
                        </div>
                        <div class="col-md">
                            <label class="form-label" for="education-category-title-ru">Название RU</label>
                            <input class="form-control" id="education-category-title-ru" name="title_translations[ru]" maxlength="255">
                        </div>
                        <div class="col-md">
                            <label class="form-label" for="education-category-title-en">Название EN</label>
                            <input class="form-control" id="education-category-title-en" name="title_translations[en]" maxlength="255">
                        </div>
                        <div class="col-md">
                            <label class="form-label" for="education-category-title-es">Название ES</label>
                            <input class="form-control" id="education-category-title-es" name="title_translations[es]" maxlength="255">
                        </div>
                        <div class="col-md">
                            <label class="form-label" for="education-category-title-fr">Название FR</label>
                            <input class="form-control" id="education-category-title-fr" name="title_translations[fr]" maxlength="255">
                        </div>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="education-category-position">Позиция</label>
                            <input class="form-control" id="education-category-position" name="position" type="number" min="0" value="0">
                        </div>
                        <div class="col-md-8 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-danger d-none" id="delete-education-category-button">Удалить</button>
                            <button type="button" class="btn btn-outline-light" id="new-education-category-button">Новая</button>
                            <button type="submit" class="btn btn-warning">Сохранить</button>
                        </div>
                    </div>
                </form>
                <form id="delete-education-category-form" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const managerButton = document.getElementById('manage-education-categories-button');
    const modalElement = document.getElementById('education-category-modal');
    if (!managerButton || !modalElement) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = document.getElementById('education-category-form');
    const method = document.getElementById('education-category-method');
    const deleteForm = document.getElementById('delete-education-category-form');
    const deleteButton = document.getElementById('delete-education-category-button');
    const items = @json($categoryEditorItems ?? []);
    const storeUrl = @json(route('education.categories.store'));
    const updateUrl = @json(route('education.categories.update', ['category' => '__ID__']));
    const deleteUrl = @json(route('education.categories.destroy', ['category' => '__ID__']));
    let currentId = null;

    function resetCategoryForm() {
        form.reset();
        currentId = null;
        form.action = storeUrl;
        method.value = 'POST';
        document.getElementById('education-category-form-title').textContent = 'Новая категория';
        document.getElementById('education-category-position').value = '0';
        deleteButton.classList.add('d-none');
    }

    function editCategory(id) {
        const item = items[id];
        if (!item) return;
        currentId = id;
        form.action = updateUrl.replace('__ID__', id);
        method.value = 'PUT';
        document.getElementById('education-category-form-title').textContent = 'Изменить категорию';
        document.getElementById('education-category-title-ua').value = item.title_translations?.ua || '';
        document.getElementById('education-category-title-ru').value = item.title_translations?.ru || item.title || '';
        document.getElementById('education-category-title-en').value = item.title_translations?.en || '';
        document.getElementById('education-category-title-es').value = item.title_translations?.es || '';
        document.getElementById('education-category-title-fr').value = item.title_translations?.fr || '';
        document.getElementById('education-category-position').value = item.position ?? 0;
        deleteButton.classList.remove('d-none');
    }

    managerButton.addEventListener('click', () => {
        resetCategoryForm();
        modal.show();
    });
    document.getElementById('new-education-category-button').addEventListener('click', resetCategoryForm);
    document.querySelectorAll('.edit-education-category').forEach((button) => {
        button.addEventListener('click', () => editCategory(button.dataset.categoryId));
    });
    deleteButton.addEventListener('click', () => {
        if (!currentId || !confirm('Удалить категорию? Курсы или тесты останутся без категории.')) return;
        deleteForm.action = deleteUrl.replace('__ID__', currentId);
        deleteForm.submit();
    });
});
</script>
@endpush
