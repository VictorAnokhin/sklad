{{--
  AI Chat Widget partial

  Usage (рекомендуемый — через data-атрибуты):
      @include('partials.ai_chat_widget')

  Usage (явное указание — для обратной совместимости):
      @include('partials.ai_chat_widget', ['fid' => 12, 'firma' => 5])

  Приоритет получения fid:
    1. Явно переданный в AiChatWidget.init({ fid })
    2. data-fid на #ai-chat-config (скрытый конфиг-элемент ниже)
    3. data-fid на .ai-chat-widget (корневой элемент, создаётся JS)
    4. session('fid') на backend, если frontend не передал fid
--}}

{{-- Скрытый конфиг-элемент для передачи параметров в JS-виджет --}}
<div id="ai-chat-config"
     data-fid="{{ $fid ?? session('fid') ?? '' }}"
     data-firma="{{ $firma ?? session('firma', 'null') }}"
     style="display:none;"
     aria-hidden="true"></div>

<link href="{{ asset('css/ai-chat-widget.css') }}?v={{ filemtime(public_path('css/ai-chat-widget.css')) }}" rel="stylesheet">
<script src="{{ asset('js/ai-chat-widget.js') }}?v={{ filemtime(public_path('js/ai-chat-widget.js')) }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof AiChatWidget !== 'undefined') {
    AiChatWidget.init({
      // fid и firma теперь читаются из #ai-chat-config (data-атрибуты),
      // что позволяет проекту (fid) определяться из сессии.
      // Если нужно явно переопределить — передайте fid/firma сюда.
      apiUrl: '/api/ai/chat',
    });
  }
});
</script>
