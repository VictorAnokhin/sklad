<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use RuntimeException;

/**
 * Фабрика для создания AI-клиентов на основе конфигурации канала.
 *
 * Позволяет выбирать разных провайдеров (DeepSeek, OpenAI, Atoma и др.)
 * и модели для разных контекстов использования (веб-чат, Telegram, агент).
 *
 * Использование:
 *   $client = app(AiClientFactory::class)->make('web_chat');
 *   $client = app(AiClientFactory::class)->make('telegram');
 *   $response = $client->chat($instructions, $messages);
 */
class AiClientFactory
{
    /**
     * Карта провайдеров: ключ => класс клиента.
     *
     * @var array<string, class-string<AiClientInterface>>
     */
    private const PROVIDER_MAP = [
        'deepseek' => DeepSeekClient::class,
        'openai'   => OpenAiClient::class,
        'atoma'    => AtomaClient::class,
    ];

    /**
     * Создать AI-клиент для указанного канала.
     *
     * @param  string  $channel  Ключ канала из config('ai.channels')
     * @return AiClientInterface
     *
     * @throws RuntimeException Если провайдер не найден или не поддерживается
     */
    public function make(string $channel = 'web_chat'): AiClientInterface
    {
        $channelConfig = config("ai.channels.{$channel}");

        if ($channelConfig === null) {
            throw new RuntimeException("AI channel '{$channel}' is not configured in config/ai.php.");
        }

        $providerName = $channelConfig['provider'] ?? config('ai.default', 'deepseek');
        $modelOverride = $channelConfig['model'] ?? null;
        $temperature = $channelConfig['temperature'] ?? null;
        $maxTokens = $channelConfig['max_tokens'] ?? null;

        // Создаём клиент
        $client = $this->resolveClient($providerName);

        // Устанавливаем модель, если указана
        if ($modelOverride !== null) {
            $client->setModel($modelOverride);
        }

        // Устанавливаем температуру, если указана
        if ($temperature !== null) {
            $client->setTemperature((float) $temperature);
        }

        // Устанавливаем max_tokens, если указаны
        if ($maxTokens !== null) {
            $client->setMaxTokens((int) $maxTokens);
        }

        return $client;
    }

    /**
     * Создать клиент для указанного провайдера.
     *
     * @param  string       $providerName  Ключ провайдера (deepseek, openai, atoma)
     * @param  string|null  $model         Опционально: переопределить модель
     * @return AiClientInterface
     */
    public function makeForProvider(string $providerName, ?string $model = null): AiClientInterface
    {
        $client = $this->resolveClient($providerName);

        if ($model !== null) {
            $client->setModel($model);
        }

        return $client;
    }

    /**
     * Получить список доступных провайдеров.
     *
     * @return array<string, string>  [provider_key => provider_model]
     */
    public function getAvailableProviders(): array
    {
        $providers = [];

        foreach (config('ai.providers', []) as $key => $config) {
            if (! empty($config['api_key'])) {
                $providers[$key] = $config['model'] ?? 'unknown';
            }
        }

        return $providers;
    }

    /**
     * Получить конфигурацию канала.
     *
     * @param  string  $channel
     * @return array<string, mixed>|null
     */
    public function getChannelConfig(string $channel): ?array
    {
        return config("ai.channels.{$channel}");
    }

    /**
     * Разрешить клиент по имени провайдера.
     *
     * @param  string  $providerName
     * @return AiClientInterface
     */
    private function resolveClient(string $providerName): AiClientInterface
    {
        $class = self::PROVIDER_MAP[$providerName] ?? null;

        if ($class === null) {
            throw new RuntimeException(
                "Unknown AI provider '{$providerName}'. " .
                "Available providers: " . implode(', ', array_keys(self::PROVIDER_MAP))
            );
        }

        $client = app($class);

        if (! $client instanceof AiClientInterface) {
            throw new RuntimeException("Provider '{$providerName}' does not implement AiClientInterface.");
        }

        return $client;
    }
}
