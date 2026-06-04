<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class AutoAgentSitemapBuildService
{
    public function build(): array
    {
        $buildUrl = trim((string) config('services.autoagent_sitemap.build_url', ''));

        if ($buildUrl !== '') {
            return $this->buildViaHttp($buildUrl);
        }

        $scriptPath = trim((string) config('services.autoagent_sitemap.script_path', ''));
        if ($scriptPath !== '' && is_file($scriptPath)) {
            return $this->buildViaLocalScript($scriptPath);
        }

        return [
            'success' => false,
            'status' => 'skipped',
            'message' => 'AutoAgent sitemap build is not configured.',
        ];
    }

    private function buildViaHttp(string $buildUrl): array
    {
        $secret = trim((string) config('services.autoagent_sitemap.secret', ''));
        $timeout = (int) config('services.autoagent_sitemap.timeout', 60);
        $request = Http::acceptJson()->timeout($timeout);

        if ($secret !== '') {
            $request = $request->withHeaders([
                'X-Manager-AI-Bridge-Secret' => $secret,
            ]);
        }

        try {
            $response = $request->post($buildUrl, $this->payload());
            $data = $response->json();

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'mode' => 'http',
                    'message' => is_array($data) && isset($data['error']) ? (string) $data['error'] : 'AutoAgent sitemap HTTP build failed.',
                    'http_status' => $response->status(),
                    'response' => $data,
                ];
            }

            return [
                'success' => true,
                'status' => 'completed',
                'mode' => 'http',
                'url' => $buildUrl,
                'response' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'mode' => 'http',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function buildViaLocalScript(string $scriptPath): array
    {
        $timeout = (int) config('services.autoagent_sitemap.timeout', 60);
        $nodeBinary = trim((string) config('services.autoagent_sitemap.node_binary', 'node')) ?: 'node';
        $outputPath = trim((string) config('services.autoagent_sitemap.output_path', ''));
        $frontendRoot = dirname(dirname($scriptPath));
        $process = new Process([$nodeBinary, $scriptPath], $frontendRoot, null, null, $timeout);
        $process->setEnv($this->scriptEnv());

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'mode' => 'local_script',
                    'message' => trim($process->getErrorOutput()) ?: 'AutoAgent sitemap local build failed.',
                    'exit_code' => $process->getExitCode(),
                    'output' => trim($process->getOutput()),
                ];
            }

            return [
                'success' => true,
                'status' => 'completed',
                'mode' => 'local_script',
                'script_path' => $scriptPath,
                'output_path' => $outputPath,
                'output' => trim($process->getOutput()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'mode' => 'local_script',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function payload(): array
    {
        return array_filter([
            'source_url' => (string) config('services.autoagent_sitemap.source_url', 'https://av8capital.space/sitemap.xml?fid=2'),
            'output_path' => trim((string) config('services.autoagent_sitemap.output_path', '')) ?: null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function scriptEnv(): array
    {
        return array_filter([
            'SITEMAP_SOURCE_URL' => (string) config('services.autoagent_sitemap.source_url', 'https://av8capital.space/sitemap.xml?fid=2'),
            'SITEMAP_OUTPUT_PATH' => trim((string) config('services.autoagent_sitemap.output_path', '')) ?: null,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
