<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project')) {
            return;
        }

        if (!Schema::hasColumn('project', 'url')) {
            Schema::table('project', function (Blueprint $table) {
                $table->text('url')->nullable()->after('phone');
            });
        }

        DB::table('project')
            ->select('id', 'phone', 'url')
            ->orderBy('id')
            ->get()
            ->each(function (object $project): void {
                $currentUrl = trim((string) ($project->url ?? ''));
                $phoneValue = trim((string) ($project->phone ?? ''));

                if ($currentUrl !== '' || $phoneValue === '') {
                    return;
                }

                $normalized = $this->normalizeProjectUrl($phoneValue);
                if ($normalized === null) {
                    return;
                }

                DB::table('project')
                    ->where('id', $project->id)
                    ->update(['url' => $normalized]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('project') || !Schema::hasColumn('project', 'url')) {
            return;
        }

        Schema::table('project', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }

    private function normalizeProjectUrl(string $value): ?string
    {
        if (!preg_match('~^https?://~i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $parts = parse_url($value);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if (!$this->isValidProjectHost($host)) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $scheme . '://' . $host . $port . $path;
    }

    private function isValidProjectHost(string $host): bool
    {
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (!str_contains($host, '.')) {
            return false;
        }

        return (bool) filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }
};
