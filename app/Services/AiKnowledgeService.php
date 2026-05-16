<?php

namespace App\Services;

use App\Models\AiKnowledgeBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiKnowledgeService
{
    /**
     * Получить контекст из базы знаний для проекта (fid) и/или компании (firma).
     *
     * Возвращает строку с релевантными записями для подстановки в system prompt.
     */
    public function getContext(int $fid, ?int $firma = null, int $limit = 10): string
    {
        $records = $this->getActiveRecords($fid, $firma, $limit);

        if ($records->isEmpty()) {
            return '';
        }

        $parts = $records->map(function (AiKnowledgeBase $item): string {
            $title = $item->title ?: 'Без заголовка';
            $category = $this->translateCategory($item->category);

            return "[{$category}] {$title}\n{$item->content}";
        });

        return "— — —\nБаза знаний проекта:\n{$parts->implode("\n\n")}\n— — —";
    }

    /**
     * Получить активные записи базы знаний для проекта и/или компании.
     *
     * @return Collection<int, AiKnowledgeBase>
     */
    public function getActiveRecords(int $fid, ?int $firma = null, int $limit = 10): Collection
    {
        return AiKnowledgeBase::forFid($fid)
            ->forFirma($firma)
            ->active()
            ->orderBy('category')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Создать запись в базе знаний.
     */
    public function create(int $fid, array $data): AiKnowledgeBase
    {
        return AiKnowledgeBase::create([
            'fid' => $fid,
            'firma' => isset($data['firma']) ? (int) $data['firma'] : null,
            'title' => trim((string) ($data['title'] ?? '')),
            'content' => trim((string) ($data['content'] ?? '')),
            'category' => trim((string) ($data['category'] ?? 'general')),
            'source' => trim((string) ($data['source'] ?? 'manual')),
            'active' => (bool) ($data['active'] ?? true),
        ]);
    }

    /**
     * Экспортировать диалог чата в базу знаний.
     *
     * Сохраняет пару вопрос-ответ как одну запись.
     */
    public function exportToKnowledgeBase(int $fid, string $question, string $answer, string $category = 'chat_export', ?int $firma = null): AiKnowledgeBase
    {
        $title = mb_substr($question, 0, 250);
        $content = "Вопрос: {$question}\nОтвет: {$answer}";

        // Если такой же вопрос уже есть — обновляем ответ
        $existing = AiKnowledgeBase::forFid($fid)
            ->forFirma($firma)
            ->where('title', $title)
            ->where('category', $category)
            ->first();

        if ($existing) {
            $existing->update([
                'content' => $content,
                'source' => 'chat_export',
            ]);

            return $existing->fresh();
        }

        return AiKnowledgeBase::create([
            'fid' => $fid,
            'firma' => $firma,
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'source' => 'chat_export',
            'active' => true,
        ]);
    }

    /**
     * Поиск по базе знаний проекта (простой LIKE-поиск).
     *
     * @return Collection<int, AiKnowledgeBase>
     */
    public function search(int $fid, string $query, ?int $firma = null, int $limit = 5): Collection
    {
        return AiKnowledgeBase::forFid($fid)
            ->forFirma($firma)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Автоматическое обучение: извлекает полезные знания из диалога
     * и сохраняет их в базу знаний для активного fid/firma.
     *
     * Анализирует пару вопрос-ответ и, если это not-general информация,
     * создаёт запись в базе знаний.
     *
     * @param  array<int, array{role: string, content: string}>  $history  История диалога
     */
    public function autoLearn(int $fid, ?int $firma, array $history): void
    {
        if ($fid <= 0 || empty($history)) {
            return;
        }

        try {
            // Берём последнюю пару вопрос-ответ
            $lastUserMsg = null;
            $lastAssistantMsg = null;

            for ($i = count($history) - 1; $i >= 0; $i--) {
                if ($history[$i]['role'] === 'assistant' && $lastAssistantMsg === null) {
                    $lastAssistantMsg = $history[$i]['content'];
                } elseif ($history[$i]['role'] === 'user' && $lastUserMsg === null) {
                    $lastUserMsg = $history[$i]['content'];
                }

                if ($lastUserMsg !== null && $lastAssistantMsg !== null) {
                    break;
                }
            }

            if ($lastUserMsg === null || $lastAssistantMsg === null) {
                return;
            }

            // Проверяем, что ответ содержит полезную информацию
            $question = trim($lastUserMsg);
            $answer = trim($lastAssistantMsg);

            // Минимальная длина для ценной информации
            if (mb_strlen($answer) < 50) {
                return;
            }

            // Не сохраняем, если ответ — это отказ или запрос данных
            $skipPatterns = [
                'не могу', 'не знаю', 'извините', 'недоступен',
                'попроси', 'открой нужную страницу', 'подключи кошелёк',
                'не хватает данных',
            ];

            foreach ($skipPatterns as $pattern) {
                if (mb_stripos($answer, $pattern) !== false) {
                    return;
                }
            }

            // Не сохраняем приветствия и общие фразы
            if (mb_strlen($question) < 10) {
                return;
            }

            // Определяем категорию на основе контента
            $category = $this->detectCategory($question, $answer);

            // Сохраняем в базу знаний
            $this->exportToKnowledgeBase($fid, $question, $answer, $category, $firma);

            Log::info('AI auto-learn: knowledge saved.', [
                'fid' => $fid,
                'firma' => $firma,
                'category' => $category,
                'question_length' => mb_strlen($question),
            ]);
        } catch (Throwable $e) {
            Log::warning('AI auto-learn failed.', [
                'fid' => $fid,
                'firma' => $firma,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Определить категорию знания на основе вопроса и ответа.
     */
    private function detectCategory(string $question, string $answer): string
    {
        $combined = mb_strtolower($question . ' ' . $answer);

        $categoryMap = [
            'invest' => ['инвестиц', 'вложен', 'доходност', 'прибыл', 'пассивн'],
            'wallet' => ['кошел', 'wallet', 'баланс', 'пополн', 'вывод', 'токен', 'депозит'],
            'token' => ['токен', 'mint', 'эмисси', 'share', 'av8'],
            'fund' => ['фонд', 'fund', 'пул', 'корзин', 'basket'],
            'admin' => ['админ', 'admin', 'whitelist', 'rebalance', 'управлен'],
            'faq' => ['как', 'что такое', 'где', 'почему', 'зачем', 'сколько', 'когда'],
        ];

        foreach ($categoryMap as $cat => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($combined, $keyword) !== false) {
                    return $cat;
                }
            }
        }

        return 'general';
    }

    private function translateCategory(string $category): string
    {
        return match ($category) {
            'general' => 'Общее',
            'invest' => 'Инвестиции',
            'wallet' => 'Кошелёк',
            'token' => 'Токены',
            'fund' => 'Фонд',
            'admin' => 'Администрирование',
            'chat_export' => 'Из чата',
            'faq' => 'FAQ',
            default => $category,
        };
    }
}
