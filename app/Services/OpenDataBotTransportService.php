<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class OpenDataBotTransportService
{
    public function normalizePlate(string $plate): string
    {
        $normalized = Str::upper(trim($plate));

        return preg_replace('/[^A-ZА-ЯІЇЄҐ0-9]/u', '', $normalized) ?? '';
    }

    public function lookup(string $plate): array
    {
        $normalized = $this->normalizePlate($plate);

        if ($normalized === '') {
            throw new InvalidArgumentException('Вкажіть номерний знак.');
        }

        $transportUrl = trim((string) config('services.opendatabot.transport_url', ''));
        $apiToken = trim((string) config('services.opendatabot.api_token', ''));

        if ($transportUrl === '') {
            throw new RuntimeException('OpenDataBot API URL is not configured.');
        }

        if ($apiToken === '') {
            throw new RuntimeException('OpenDataBot API token is not configured.');
        }

        $response = Http::acceptJson()
            ->timeout(max(1, (int) config('services.opendatabot.timeout', 12)))
            ->connectTimeout(5)
            ->get($transportUrl, [
                'number' => $normalized,
                'apiKey' => $apiToken,
            ]);

        return [
            'success' => $response->successful(),
            'plate' => $normalized,
            'status' => $response->status(),
            'data' => $response->json() ?? $response->body(),
        ];
    }
}
