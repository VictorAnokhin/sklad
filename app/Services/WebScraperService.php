<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebScraperService
{
    /**
     * Максимальный размер контента для сохранения (символов).
     */
    private const MAX_CONTENT_LENGTH = 50000;

    /**
     * Fetch content from a URL and extract text.
     *
     * @param  string  $url
     * @return array{success: bool, content?: string, title?: string, error?: string}
     */
    public function fetchUrl(string $url): array
    {
        // Валидация URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'Некорректный URL. Пожалуйста, укажите валидный URL (https://...)',
            ];
        }

        // Ограничиваем только http/https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return [
                'success' => false,
                'error' => 'Поддерживаются только HTTP и HTTPS протоколы.',
            ];
        }

        try {
            $response = Http::acceptJson()
                ->accept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 AV8Capital-Analyst/1.0')
                ->timeout(30)
                ->connectTimeout(10)
                ->get($url);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: Сервер вернул ошибку.",
                ];
            }

            $body = $response->body();
            $contentType = $response->header('Content-Type', '');

            // Если JSON — возвращаем как есть
            if (str_contains($contentType, 'json')) {
                return [
                    'success' => true,
                    'content' => mb_substr($body, 0, self::MAX_CONTENT_LENGTH),
                    'title' => 'JSON Response',
                    'content_type' => 'json',
                ];
            }

            // Извлекаем заголовок страницы
            $title = $this->extractTitle($body);

            // Извлекаем текстовое содержимое из HTML
            $text = $this->extractText($body);

            if (trim($text) === '') {
                $text = 'Не удалось извлечь текстовое содержимое со страницы. Сайт может требовать JavaScript или быть защищён.';
            }

            return [
                'success' => true,
                'content' => mb_substr($text, 0, self::MAX_CONTENT_LENGTH),
                'title' => $title,
                'content_type' => 'website',
            ];

        } catch (Throwable $e) {
            Log::warning('WebScraper: failed to fetch URL.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Не удалось загрузить страницу: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Извлечь заголовок из HTML.
     */
    private function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        return 'Без заголовка';
    }

    /**
     * Извлечь текстовое содержимое из HTML.
     */
    private function extractText(string $html): string
    {
        // Удаляем скрипты, стили, навигацию
        $text = preg_replace('/<script[^>]*>.*?<\/script>/si', ' ', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', ' ', $text);
        $text = preg_replace('/<nav[^>]*>.*?<\/nav>/si', ' ', $text);
        $text = preg_replace('/<footer[^>]*>.*?<\/footer>/si', ' ', $text);
        $text = preg_replace('/<header[^>]*>.*?<\/header>/si', ' ', $text);

        // Заменяем блочные теги на переносы строк
        $text = preg_replace('/<\/?(p|div|h[1-6]|li|br|tr|blockquote|section|article)[^>]*>/si', "\n", $text);

        // Удаляем все HTML-теги
        $text = strip_tags($text);

        // Декодируем HTML-сущности
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Удаляем пустые строки и лишние пробелы
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
