<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use RuntimeException;

class SmsClubService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function sendOtp(string $phone, string $message): array
    {
        $token = trim((string) config('services.smsclub.token', ''));
        $sender = trim((string) config('services.smsclub.sender', ''));
        $endpoint = trim((string) config('services.smsclub.endpoint', 'https://im.smsclub.mobi/sms/send'));

        if ($token === '') {
            throw new RuntimeException('SMS Club token is not configured.');
        }

        if ($sender === '') {
            throw new RuntimeException('SMS Club sender is not configured.');
        }

        $response = $this->http
            ->timeout(10)
            ->withToken($token)
            ->acceptJson()
            ->post($endpoint, [
                'phone' => [$phone],
                'message' => $message,
                'src_addr' => $sender,
            ]);

        return $this->parseResponse($response);
    }

    private function parseResponse(Response $response): array
    {
        $payload = $response->json();

        if (!$response->successful()) {
            $message = is_array($payload)
                ? (string) ($payload['error_request']['message'] ?? $payload['message'] ?? '')
                : '';

            throw new RuntimeException($message !== '' ? $message : 'SMS Club request failed.');
        }

        if (!is_array($payload)) {
            throw new RuntimeException('SMS Club returned an invalid payload.');
        }

        if (!isset($payload['success_request'])) {
            $message = (string) ($payload['error_request']['message'] ?? $payload['message'] ?? '');

            throw new RuntimeException($message !== '' ? $message : 'SMS Club did not confirm the request.');
        }

        return $payload;
    }
}
