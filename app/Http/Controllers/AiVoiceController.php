<?php

namespace App\Http\Controllers;

use App\Services\VoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiVoiceController extends Controller
{
    public function __construct(
        private readonly VoiceService $voiceService,
    ) {}

    /**
     * POST /api/ai/voice/stt
     *
     * Speech-to-Text: принимает аудиофайл, возвращает распознанный текст.
     * Удобно для мобильных интерфейсов, где браузерный SpeechRecognition недоступен.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function stt(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'mimes:webm,mp3,wav,ogg,flac,m4a,opus', 'max:10240'],
            'language' => ['nullable', 'string', 'in:ru,ua,en'],
        ]);

        $audio = $request->file('audio');
        $language = trim((string) ($request->input('language', 'ru')));

        try {
            $text = $this->voiceService->transcribe($audio, $language);
        } catch (Throwable $e) {
            Log::warning('STT transcription failed.', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Speech recognition failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        return response()->json([
            'text' => $text,
            'language' => $language,
        ]);
    }

    /**
     * POST /api/ai/voice/tts
     *
     * Text-to-Speech: принимает текст, возвращает аудиофайл (MP3).
     * Клиент может воспроизвести ответ через <audio> или AudioContext.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function tts(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        $request->validate([
            'text' => ['required', 'string', 'min:2', 'max:4096'],
            'language' => ['nullable', 'string', 'in:ru,ua,en'],
            'voice' => ['nullable', 'string', 'in:alloy,echo,fable,nova,shimmer,coral'],
        ]);

        $text = trim($request->input('text'));
        $language = trim((string) ($request->input('language', 'ru')));
        $voice = trim((string) ($request->input('voice', 'nova')));

        try {
            $audio = $this->voiceService->synthesize($text, $language, $voice);
        } catch (Throwable $e) {
            Log::warning('TTS synthesis failed.', [
                'language' => $language,
                'text_length' => mb_strlen($text),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Text-to-speech synthesis failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        return response($audio, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) strlen($audio),
            'Content-Disposition' => 'inline; filename="speech.mp3"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
