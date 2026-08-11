<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SmsClubService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function sendOtp(string $phone, string $message): array
    {
        $token = $this->apiToken();
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

    public function balance(?string $token = null): array
    {
        $token = trim((string) ($token ?? $this->apiToken()));
        $endpoint = trim((string) config('services.smsclub.balance_endpoint', 'https://im.smsclub.mobi/sms/balance'));

        if ($token === '') {
            throw new RuntimeException('SMS Club token is not configured.');
        }

        $response = $this->http
            ->timeout(10)
            ->withToken($token)
            ->acceptJson()
            ->get($endpoint);

        return $this->parseBalanceResponse($response);
    }

    private function apiToken(): string
    {
        $fallback = trim((string) config('services.smsclub.token', ''));

        if (!Schema::hasTable('conf') || !Schema::hasColumn('conf', 'constanta')) {
            return $fallback;
        }

        $fid = (string) session('fid', '');
        $query = DB::table('conf')
            ->where('type', 'smsclub')
            ->where('name', 'api_key');

        $row = (clone $query)
            ->where('firma', $fid !== '' ? $fid : '0')
            ->first();

        if (!$row) {
            $row = (clone $query)->where('firma', 0)->first();
        }

        $token = trim((string) ($row->constanta ?? ''));

        return $token !== '' ? $token : $fallback;
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

    private function parseBalanceResponse(Response $response): array
    {
        $payload = $response->json();

        if (!$response->successful()) {
            $message = is_array($payload)
                ? (string) ($payload['error_request']['message'] ?? $payload['message'] ?? '')
                : '';

            throw new RuntimeException($message !== '' ? $message : 'SMS Club balance request failed.');
        }

        if (!is_array($payload)) {
            throw new RuntimeException('SMS Club returned an invalid balance payload.');
        }

        if (isset($payload['error_request'])) {
            $message = (string) ($payload['error_request']['message'] ?? $payload['message'] ?? '');
            throw new RuntimeException($message !== '' ? $message : 'SMS Club did not return balance.');
        }

        return $payload;
    }
}
