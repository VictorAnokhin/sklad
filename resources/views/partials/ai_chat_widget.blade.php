{{-- 
  AI Chat Widget partial
  Usage: @include('partials.ai_chat_widget', ['fid' => 1])
  If $fid is omitted, defaults to 1.
  Optionally pass $firma to associate with a specific company.
--}}

<link href="{{ asset('css/ai-chat-widget.css') }}" rel="stylesheet">
<script src="{{ asset('js/ai-chat-widget.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof AiChatWidget !== 'undefined') {
    AiChatWidget.init({
      fid: {{ $fid ?? 1 }},
      firma: {{ $firma ?? session('fid', 'null') }},
      apiUrl: '/api/ai/chat',
    });
  }
});
</script>
