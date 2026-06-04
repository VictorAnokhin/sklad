<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class AutoAgentSitemapBuildService
{
    public function build(?int $fid = null, ?string $generatedSitemapPath = null): array
    {
        $project = $this->resolveProject($fid);
        $domain = $this->projectDomain($project);
        $scriptPath = $this->scriptPath($domain);
        $outputPath = $this->outputPath($domain);

        if ($scriptPath !== '' && is_file($scriptPath)) {
            $result = $this->buildViaLocalScript($scriptPath, $fid, $outputPath);
            $result['domain'] = $domain;

            return $result;
        }

        if ($outputPath !== '' && $generatedSitemapPath !== null && File::exists($generatedSitemapPath)) {
            return [
                ...$this->copyGeneratedSitemap($generatedSitemapPath, $outputPath),
                'domain' => $domain,
                'script_path' => $scriptPath,
            ];
        }

        return [
            'success' => true,
            'status' => 'skipped',
            'mode' => 'local_script',
            'domain' => $domain,
            'script_path' => $scriptPath,
            'output_path' => $outputPath,
            'message' => 'AutoAgent sitemap script was not found and no generated sitemap file was available to copy.',
        ];
    }

    private function copyGeneratedSitemap(string $sourcePath, string $outputPath): array
    {
        try {
            $outputDirectory = dirname($outputPath);
            if (!File::isDirectory($outputDirectory)) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'mode' => 'file_copy',
                    'source_path' => $sourcePath,
                    'output_path' => $outputPath,
                    'message' => 'AutoAgent sitemap output directory does not exist: ' . $outputDirectory,
                ];
            }

            if (!is_writable($outputDirectory)) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'mode' => 'file_copy',
                    'source_path' => $sourcePath,
                    'output_path' => $outputPath,
                    'message' => 'AutoAgent sitemap output directory is not writable: ' . $outputDirectory,
                ];
            }

            File::copy($sourcePath, $outputPath);

            return [
                'success' => true,
                'status' => 'completed',
                'mode' => 'file_copy',
                'source_path' => $sourcePath,
                'output_path' => $outputPath,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'mode' => 'file_copy',
                'source_path' => $sourcePath,
                'output_path' => $outputPath,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function buildViaLocalScript(string $scriptPath, ?int $fid = null, string $outputPath = ''): array
    {
        $timeout = (int) config('services.autoagent_sitemap.timeout', 60);
        $nodeBinary = trim((string) config('services.autoagent_sitemap.node_binary', 'node')) ?: 'node';
        $frontendRoot = dirname(dirname($scriptPath));
        $process = new Process([$nodeBinary, $scriptPath], $frontendRoot, $this->scriptEnv($fid, $outputPath), null, $timeout);

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

    private function scriptEnv(?int $fid = null, string $outputPath = ''): array
    {
        return array_filter([
            'SITEMAP_SOURCE_URL' => $this->sourceUrl($fid),
            'SITEMAP_OUTPUT_PATH' => $outputPath !== '' ? $outputPath : null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function sourceUrl(?int $fid = null): string
    {
        $sourceUrl = (string) config('services.autoagent_sitemap.source_url', 'https://av8capital.space/sitemap.xml?fid=2');
        if ($fid === null || $fid <= 0) {
            return $sourceUrl;
        }

        if (preg_match('/([?&])fid=[^&]*/', $sourceUrl)) {
            return preg_replace('/([?&])fid=[^&]*/', '${1}fid=' . $fid, $sourceUrl) ?: $sourceUrl;
        }

        $separator = str_contains($sourceUrl, '?') ? '&' : '?';

        return $sourceUrl . $separator . 'fid=' . $fid;
    }

    private function scriptPath(?string $domain = null): string
    {
        $scriptPath = trim((string) config('services.autoagent_sitemap.script_path', ''));
        if ($scriptPath !== '') {
            return $scriptPath;
        }

        if ($domain === null || $domain === '') {
            return '';
        }

        $template = trim((string) config('services.autoagent_sitemap.script_path_template', ''));
        if ($template !== '') {
            return str_replace('{domain}', $domain, $template);
        }

        $basePath = rtrim((string) config('services.autoagent_sitemap.script_base_path', '/var/www'), '/');
        $relativePath = ltrim((string) config('services.autoagent_sitemap.script_relative_path', 'scripts/build-sitemap.mjs'), '/');

        return $basePath . '/' . $domain . '/' . $relativePath;
    }

    private function outputPath(?string $domain = null): string
    {
        $outputPath = trim((string) config('services.autoagent_sitemap.output_path', ''));
        if ($outputPath !== '') {
            return $outputPath;
        }

        if ($domain === null || $domain === '') {
            return '';
        }

        $template = trim((string) config('services.autoagent_sitemap.output_path_template', ''));
        if ($template !== '') {
            return str_replace('{domain}', $domain, $template);
        }

        $basePath = rtrim((string) config('services.autoagent_sitemap.output_base_path', '/var/www'), '/');
        $relativePath = ltrim((string) config('services.autoagent_sitemap.output_relative_path', 'sitemap.xml'), '/');

        return $basePath . '/' . $domain . '/' . $relativePath;
    }

    private function resolveProject(?int $fid = null): ?Project
    {
        if ($fid === null || $fid <= 0) {
            return null;
        }

        try {
            return Project::query()->find($fid);
        } catch (\Throwable) {
            return null;
        }
    }

    private function projectDomain(?Project $project): ?string
    {
        if (!$project) {
            return null;
        }

        foreach ([$project->url ?? null, $project->phone ?? null] as $candidate) {
            $domain = $this->domainFromUrl((string) $candidate);
            if ($domain !== null) {
                return $domain;
            }
        }

        return null;
    }

    private function domainFromUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('~^https?://~i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        return strtolower(trim($host));
    }
}
