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

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->withHeaders($headers)
            ->post($this->url().'/api/external/site-chat/messages', [
                'companyId' => $this->companyId(),
                'externalUserId' => $this->externalUserId($payload, $sessionToken),
                'body' => $this->messageBody($payload, $body),
                'mode' => $this->mode($payload, $body),
            ]);

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
            'source' => 'laravel-api webchat',
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

        return trim(<<<TEXT
В вебчате не нашлось надежного ответа в базе знаний laravel-api.

Задача для manager-ai:
1. Найди или подготовь проверенный ответ на вопрос пользователя.
2. Пополни базу знаний laravel-api через POST {$this->knowledgeCallbackUrl()}.
3. Передай JSON:
   {
     "fid": {$fid},
     "session_token": "{$sessionToken}",
     "title": "короткий заголовок",
     "content": "самодостаточная запись знания",
     "category": "manager_ai_research",
     "answer": "короткий ответ для пользователя"
   }
4. В запросе обязательно передай header X-ManagerAI-Bridge-Secret с тем же bridge secret.
5. После успешной записи laravel-api вернет команду вебчату read_knowledge_base.

Вопрос пользователя:
{$question}

Локальный ответ вебчата, который признан недостаточным:
{$localAnswer}
TEXT);
    }

    private function knowledgeCallbackUrl(): string
    {
        $baseUrl = rtrim((string) config('services.manager_ai.laravel_api_url', ''), '/');
        if ($baseUrl === '') {
            $baseUrl = rtrim((string) config('app.url', ''), '/');
        }

        return $baseUrl . '/api/ai/knowledge-base/manager-ai-ingest';
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
