<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class WebChatKnowledgeCurator
{
    private const MIN_ANSWER_LENGTH = 80;

    public function __construct(
        private readonly AiClientFactory $aiFactory,
        private readonly AiKnowledgeService $knowledgeService,
    ) {}

    /**
     * Decide whether a web-chat turn contains reusable project knowledge,
     * then save a concise curated record instead of dumping raw chat history.
     *
     * @param  array<int, array{role: string, content: string}>  $recentHistory
     * @return array{saved: bool, reason: string, title?: string, category?: string, record_id?: int}
     */
    public function curateFromTurn(
        int $fid,
        string $question,
        string $answer,
        string $page = '',
        string $language = 'ru',
        array $recentHistory = [],
    ): array {
        $question = trim($question);
        $answer = trim($answer);

        if (! $this->isEligible($fid, $question, $answer)) {
            return ['saved' => false, 'reason' => 'not_eligible'];
        }

        try {
            $decision = $this->classifyWithAi($question, $answer, $page, $language, $recentHistory);
        } catch (Throwable $e) {
            Log::debug('Web chat knowledge curator: AI decision failed, using fallback.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);

            $decision = $this->fallbackDecision($question, $answer, $page);
        }

        if (! ($decision['save'] ?? false)) {
            return [
                'saved' => false,
                'reason' => (string) ($decision['reason'] ?? 'curator_skip'),
            ];
        }

        $title = mb_substr(trim((string) ($decision['title'] ?? $question)), 0, 250);
        $content = trim((string) ($decision['content'] ?? ''));
        $category = $this->normalizeCategory((string) ($decision['category'] ?? 'web_chat_faq'));

        if ($title === '' || mb_strlen($content) < 40) {
            return ['saved' => false, 'reason' => 'curated_content_too_short'];
        }

        $content = $this->redactSensitiveData($content);

        $result = $this->knowledgeService->saveInformation(
            $fid,
            $title,
            $content,
            $category,
            'web_chat_curator',
        );

        if (! ($result['success'] ?? false)) {
            return [
                'saved' => false,
                'reason' => (string) ($result['error'] ?? 'save_failed'),
            ];
        }

        $record = $result['record'] ?? null;

        Log::info('Web chat knowledge curator: record saved.', [
            'fid' => $fid,
            'category' => $category,
            'title' => $title,
            'record_id' => $record?->id,
        ]);

        return [
            'saved' => true,
            'reason' => 'saved',
            'title' => $title,
            'category' => $category,
            'record_id' => $record?->id,
        ];
    }

    private function isEligible(int $fid, string $question, string $answer): bool
    {
        if ($fid <= 0 || mb_strlen($question) < 8 || mb_strlen($answer) < self::MIN_ANSWER_LENGTH) {
            return false;
        }

        $combined = mb_strtolower($question . "\n" . $answer);

        foreach ([
            'seed phrase',
            'private key',
            'mnemonic',
            'сид фраз',
            'приватн',
            'секретн',
            'не знаю',
            'не могу',
            'нет информации',
            'недостаточно данных',
            'временно недоступ',
            'произошла ошибка',
        ] as $marker) {
            if (mb_stripos($combined, $marker) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $recentHistory
     * @return array<string, mixed>
     */
    private function classifyWithAi(
        string $question,
        string $answer,
        string $page,
        string $language,
        array $recentHistory,
    ): array {
        $ai = $this->aiFactory->make('agent');
        $historySummary = collect($recentHistory)
            ->take(-6)
            ->map(fn (array $message): string => ($message['role'] ?? 'unknown') . ': ' . mb_substr((string) ($message['content'] ?? ''), 0, 500))
            ->implode("\n");

        $result = $ai->chat(
            instructions: <<<'PROMPT'
Ты WebChatKnowledgeCurator. Твоя задача — решить, стоит ли сохранять пару вопрос-ответ в базу знаний проекта.

Сохраняй только повторно полезные знания: FAQ, инструкции, правила продукта, объяснения терминов, workflow, ограничения.
Не сохраняй: приветствия, small talk, персональные данные, адреса кошельков, временные ошибки, неподтверждённые утверждения, финансовые обещания.

Верни строго JSON без markdown:
{
  "save": true|false,
  "reason": "short_reason",
  "title": "короткий заголовок знания",
  "category": "web_chat_faq|web_chat_howto|web_chat_product|web_chat_policy|web_chat_support",
  "content": "самодостаточная запись для базы знаний без персональных данных"
}
PROMPT,
            messages: [[
                'role' => 'user',
                'content' => "Язык: {$language}\nСтраница: {$page}\nНедавний контекст:\n{$historySummary}\n\nВопрос пользователя:\n{$question}\n\nОтвет ассистента:\n{$answer}",
            ]],
            options: [
                'temperature' => 0.1,
                'max_tokens' => 700,
            ],
        );

        return $this->decodeJsonDecision((string) ($result['answer'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonDecision(string $answer): array
    {
        $json = trim($answer);
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $json) ?? $json;

        if (preg_match('/\{.*\}/su', $json, $match)) {
            $json = $match[0];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Curator returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackDecision(string $question, string $answer, string $page): array
    {
        return [
            'save' => true,
            'reason' => 'fallback_reusable_answer',
            'title' => mb_substr($question, 0, 120),
            'category' => str_contains(mb_strtolower($question), 'как') ? 'web_chat_howto' : 'web_chat_faq',
            'content' => trim("Страница: {$page}\n\nВопрос: {$question}\n\nОтвет: {$answer}"),
        ];
    }

    private function normalizeCategory(string $category): string
    {
        $allowed = [
            'web_chat_faq',
            'web_chat_howto',
            'web_chat_product',
            'web_chat_policy',
            'web_chat_support',
        ];

        return in_array($category, $allowed, true) ? $category : 'web_chat_faq';
    }

    private function redactSensitiveData(string $content): string
    {
        $content = preg_replace('/0x[a-f0-9]{32,}/i', '[wallet_address]', $content) ?? $content;
        $content = preg_replace('/\b[A-Z2-7]{48,}\b/i', '[secret_or_address]', $content) ?? $content;

        return trim($content);
    }
}
