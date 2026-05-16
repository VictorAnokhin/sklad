<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class VoiceService
{
    /**
     * Speech-to-Text: транскрибация аудио через OpenAI Whisper API.
     *
     * @param  UploadedFile  $audio  Аудиофайл (webm, mp3, wav, ogg, etc.)
     * @param  string  $language  Язык распознавания (ru, ua, en)
     * @return string Распознанный текст
     */
    public function transcribe(UploadedFile $audio, string $language = 'ru'): string
    {
        $apiKey = trim((string) config('services.openai.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY).');
        }

        $langMap = ['ru' => 'ru', 'ua' => 'uk', 'en' => 'en'];

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->timeout(60)
                ->connectTimeout(15)
                ->attach(
                    'file',
                    $audio->getContent(),
                    $audio->getClientOriginalName(),
                )
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => $langMap[$language] ?? 'ru',
                    'response_format' => 'json',
                ]);
        } catch (Throwable $e) {
            throw new RuntimeException('Whisper STT request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException('Whisper returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Whisper returned non-JSON response.');
        }

        $text = trim((string) data_get($payload, 'text', ''));
        if ($text === '') {
            throw new RuntimeException('Whisper returned empty transcription.');
        }

        return $text;
    }

    /**
     * Text-to-Speech: генерация аудио из текста через OpenAI TTS API.
     *
     * @param  string  $text  Текст для озвучивания
     * @param  string  $language  Язык (ru, ua, en)
     * @param  string  $voice  Голос (alloy, echo, fable, nova, shimmer, coral)
     * @return string Бинарное содержимое аудиофайла (MP3)
     */
    public function synthesize(string $text, string $language = 'ru', string $voice = 'nova'): string
    {
        $apiKey = trim((string) config('services.openai.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY).');
        }

        $voice = in_array($voice, ['alloy', 'echo', 'fable', 'nova', 'shimmer', 'coral'], true)
            ? $voice
            : 'nova';

        // OpenAI TTS поддерживает только ru-RU / en-US. Украинский через en-US.
        $langMap = ['ru' => 'ru-RU', 'ua' => 'en-US', 'en' => 'en-US'];

        try {
            $response = Http::asJson()
                ->withToken($apiKey)
                ->timeout(60)
                ->connectTimeout(15)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => 'tts-1',
                    'input' => $text,
                    'voice' => $voice,
                    'response_format' => 'mp3',
                ]);
        } catch (Throwable $e) {
            throw new RuntimeException('OpenAI TTS request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException('OpenAI TTS returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
        }

        $audio = $response->body();
        if ($audio === '') {
            throw new RuntimeException('OpenAI TTS returned empty audio.');
        }

        return $audio;
    }
}
