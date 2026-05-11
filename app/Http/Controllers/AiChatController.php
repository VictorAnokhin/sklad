<?php

namespace App\Http\Controllers;

use App\Services\AtomaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiChatController extends Controller
{
    public function chat(Request $request, AtomaClient $atoma): JsonResponse
    {
        $payload = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'language' => ['nullable', 'string', 'in:ru,ua,en'],
            'page' => ['nullable', 'string', 'max:80'],
            'wallet' => ['nullable', 'string', 'max:100'],
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1600'],
        ]);

        $language = (string) ($payload['language'] ?? 'ru');
        $page = (string) ($payload['page'] ?? 'unknown');
        $wallet = trim((string) ($payload['wallet'] ?? ''));

        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt($language),
            ],
        ];

        foreach (($payload['history'] ?? []) as $historyItem) {
            $messages[] = [
                'role' => (string) $historyItem['role'],
                'content' => (string) $historyItem['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => "Контекст страницы: {$page}\n".
                'Кошелек пользователя: '.($wallet !== '' ? $wallet : 'не подключен')."\n".
                "Вопрос пользователя: {$payload['message']}",
        ];

        try {
            $result = $atoma->chat($messages);
        } catch (Throwable $e) {
            Log::warning('Atoma chat failed.', [
                'message' => $e->getMessage(),
                'page' => $page,
                'wallet' => $wallet,
            ]);

            return response()->json([
                'message' => 'AI assistant is temporarily unavailable.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        return response()->json([
            'answer' => $result['answer'],
            'provider' => 'atoma',
            'model' => $result['model'],
            'usage' => $result['usage'],
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ]);
    }

    private function systemPrompt(string $language): string
    {
        $answerLanguage = match ($language) {
            'ua' => 'украинском',
            'en' => 'английском',
            default => 'русском',
        };

        return <<<PROMPT
Ты AI-консультант AV8Capital. Отвечай на {$answerLanguage} языке.

Твоя задача: помогать посетителям пользоваться продуктами AV8Capital, особенно Sui-разделами portfolio, invest, token admin, fund basket, fund accounts и mint.

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
PROMPT;
    }

    private function suiGasSponsorAvailable(): bool
    {
        return trim((string) config('services.sui.gas_sponsor_private_key', '')) !== ''
            || trim((string) config('services.shinami.gas_access_key', '')) !== '';
    }
}
