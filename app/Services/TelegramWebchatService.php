<?php

namespace App\Services;

use App\Events\TelegramWebchatOperatorMessageReceived;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\TelegramWebchatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebchatService
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolveSite(array $payload, int $fid): ?array
    {
        if (! (bool) config('services.telegram_webchat.enabled')) {
            return null;
        }

        $token = trim((string) config('services.telegram_webchat.bot_token'));
        if ($token === '') {
            return null;
        }

        $domain = $this->normalizeDomain((string) ($payload['site_domain'] ?? ''));
        $sites = config('services.telegram_webchat.sites', []);
        if (! is_array($sites)) {
            return null;
        }

        foreach ($sites as $key => $site) {
            if (! is_array($site) || ! (bool) ($site['enabled'] ?? false)) {
                continue;
            }

            $domains = array_map(fn ($item): string => $this->normalizeDomain((string) $item), (array) ($site['domains'] ?? []));
            if ($domain !== '' && in_array($domain, $domains, true)) {
                return $this->normalizeSiteConfig((string) $key, $site, $domain);
            }
        }

        return null;
    }

    public function enabledFor(array $payload, int $fid): bool
    {
        return $this->resolveSite($payload, $fid) !== null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function diagnostics(array $payload, int $fid = 0): array
    {
        $domain = $this->normalizeDomain((string) ($payload['site_domain'] ?? ''));
        $globalEnabled = (bool) config('services.telegram_webchat.enabled');
        $botToken = trim((string) config('services.telegram_webchat.bot_token'));
        $sites = config('services.telegram_webchat.sites', []);
        $siteDiagnostics = [];
        $matchedSite = null;

        if (is_array($sites)) {
            foreach ($sites as $key => $site) {
                if (! is_array($site)) {
                    continue;
                }

                $domains = array_map(fn ($item): string => $this->normalizeDomain((string) $item), (array) ($site['domains'] ?? []));
                $matchesDomain = $domain !== '' && in_array($domain, $domains, true);
                $siteEnabled = (bool) ($site['enabled'] ?? false);
                $chatId = trim((string) ($site['chat_id'] ?? config('services.telegram_webchat.operator_chat_id', '')));

                $siteDiagnostics[(string) $key] = [
                    'enabled' => $siteEnabled,
                    'domains' => $domains,
                    'matches_domain' => $matchesDomain,
                    'chat_id_present' => $chatId !== '',
                    'thread_id_present' => trim((string) ($site['thread_id'] ?? '')) !== '',
                ];

                if ($matchesDomain) {
                    $matchedSite = (string) $key;
                }
            }
        }

        $resolved = $this->resolveSite($payload, $fid);
        $reason = null;
        if (! $globalEnabled) {
            $reason = 'TELEGRAM_WEBCHAT_ENABLED is false';
        } elseif ($botToken === '') {
            $reason = 'TELEGRAM_WEBCHAT_BOT_TOKEN and TELEGRAM_BOT_TOKEN are empty';
        } elseif ($matchedSite === null) {
            $reason = 'site_domain does not match any configured TELEGRAM_WEBCHAT_*_DOMAINS';
        } elseif (! (bool) ($siteDiagnostics[$matchedSite]['enabled'] ?? false)) {
            $reason = "TELEGRAM_WEBCHAT_".strtoupper($matchedSite)."_ENABLED is false";
        } elseif (! (bool) ($siteDiagnostics[$matchedSite]['chat_id_present'] ?? false)) {
            $reason = "TELEGRAM_WEBCHAT_".strtoupper($matchedSite)."_CHAT_ID is empty";
        }

        return [
            'enabled' => $resolved !== null,
            'reason' => $resolved !== null ? null : $reason,
            'site_domain' => $domain,
            'fid' => $fid,
            'global_enabled' => $globalEnabled,
            'bot_token_present' => $botToken !== '',
            'webhook_secret_present' => trim((string) config('services.telegram_webchat.webhook_secret')) !== '',
            'operator_chat_id_present' => trim((string) config('services.telegram_webchat.operator_chat_id')) !== '',
            'matched_site' => $matchedSite,
            'resolved_site' => $resolved,
            'sites' => $siteDiagnostics,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function forwardUserMessage(ChatSession $session, ChatMessage $message, array $payload, int $fid, ?int $firma): array
    {
        $site = $this->resolveSite($payload, $fid);
        if ($site === null) {
            throw new \RuntimeException('Telegram webchat site is not configured.');
        }

        $chatId = trim((string) ($site['chat_id'] ?? ''));
        if ($chatId === '') {
            throw new \RuntimeException('Telegram webchat chat_id is not configured.');
        }

        $telegramPayload = [
            'chat_id' => $chatId,
            'text' => $this->formatUserMessage($session, $message, $payload, $site),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $threadId = trim((string) ($site['thread_id'] ?? ''));
        if ($threadId !== '') {
            $telegramPayload['message_thread_id'] = $threadId;
        }

        $response = Http::timeout((int) config('services.telegram_webchat.timeout', 10))
            ->asForm()
            ->post($this->apiUrl('sendMessage'), $telegramPayload);

        if (! $response->successful()) {
            Log::warning('Telegram webchat sendMessage failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'session_token' => $session->session_token,
            ]);

            throw new \RuntimeException('Telegram webchat sendMessage failed.');
        }

        $data = $response->json();
        $result = is_array($data) ? ($data['result'] ?? []) : [];
        $telegramMessageId = (int) ($result['message_id'] ?? 0);
        if ($telegramMessageId <= 0) {
            throw new \RuntimeException('Telegram webchat sendMessage returned no message_id.');
        }

        TelegramWebchatMessage::create([
            'chat_session_id' => $session->id,
            'chat_message_id' => $message->id,
            'fid' => $fid > 0 ? $fid : null,
            'firma' => $firma,
            'site_key' => $site['key'] ?? null,
            'site_domain' => $site['domain'] ?? null,
            'telegram_chat_id' => $chatId,
            'telegram_thread_id' => $threadId !== '' ? $threadId : null,
            'telegram_message_id' => $telegramMessageId,
            'telegram_reply_to_message_id' => null,
            'direction' => 'web_to_telegram',
            'payload' => [
                'request' => $this->safePayload($payload),
                'telegram_result' => $result,
            ],
        ]);

        return [
            'site_key' => $site['key'] ?? null,
            'site_domain' => $site['domain'] ?? null,
            'telegram_chat_id' => $chatId,
            'telegram_thread_id' => $threadId !== '' ? $threadId : null,
            'telegram_message_id' => $telegramMessageId,
        ];
    }

    /**
     * Mirror an assistant/agent answer into the same Telegram operator thread.
     */
    public function mirrorAssistantMessage(ChatSession $session, ChatMessage $message): void
    {
        $target = TelegramWebchatMessage::query()
            ->where('chat_session_id', $session->id)
            ->where('direction', 'web_to_telegram')
            ->latest('id')
            ->first();

        if ($target === null) {
            return;
        }

        $payload = [
            'chat_id' => $target->telegram_chat_id,
            'text' => $this->formatAssistantMessage($session, $message),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($target->telegram_thread_id !== null && $target->telegram_thread_id !== '') {
            $payload['message_thread_id'] = $target->telegram_thread_id;
        }

        try {
            $response = Http::timeout((int) config('services.telegram_webchat.timeout', 10))
                ->asForm()
                ->post($this->apiUrl('sendMessage'), $payload);

            if (! $response->successful()) {
                Log::warning('Telegram webchat assistant mirror failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'session_token' => $session->session_token,
                ]);

                return;
            }

            $data = $response->json();
            $result = is_array($data) ? ($data['result'] ?? []) : [];
            $telegramMessageId = (int) ($result['message_id'] ?? 0);
            if ($telegramMessageId <= 0) {
                return;
            }

            TelegramWebchatMessage::create([
                'chat_session_id' => $session->id,
                'chat_message_id' => $message->id,
                'fid' => $message->fid,
                'firma' => $message->firma,
                'site_key' => $target->site_key,
                'site_domain' => $target->site_domain,
                'telegram_chat_id' => $target->telegram_chat_id,
                'telegram_thread_id' => $target->telegram_thread_id,
                'telegram_message_id' => $telegramMessageId,
                'telegram_reply_to_message_id' => null,
                'direction' => 'web_to_telegram',
                'payload' => [
                    'mirror_role' => 'assistant',
                    'telegram_result' => $result,
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('Telegram webchat assistant mirror exception.', [
                'message' => $e->getMessage(),
                'session_token' => $session->session_token,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    public function handleWebhookUpdate(array $update): array
    {
        $telegramMessage = $update['message'] ?? null;
        if (! is_array($telegramMessage)) {
            return $this->ignored('no_message', $update);
        }

        if ((bool) ($telegramMessage['from']['is_bot'] ?? false)) {
            return $this->ignored('bot_message', $telegramMessage);
        }

        $text = trim((string) ($telegramMessage['text'] ?? $telegramMessage['caption'] ?? ''));
        if ($text === '') {
            return $this->ignored('empty_text', $telegramMessage);
        }

        $chatId = (string) ($telegramMessage['chat']['id'] ?? '');
        $messageId = (int) ($telegramMessage['message_id'] ?? 0);
        $replyToMessageId = (int) ($telegramMessage['reply_to_message']['message_id'] ?? 0);
        if ($chatId === '' || $messageId <= 0 || $replyToMessageId <= 0) {
            return $this->ignored('not_reply', $telegramMessage);
        }

        $source = TelegramWebchatMessage::query()
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_message_id', $replyToMessageId)
            ->latest('id')
            ->first();

        if ($source === null) {
            return $this->ignored('unknown_reply', $telegramMessage);
        }

        $session = $source->session;
        if ($session === null) {
            return $this->ignored('missing_session', $telegramMessage);
        }

        $existing = TelegramWebchatMessage::query()
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_message_id', $messageId)
            ->first();

        if ($existing !== null) {
            return ['ok' => true, 'duplicate' => true, 'chat_message_id' => $existing->chat_message_id];
        }

        $command = $this->replyModeCommand($text);
        if ($command !== null) {
            return $this->handleReplyModeCommand($command, $session, $source, $telegramMessage, $update);
        }

        if (($session->reply_mode ?? 'agent') !== 'telegram') {
            $this->sendOperatorNotice(
                chatId: $chatId,
                threadId: isset($telegramMessage['message_thread_id']) ? (string) $telegramMessage['message_thread_id'] : $source->telegram_thread_id,
                text: "Сейчас режим webchat-сессии: <code>agent</code>.\n".
                    "Обычный Reply не отправлен клиенту. Ответьте <code>/telegram</code>, чтобы оператор перехватил следующие сообщения, или <code>/status</code> для проверки режима.",
            );

            return $this->ignored('operator_reply_while_agent_mode', $telegramMessage);
        }

        $operatorName = $this->operatorName($telegramMessage);
        $chatMessage = ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $source->fid,
            'firma' => $source->firma,
            'role' => 'assistant',
            'content' => $text,
            'metadata' => [
                'source' => 'telegram_operator',
                'operator_name' => $operatorName,
                'telegram_chat_id' => $chatId,
                'telegram_message_id' => $messageId,
                'telegram_reply_to_message_id' => $replyToMessageId,
                'telegram_thread_id' => $telegramMessage['message_thread_id'] ?? null,
                'site_key' => $source->site_key,
                'site_domain' => $source->site_domain,
            ],
        ]);

        TelegramWebchatMessage::create([
            'chat_session_id' => $session->id,
            'chat_message_id' => $chatMessage->id,
            'fid' => $source->fid,
            'firma' => $source->firma,
            'site_key' => $source->site_key,
            'site_domain' => $source->site_domain,
            'telegram_chat_id' => $chatId,
            'telegram_thread_id' => isset($telegramMessage['message_thread_id']) ? (string) $telegramMessage['message_thread_id'] : $source->telegram_thread_id,
            'telegram_message_id' => $messageId,
            'telegram_reply_to_message_id' => $replyToMessageId,
            'direction' => 'telegram_to_web',
            'payload' => [
                'update_id' => $update['update_id'] ?? null,
                'message' => $telegramMessage,
            ],
        ]);

        broadcast(new TelegramWebchatOperatorMessageReceived($session, $chatMessage));

        return [
            'ok' => true,
            'chat_session_id' => $session->id,
            'session_token' => $session->session_token,
            'chat_message_id' => $chatMessage->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function ignored(string $reason, array $payload): array
    {
        $message = is_array($payload['message'] ?? null) ? $payload['message'] : $payload;

        Log::info('Telegram webchat webhook update ignored.', [
            'reason' => $reason,
            'chat_id' => $message['chat']['id'] ?? null,
            'message_id' => $message['message_id'] ?? null,
            'message_thread_id' => $message['message_thread_id'] ?? null,
            'reply_to_message_id' => $message['reply_to_message']['message_id'] ?? null,
            'text_present' => trim((string) ($message['text'] ?? $message['caption'] ?? '')) !== '',
        ]);

        return ['ok' => true, 'ignored' => $reason];
    }

    private function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram_webchat.bot_token').'/'.$method;
    }

    /**
     * @param  array<string, mixed>  $site
     * @return array<string, mixed>
     */
    private function normalizeSiteConfig(string $key, array $site, string $domain): array
    {
        return [
            'key' => $key,
            'domain' => $domain,
            'chat_id' => trim((string) ($site['chat_id'] ?? config('services.telegram_webchat.operator_chat_id', ''))),
            'thread_id' => trim((string) ($site['thread_id'] ?? '')),
        ];
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain) ?? $domain;
        $domain = preg_replace('/\/.*$/', '', $domain) ?? $domain;

        return preg_replace('/:\d+$/', '', $domain) ?? $domain;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $site
     */
    private function formatUserMessage(ChatSession $session, ChatMessage $message, array $payload, array $site): string
    {
        $lines = [
            '<b>Новое сообщение вебчата</b>',
            'Сайт: <b>'.$this->escapeHtml((string) ($site['domain'] ?? $payload['site_domain'] ?? 'unknown')).'</b>',
            'FID: '.$this->escapeHtml((string) ($message->fid ?? $session->fid ?? '')),
            'Session: <code>'.$this->escapeHtml((string) $session->session_token).'</code>',
        ];

        $pageUrl = trim((string) ($payload['page_url'] ?? ''));
        if ($pageUrl !== '') {
            $lines[] = 'Страница: '.$this->escapeHtml($pageUrl);
        }

        $visitorUid = trim((string) ($payload['visitor_uid'] ?? ''));
        if ($visitorUid !== '') {
            $lines[] = 'Visitor: <code>'.$this->escapeHtml($visitorUid).'</code>';
        }

        $lines[] = '';
        $lines[] = '<b>Сообщение:</b>';
        $lines[] = $this->escapeHtml($message->content);
        $lines[] = '';
        $lines[] = 'Команды Reply: <code>/telegram</code> оператор отвечает клиенту, <code>/agent</code> отвечает AI, <code>/status</code> текущий режим.';
        $lines[] = 'В режиме <code>/telegram</code> обычный Reply отправляется клиенту в вебчат.';

        return implode("\n", array_filter($lines, fn ($line): bool => $line !== null));
    }

    private function formatAssistantMessage(ChatSession $session, ChatMessage $message): string
    {
        return implode("\n", [
            '<b>Ответ агента вебчата</b>',
            'Session: <code>'.$this->escapeHtml((string) $session->session_token).'</code>',
            'Режим: <code>'.$this->escapeHtml((string) ($session->reply_mode ?: 'agent')).'</code>',
            '',
            $this->escapeHtml($message->content),
            '',
            'Reply <code>/telegram</code>, чтобы оператор перехватил следующие сообщения клиента.',
        ]);
    }

    private function replyModeCommand(string $text): ?string
    {
        $firstToken = strtolower(trim(strtok($text, " \t\r\n") ?: ''));
        $firstToken = preg_replace('/@.+$/', '', $firstToken) ?? $firstToken;

        return match ($firstToken) {
            '/telegram', '/operator' => 'telegram',
            '/agent', '/ai' => 'agent',
            '/status' => 'status',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $telegramMessage
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    private function handleReplyModeCommand(
        string $command,
        ChatSession $session,
        TelegramWebchatMessage $source,
        array $telegramMessage,
        array $update,
    ): array {
        $chatId = (string) ($telegramMessage['chat']['id'] ?? '');
        $messageId = (int) ($telegramMessage['message_id'] ?? 0);
        $replyToMessageId = (int) ($telegramMessage['reply_to_message']['message_id'] ?? 0);
        $mode = $session->reply_mode ?: 'agent';

        if ($command !== 'status') {
            $mode = $command;
            $session->update(['reply_mode' => $mode]);
        }

        TelegramWebchatMessage::create([
            'chat_session_id' => $session->id,
            'chat_message_id' => null,
            'fid' => $source->fid,
            'firma' => $source->firma,
            'site_key' => $source->site_key,
            'site_domain' => $source->site_domain,
            'telegram_chat_id' => $chatId,
            'telegram_thread_id' => isset($telegramMessage['message_thread_id']) ? (string) $telegramMessage['message_thread_id'] : $source->telegram_thread_id,
            'telegram_message_id' => $messageId,
            'telegram_reply_to_message_id' => $replyToMessageId,
            'direction' => 'telegram_command',
            'payload' => [
                'command' => $command,
                'reply_mode' => $mode,
                'update_id' => $update['update_id'] ?? null,
            ],
        ]);

        $this->sendOperatorNotice(
            chatId: $chatId,
            threadId: isset($telegramMessage['message_thread_id']) ? (string) $telegramMessage['message_thread_id'] : $source->telegram_thread_id,
            text: "Режим webchat-сессии: <code>{$mode}</code>\n".
                ($mode === 'telegram'
                    ? 'Обычные Reply оператора будут отправляться клиенту.'
                    : 'Клиенту отвечает AI agent, переписка дублируется сюда.'),
        );

        return [
            'ok' => true,
            'command' => $command,
            'reply_mode' => $mode,
            'session_token' => $session->session_token,
        ];
    }

    private function sendOperatorNotice(string $chatId, ?string $threadId, string $text): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($threadId !== null && $threadId !== '') {
            $payload['message_thread_id'] = $threadId;
        }

        try {
            Http::timeout((int) config('services.telegram_webchat.timeout', 10))
                ->asForm()
                ->post($this->apiUrl('sendMessage'), $payload);
        } catch (Throwable $e) {
            Log::debug('Telegram webchat operator notice failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function safePayload(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'language',
            'page',
            'page_url',
            'site_domain',
            'referrer',
            'visitor_uid',
            'fingerprint_hash',
            'fid',
            'firma',
        ]));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function operatorName(array $message): string
    {
        $from = is_array($message['from'] ?? null) ? $message['from'] : [];
        $parts = array_filter([
            trim((string) ($from['first_name'] ?? '')),
            trim((string) ($from['last_name'] ?? '')),
        ]);

        $name = trim(implode(' ', $parts));
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($from['username'] ?? 'Telegram operator'));
    }
}
