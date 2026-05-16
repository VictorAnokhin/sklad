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
        $instructions = $this->buildSystemPrompt($language, $fid, $firma, $knowledgeContext);

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

        // Отправляем запрос в DeepSeek
        $result = $this->deepseek->chat($instructions, $history);

        // Сохраняем ответ ассистента
        $this->saveMessage($session->id, $fid, $firma, 'assistant', $result['answer'], [
            'model' => $result['model'] ?? null,
            'usage' => $result['usage'] ?? null,
            'provider' => 'deepseek',
        ]);

        // ── Автообучение: сохраняем полезные знания из диалога ──────
        $this->knowledgeService->autoLearn($fid, $firma, $session->getHistoryForAi(5));

        return [
            'session_token' => $session->session_token,
            'answer' => $result['answer'],
            'provider' => 'deepseek',
            'model' => $result['model'],
            'usage' => $result['usage'],
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
     */
    private function loadKnowledgeContext(int $fid, ?int $firma): string
    {
        if ($fid <= 0) {
            return '';
        }

        try {
            return $this->knowledgeService->getContext($fid, $firma);
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
     */
    private function buildSystemPrompt(string $language, int $fid, ?int $firma, string $knowledgeContext = ''): string
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
- Если в База знаний проекта есть информация по вопросу — используй её в первую очередь.{$knowledgeSection}{$learningInstruction}
PROMPT;
    }

    private function suiGasSponsorAvailable(): bool
    {
        return trim((string) config('services.sui.gas_sponsor_private_key', '')) !== ''
            || trim((string) config('services.shinami.gas_access_key', '')) !== '';
    }
}
