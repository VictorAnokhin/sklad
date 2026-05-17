<?php

namespace App\Contracts;

interface AiClientInterface
{
    /**
     * Простой чат-запрос без инструментов.
     *
     * @param  string                                               $instructions  System prompt
     * @param  array<int, array{role: string, content: string}>     $messages      История сообщений
     * @param  array<string, mixed>                                  $options       Доп. опции (temperature, max_tokens, model)
     * @return array{answer: string, model: string, usage: array<string, mixed>}
     */
    public function chat(string $instructions, array $messages, array $options = []): array;

    /**
     * Чат-запрос с поддержкой function calling (tools).
     *
     * @param  string                                               $instructions   System prompt
     * @param  array<int, array{role: string, content: string}>     $messages       История сообщений
     * @param  array<int, array<string, mixed>>                      $tools          Список инструментов (OpenAI JSON Schema)
     * @param  callable(string $name, array $arguments): string      $toolExecutor   Функция выполнения инструментов
     * @param  array<string, mixed>                                  $options        Доп. опции (max_tool_iterations, temperature, model)
     * @return array{answer: string, model: string, usage: array<string, mixed>}
     */
    public function chatWithTools(
        string $instructions,
        array $messages,
        array $tools,
        callable $toolExecutor,
        array $options = [],
    ): array;

    /**
     * Получить название провайдера (deepseek, openai, atoma).
     */
    public function getProviderName(): string;

    /**
     * Установить модель для клиента (переопределяет значение из config).
     */
    public function setModel(?string $model): static;

    /**
     * Получить текущую модель.
     */
    public function getModel(): string;

    /**
     * Установить температуру генерации (переопределяет значение по умолчанию).
     */
    public function setTemperature(?float $temperature): static;

    /**
     * Получить текущую температуру.
     */
    public function getTemperature(): ?float;

    /**
     * Установить максимальное количество токенов (переопределяет значение по умолчанию).
     */
    public function setMaxTokens(?int $maxTokens): static;

    /**
     * Получить текущее максимальное количество токенов.
     */
    public function getMaxTokens(): ?int;
}
