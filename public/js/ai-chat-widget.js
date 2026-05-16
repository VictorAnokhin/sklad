/**
 * AI Chat Widget — vanilla JS, no framework dependencies.
 * Communicates with the Laravel /api/ai/chat endpoint.
 */
(function () {
  "use strict";

  var CONFIG = {
    fid: 1,
    firma: null,
    apiUrl: "/api/ai/chat",
    maxHistory: 6,
    messages: {
      ru: {
        title: "AI консультант",
        subtitle: "DeepSeek",
        thinking: "Думаю…",
        placeholder: "Напишите сообщение…",
        sendLabel: "Отправить",
        button: "AI консультант",
        collapse: "Свернуть",
        welcome:
          "Спросите об AV8Capital, услугах для микро-бизнеса, финансовом учёте или как пользоваться системой.",
        error: "AI-ассистент временно недоступен. Попробуйте позже.",
      },
      ua: {
        title: "AI консультант",
        subtitle: "DeepSeek",
        thinking: "Думаю…",
        placeholder: "Напишіть повідомлення…",
        sendLabel: "Надіслати",
        button: "AI консультант",
        collapse: "Згорнути",
        welcome:
          "Запитайте про AV8Capital, послуги для мікро-бізнесу, фінансовий облік або як користуватися системою.",
        error: "AI-асистент тимчасово недоступний. Спробуйте пізніше.",
      },
      en: {
        title: "AI Assistant",
        subtitle: "DeepSeek",
        thinking: "Thinking…",
        placeholder: "Type a message…",
        sendLabel: "Send",
        button: "AI Assistant",
        collapse: "Collapse",
        welcome:
          "Ask about AV8Capital, micro-business services, financial accounting, or how to use the system.",
        error: "AI assistant is temporarily unavailable. Please try again later.",
      },
    },
  };

  var state = {
    open: false,
    busy: false,
    rows: [],
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
      bot: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>',
      chevronDown:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
      send: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>',
      sparkles:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2m0 14v2m-7-9H3m18 0h-2M5.64 5.64l1.41 1.41m10.54 10.54 1.41 1.41M18.36 5.64l-1.41 1.41M7.05 16.95l-1.41 1.41"/><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/></svg>',
      spinner:
        '<svg class="ai-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
    };
    var wrapper = document.createElement("div");
    wrapper.innerHTML = icons[name] || "";
    return wrapper.firstChild;
  }

  // ── Build widget DOM ────────────────────────────────────
  function buildWidget() {
    var widget = createEl("div", { className: "ai-chat-widget", id: "ai-chat-widget" });

    // Window
    var window = createEl("div", { className: "ai-chat-window" });

    // Header
    var header = createEl("div", { className: "ai-chat-header" });
    var headerLeft = createEl("div", { className: "ai-chat-header-left" });
    var avatar = createEl("div", { className: "ai-chat-avatar" }, svgIcon("bot"));
    var titleBlock = createEl("div", null, [
      createEl("div", { className: "ai-chat-title" }, msg("title")),
      createEl("div", { className: "ai-chat-subtitle" }, msg("subtitle")),
    ]);
    headerLeft.appendChild(avatar);
    headerLeft.appendChild(titleBlock);
    var collapseBtn = createEl("button", {
      className: "ai-chat-collapse-btn",
      type: "button",
      "aria-label": msg("collapse"),
    }, svgIcon("chevronDown"));
    header.appendChild(headerLeft);
    header.appendChild(collapseBtn);
    window.appendChild(header);

    // Messages
    var messages = createEl("div", { className: "ai-chat-messages" });
    window.appendChild(messages);

    // Error
    var errorBar = createEl("div", { className: "ai-chat-error", style: "display:none" });
    window.appendChild(errorBar);

    // Form
    var form = createEl("form", { className: "ai-chat-form" });
    var input = createEl("input", {
      className: "ai-chat-input",
      type: "text",
      placeholder: msg("placeholder"),
      autocomplete: "off",
    });
    var sendBtn = createEl("button", {
      className: "ai-chat-send-btn",
      type: "submit",
      "aria-label": msg("sendLabel"),
      disabled: "disabled",
    }, svgIcon("send"));
    form.appendChild(input);
    form.appendChild(sendBtn);
    window.appendChild(form);

    widget.appendChild(window);

    // Toggle button
    var toggle = createEl("button", {
      className: "ai-chat-toggle",
      type: "button",
    }, [svgIcon("sparkles"), createEl("span", null, msg("button"))]);
    widget.appendChild(toggle);

    document.body.appendChild(widget);

    // Store refs
    elements.widget = widget;
    elements.window = window;
    elements.messages = messages;
    elements.errorBar = errorBar;
    elements.form = form;
    elements.input = input;
    elements.sendBtn = sendBtn;
    elements.toggle = toggle;
    elements.collapseBtn = collapseBtn;
  }

  // ── Render messages ────────────────────────────────────
  function renderMessages() {
    var container = elements.messages;
    container.innerHTML = "";

    state.rows.forEach(function (row) {
      var div = createEl("div", {
        className: "ai-msg ai-msg--" + row.role,
      }, row.content);
      container.appendChild(div);
    });

    if (state.busy) {
      var thinking = createEl("div", { className: "ai-msg--thinking" }, [
        svgIcon("spinner"),
        document.createTextNode(msg("thinking")),
      ]);
      container.appendChild(thinking);
    }

    container.scrollTop = container.scrollHeight;
  }

  // ── Show error ─────────────────────────────────────────
  function showError(text) {
    var bar = elements.errorBar;
    bar.textContent = text;
    bar.style.display = "block";
  }

  function hideError() {
    elements.errorBar.style.display = "none";
  }

  // ── API call ───────────────────────────────────────────
  function sendMessage(message) {
    if (state.busy || !message.trim()) return;

    var userRow = { role: "user", content: message.trim() };
    state.rows.push(userRow);
    state.busy = true;
    hideError();

    elements.input.value = "";
    elements.sendBtn.disabled = true;
    renderMessages();

    var history = state.rows
      .slice(1, -1) // exclude welcome and current user msg
      .slice(-CONFIG.maxHistory)
      .map(function (r) { return { role: r.role, content: r.content }; });

    var payload = JSON.stringify({
      message: message.trim(),
      language: getLanguage(),
      page: window.location.pathname,
      fid: CONFIG.fid,
      firma: CONFIG.firma,
      history: history,
    });

    fetch(CONFIG.apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: payload,
    })
      .then(function (res) {
        if (!res.ok)
          return res.text().then(function (text) {
            throw new Error("HTTP " + res.status + ": " + text);
          });
        return res.json();
      })
      .then(function (data) {
        state.rows.push({ role: "assistant", content: data.answer || data.message || "" });
        state.busy = false;
        renderMessages();
      })
      .catch(function (err) {
        state.busy = false;
        showError(msg("error"));
        renderMessages();
        console.error("[AI Chat] API error:", err);
      });
  }

  // ── Event bindings ─────────────────────────────────────
  function bindEvents() {
    elements.toggle.addEventListener("click", function () {
      state.open = !state.open;
      elements.window.classList.toggle("open", state.open);
      if (state.open) {
        elements.input.focus();
        elements.messages.scrollTop = elements.messages.scrollHeight;
      }
    });

    elements.collapseBtn.addEventListener("click", function () {
      state.open = false;
      elements.window.classList.remove("open");
    });

    elements.form.addEventListener("submit", function (e) {
      e.preventDefault();
      var val = elements.input.value.trim();
      if (val && !state.busy) {
        sendMessage(val);
      }
    });

    elements.input.addEventListener("input", function () {
      var val = elements.input.value.trim();
      elements.sendBtn.disabled = val.length < 2 || state.busy;
    });
  }

  // ── Init ───────────────────────────────────────────────
  function init(userConfig) {
    if (userConfig) {
      if (userConfig.fid) CONFIG.fid = userConfig.fid;
      if (userConfig.firma !== undefined) CONFIG.firma = userConfig.firma;
      if (userConfig.apiUrl) CONFIG.apiUrl = userConfig.apiUrl;
    }

    state.rows = [{ role: "assistant", content: msg("welcome") }];

    buildWidget();
    renderMessages();
    bindEvents();
  }

  // Expose to global scope so Blade can pass config
  window.AiChatWidget = { init: init };
})();
