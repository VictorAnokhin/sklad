<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class WebChatIntentDetector
{
    public const FAQ = 'faq';
    public const HOW_TO = 'how_to';
    public const SUPPORT = 'support';
    public const RESEARCH = 'research';
    public const PUBLISH_NEWS = 'publish_news';
    public const WALLET_ACTION = 'wallet_action';
    public const SMALL_TALK = 'small_talk';

    public function __construct(
        private readonly AiClientFactory $aiFactory,
    ) {}

    /**
     * @return array{type: string, confidence: float, topic: string, reason: string, needs_tools: bool}
     */
    public function detect(string $message, string $page = '', string $language = 'ru'): array
    {
        $message = trim($message);
        $heuristic = $this->detectByHeuristics($message, $page);

        if ($heuristic['confidence'] >= 0.82) {
            return $heuristic;
        }

        try {
            $ai = $this->detectWithAi($message, $page, $language);

            if (($ai['confidence'] ?? 0) >= $heuristic['confidence']) {
                return $ai;
            }
        } catch (Throwable $e) {
            Log::debug('Web chat intent detector: AI fallback failed.', [
                'error' => $e->getMessage(),
            ]);
        }

        return $heuristic;
    }

    /**
     * @return array{type: string, confidence: float, topic: string, reason: string, needs_tools: bool}
     */
    private function detectByHeuristics(string $message, string $page): array
    {
        $text = mb_strtolower($message);
        $topic = $this->extractTopic($message);

        if ($this->containsAny($text, ['привет', 'здравствуйте', 'добрый день', 'hello', 'hi', 'спасибо', 'благодарю'])) {
            return $this->result(self::SMALL_TALK, 0.9, $topic, 'greeting_or_polite_phrase', false);
        }

        if ($this->containsAny($text, ['новост', 'опубликуй', 'публикац', 'сделай стать', 'напиши стать', 'материал про', 'press release'])) {
            return $this->result(self::PUBLISH_NEWS, 0.9, $topic, 'publication_request', true);
        }

        if ($this->containsAny($text, ['изучи', 'исслед', 'проанализируй', 'найди в интернете', 'собери информацию', 'проверь сайт', 'url', 'http://', 'https://'])) {
            return $this->result(self::RESEARCH, 0.88, $topic, 'research_request', true);
        }

        if ($this->containsAny($text, ['кошел', 'wallet', 'баланс', 'deposit', 'withdraw', 'вывод', 'депозит', 'подпис', 'транзакц', 'mint', 'swap'])) {
            return $this->result(self::WALLET_ACTION, 0.84, $topic, 'wallet_or_transaction_flow', true);
        }

        if ($this->containsAny($text, ['ошибка', 'не работает', 'не открывается', 'завис', 'проблем', 'баг', 'support', 'помоги'])) {
            return $this->result(self::SUPPORT, 0.84, $topic, 'support_problem', true);
        }

        if (preg_match('/^(как|что нажать|куда нажать|где найти|как сделать|как подключ|как вывести|как пополн)/iu', $text)) {
            return $this->result(self::HOW_TO, 0.86, $topic, 'how_to_question', true);
        }

        if (preg_match('/^(что такое|что значит|зачем|почему|сколько|когда|где|можно ли)/iu', $text)) {
            return $this->result(self::FAQ, 0.78, $topic, 'faq_question', true);
        }

        if ($page !== '' && $page !== 'unknown') {
            return $this->result(self::FAQ, 0.62, $topic, 'page_context_question', true);
        }

        return $this->result(self::FAQ, 0.55, $topic, 'default_question', true);
    }

    /**
     * @return array{type: string, confidence: float, topic: string, reason: string, needs_tools: bool}
     */
    private function detectWithAi(string $message, string $page, string $language): array
    {
        $ai = $this->aiFactory->make('agent');

        $result = $ai->chat(
            instructions: <<<'PROMPT'
Ты WebChatIntentDetector. Классифицируй сообщение пользователя для вебчата AV8Capital.

Допустимые type:
- faq: короткий вопрос о факте/понятии/условии
- how_to: пользователь хочет инструкцию или следующий шаг
- support: проблема, ошибка, жалоба, не работает
- research: нужно изучить сайт/проект/источники
- publish_news: нужно написать или опубликовать новость/статью/материал
- wallet_action: кошелёк, баланс, депозит, вывод, подпись, транзакция, mint/swap
- small_talk: приветствие, благодарность, короткая социальная фраза

Верни строго JSON без markdown:
{
  "type": "faq|how_to|support|research|publish_news|wallet_action|small_talk",
  "confidence": 0.0,
  "topic": "краткая тема",
  "reason": "short_reason",
  "needs_tools": true|false
}
PROMPT,
            messages: [[
                'role' => 'user',
                'content' => "Язык: {$language}\nСтраница: {$page}\nСообщение:\n{$message}",
            ]],
            options: [
                'temperature' => 0.0,
                'max_tokens' => 250,
            ],
        );

        $decoded = $this->decodeJson((string) ($result['answer'] ?? ''));
        $type = $this->normalizeType((string) ($decoded['type'] ?? self::FAQ));
        $confidence = max(0.0, min(1.0, (float) ($decoded['confidence'] ?? 0.5)));

        return $this->result(
            $type,
            $confidence,
            (string) ($decoded['topic'] ?? $this->extractTopic($message)),
            (string) ($decoded['reason'] ?? 'ai_detected'),
            (bool) ($decoded['needs_tools'] ?? $type !== self::SMALL_TALK),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $answer): array
    {
        $json = trim($answer);
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $json) ?? $json;

        if (preg_match('/\{.*\}/su', $json, $match)) {
            $json = $match[0];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Intent detector returned invalid JSON.');
        }

        return $decoded;
    }

    private function normalizeType(string $type): string
    {
        return in_array($type, $this->allowedTypes(), true) ? $type : self::FAQ;
    }

    /**
     * @return array<int, string>
     */
    private function allowedTypes(): array
    {
        return [
            self::FAQ,
            self::HOW_TO,
            self::SUPPORT,
            self::RESEARCH,
            self::PUBLISH_NEWS,
            self::WALLET_ACTION,
            self::SMALL_TALK,
        ];
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_stripos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractTopic(string $message): string
    {
        $topic = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);
        $topic = preg_replace('/^(сделай|напиши|опубликуй|изучи|проанализируй|расскажи|объясни|как|что такое)\s+/iu', '', $topic) ?? $topic;

        return mb_substr($topic, 0, 120);
    }

    /**
     * @return array{type: string, confidence: float, topic: string, reason: string, needs_tools: bool}
     */
    private function result(string $type, float $confidence, string $topic, string $reason, bool $needsTools): array
    {
        return [
            'type' => $this->normalizeType($type),
            'confidence' => max(0.0, min(1.0, $confidence)),
            'topic' => trim($topic),
            'reason' => $reason,
            'needs_tools' => $needsTools,
        ];
    }
}
