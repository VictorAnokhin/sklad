<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly AiKnowledgeService $knowledgeService,
        private readonly DeepSeekClient $deepseek,
        private readonly DbQueryService $dbQuery,
    ) {}

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
        $useDbTools = (bool) ($payload['use_db_tools'] ?? true); // Включать ли доступ к БД

        // Определяем fid — из payload, из сессии чата или из PHP-сессии (рабочее пространство)
        if ($fid <= 0) {
            $fid = (int) ($session->fid ?? 0);
        }
        if ($fid <= 0) {
            $fid = (int) (session('fid', 0));
        }

        // Определяем firma — из payload или из сессии
        if ($firma === null || $firma <= 0) {
            $firma = $session->firma ?? null;
        }
        if ($firma === null || $firma <= 0) {
            // Пробуем взять из PHP-сессии (рабочее пространство)
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
        $knowledgeContext = $this->loadKnowledgeContext($fid, $firma);

        // Формируем system prompt
        $instructions = $this->buildSystemPrompt($language, $fid, $firma, $knowledgeContext, $useDbTools);

        // Загружаем историю для AI (из БД)
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

        // ── Отправка с function calling (доступ к БД) или обычный запрос ──
        if ($useDbTools && $fid > 0) {
            // Получаем инструменты для доступа к БД
            $tools = $this->dbQuery->getTools();

            // Создаём executor для вызова функций БД
            $toolExecutor = function (string $name, array $arguments) use ($fid, $firma): string {
                return $this->dbQuery->executeTool($fid, $firma, $name, $arguments);
            };

            // Отправляем запрос с поддержкой function calling
            $result = $this->deepseek->chatWithTools($instructions, $history, $tools, $toolExecutor);
        } else {
            // Обычный запрос без доступа к БД
            $result = $this->deepseek->chat($instructions, $history);
        }

        // Сохраняем ответ ассистента
        $this->saveMessage($session->id, $fid, $firma, 'assistant', $result['answer'], [
            'model' => $result['model'] ?? null,
            'usage' => $result['usage'] ?? null,
            'provider' => 'deepseek',
            'db_tools_used' => $useDbTools && $fid > 0,
        ]);

        // ── Автообучение: сохраняем полезные знания из диалога ──────
        $this->knowledgeService->autoLearn($fid, $firma, $session->getHistoryForAi(5));

        return [
            'session_token' => $session->session_token,
            'answer' => $result['answer'],
            'provider' => 'deepseek',
            'model' => $result['model'],
            'usage' => $result['usage'],
            'db_tools_enabled' => $useDbTools && $fid > 0,
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
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
     * Сохранить сообщение в БД.
     *
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

    /**
     * Загрузить контекст из базы знаний для проекта и компании.
     *
     * Если по переданному fid ничего не найдено, пробует session('fid') как fallback.
     * Это необходимо, когда фронтенд передаёт неверный fid (например, захардкоженный 1),
     * а реальные записи в БЗ привязаны к другому проекту (например, session('fid') = 2).
     */
    private function loadKnowledgeContext(int $fid, ?int $firma): string
    {
        if ($fid <= 0) {
            $fid = (int) session('fid', 0);
        }

        if ($fid <= 0) {
            return '';
        }

        try {
            $context = $this->knowledgeService->getContext($fid, $firma);

            // Если контекст пустой — пробуем session('fid') как fallback
            if ($context === '') {
                $sessionFid = (int) session('fid', 0);
                if ($sessionFid > 0 && $sessionFid !== $fid) {
                    Log::info('Knowledge base context empty for fid {fid}, trying session fid {sessionFid}.', [
                        'fid' => $fid,
                        'sessionFid' => $sessionFid,
                        'firma' => $firma,
                    ]);

                    $context = $this->knowledgeService->getContext($sessionFid, $firma);
                }
            }

            return $context;
        } catch (Throwable $e) {
            Log::warning('Failed to load knowledge base context.', [
                'fid' => $fid,
                'firma' => $firma,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Сформировать system prompt для AI.
     *
     * @param  bool  $useDbTools  Если true — в prompt добавляется инструкция по работе с БД
     */
    private function buildSystemPrompt(string $language, int $fid, ?int $firma, string $knowledgeContext = '', bool $useDbTools = true): string
    {
        $answerLanguage = match ($language) {
            'ua' => 'українській',
            'en' => 'английском',
            default => 'русском',
        };

        $firmaSection = ($firma !== null && $firma > 0)
            ? "Контекст компании (firma): {$firma}\n"
            : '';

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\nБаза знаний проекта (используй эти данные для ответа):\n{$knowledgeContext}"
            : '';

        // Инструкция для автообучения: AI помечает знания, которые стоит сохранить
        $learningInstruction = <<<'LEARN'

ВАЖНО: Ты можешь помогать проекту накапливать знания.
Если пользователь задаёт вопрос, ответ на который будет полезен другим пользователям этого же проекта,
постарайся дать полный, точный и структурированный ответ.
Не выдумывай информацию — если не знаешь, скажи об этом честно.
LEARN;

        // Инструкция по работе с базой данных проекта
        $dbToolsInstruction = $useDbTools && $fid > 0 ? <<<'DBTOOLS'

ДОПОЛНИТЕЛЬНЫЕ ВОЗМОЖНОСТИ: У тебя есть доступ к базе данных проекта через функции.

Ты МОЖЕШЬ и ДОЛЖЕН использовать эти функции, когда пользователь спрашивает:
- О товарах, ценах, наличии — используй search_goods или get_goods_by_id
- О категориях товаров — используй get_goods_categories
- О новостях проекта — используй search_news
- О проекте в целом (контакты, описание) — используй get_project_info
- О документах/статьях — используй search_docs
- Если вопрос похож на тот, что уже задавали — используй search_knowledge_base

ВАЖНО: Всегда используй функции для получения реальных данных из БД.
НЕ выдумывай названия товаров, цены или другие данные — запроси их через функции.
Если функция вернула пустой результат — честно скажи, что ничего не найдено.
DBTOOLS
        : '';

        return <<<PROMPT
Ты AI-консультант AV8Capital. Отвечай на {$answerLanguage} языке.

Твоя задача: помогать посетителям пользоваться продуктами AV8Capital, особенно Sui-разделами portfolio, invest, token admin, fund basket, fund accounts и mint.

Контекст сессии:
- ID проекта (fid): {$fid}
{$firmaSection}
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
- Если в База знаний проекта есть информация по вопросу — используй её в первую очередь.{$knowledgeSection}{$dbToolsInstruction}{$learningInstruction}
PROMPT;
    }

    private function suiGasSponsorAvailable(): bool
    {
        return trim((string) config('services.sui.gas_sponsor_private_key', '')) !== ''
            || trim((string) config('services.shinami.gas_access_key', '')) !== '';
    }
}
