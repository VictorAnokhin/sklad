<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки AI-провайдеров и каналов.
    | Позволяет выбирать разных провайдеров и модели для разных контекстов
    | (веб-чат, Telegram бот, агент).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Провайдер по умолчанию
    |--------------------------------------------------------------------------
    |
    | Используется, если для канала не указан конкретный провайдер.
    |
    */
    'default' => env('AI_DEFAULT_PROVIDER', 'deepseek'),

    /*
    |--------------------------------------------------------------------------
    | Конфигурация провайдеров
    |--------------------------------------------------------------------------
    |
    | Каждый провайдер может иметь свой API-ключ, базовый URL, модель и таймаут.
    | Здесь же можно добавлять новых провайдеров (например, claude, gemini, groq).
    |
    */
    'providers' => [

        'deepseek' => [
            'api_base' => env('DEEPSEEK_API_BASE', 'https://api.deepseek.com'),
            'api_key'  => env('DEEPSEEK_API_KEY', ''),
            'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'timeout'  => (int) env('DEEPSEEK_TIMEOUT', 60),
        ],

        'openai' => [
            'api_base' => env('OPENAI_API_BASE', 'https://api.openai.com'),
            'api_key'  => env('OPENAI_API_KEY', ''),
            'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout'  => (int) env('OPENAI_TIMEOUT', 60),
        ],

        'atoma' => [
            'api_base' => env('ATOMA_API_BASE', 'https://api.atoma.network'),
            'api_key'  => env('ATOMA_API_KEY', ''),
            'model'    => env('ATOMA_MODEL', 'openai/gpt-4o-mini'),
            'timeout'  => (int) env('ATOMA_TIMEOUT', 60),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Каналы (контексты использования)
    |--------------------------------------------------------------------------
    |
    | Каждый канал — это отдельный контекст с возможностью выбора:
    |   - provider  : какой провайдер использовать (ключ из 'providers' выше)
    |   - model     : какая модель (null = модель провайдера по умолчанию)
    |   - temperature: температура генерации
    |   - max_tokens: максимальное количество токенов в ответе
    |
    | Это позволяет, например:
    |   - Веб-чат: DeepSeek (deepseek-chat)
    |   - Telegram: OpenAI (gpt-4o-mini)
    |   - Агент: DeepSeek (deepseek-chat)
    |
    */
    'channels' => [

        /*
        | Веб-чат (сайт / админ-панель).
        | Используется ChatService для AiChatController и BackendAgentChatController.
        */
        'web_chat' => [
            'provider'    => env('AI_WEB_CHAT_PROVIDER', 'deepseek'),
            'model'       => env('AI_WEB_CHAT_MODEL', null),
            'temperature' => (float) env('AI_WEB_CHAT_TEMPERATURE', 0.35),
            'max_tokens'  => (int) env('AI_WEB_CHAT_MAX_TOKENS', 1500),
        ],

        /*
        | Telegram бот.
        | Используется TelegramChatService и TelegramAgent.
        */
        'telegram' => [
            'provider'    => env('AI_TELEGRAM_PROVIDER', 'deepseek'),
            'model'       => env('AI_TELEGRAM_MODEL', null),
            'temperature' => (float) env('AI_TELEGRAM_TEMPERATURE', 0.3),
            'max_tokens'  => (int) env('AI_TELEGRAM_MAX_TOKENS', 2000),
            'fid'         => (int) env('AI_TELEGRAM_FID', 12),
        ],

        /*
        | Agent (бэкенд).
        | Используется FrontendAgent и BackendAgent.
        */
        'agent' => [
            'provider'    => env('AI_AGENT_PROVIDER', 'deepseek'),
            'model'       => env('AI_AGENT_MODEL', null),
            'temperature' => (float) env('AI_AGENT_TEMPERATURE', 0.35),
            'max_tokens'  => (int) env('AI_AGENT_MAX_TOKENS', 1500),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки Function Calling (tools)
    |--------------------------------------------------------------------------
    |
    | Глобальные настройки для механизма function calling.
    |
    */
    'tools' => [
        /*
        | Максимальное количество итераций tool calling.
        | Предотвращает бесконечный цикл вызова инструментов.
        */
        'max_iterations' => (int) env('AI_TOOLS_MAX_ITERATIONS', 10),
    ],

];
