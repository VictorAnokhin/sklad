/**
 * AI Chat Widget — vanilla JS, no framework dependencies.
 * Communicates with the Laravel /api/ai/chat endpoint.
 * Supports voice input (Speech-to-Text) and voice output (Text-to-Speech).
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
        voiceInput: "Голосовой ввод",
        voiceInputActive: "Говорите…",
        voiceInputUnsupported: "Голосовой ввод не поддерживается в этом браузере",
        listen: "Озвучить ответ",
        stopListen: "Остановить",
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
        voiceInput: "Голосовий ввід",
        voiceInputActive: "Говоріть…",
        voiceInputUnsupported: "Голосовий ввід не підтримується в цьому браузері",
        listen: "Озвучити відповідь",
        stopListen: "Зупинити",
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
        voiceInput: "Voice input",
        voiceInputActive: "Speak now…",
        voiceInputUnsupported: "Voice input is not supported in this browser",
        listen: "Read aloud",
        stopListen: "Stop",
      },
    },
  };

  var state = {
    open: false,
    busy: false,
    rows: [],
    recording: false,
    speakingIndex: -1, // index of message currently being spoken, -1 = none
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
      mic: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="11" rx="3"/><path d="M5 10a7 7 0 0 0 14 0"/><line x1="12" y1="19" x2="12" y2="22"/></svg>',
      micOff: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="2" x2="22" y2="22"/><path d="M15.5 10.5V10a3.5 3.5 0 0 0-6.4-1.9"/><path d="M5 10a7 7 0 0 0 10.5 5.5"/><line x1="12" y1="19" x2="12" y2="22"/></svg>',
      speaker: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>',
      speakerOff: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>',
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

    // Microphone button for voice input
    var micBtn = createEl("button", {
      className: "ai-chat-mic-btn",
      type: "button",
      "aria-label": msg("voiceInput"),
      title: msg("voiceInput"),
    }, svgIcon("mic"));
    // Hide mic button if SpeechRecognition is not supported
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      micBtn.style.display = "none";
    }

    var sendBtn = createEl("button", {
      className: "ai-chat-send-btn",
      type: "submit",
      "aria-label": msg("sendLabel"),
      disabled: "disabled",
    }, svgIcon("send"));
    form.appendChild(input);
    form.appendChild(micBtn);
    form.appendChild(sendBtn);
    window.appendChild(form);

    // Store refs
    elements.micBtn = micBtn;

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

    state.rows.forEach(function (row, idx) {
      var div = createEl("div", {
        className: "ai-msg ai-msg--" + row.role,
      }, row.content);

      // Add speaker button to assistant messages for TTS
      if (row.role === "assistant" && row.content) {
        var isSpeaking = state.speakingIndex === idx;
        var speakBtn = createEl("button", {
          className: "ai-msg-speak-btn" + (isSpeaking ? " is-speaking" : ""),
          type: "button",
          "aria-label": isSpeaking ? msg("stopListen") : msg("listen"),
          title: isSpeaking ? msg("stopListen") : msg("listen"),
          dataset: { msgIndex: idx },
        }, svgIcon(isSpeaking ? "speakerOff" : "speaker"));

        speakBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          var index = parseInt(this.dataset.msgIndex, 10);
          if (state.speakingIndex === index) {
            stopSpeaking();
          } else {
            speakRow(index);
          }
        });

        // Wrap message content and button
        var wrapper = createEl("div", { className: "ai-msg-wrapper" });
        wrapper.appendChild(div);
        wrapper.appendChild(speakBtn);
        container.appendChild(wrapper);
      } else {
        container.appendChild(div);
      }
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

  // ── Speech-to-Text (Voice Input) ──────────────────────
  var recognition = null;

  function startVoiceInput() {
    if (state.recording || state.busy) return;

    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      showError(msg("voiceInputUnsupported"));
      return;
    }

    try {
      recognition = new SpeechRecognition();
      recognition.lang = getLanguage() === "ua" ? "uk-UA" : getLanguage() === "en" ? "en-US" : "ru-RU";
      recognition.continuous = false;
      recognition.interimResults = true;
      recognition.maxAlternatives = 1;

      state.recording = true;
      elements.micBtn.classList.add("is-recording");
      elements.micBtn.innerHTML = "";
      elements.micBtn.appendChild(svgIcon("micOff"));
      elements.micBtn.title = msg("voiceInputActive");
      elements.input.placeholder = msg("voiceInputActive");

      recognition.onresult = function (event) {
        var transcript = "";
        for (var i = event.resultIndex; i < event.results.length; i++) {
          transcript += event.results[i][0].transcript;
        }
        elements.input.value = transcript;
        elements.sendBtn.disabled = transcript.trim().length < 2 || state.busy;
      };

      recognition.onerror = function (event) {
        console.error("[AI Chat] Speech recognition error:", event.error);
        stopVoiceInput();
        if (event.error === "no-speech") {
          // silently reset
        } else if (event.error === "not-allowed") {
          showError(msg("voiceInputUnsupported"));
        }
      };

      recognition.onend = function () {
        // Auto-submit if there's meaningful text when recognition ends
        var text = elements.input.value.trim();
        if (text.length >= 2 && !state.busy) {
          sendMessage(text);
        }
        stopVoiceInput();
      };

      recognition.start();
    } catch (err) {
      console.error("[AI Chat] Failed to start speech recognition:", err);
      stopVoiceInput();
    }
  }

  function stopVoiceInput() {
    if (recognition) {
      try { recognition.abort(); } catch (e) { /* ignore */ }
      recognition = null;
    }
    state.recording = false;
    if (elements.micBtn) {
      elements.micBtn.classList.remove("is-recording");
      elements.micBtn.innerHTML = "";
      elements.micBtn.appendChild(svgIcon("mic"));
      elements.micBtn.title = msg("voiceInput");
    }
    if (elements.input) {
      elements.input.placeholder = msg("placeholder");
    }
  }

  // ── Text-to-Speech (Voice Output) ─────────────────────
  var currentUtterance = null;

  function speakRow(index) {
    var row = state.rows[index];
    if (!row || row.role !== "assistant" || !row.content) return;

    // Stop any previous speech
    stopSpeaking();

    var text = row.content;
    state.speakingIndex = index;
    renderMessages();

    try {
      var langMap = { ru: "ru-RU", ua: "uk-UA", en: "en-US" };
      var utterance = new SpeechSynthesisUtterance(text);
      utterance.lang = langMap[getLanguage()] || "ru-RU";
      utterance.rate = 1.0;
      utterance.pitch = 1.0;

      utterance.onend = function () {
        state.speakingIndex = -1;
        currentUtterance = null;
        renderMessages();
      };

      utterance.onerror = function () {
        state.speakingIndex = -1;
        currentUtterance = null;
        renderMessages();
      };

      currentUtterance = utterance;
      window.speechSynthesis.speak(utterance);
    } catch (err) {
      console.error("[AI Chat] TTS error:", err);
      state.speakingIndex = -1;
      renderMessages();
    }
  }

  function stopSpeaking() {
    if (currentUtterance) {
      window.speechSynthesis.cancel();
      currentUtterance = null;
    }
    if (state.speakingIndex !== -1) {
      state.speakingIndex = -1;
      renderMessages();
    }
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

    // Microphone button: toggle voice input
    if (elements.micBtn) {
      elements.micBtn.addEventListener("click", function () {
        if (state.recording) {
          stopVoiceInput();
        } else {
          startVoiceInput();
        }
      });
    }
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
