<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ManagerAiBridgeClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendChatMessage(array $payload): array
    {
        $sessionToken = trim((string) ($payload['session_token'] ?? ''));
        if ($sessionToken === '') {
            $sessionToken = (string) Str::uuid();
        }

        $body = trim((string) ($payload['message'] ?? ''));
        if ($body === '') {
            throw new RuntimeException('ManagerAI bridge message is empty.');
        }

        $headers = [
            'X-ManagerAI-Bridge-Secret' => $this->secret(),
        ];

        $forwardedHost = trim((string) config('services.manager_ai.forwarded_host', ''));
        if ($forwardedHost !== '') {
            $headers['X-Forwarded-Host'] = $forwardedHost;
        }

        $requestBody = [
            'companyId' => $this->companyId(),
            'externalUserId' => $this->externalUserId($payload, $sessionToken),
            'body' => $this->messageBody($payload, $body),
            'mode' => $this->mode($payload, $body),
        ];

        $targetIssueId = $this->targetIssueId();
        if ($targetIssueId !== '') {
            $requestBody['targetIssueId'] = $targetIssueId;
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->withHeaders($headers)
            ->post($this->url().'/api/external/site-chat/messages', $requestBody);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'ManagerAI bridge failed with HTTP %s: %s',
                $response->status(),
                $response->body()
            ));
        }

        $data = $response->json();

        return [
            'session_token' => $sessionToken,
            'answer' => $this->answer($data),
            'provider' => 'manager-ai',
            'model' => 'cto/opencode_local',
            'usage' => [],
            'actions' => [],
            'manager_ai' => [
                'issue_id' => data_get($data, 'issue.id'),
                'execution_issue_id' => data_get($data, 'executionIssue.id'),
                'manager_agent_id' => data_get($data, 'managerAgent.id'),
                'wakeup_run_id' => data_get($data, 'wakeupRunId'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendKnowledgeGapRequest(array $payload): array
    {
        $payload['manager_ai_mode'] = 'execute';
        $payload['message'] = $this->knowledgeGapMessage($payload);

        return $this->sendChatMessage($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendUrlResearchRequest(array $payload): array
    {
        $payload['manager_ai_mode'] = 'execute';
        $payload['message'] = $this->urlResearchMessage($payload);

        return $this->sendChatMessage($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendBusinessAssistantRequest(array $payload): array
    {
        $payload['manager_ai_mode'] = 'execute';
        $payload['message'] = $this->businessAssistantMessage($payload);

        return $this->sendChatMessage($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendWebchatIntelligence(array $payload): array
    {
        $summary = $payload['summary'] ?? null;
        if (! is_array($summary)) {
            throw new RuntimeException('ManagerAI webchat intelligence summary is empty.');
        }

        $fid = (int) ($payload['fid'] ?? data_get($summary, 'fid', 0));
        if ($fid < 1) {
            throw new RuntimeException('ManagerAI webchat intelligence fid is empty.');
        }

        $headers = [
            'X-ManagerAI-Bridge-Secret' => $this->secret(),
        ];

        $forwardedHost = trim((string) config('services.manager_ai.forwarded_host', ''));
        if ($forwardedHost !== '') {
            $headers['X-Forwarded-Host'] = $forwardedHost;
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->withHeaders($headers)
            ->post($this->url().'/api/external/webchat/intelligence', [
                'companyId' => $this->companyId(),
                'fid' => $fid,
                'siteDomain' => $payload['site_domain'] ?? null,
                'summary' => $summary,
                'mode' => in_array(($payload['mode'] ?? 'discuss'), ['execute', 'discuss'], true)
                    ? $payload['mode']
                    : 'discuss',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'ManagerAI webchat intelligence bridge failed with HTTP %s: %s',
                $response->status(),
                $response->body()
            ));
        }

        $data = $response->json();

        return [
            'answer' => 'Webchat intelligence передан агенту ManagerAI для UX-анализа и рекомендаций.',
            'provider' => 'manager-ai',
            'model' => 'manager/webchat-intelligence',
            'usage' => [],
            'actions' => [],
            'manager_ai' => [
                'issue_id' => data_get($data, 'issue.id'),
                'comment_id' => data_get($data, 'comment.id'),
                'manager_agent_id' => data_get($data, 'managerAgent.id'),
                'wakeup_run_id' => data_get($data, 'wakeupRunId'),
            ],
        ];
    }

    public function enabled(): bool
    {
        return (bool) config('services.manager_ai.enabled', false)
            && $this->url() !== ''
            && $this->companyId() !== ''
            && $this->secret() !== '';
    }

    public function fallbackToLocal(): bool
    {
        return (bool) config('services.manager_ai.fallback_to_local', true);
    }

    private function url(): string
    {
        return rtrim((string) config('services.manager_ai.url', ''), '/');
    }

    private function companyId(): string
    {
        return trim((string) config('services.manager_ai.company_id', ''));
    }

    private function secret(): string
    {
        return trim((string) config('services.manager_ai.bridge_secret', ''));
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.manager_ai.timeout', 10));
    }

    private function targetIssueId(): string
    {
        return trim((string) config('services.manager_ai.webchat_issue_id', ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function externalUserId(array $payload, string $sessionToken): string
    {
        $parts = [
            'laravel-api',
            'session:'.$sessionToken,
        ];

        foreach (['fid', 'firma', 'user_id', 'wallet'] as $key) {
            $value = trim((string) ($payload[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $key.':'.$value;
            }
        }

        return Str::limit(implode('|', $parts), 200, '');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function messageBody(array $payload, string $body): string
    {
        $context = [
            'source' => 'av8capital.space webchat',
            'language' => $payload['language'] ?? null,
            'page' => $payload['page'] ?? null,
            'fid' => $payload['fid'] ?? null,
            'firma' => $payload['firma'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'wallet' => $payload['wallet'] ?? null,
            'knowledge_callback_url' => $this->knowledgeCallbackUrl(),
            'knowledge_callback_header' => 'X-ManagerAI-Bridge-Secret',
        ];

        $context = array_filter($context, static fn ($value) => $value !== null && $value !== '');

        return "Контекст вебчата:\n".
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
            "\n\nСообщение пользователя:\n".
            $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function knowledgeGapMessage(array $payload): string
    {
        $question = trim((string) ($payload['original_question'] ?? $payload['message'] ?? ''));
        $localAnswer = trim((string) ($payload['local_answer'] ?? ''));
        $fid = (int) ($payload['fid'] ?? 0);
        $sessionToken = trim((string) ($payload['session_token'] ?? ''));
        $apiBaseUrl = $this->knowledgeCallbackBaseUrl();
        $goodsAccessQuery = $this->managerAiGoodsAccessQuery($fid);

        return trim(<<<TEXT
В вебчате не нашлось надежного ответа в базе знаний laravel-api.

Задача для manager-ai:
1. Найди или подготовь проверенный ответ на вопрос пользователя.
2. Пополни базу знаний laravel-api через POST {$this->knowledgeCallbackUrl()}.
3. Если вопрос о товаре, ищи товар в таблице comp через защищенный API:
   GET {$apiBaseUrl}/api/goods/manager-ai/search?fid={$fid}&{$goodsAccessQuery}&q=<поисковый запрос>
   GET {$apiBaseUrl}/api/goods/manager-ai/<id или nickname>?fid={$fid}&{$goodsAccessQuery}
   GET {$apiBaseUrl}/api/goods/manager-ai/items/by-pnum?fid={$fid}&{$goodsAccessQuery}&pnum=<id товара из comp.id>
   GET {$apiBaseUrl}/api/goods/manager-ai/items/by-category?fid={$fid}&{$goodsAccessQuery}&idglava=<igla из URL>&idcaption=<idcapt из URL>
   Эти URL уже содержат подписанный доступ для поиска товара. Если используешь header, передай X-ManagerAI-Bridge-Secret: тот же bridge secret.
   Для адреса /goods?igla=2219&idcapt=2171 используй idglava=2219 и idcaption=2171. При передаче обоих значений API применяет строгую фильтрацию по их паре.

   API вернет JSON с полями:
   {
     "id": 123,
     "fid": {$fid},
     "name": "наименование товара",
     "description": "описание товара",
     "link": "/goods/product-code",
     "url": "/goods/product-code"
   }

4. После поиска товара пополни базу знаний JSON-запросом:
   {
     "fid": {$fid},
     "session_token": "{$sessionToken}",
     "title": "короткий заголовок",
     "content": "самодостаточная запись знания с наименованием, описанием и ссылкой на товар",
     "category": "manager_ai_product",
     "answer": "короткий ответ для пользователя"
   }
5. В запросе обязательно передай header X-ManagerAI-Bridge-Secret с тем же bridge secret.
6. После успешной записи laravel-api вернет команду вебчату read_knowledge_base.

Вопрос пользователя:
{$question}

Локальный ответ вебчата, который признан недостаточным:
{$localAnswer}
TEXT);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function urlResearchMessage(array $payload): string
    {
        $url = trim((string) ($payload['url'] ?? ''));
        $question = trim((string) ($payload['original_question'] ?? $payload['message'] ?? ''));
        $fid = (int) ($payload['fid'] ?? 0);
        $sessionToken = trim((string) ($payload['session_token'] ?? ''));

        return trim(<<<TEXT
Пользователь вебчата laravel-api указал URL для изучения: {$url}

Задача для manager-ai:
1. Открой и изучи этот URL.
2. Подготовь краткое, проверяемое описание найденной информации.
3. Сохрани результат в базу знаний laravel-api через POST {$this->knowledgeCallbackUrl()}.
4. Используй header X-ManagerAI-Bridge-Secret с bridge secret.
5. Тело callback-запроса:
   {
     "fid": {$fid},
     "session_token": "{$sessionToken}",
     "title": "короткий заголовок страницы или исследования",
     "content": "самодостаточная запись знания с URL источника, фактами и кратким выводом",
     "category": "manager_ai_web_research",
     "answer": "короткий ответ для пользователя"
   }
6. Если страница недоступна, сохрани в issue комментарий с причиной и верни пользователю понятный статус.

Исходное сообщение пользователя:
{$question}
TEXT);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function businessAssistantMessage(array $payload): string
    {
        $question = trim((string) ($payload['original_question'] ?? $payload['message'] ?? ''));
        $agent = trim((string) ($payload['target_agent'] ?? 'WebChatAgent'));
        if (! in_array($agent, ['WebChatAgent', 'FinancialAnalyst'], true)) {
            $agent = 'WebChatAgent';
        }

        $fid = (int) ($payload['fid'] ?? 0);
        $sessionToken = trim((string) ($payload['session_token'] ?? ''));
        $visitorUid = trim((string) ($payload['visitor_uid'] ?? ''));
        $page = trim((string) ($payload['page'] ?? ''));
        $pageUrl = trim((string) ($payload['page_url'] ?? ''));
        $siteDomain = trim((string) ($payload['site_domain'] ?? ''));
        $language = trim((string) ($payload['language'] ?? 'ru'));
        $delegationReason = trim((string) ($payload['delegation_reason'] ?? 'business_assistant'));

        $agentScope = $agent === 'FinancialAnalyst'
            ? 'Фокус: финансовая аналитика, отчеты, cash flow, P&L, balance sheet, finance, unit economics, риски и выводы для собственника. Не анализируй проекты вне указанного fid.'
            : 'Фокус: webchat, поведение посетителя, текущая страница, поиск свежей информации, UX-навигация, потребности клиента и рекомендации для следующего действия.';

        $unclearInstruction = $delegationReason === 'unclear_question'
            ? "\nОсобый режим: вопрос пользователя неоднозначный или обрывочный. Восстанови вероятный смысл по странице, fid, истории webchat/visitor context и задачам системы. Если уверенности недостаточно, верни короткое уточнение с 1-2 конкретными вариантами, что пользователь мог иметь в виду."
            : '';

        return trim(<<<TEXT
Запрос из WebChat laravel-api для агента {$agent} на ai.autoagent.in.ua.

{$agentScope}
{$unclearInstruction}

Задача:
1. Изучи запрос пользователя и доступный контекст.
2. Если нужна свежая или обновленная информация, получи ее своими доступными инструментами.
3. Если запрос про финансовую аналитику, используй только данные текущего fid={$fid}.
4. Подготовь короткий ответ для пользователя вебчата: простым языком, с выводами и ближайшим действием.
5. Если результат полезен для будущих сессий, сохрани краткое знание в laravel-api через POST {$this->knowledgeCallbackUrl()}.
6. В callback используй header X-ManagerAI-Bridge-Secret и JSON:
   {
     "fid": {$fid},
     "session_token": "{$sessionToken}",
     "title": "короткий заголовок",
     "content": "самодостаточная запись: вопрос, выводы, данные, дата проверки, ограничения",
     "category": "business_assistant_{$agent}",
     "answer": "короткий ответ для пользователя"
   }

Контекст:
- fid: {$fid}
- session_token: {$sessionToken}
- visitor_uid: {$visitorUid}
- page: {$page}
- page_url: {$pageUrl}
- site_domain: {$siteDomain}
- language: {$language}
- delegation_reason: {$delegationReason}

Запрос пользователя:
{$question}
TEXT);
    }

    private function knowledgeCallbackUrl(): string
    {
        return $this->knowledgeCallbackBaseUrl() . '/api/ai/knowledge-base/manager-ai-ingest';
    }

    private function knowledgeCallbackBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('services.manager_ai.laravel_api_url', ''), '/');
        if ($baseUrl === '') {
            $baseUrl = rtrim((string) config('app.url', ''), '/');
        }

        return $baseUrl;
    }

    private function managerAiGoodsAccessQuery(int $fid): string
    {
        $expires = now()->addMinutes(30)->timestamp;
        $token = hash_hmac('sha256', $this->managerAiGoodsAccessPayload($fid, $expires), $this->secret());

        return http_build_query([
            'manager_ai_expires' => $expires,
            'manager_ai_token' => $token,
        ]);
    }

    private function managerAiGoodsAccessPayload(int $fid, int $expires): string
    {
        return implode('|', ['manager-ai-goods', $fid, $expires]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mode(array $payload, string $body): string
    {
        $explicit = trim((string) ($payload['manager_ai_mode'] ?? ''));
        if (in_array($explicit, ['execute', 'discuss'], true)) {
            return $explicit;
        }

        return preg_match('/^\s*(?:\/)?(?:приступай|сделай|выполни|запусти|начинай|execute|run|do it)\b/iu', $body) === 1
            ? 'execute'
            : 'discuss';
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function answer(?array $data): string
    {
        $executionIssueId = data_get($data, 'executionIssue.id');

        if (is_string($executionIssueId) && $executionIssueId !== '') {
            return 'Задача передана агенту ManagerAI, CTO приступает к выполнению.';
        }

        return 'Сообщение передано агенту ManagerAI. Он продолжит обсуждение в рабочем контексте.';
    }
}
