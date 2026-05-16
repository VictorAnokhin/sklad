/**
 * AI Knowledge Base Manager — vanilla JS, no framework dependencies.
 * Communicates with the Laravel /api/ai/knowledge-base endpoints.
 * Provides CRUD operations, search, and chat export functionality.
 */
(function () {
  "use strict";

  var CONFIG = {
    fid: 1,
    firma: null,
    apiUrl: "/api/ai/knowledge-base",
    categoriesApiUrl: "/api/ai/knowledge-base/categories",
    searchUrl: "/api/ai/knowledge-base/search",
    exportUrl: "/api/ai/chat/export",
    perPage: 100,
    messages: {
      ru: {
        title: "База знаний",
        subtitle: "Проект #",
        close: "Закрыть",
        searchPlaceholder: "Поиск в базе знаний...",
        allCategories: "Все категории",
        add: "Добавить",
        fromChat: "Из чата",
        loading: "Загрузка...",
        empty: "База знаний пуста",
        emptyAdd: "Добавить первую запись",
        noTitle: "Без заголовка",
        recordsCount: "записей",
        activate: "Активировать",
        deactivate: "Деактивировать",
        delete: "Удалить",
        deleteConfirm: "Удалить запись из базы знаний?",
        edit: "Редактировать",
        newRecord: "Новая запись",
        editRecord: "Редактировать запись",
        titleLabel: "Заголовок",
        titlePlaceholder: "Краткое описание...",
        contentLabel: "Содержание *",
        contentPlaceholder: "Текст базы знаний. Модель будет использовать это при ответах...",
        categoryLabel: "Категория",
        create: "Создать",
        save: "Сохранить",
        cancel: "Отмена",
        exportTitle: "Экспорт диалога в базу знаний",
        questionLabel: "Вопрос пользователя *",
        questionPlaceholder: "Скопируйте вопрос из чата...",
        answerLabel: "Ответ модели *",
        answerPlaceholder: "Скопируйте ответ ассистента...",
        exportSubmit: "Экспортировать",
        error: "Ошибка",
        searchError: "Поиск не удался",
        loadError: "Не удалось загрузить базу знаний",
        deleteError: "Не удалось удалить запись",
        updateError: "Не удалось обновить запись",
        operationError: "Операция не удалась",
        button: "База знаний",
        categoryLoadError: "Не удалось загрузить категории",
      },
      ua: {
        title: "База знань",
        subtitle: "Проект #",
        close: "Закрити",
        searchPlaceholder: "Пошук у базі знань...",
        allCategories: "Всі категорії",
        add: "Додати",
        fromChat: "З чату",
        loading: "Завантаження...",
        empty: "База знань порожня",
        emptyAdd: "Додати перший запис",
        noTitle: "Без заголовка",
        recordsCount: "записів",
        activate: "Активувати",
        deactivate: "Деактивувати",
        delete: "Видалити",
        deleteConfirm: "Видалити запис із бази знань?",
        edit: "Редагувати",
        newRecord: "Новий запис",
        editRecord: "Редагувати запис",
        titleLabel: "Заголовок",
        titlePlaceholder: "Короткий опис...",
        contentLabel: "Зміст *",
        contentPlaceholder: "Текст бази знань. Модель буде використовувати це у відповідях...",
        categoryLabel: "Категорія",
        create: "Створити",
        save: "Зберегти",
        cancel: "Скасувати",
        exportTitle: "Експорт діалогу в базу знань",
        questionLabel: "Питання користувача *",
        questionPlaceholder: "Скопіюйте питання з чату...",
        answerLabel: "Відповідь моделі *",
        answerPlaceholder: "Скопіюйте відповідь асистента...",
        exportSubmit: "Експортувати",
        error: "Помилка",
        searchError: "Пошук не вдався",
        loadError: "Не вдалося завантажити базу знань",
        deleteError: "Не вдалося видалити запис",
        updateError: "Не вдалося оновити запис",
        operationError: "Операція не вдалася",
        button: "База знань",
        categoryLoadError: "Не вдалося завантажити категорії",
      },
      en: {
        title: "Knowledge Base",
        subtitle: "Project #",
        close: "Close",
        searchPlaceholder: "Search knowledge base...",
        allCategories: "All categories",
        add: "Add",
        fromChat: "From chat",
        loading: "Loading...",
        empty: "Knowledge base is empty",
        emptyAdd: "Add first record",
        noTitle: "No title",
        recordsCount: "records",
        activate: "Activate",
        deactivate: "Deactivate",
        delete: "Delete",
        deleteConfirm: "Delete record from knowledge base?",
        edit: "Edit",
        newRecord: "New record",
        editRecord: "Edit record",
        titleLabel: "Title",
        titlePlaceholder: "Short description...",
        contentLabel: "Content *",
        contentPlaceholder: "Knowledge base text. The model will use this when answering...",
        categoryLabel: "Category",
        create: "Create",
        save: "Save",
        cancel: "Cancel",
        exportTitle: "Export chat to knowledge base",
        questionLabel: "User question *",
        questionPlaceholder: "Copy the question from chat...",
        answerLabel: "Model answer *",
        answerPlaceholder: "Copy the assistant's answer...",
        exportSubmit: "Export",
        error: "Error",
        searchError: "Search failed",
        loadError: "Failed to load knowledge base",
        deleteError: "Failed to delete record",
        updateError: "Failed to update record",
        operationError: "Operation failed",
        button: "Knowledge Base",
        categoryLoadError: "Failed to load categories",
      },
    },
  };

  var state = {
    open: false,
    records: [],
    categories: [],
    categoriesLoaded: false,
    loading: false,
    error: "",
    searchQuery: "",
    categoryFilter: "",
    modalMode: null, // 'create' | 'edit' | 'export' | null
    editRecordId: null,
    formTitle: "",
    formContent: "",
    formCategory: "general",
    formBusy: false,
    formError: "",
    exportQuestion: "",
    exportAnswer: "",
  };

  var elements = {};

  // ── Helper: current language ────────────────────────────
  function getLanguage() {
    var lang = document.documentElement.getAttribute("lang") || "ru";
    if (lang.indexOf("ua") !== -1 || lang.indexOf("uk") !== -1) return "ua";
    if (lang.indexOf("en") !== -1) return "en";
    return "ru";
  }

  function msg(key) {
    var lang = getLanguage();
    return (CONFIG.messages[lang] && CONFIG.messages[lang][key]) || CONFIG.messages.ru[key] || key;
  }

  function catLabel(catValue) {
    if (state.categories.length === 0) return catValue;
    for (var i = 0; i < state.categories.length; i++) {
      if (state.categories[i].key === catValue) {
        return state.categories[i].name || catValue;
      }
    }
    return catValue;
  }

  // ── DOM helpers ─────────────────────────────────────────
  function createEl(tag, attrs, children) {
    var el = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === "className") el.className = attrs[k];
        else if (k === "html") el.innerHTML = attrs[k];
        else if (k === "dataset") {
          Object.keys(attrs[k]).forEach(function (dk) {
            el.dataset[dk] = attrs[k][dk];
          });
        } else el.setAttribute(k, attrs[k]);
      });
    }
    if (children) {
      (Array.isArray(children) ? children : [children]).forEach(function (c) {
        if (typeof c === "string") el.appendChild(document.createTextNode(c));
        else if (c) el.appendChild(c);
      });
    }
    return el;
  }

  function svgIcon(name) {
    var icons = {
      database: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
      x: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
      search: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
      plus: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>',
      sparkles: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2m0 14v2m-7-9H3m18 0h-2M5.64 5.64l1.41 1.41m10.54 10.54 1.41 1.41M18.36 5.64l-1.41 1.41M7.05 16.95l-1.41 1.41"/><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/></svg>',
      spinner: '<svg class="kb-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
      edit: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
      check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
      trash: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
    };
    var wrapper = document.createElement("div");
    wrapper.innerHTML = icons[name] || "";
    return wrapper.firstChild;
  }

  // ── API calls ───────────────────────────────────────────
  function apiFetch(url, options) {
    var defaults = {
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      credentials: "same-origin",
    };
    var merged = Object.assign({}, defaults, options);
    if (merged.body && typeof merged.body === "object") {
      merged.body = JSON.stringify(merged.body);
    }
    return fetch(url, merged);
  }

  function loadCategories() {
    if (state.categoriesLoaded) return;

    var params = new URLSearchParams({ fid: CONFIG.fid });
    apiFetch(CONFIG.categoriesApiUrl + "?" + params.toString(), { method: "GET" })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("categoryLoadError") + ": " + res.status);
        return res.json();
      })
      .then(function (data) {
        state.categories = Array.isArray(data.data) ? data.data : [];
        state.categoriesLoaded = true;
        render();
      })
      .catch(function () {
        // If categories fail to load, keep defaults for compatibility
        state.categories = [
          { key: "general", name: msg("allCategories") === "Все категории" ? "Общее" : "General" },
          { key: "invest", name: "Invest" },
          { key: "wallet", name: "Wallet" },
          { key: "token", name: "Tokens" },
          { key: "fund", name: "Fund" },
          { key: "admin", name: "Admin" },
          { key: "faq", name: "FAQ" },
        ];
        state.categoriesLoaded = true;
        render();
      });
  }

  function loadRecords() {
    state.loading = true;
    state.error = "";
    render();

    var params = new URLSearchParams({
      fid: CONFIG.fid,
      per_page: CONFIG.perPage,
    });
    if (state.categoryFilter) {
      params.set("category", state.categoryFilter);
    }
    if (CONFIG.firma !== null && CONFIG.firma !== undefined) {
      params.set("firma", CONFIG.firma);
    }

    apiFetch(CONFIG.apiUrl + "?" + params.toString(), { method: "GET" })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("loadError") + ": " + res.status);
        return res.json();
      })
      .then(function (data) {
        state.records = Array.isArray(data.data) ? data.data : [];
        state.loading = false;
        render();
      })
      .catch(function (err) {
        state.loading = false;
        state.error = err.message || msg("loadError");
        render();
      });
  }

  function handleSearch() {
    if (!state.searchQuery.trim()) {
      loadRecords();
      return;
    }

    state.loading = true;
    state.error = "";
    render();

    var body = { fid: CONFIG.fid, query: state.searchQuery.trim() };
    if (CONFIG.firma !== null && CONFIG.firma !== undefined) {
      body.firma = CONFIG.firma;
    }

    apiFetch(CONFIG.searchUrl, {
      method: "POST",
      body: body,
    })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("searchError") + ": " + res.status);
        return res.json();
      })
      .then(function (data) {
        state.records = Array.isArray(data.data) ? data.data : [];
        state.loading = false;
        render();
      })
      .catch(function (err) {
        state.loading = false;
        state.error = err.message || msg("searchError");
        render();
      });
  }

  function openCreateModal() {
    state.modalMode = "create";
    state.editRecordId = null;
    state.formTitle = "";
    state.formContent = "";
    state.formCategory = state.categories.length > 0 ? state.categories[0].key : "general";
    state.formError = "";
    renderModal();
  }

  function openEditModal(record) {
    state.modalMode = "edit";
    state.editRecordId = record.id;
    state.formTitle = record.title || "";
    state.formContent = record.content || "";
    state.formCategory = record.category || "general";
    state.formError = "";
    renderModal();
  }

  function openExportModal() {
    state.modalMode = "export";
    state.exportQuestion = "";
    state.exportAnswer = "";
    state.formCategory = state.categories.length > 0 ? state.categories[0].key : "general";
    state.formError = "";
    renderModal();
  }

  function closeModal() {
    state.modalMode = null;
    state.editRecordId = null;
    state.formBusy = false;
    state.formError = "";
    var modal = document.getElementById("kb-modal-backdrop");
    if (modal) modal.remove();
  }

  function handleSubmit(e) {
    e.preventDefault();
    if (state.formBusy) return;

    state.formBusy = true;
    state.formError = "";
    renderModal();

    var isCreate = state.modalMode === "create";
    var url = isCreate ? CONFIG.apiUrl : CONFIG.apiUrl + "/" + state.editRecordId;
    var method = isCreate ? "POST" : "PUT";
    var body = {
      fid: CONFIG.fid,
      title: state.formTitle,
      content: state.formContent,
      category: state.formCategory,
    };
    if (CONFIG.firma !== null && CONFIG.firma !== undefined) {
      body.firma = CONFIG.firma;
    }

    apiFetch(url, { method: method, body: body })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("operationError") + ": " + res.status);
        return res.json();
      })
      .then(function () {
        closeModal();
        loadRecords();
      })
      .catch(function (err) {
        state.formBusy = false;
        state.formError = err.message || msg("operationError");
        renderModal();
      });
  }

  function handleExport(e) {
    e.preventDefault();
    if (state.formBusy || !state.exportQuestion.trim() || !state.exportAnswer.trim()) return;

    state.formBusy = true;
    state.formError = "";
    renderModal();

    var body = {
      fid: CONFIG.fid,
      question: state.exportQuestion.trim(),
      answer: state.exportAnswer.trim(),
      category: state.formCategory,
    };
    if (CONFIG.firma !== null && CONFIG.firma !== undefined) {
      body.firma = CONFIG.firma;
    }

    apiFetch(CONFIG.exportUrl, { method: "POST", body: body })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("operationError") + ": " + res.status);
        return res.json();
      })
      .then(function () {
        closeModal();
        loadRecords();
      })
      .catch(function (err) {
        state.formBusy = false;
        state.formError = err.message || msg("operationError");
        renderModal();
      });
  }

  function handleDelete(id) {
    if (!confirm(msg("deleteConfirm"))) return;

    apiFetch(CONFIG.apiUrl + "/" + id, { method: "DELETE" })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("deleteError") + ": " + res.status);
        loadRecords();
      })
      .catch(function (err) {
        state.error = err.message || msg("deleteError");
        render();
      });
  }

  function handleToggleActive(record) {
    apiFetch(CONFIG.apiUrl + "/" + record.id, {
      method: "PUT",
      body: { active: !record.active },
    })
      .then(function (res) {
        if (!res.ok) throw new Error(msg("updateError") + ": " + res.status);
        loadRecords();
      })
      .catch(function (err) {
        state.error = err.message || msg("updateError");
        render();
      });
  }

  // ── Render category options ────────────────────────────
  function renderCategoryOptions(selectEl, selectedValue) {
    selectEl.innerHTML = "";
    state.categories.forEach(function (cat) {
      var opt = document.createElement("option");
      opt.value = cat.key;
      opt.textContent = cat.name || cat.key;
      if (cat.key === selectedValue) opt.selected = true;
      selectEl.appendChild(opt);
    });
  }

  // ── Render ─────────────────────────────────────────────
  function render() {
    var container = elements.content;
    if (!container) return;

    container.innerHTML = "";

    if (state.loading) {
      var loadingEl = createEl("div", { className: "kb-loading" }, [
        svgIcon("spinner"),
        document.createTextNode(" " + msg("loading")),
      ]);
      container.appendChild(loadingEl);
      return;
    }

    if (state.error) {
      var errorEl = createEl("div", { className: "kb-error" }, state.error);
      container.appendChild(errorEl);
      return;
    }

    if (state.records.length === 0) {
      var emptyEl = createEl("div", { className: "kb-empty" }, [
        createEl("div", { className: "kb-empty-icon" }, svgIcon("database")),
        createEl("div", { className: "kb-empty-text" }, msg("empty")),
        createEl("button", {
          className: "kb-empty-add-btn",
          type: "button",
        }, msg("emptyAdd")),
      ]);
      emptyEl.querySelector(".kb-empty-add-btn").addEventListener("click", openCreateModal);
      container.appendChild(emptyEl);
      return;
    }

    var list = createEl("div", { className: "kb-list" });
    state.records.forEach(function (record) {
      var item = createEl("div", {
        className: "kb-list-item" + (record.active ? "" : " kb-list-item--inactive"),
      });

      var header = createEl("div", { className: "kb-list-item-header" });
      var titleGroup = createEl("div", { className: "kb-list-item-title-group" });
      var title = createEl("span", { className: "kb-list-item-title" }, record.title || msg("noTitle"));
      var cat = createEl("span", { className: "kb-list-item-cat" }, catLabel(record.category));
      titleGroup.appendChild(title);
      titleGroup.appendChild(cat);

      var actions = createEl("div", { className: "kb-list-item-actions" });

      var editBtn = createEl("button", {
        className: "kb-action-btn",
        type: "button",
        "aria-label": msg("edit"),
        title: msg("edit"),
        dataset: { id: record.id },
      }, svgIcon("edit"));
      editBtn.addEventListener("click", function () {
        openEditModal(record);
      });
      actions.appendChild(editBtn);

      var toggleBtn = createEl("button", {
        className: "kb-action-btn" + (record.active ? " kb-action-btn--active" : ""),
        type: "button",
        "aria-label": record.active ? msg("deactivate") : msg("activate"),
        title: record.active ? msg("deactivate") : msg("activate"),
        dataset: { id: record.id },
      }, svgIcon("check"));
      toggleBtn.addEventListener("click", function () {
        handleToggleActive(record);
      });
      actions.appendChild(toggleBtn);

      var deleteBtn = createEl("button", {
        className: "kb-action-btn kb-action-btn--danger",
        type: "button",
        "aria-label": msg("delete"),
        title: msg("delete"),
        dataset: { id: record.id },
      }, svgIcon("trash"));
      deleteBtn.addEventListener("click", function () {
        handleDelete(record.id);
      });
      actions.appendChild(deleteBtn);

      header.appendChild(titleGroup);
      header.appendChild(actions);

      var content = createEl("p", { className: "kb-list-item-content" }, record.content || "");

      item.appendChild(header);
      item.appendChild(content);
      list.appendChild(item);
    });

    container.appendChild(list);
  }

  function renderModal() {
    var existing = document.getElementById("kb-modal-backdrop");
    if (existing) existing.remove();
    if (!state.modalMode) return;

    var backdrop = createEl("div", { className: "kb-modal-backdrop", id: "kb-modal-backdrop" });
    var isCreate = state.modalMode === "create";
    var isEdit = state.modalMode === "edit";
    var isExport = state.modalMode === "export";

    if (isCreate || isEdit) {
      var modal = createEl("div", { className: "kb-modal" });
      var heading = createEl("h3", { className: "kb-modal-title" }, isCreate ? msg("newRecord") : msg("editRecord"));

      var form = createEl("form", { className: "kb-modal-form" });

      // Title
      var titleGroup = createEl("div", { className: "kb-field" });
      titleGroup.appendChild(createEl("label", { className: "kb-field-label" }, msg("titleLabel")));
      var titleInput = createEl("input", {
        className: "kb-field-input",
        type: "text",
        placeholder: msg("titlePlaceholder"),
        value: state.formTitle,
      });
      titleInput.addEventListener("input", function (e) { state.formTitle = e.target.value; });
      titleGroup.appendChild(titleInput);
      form.appendChild(titleGroup);

      // Content
      var contentGroup = createEl("div", { className: "kb-field" });
      contentGroup.appendChild(createEl("label", { className: "kb-field-label" }, msg("contentLabel")));
      var contentTextarea = createEl("textarea", {
        className: "kb-field-textarea",
        placeholder: msg("contentPlaceholder"),
        rows: 6,
      });
      contentTextarea.textContent = state.formContent;
      contentTextarea.addEventListener("input", function (e) { state.formContent = e.target.value; });
      contentGroup.appendChild(contentTextarea);
      form.appendChild(contentGroup);

      // Category
      var catGroup = createEl("div", { className: "kb-field" });
      catGroup.appendChild(createEl("label", { className: "kb-field-label" }, msg("categoryLabel")));
      var catSelect = createEl("select", { className: "kb-field-select" });
      renderCategoryOptions(catSelect, state.formCategory);
      catSelect.addEventListener("change", function (e) { state.formCategory = e.target.value; });
      catGroup.appendChild(catSelect);
      form.appendChild(catGroup);

      // Error
      if (state.formError) {
        form.appendChild(createEl("div", { className: "kb-form-error" }, state.formError));
      }

      // Buttons
      var btnGroup = createEl("div", { className: "kb-modal-btns" });
      var submitBtn = createEl("button", {
        className: "kb-btn kb-btn--primary",
        type: "submit",
        disabled: state.formBusy || !state.formContent.trim(),
      }, isCreate ? msg("create") : msg("save"));
      if (state.formBusy) {
        submitBtn.innerHTML = "";
        submitBtn.appendChild(svgIcon("spinner"));
        submitBtn.appendChild(document.createTextNode(" " + (isCreate ? msg("create") : msg("save"))));
        submitBtn.classList.add("kb-btn--loading");
      }
      btnGroup.appendChild(submitBtn);

      var cancelBtn = createEl("button", {
        className: "kb-btn kb-btn--secondary",
        type: "button",
      }, msg("cancel"));
      cancelBtn.addEventListener("click", closeModal);
      btnGroup.appendChild(cancelBtn);

      form.appendChild(btnGroup);
      form.addEventListener("submit", handleSubmit);

      modal.appendChild(heading);
      modal.appendChild(form);
      backdrop.appendChild(modal);
    }

    if (isExport) {
      var modalExport = createEl("div", { className: "kb-modal" });
      modalExport.appendChild(createEl("h3", { className: "kb-modal-title" }, msg("exportTitle")));

      var exportForm = createEl("form", { className: "kb-modal-form" });

      // Question
      var qGroup = createEl("div", { className: "kb-field" });
      qGroup.appendChild(createEl("label", { className: "kb-field-label" }, msg("questionLabel")));
      var qTextarea = createEl("textarea", {
        className: "kb-field-textarea",
        placeholder: msg("questionPlaceholder"),
        rows: 3,
      });
      qTextarea.textContent = state.exportQuestion;
      qTextarea.addEventListener("input", function (e) { state.exportQuestion = e.target.value; });
      qGroup.appendChild(qTextarea);
      exportForm.appendChild(qGroup);

      // Answer
      var aGroup = createEl("div", { className: "kb-field" });
      aGroup.appendChild(createEl("label", { className: "kb-field-label" }, msg("answerLabel")));
      var aTextarea = createEl("textarea", {
        className: "kb-field-textarea",
        placeholder: msg("answerPlaceholder"),
        rows: 5,
      });
      aTextarea.textContent = state.exportAnswer;
      aTextarea.addEventListener("input", function (e) { state.exportAnswer = e.target.value; });
      aGroup.appendChild(aTextarea);
      exportForm.appendChild(aGroup);

      // Category
      var ecatGroup = createEl("div", { className: "kb-field" });
      ecatGroup.appendChild(createEl("label", { className: "kb-field-label" }, msg("categoryLabel")));
      var ecatSelect = createEl("select", { className: "kb-field-select" });
      renderCategoryOptions(ecatSelect, state.formCategory);
      ecatSelect.addEventListener("change", function (e) { state.formCategory = e.target.value; });
      ecatGroup.appendChild(ecatSelect);
      exportForm.appendChild(ecatGroup);

      // Error
      if (state.formError) {
        exportForm.appendChild(createEl("div", { className: "kb-form-error" }, state.formError));
      }

      // Buttons
      var ebtnGroup = createEl("div", { className: "kb-modal-btns" });
      var esubmitBtn = createEl("button", {
        className: "kb-btn kb-btn--primary",
        type: "submit",
        disabled: state.formBusy || !state.exportQuestion.trim() || !state.exportAnswer.trim(),
      }, msg("exportSubmit"));
      if (state.formBusy) {
        esubmitBtn.innerHTML = "";
        esubmitBtn.appendChild(svgIcon("spinner"));
        esubmitBtn.appendChild(document.createTextNode(" " + msg("exportSubmit")));
        esubmitBtn.classList.add("kb-btn--loading");
      }
      ebtnGroup.appendChild(esubmitBtn);

      var ecancelBtn = createEl("button", {
        className: "kb-btn kb-btn--secondary",
        type: "button",
      }, msg("cancel"));
      ecancelBtn.addEventListener("click", closeModal);
      ebtnGroup.appendChild(ecancelBtn);

      exportForm.appendChild(ebtnGroup);
      exportForm.addEventListener("submit", handleExport);

      modalExport.appendChild(exportForm);
      backdrop.appendChild(modalExport);
    }

    document.body.appendChild(backdrop);

    // Close on backdrop click
    backdrop.addEventListener("click", function (e) {
      if (e.target === backdrop) closeModal();
    });
  }

  function updateFooter() {
    var footer = elements.footer;
    if (footer) {
      footer.textContent = state.records.length + " " + msg("recordsCount") + " • fid=" + CONFIG.fid;
    }
  }

  // ── Build widget DOM ────────────────────────────────────
  function buildWidget() {
    var widget = createEl("div", { className: "kb-widget", id: "kb-widget" });

    // Window
    var window = createEl("div", { className: "kb-window" });

    // Header
    var header = createEl("div", { className: "kb-header" });
    var headerLeft = createEl("div", { className: "kb-header-left" });
    var avatar = createEl("div", { className: "kb-avatar" }, svgIcon("database"));
    var titleBlock = createEl("div", null, [
      createEl("div", { className: "kb-title" }, msg("title")),
      createEl("div", { className: "kb-subtitle" }, msg("subtitle") + CONFIG.fid),
    ]);
    headerLeft.appendChild(avatar);
    headerLeft.appendChild(titleBlock);
    var closeBtn = createEl("button", {
      className: "kb-close-btn",
      type: "button",
      "aria-label": msg("close"),
    }, svgIcon("x"));
    header.appendChild(headerLeft);
    header.appendChild(closeBtn);
    window.appendChild(header);

    // Actions bar
    var actionsBar = createEl("div", { className: "kb-actions-bar" });

    var searchWrap = createEl("div", { className: "kb-search-wrap" });
    var searchInput = createEl("input", {
      className: "kb-search-input",
      type: "text",
      placeholder: msg("searchPlaceholder"),
      value: state.searchQuery,
    });
    searchInput.addEventListener("input", function (e) { state.searchQuery = e.target.value; });
    searchInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") handleSearch();
    });
    var searchBtn = createEl("button", {
      className: "kb-search-btn",
      type: "button",
    }, svgIcon("search"));
    searchBtn.addEventListener("click", handleSearch);
    searchWrap.appendChild(searchInput);
    searchWrap.appendChild(searchBtn);
    actionsBar.appendChild(searchWrap);

    var catSelect = createEl("select", { className: "kb-cat-select" });
    catSelect.appendChild(createEl("option", { value: "" }, msg("allCategories")));
    // Categories will be populated after load
    actionsBar.appendChild(catSelect);

    var addBtn = createEl("button", {
      className: "kb-action-primary-btn",
      type: "button",
    }, [svgIcon("plus"), document.createTextNode(" " + msg("add"))]);
    addBtn.addEventListener("click", openCreateModal);
    actionsBar.appendChild(addBtn);

    var exportBtn = createEl("button", {
      className: "kb-action-secondary-btn",
      type: "button",
    }, [svgIcon("sparkles"), document.createTextNode(" " + msg("fromChat"))]);
    exportBtn.addEventListener("click", openExportModal);
    actionsBar.appendChild(exportBtn);

    window.appendChild(actionsBar);

    // Content area
    var content = createEl("div", { className: "kb-content" });
    window.appendChild(content);

    // Footer
    var footer = createEl("div", { className: "kb-footer" });
    window.appendChild(footer);

    widget.appendChild(window);

    // Toggle button
    var toggle = createEl("button", {
      className: "kb-toggle",
      type: "button",
    }, [svgIcon("database"), createEl("span", null, msg("button"))]);
    widget.appendChild(toggle);

    document.body.appendChild(widget);

    // Store refs
    elements.widget = widget;
    elements.window = window;
    elements.content = content;
    elements.footer = footer;
    elements.toggle = toggle;
    elements.closeBtn = closeBtn;
    elements.searchInput = searchInput;
    elements.catSelect = catSelect;
  }

  // ── Populate category filter dropdown ──────────────────
  function populateCategoryFilter() {
    var catSelect = elements.catSelect;
    if (!catSelect) return;

    // Keep the "All categories" option, remove the rest
    while (catSelect.options.length > 1) {
      catSelect.remove(1);
    }

    state.categories.forEach(function (cat) {
      var opt = document.createElement("option");
      opt.value = cat.key;
      opt.textContent = cat.name || cat.key;
      if (cat.key === state.categoryFilter) opt.selected = true;
      catSelect.appendChild(opt);
    });
  }

  // ── Event bindings ─────────────────────────────────────
  function bindEvents() {
    elements.toggle.addEventListener("click", function () {
      state.open = !state.open;
      elements.window.classList.toggle("open", state.open);
      if (state.open) {
        loadCategories();
        loadRecords();
        elements.searchInput.focus();
      }
    });

    elements.closeBtn.addEventListener("click", function () {
      state.open = false;
      elements.window.classList.remove("open");
    });

    // Category filter change
    elements.catSelect.addEventListener("change", function (e) {
      state.categoryFilter = e.target.value;
      loadRecords();
    });
  }

  // ── Watch footer updates ───────────────────────────────
  function watchFooter() {
    var origRender = render;
    render = function () {
      origRender();
      updateFooter();
      populateCategoryFilter();
    };
  }

  // ── Init ───────────────────────────────────────────────
  function init(userConfig) {
    if (userConfig) {
      if (userConfig.fid) CONFIG.fid = userConfig.fid;
      if (userConfig.firma !== undefined) CONFIG.firma = userConfig.firma;
      if (userConfig.apiUrl) CONFIG.apiUrl = userConfig.apiUrl;
      if (userConfig.categoriesApiUrl) CONFIG.categoriesApiUrl = userConfig.categoriesApiUrl;
    }

    buildWidget();
    watchFooter();
    bindEvents();
    updateFooter();
  }

  // Expose to global scope so Blade can pass config
  window.AiKnowledgeBase = { init: init };
})();
