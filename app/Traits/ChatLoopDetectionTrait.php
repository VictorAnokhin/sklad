<?php

namespace App\Traits;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;

/**
 * Trait ChatLoopDetectionTrait
 *
 * Обнаружение и предотвращение зацикливания AI-диалогов.
 * Используется в TelegramChatService и TelegramAgent для устранения дублирования кода.
 *
 * @method TelegramBotService getBot()  Должен быть реализован в классе
 */
trait ChatLoopDetectionTrait
{
    /**
     * Определить, зациклился ли диалог.
     *
     * Проверяет:
     * 1. Превышение лимита повторяющихся сообщений пользователя
     * 2. Наличие break-ключевых слов в истории
     */
    protected function detectLoop(ChatSession $session, string $currentText): bool
    {
        $history = $session->messages()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->pluck('content')
            ->toArray();

        // Если пользователь повторяет одно и то же 3+ раза
        $repeatCount = 0;
        $lastText = '';

        foreach ($history as $msg) {
            if ($msg === $lastText && $msg === $currentText) {
                $repeatCount++;
            }
            $lastText = $msg;
        }

        if ($repeatCount >= 3) {
            Log::warning('ChatLoopDetection: user repeating same message.', [
                'session_token' => $session->session_token,
                'text_preview' => mb_substr($currentText, 0, 50),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Проверить, является ли ответ повторением предыдущего.
     */
    protected function isAnswerRepeatOfLast(ChatSession $session, string $answer): bool
    {
        $lastAssistantMsg = $session->messages()
            ->where('role', 'assistant')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastAssistantMsg === null) {
            return false;
        }

        $similarity = $this->areTextsSimilar($lastAssistantMsg->content, $answer);

        if ($similarity > 0.85) {
            Log::warning('ChatLoopDetection: AI repeating same answer.', [
                'session_token' => $session->session_token,
                'similarity' => $similarity,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Сравнить два текста на схожесть (через пересечение слов).
     *
     * @return float 0.0 — 1.0 (1.0 = идентичны)
     */
    protected function areTextsSimilar(string $a, string $b): float
    {
        $normalize = fn (string $t): array => array_unique(
            preg_split('/\s+/u', mb_strtolower(trim($t)))
        );

        $wordsA = $normalize($a);
        $wordsB = $normalize($b);

        if (empty($wordsA) && empty($wordsB)) {
            return 1.0;
        }

        if (empty($wordsA) || empty($wordsB)) {
            return 0.0;
        }

        $intersection = array_intersect($wordsA, $wordsB);
        $union = array_unique(array_merge($wordsA, $wordsB));

        return count($intersection) / count($union);
    }

    /**
     * Проверить, является ли текст командой принудительного выхода из цикла.
     */
    protected function isBreakKeyword(string $text): bool
    {
        $breakKeywords = [
            '—break', '--break', '-break',
            'stop', 'хватит', 'прекрати', 'остановись',
            'давай по новой', 'давай заново',
        ];

        $lower = mb_strtolower(trim($text));

        foreach ($breakKeywords as $keyword) {
            if ($lower === $keyword || str_starts_with($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Принудительно прервать цикл: отправить сообщение и начать новую сессию.
     */
    protected function breakTheLoop(int|string $chatId, ChatSession $session): string
    {
        try {
            $this->getBot()->sendMessage($chatId, '🔄 Обнаружено зацикливание. Начинаю новый диалог...');
        } catch (\Throwable) {
            // Игнорируем ошибку отправки
        }

        // Архивируем текущую сессию
        $session->update(['status' => 'archived']);

        // Новая сессия создастся при следующем обращении
        return '🔄 Диалог перезапущен из-за обнаружения цикла. Задайте новый вопрос.';
    }

    /**
     * Получить экземпляр TelegramBotService для отправки сообщений.
     * Должен быть реализован в классе, использующем трейт.
     */
    abstract protected function getBot(): TelegramBotService;
}
