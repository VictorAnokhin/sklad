{{-- 
  AI Knowledge Base Manager partial
  Usage: @include('partials.ai_knowledge_base', ['fid' => 1])
  If $fid is omitted, defaults to 1.
  Optionally pass $firma to associate with a specific company.
--}}

<link href="{{ asset('css/ai-knowledge-base.css') }}" rel="stylesheet">
<script src="{{ asset('js/ai-knowledge-base.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof AiKnowledgeBase !== 'undefined') {
    AiKnowledgeBase.init({
      fid: {{ $fid ?? 1 }},
      firma: {{ $firma ?? session('fid', 'null') }},
      apiUrl: '/api/ai/knowledge-base',
      categoriesApiUrl: '/api/ai/knowledge-base/categories',
    });
  }
});
</script>
