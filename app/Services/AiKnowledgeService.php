<?php

namespace App\Services;

use App\Models\AiKnowledgeBase;
use App\Models\AiKnowledgeCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiKnowledgeService
{
    /**
     * Получить контекст из базы знаний для проекта (fid).
     *
     * Возвращает строку с релевантными записями для подстановки в system prompt.
     */
    public function getContext(int $fid, int $limit = 10): string
    {
        $records = $this->getActiveRecords($fid, $limit);

        if ($records->isEmpty()) {
            return '';
        }

        $parts = $records->map(function (AiKnowledgeBase $item) use ($fid): string {
            $title = $item->title ?: 'Без заголовка';
            $category = $this->translateCategory($item->category, $fid);

            return "[{$category}] {$title}\n{$item->content}";
        });

        return "— — —\nБаза знаний проекта:\n{$parts->implode("\n\n")}\n— — —";
    }

    /**
     * Сохранить информацию с веб-страницы в базу знаний.
     *
     * Парсит страницу по URL через WebScraperService, затем сохраняет
     * извлечённый контент в базу знаний для указанного fid.
     *
     * @param  int     $fid       ID проекта
     * @param  string  $url       URL страницы для парсинга
     * @param  string  $category  Категория знания (по умолчанию 'web_page')
     * @return array{success: bool, record?: AiKnowledgeBase, error?: string, title?: string}
     */
    public function fetchAndSavePage(int $fid, string $url, string $category = 'web_page'): array
    {
        try {
            // Парсим страницу
            $scraper = app(WebScraperService::class);
            $result = $scraper->fetchUrl($url);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Не удалось загрузить страницу.',
                ];
            }

            $title = $result['title'] ?? 'Без заголовка';
            $content = $result['content'] ?? '';
            $contentType = $result['content_type'] ?? 'website';

            if (trim($content) === '') {
                return [
                    'success' => false,
                    'error' => 'На странице не найдено текстового содержимого.',
                ];
            }

            // Сохраняем в базу знаний
            $record = $this->create($fid, [
                'title' => $title,
                'content' => sprintf(
                    "Источник: %s (%s)\n\n%s",
                    $url,
                    $contentType === 'json' ? 'JSON API' : 'Веб-страница',
                    $content
                ),
                'category' => $category,
                'source' => 'web_scrape',
                'active' => true,
            ]);

            Log::info('AI knowledge: web page saved.', [
                'fid' => $fid,
                'url' => $url,
                'title' => $title,
                'category' => $category,
                'content_length' => mb_strlen($content),
            ]);

            return [
                'success' => true,
                'record' => $record,
                'title' => $title,
            ];
        } catch (Throwable $e) {
            Log::error('AI knowledge: failed to fetch and save page.', [
                'fid' => $fid,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при обработке страницы: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Сохранить произвольную информацию в базу знаний.
     *
     * @param  int     $fid       ID проекта
     * @param  string  $title     Заголовок/тема информации
     * @param  string  $content   Содержание информации
     * @param  string  $category  Категория знания
     * @return array{success: bool, record?: AiKnowledgeBase, error?: string}
     */
    public function saveInformation(int $fid, string $title, string $content, string $category = 'manual'): array
    {
        try {
            if (mb_strlen($content) < 10) {
                return [
                    'success' => false,
                    'error' => 'Содержание слишком короткое (минимум 10 символов).',
                ];
            }

            // Проверяем, есть ли уже запись с таким же заголовком
            $existing = AiKnowledgeBase::forFid($fid)
                ->where('title', $title)
                ->where('category', $category)
                ->first();

            if ($existing) {
                // Обновляем существующую запись
                $existing->update([
                    'content' => $content,
                    'source' => 'manual',
                ]);

                Log::info('AI knowledge: existing record updated.', [
                    'fid' => $fid,
                    'id' => $existing->id,
                    'title' => $title,
                ]);

                return [
                    'success' => true,
                    'record' => $existing->fresh(),
                ];
            }

            // Создаём новую запись
            $record = $this->create($fid, [
                'title' => $title,
                'content' => $content,
                'category' => $category,
                'source' => 'manual',
                'active' => true,
            ]);

            Log::info('AI knowledge: information saved.', [
                'fid' => $fid,
                'id' => $record->id,
                'title' => $title,
                'category' => $category,
            ]);

            return [
                'success' => true,
                'record' => $record,
            ];
        } catch (Throwable $e) {
            Log::error('AI knowledge: failed to save information.', [
                'fid' => $fid,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при сохранении информации: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить активные записи базы знаний для проекта.
     *
     * @return Collection<int, AiKnowledgeBase>
     */
    public function getActiveRecords(int $fid, int $limit = 10): Collection
    {
        return AiKnowledgeBase::forFid($fid)
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
    public function exportToKnowledgeBase(int $fid, string $question, string $answer, string $category = 'chat_export'): AiKnowledgeBase
    {
        $title = mb_substr($question, 0, 250);
        $content = "Вопрос: {$question}\nОтвет: {$answer}";

        // Если такой же вопрос уже есть — обновляем ответ
        $existing = AiKnowledgeBase::forFid($fid)
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
    public function search(int $fid, string $query, int $limit = 5): Collection
    {
        return AiKnowledgeBase::forFid($fid)
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
     * и сохраняет их в базу знаний для активного fid.
     *
     * Анализирует пару вопрос-ответ и, если это not-general информация,
     * создаёт запись в базе знаний.
     *
     * @param  array<int, array{role: string, content: string}>  $history  История диалога
     */
    public function autoLearn(int $fid, array $history): void
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
            $this->exportToKnowledgeBase($fid, $question, $answer, $category);

            Log::info('AI auto-learn: knowledge saved.', [
                'fid' => $fid,
                'category' => $category,
                'question_length' => mb_strlen($question),
            ]);
        } catch (Throwable $e) {
            Log::warning('AI auto-learn failed.', [
                'fid' => $fid,
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

    private function translateCategory(string $category, ?int $fid = null): string
    {
        $cacheKey = 'ai_knowledge_category_names_fid_' . ($fid ?? 'global');

        $map = Cache::remember($cacheKey, 3600, function () use ($fid) {
            return AiKnowledgeCategory::forFid($fid)
                ->pluck('name', 'key')
                ->toArray();
        });

        return $map[$category] ?? $category;
    }
}
