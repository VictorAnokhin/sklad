<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\AiTool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatService
{
    private AiClientInterface $ai;

    public function __construct(
        private readonly AiKnowledgeService $knowledgeService,
        private readonly DbQueryService $dbQuery,
        private readonly AiClientFactory $aiFactory,
        private readonly AgentOrchestrator $orchestrator,
    ) {
        // По умолчанию используем канал 'web_chat'
        $this->ai = $this->aiFactory->make('web_chat');
    }

    /**
     * Переключить AI-клиента на другой канал/провайдер.
     *
     * @param  string  $channel  Ключ канала из config('ai.channels')
     * @return $this
     */
    public function useChannel(string $channel): static
    {
        $this->ai = $this->aiFactory->make($channel);

        return $this;
    }

    /**
     * Переключить AI-клиента на конкретного провайдера.
     *
     * @param  string  $provider  Ключ провайдера (deepseek, openai, atoma)
     * @param  string|null  $model  Опционально: модель
     * @return $this
     */
    public function useProvider(string $provider, ?string $model = null): static
    {
        $this->ai = $this->aiFactory->makeForProvider($provider);

        if ($model !== null) {
            $this->ai->setModel($model);
        }

        return $this;
    }

    /**
     * Получить текущего AI-клиента.
     */
    public function getAiClient(): AiClientInterface
    {
        return $this->ai;
    }

    // ── Управление сессиями ─────────────────────────────────────────────

    /**
     * Создать или получить существующую сессию.
     *
     * @param  array<string, mixed>  $params
     */
    public function resolveSession(array $params = []): ChatSession
    {
        $token = trim((string) ($params['session_token'] ?? ''));

        if ($token !== '') {
            $session = ChatSession::resolveByToken($token);
            if ($session !== null) {
                return $session;
            }
        }

        return ChatSession::createSession([
            'user_id' => isset($params['user_id']) ? (int) $params['user_id'] : null,
            'fid' => isset($params['fid']) ? (int) $params['fid'] : null,
            'firma' => isset($params['firma']) ? (int) $params['firma'] : null,
            'wallet' => trim((string) ($params['wallet'] ?? '')),
            'language' => trim((string) ($params['language'] ?? 'ru')),
            'page' => trim((string) ($params['page'] ?? '')),
        ]);
    }

    /**
     * Получить сессии пользователя.
     *
     * @return Collection<int, ChatSession>
     */
    public function getUserSessions(?int $userId, string $sessionToken = '', ?int $fid = null, int $limit = 50): Collection
    {
        $query = ChatSession::active()->orderByDesc('created_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($sessionToken !== '') {
            $query->where('session_token', $sessionToken);
        } else {
            return collect();
        }

        if ($fid !== null) {
            $query->where('fid', $fid);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Найти сессию по токену.
     */
    public function findSession(string $sessionToken): ?ChatSession
    {
        return ChatSession::where('session_token', $sessionToken)->first();
    }

    /**
     * Архивировать сессию.
     */
    public function archiveSession(string $sessionToken): bool
    {
        $session = $this->findSession($sessionToken);
        if ($session === null) {
            return false;
        }

        return $session->update(['status' => 'archived']);
    }

    /**
     * Удалить сессию и все её сообщения.
     */
    public function deleteSession(string $sessionToken): bool
    {
        $session = $this->findSession($sessionToken);
        if ($session === null) {
            return false;
        }

        return $session->delete() !== null;
    }

    // ── Отправка сообщений ──────────────────────────────────────────────

    /**
     * Отправить сообщение в чат и получить ответ от AI.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMessage(array $payload): array
    {
        $session = $this->resolveSession($payload);

        $message = trim((string) ($payload['message'] ?? ''));
        $language = trim((string) ($payload['language'] ?? 'ru'));
        $page = trim((string) ($payload['page'] ?? 'unknown'));
        $wallet = trim((string) ($payload['wallet'] ?? ''));
        $fid = (int) ($payload['fid'] ?? 0);
        $firma = isset($payload['firma']) ? (int) $payload['firma'] : null;
        $useDbTools = (bool) ($payload['use_db_tools'] ?? true);

        // Определяем fid
        if ($fid <= 0) {
            $fid = (int) ($session->fid ?? 0);
        }
        if ($fid <= 0) {
            $fid = (int) (session('fid', 0));
        }

        // Определяем firma
        if ($firma === null || $firma <= 0) {
            $firma = $session->firma ?? null;
        }
        if ($firma === null || $firma <= 0) {
            $firma = (int) (session('fid', 0)) ?: null;
        }

        // Обновляем данные сессии
        $session->update([
            'fid' => $fid,
            'firma' => $firma,
            'wallet' => $wallet ?: $session->wallet,
            'language' => $language,
            'page' => $page ?: $session->page,
        ]);

        // Обновляем заголовок сессии первым сообщением
        $session->updateTitle($message);

        // Сохраняем сообщение пользователя
        $this->saveMessage($session->id, $fid, $firma, 'user', $message);

        // Загружаем контекст из базы знаний
        $knowledgeContext = $this->loadKnowledgeContext($fid);

        // Формируем system prompt
        $instructions = $this->buildSystemPrompt($language, $fid, $knowledgeContext, $useDbTools);

        // Загружаем историю для AI
        $history = $session->getHistoryForAi(20);

        // Добавляем текущее сообщение с контекстом страницы
        $history[] = [
            'role' => 'user',
            'content' => "Контекст страницы: {$page}\n".
                'Кошелек пользователя: '.($wallet !== '' ? $wallet : 'не подключен')."\n".
                "ID проекта (fid): {$fid}\n".
                ($firma !== null && $firma > 0 ? "ID компании (firma): {$firma}\n" : '').
                "Вопрос пользователя: {$message}",
        ];

        // Получаем опции канала из конфига
        $channelConfig = $this->aiFactory->getChannelConfig('web_chat');
        $options = [
            'temperature' => $channelConfig['temperature'] ?? 0.35,
            'max_tokens'  => $channelConfig['max_tokens'] ?? 1500,
        ];

        // ── Отправка с function calling или обычный запрос ──
        if ($useDbTools && $fid > 0) {
            // Базовые инструменты из DbQueryService
            $tools = $this->dbQuery->getTools();

            // Пользовательские инструменты из таблицы ai_tools
            $customTools = AiTool::getToolsForPrompt($fid);
            if (!empty($customTools)) {
                $tools = array_merge($tools, $customTools);
            }

            $toolExecutor = function (string $name, array $arguments) use ($fid, $firma): string {
                return $this->dbQuery->executeTool($fid, $firma, $name, $arguments);
            };

            $result = $this->ai->chatWithTools($instructions, $history, $tools, $toolExecutor, $options);
        } else {
            $result = $this->ai->chat($instructions, $history, $options);
        }

        // Сохраняем ответ ассистента
        $this->saveMessage($session->id, $fid, $firma, 'assistant', $result['answer'], [
            'model' => $result['model'] ?? null,
            'usage' => $result['usage'] ?? null,
            'provider' => $this->ai->getProviderName(),
            'db_tools_used' => $useDbTools && $fid > 0,
        ]);

        // ── Автообучение ──
        $this->knowledgeService->autoLearn($fid, $session->getHistoryForAi(5));

        if ($this->shouldDelegateToTelegramAgent($message, (string) $result['answer'], $knowledgeContext)) {
            $task = $this->orchestrator->createTask(
                sourceAgent: 'frontend',
                targetAgent: 'telegram',
                fid: $fid,
                taskType: 'complex_question',
                inputData: [
                    'query' => $message,
                    'question' => $message,
                    'language' => $language,
                    'response_channel' => 'web_chat',
                ],
                sessionToken: $session->session_token,
                priority: 1,
            );

            $delegatedAnswer = "⏳ В базе проекта не нашлось достаточной информации. Я передал вопрос TelegramAgent; если он не найдёт быстрый ответ, он спросит оператора в Telegram.";

            $this->saveMessage($session->id, $fid, $firma, 'assistant', $delegatedAnswer, [
                'source' => 'telegram_agent_delegation',
                'task_uuid' => $task->uuid,
            ]);

            return [
                'session_token' => $session->session_token,
                'answer' => $delegatedAnswer,
                'provider' => $this->ai->getProviderName(),
                'model' => $result['model'],
                'usage' => $result['usage'],
                'db_tools_enabled' => $useDbTools && $fid > 0,
                'delegated' => true,
                'task_uuid' => $task->uuid,
                'billing' => [
                    'paid_by' => 'project',
                    'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
                ],
            ];
        }

        return [
            'session_token' => $session->session_token,
            'answer' => $result['answer'],
            'provider' => $this->ai->getProviderName(),
            'model' => $result['model'],
            'usage' => $result['usage'],
            'db_tools_enabled' => $useDbTools && $fid > 0,
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
    }

    private function shouldDelegateToTelegramAgent(string $question, string $answer, string $knowledgeContext): bool
    {
        if ($knowledgeContext !== '') {
            return false;
        }

        $text = mb_strtolower($question . "\n" . $answer);

        foreach ([
            'не знаю',
            'нет информации',
            'не нашел',
            'не нашёл',
            'недостаточно данных',
            'недостаточно информации',
            'не удалось найти',
            'уточните',
        ] as $marker) {
            if (mb_stripos($text, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    // ── История сообщений ───────────────────────────────────────────────

    /**
     * Получить историю сообщений сессии.
     *
     * @return Collection<int, ChatMessage>
     */
    public function getSessionHistory(string $sessionToken, int $limit = 50): Collection
    {
        $session = $this->findSession($sessionToken);
        if ($session === null) {
            return collect();
        }

        return $session->messages()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    // ── Внутренние методы ───────────────────────────────────────────────

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function saveMessage(int $sessionId, ?int $fid, ?int $firma, string $role, string $content, ?array $metadata = null): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $sessionId,
            'fid' => $fid,
            'firma' => $firma,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    private function loadKnowledgeContext(int $fid): string
    {
        if ($fid <= 0) {
            $fid = (int) session('fid', 0);
        }

        if ($fid <= 0) {
            return '';
        }

        try {
            $context = $this->knowledgeService->getContext($fid);

            if ($context === '') {
                $sessionFid = (int) session('fid', 0);
                if ($sessionFid > 0 && $sessionFid !== $fid) {
                    Log::info('Knowledge base context empty for fid {fid}, trying session fid {sessionFid}.', [
                        'fid' => $fid,
                        'sessionFid' => $sessionFid,
                    ]);

                    $context = $this->knowledgeService->getContext($sessionFid);
                }
            }

            return $context;
        } catch (Throwable $e) {
            Log::warning('Failed to load knowledge base context.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Сформировать system prompt для AI.
     */
    private function buildSystemPrompt(string $language, int $fid, string $knowledgeContext = '', bool $useDbTools = true): string
    {
        $answerLanguage = match ($language) {
            'ua' => 'українській',
            'en' => 'английском',
            default => 'русском',
        };

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\nБаза знаний проекта (используй эти данные для ответа):\n{$knowledgeContext}"
            : '';

        $learningInstruction = <<<'LEARN'

ВАЖНО: Ты можешь помогать проекту накапливать знания.
Если пользователь задаёт вопрос, ответ на который будет полезен другим пользователям этого же проекта,
постарайся дать полный, точный и структурированный ответ.
Не выдумывай информацию — если не знаешь, скажи об этом честно.
LEARN;

        $dbToolsInstruction = $useDbTools && $fid > 0 ? <<<'DBTOOLS'

ДОПОЛНИТЕЛЬНЫЕ ВОЗМОЖНОСТИ: У тебя есть доступ к базе данных проекта через функции.

Ты МОЖЕШЬ и ДОЛЖЕН использовать эти функции, когда пользователь спрашивает:
- О товарах, ценах, наличии — используй search_goods или get_goods_by_id
- О категориях товаров — используй get_goods_categories
- О новостях проекта — используй search_news
- О проекте в целом (контакты, описание) — используй get_project_info
- О документах/статьях — используй search_docs
- Если вопрос похож на тот, что уже задавали — используй search_knowledge_base

НОВЫЕ ВОЗМОЖНОСТИ (парсинг сайтов и сохранение знаний):

1. fetch_and_save_page — Когда пользователь даёт URL сайта или просит проанализировать/сохранить страницу
2. save_to_knowledge_base — Когда пользователь предоставляет полезную информацию

ВАЖНО: Всегда используй функции для получения реальных данных из БД.
НЕ выдумывай названия товаров, цены или другие данные — запроси их через функции.
DBTOOLS
        : '';

        return <<<PROMPT
Ты AI-консультант AV8Capital. Отвечай на {$answerLanguage} языке.

Твоя задача: помогать посетителям пользоваться продуктами AV8Capital, особенно Sui-разделами portfolio, invest, token admin, fund basket, fund accounts и mint.

Контекст сессии:
- ID проекта (fid): {$fid}
Правила:
- Объясняй коротко, практически и пошагово.
- Не обещай доходность и не давай персональную финансовую рекомендацию.
- Не проси seed phrase, private key, mnemonic или секреты кошелька.
- Любая операция с активами требует подписи пользователя или админа в кошельке.
- Если пользователь спрашивает про депозит: объясни, что он выбирает whitelisted token, вводит сумму, подписывает транзакцию и получает AV8/fund share по политике эмиссии.
- Если пользователь спрашивает про вывод: объясни, что нужен баланс AV8/fund share и подпись вывода.
- Если пользователь спрашивает про админку: объясни, что whitelist, веса корзины, RWA minting и rebalance доступны только админам с правами/owner cap.
- Если данных не хватает, попроси открыть нужную страницу или подключить кошелёк.
- Не выдумывай onchain-состояние. Если точный баланс или объект не передан в контексте, скажи, где его увидеть в интерфейсе.
- Если в База знаний проекта есть информация по вопросу — используй её в первую очередь.
- Ты можешь парсить веб-страницы по URL и сохранять их содержимое в базу знаний проекта (функция fetch_and_save_page).
- Если пользователь просит изучить сайт или сохранить информацию — используй эту возможность.
- Если пользователь делится полезной информацией — предложи сохранить её в базу знаний (функция save_to_knowledge_base).{$knowledgeSection}{$dbToolsInstruction}{$learningInstruction}
PROMPT;
    }

    private function suiGasSponsorAvailable(): bool
    {
        return trim((string) config('services.sui.gas_sponsor_private_key', '')) !== ''
            || trim((string) config('services.shinami.gas_access_key', '')) !== '';
    }
}
