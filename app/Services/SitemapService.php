<?php

namespace App\Services;

use App\Models\EducationUtility;
use App\Models\Field;
use App\Models\Goods;
use App\Models\News;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SitemapService
{
    private const AUTOAGENT_STATIC_PAGES = [
        '',
        '/automarket',
        '/goods',
        '/service',
        '/delivery',
        '/about',
        '/news',
        '/privacy',
        '/refund-policy',
    ];

    private const AV8_STATIC_PAGES = [
        '',
        '/about',
        '/articles',
        '/bridge',
        '/swap',
        '/mint',
        '/invest',
        '/portfolio',
        '/nft',
        '/fund-accounts',
        '/fund-basket',
        '/know-yourself',
        '/academy',
        '/models',
        '/business-digitization',
        '/whitepaper',
        '/privacy-policy',
        '/terms-of-service',
        '/public-offer',
        '/kyc-aml',
    ];

    public function generate(?int $fid = null): array
    {
        $project = $this->resolveProject($fid);
        $destinationPath = $this->getDestinationPath($project?->id);

        File::ensureDirectoryExists(dirname($destinationPath));
        File::put($destinationPath, $this->buildXml($project?->id));

        return [
            'fid' => $project?->id,
            'path' => $destinationPath,
            'url' => $this->getPublicUrl($project?->id),
            'frontend_url' => $this->resolveFrontendBaseUrl($project),
        ];
    }

    public function getDestinationPath(?int $fid = null): string
    {
        $configuredPath = env('SITEMAP_DESTINATION_PATH');
        if (is_string($configuredPath) && trim($configuredPath) !== '') {
            return $configuredPath;
        }

        $suffix = $fid ? 'project-' . $fid : 'default';

        return storage_path('app/public/sitemaps/' . $suffix . '.xml');
    }

    public function getPublicUrl(?int $fid = null): string
    {
        if (Route::has('sitemap.public')) {
            return route('sitemap.public', array_filter([
                'fid' => $fid,
            ], static fn ($value) => $value !== null));
        }

        $baseUrl = rtrim((string) config('app.url'), '/');

        return $fid
            ? $baseUrl . '/sitemap.xml?fid=' . $fid
            : $baseUrl . '/sitemap.xml';
    }

    public function exists(?int $fid = null): bool
    {
        return File::exists($this->getDestinationPath($fid));
    }

    public function lastModifiedAt(?int $fid = null): ?int
    {
        return $this->exists($fid) ? File::lastModified($this->getDestinationPath($fid)) : null;
    }

    public function read(?int $fid = null, ?string $host = null): ?string
    {
        $resolvedFid = $this->resolveProject($fid, $host)?->id;
        $path = $this->getDestinationPath($resolvedFid);

        if (!File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    public function buildXml(?int $fid = null): string
    {
        $project = $this->resolveProject($fid);
        $baseUrl = $this->resolveFrontendBaseUrl($project);
        $isAv8Fund = $this->isAv8FundProject($project, $baseUrl);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($this->staticPagesForProject($isAv8Fund) as $page) {
            $xml .= $this->formatUrl($baseUrl . $page, '1.0', 'daily');
        }

        if ($isAv8Fund) {
            $xml .= $this->appendNewsUrls($baseUrl, $project?->id, '/articles');
            $xml .= $this->appendEducationUtilityUrls($baseUrl, $project?->id);
        } else {
            $xml .= $this->appendNewsUrls($baseUrl, $project?->id);
            $xml .= $this->appendCatalogSectionUrls($baseUrl, $project?->id);
            $xml .= $this->appendGoodsUrls($baseUrl, $project?->id);
        }

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }

    public function resolveProject(?int $fid = null, ?string $host = null): ?Project
    {
        if (!Schema::hasTable('project')) {
            return null;
        }

        if ($fid !== null && $fid > 0) {
            return Project::query()->find($fid);
        }

        if ($host !== null && $host !== '') {
            return $this->resolveProjectByHost($host);
        }

        return null;
    }

    private function resolveFrontendBaseUrl(?Project $project): string
    {
        $projectUrl = $this->extractProjectBaseUrl($project);
        if ($projectUrl !== null) {
            return $projectUrl;
        }

        $configuredUrl = env('FRONTEND_URL');
        if (is_string($configuredUrl) && trim($configuredUrl) !== '') {
            return rtrim($configuredUrl, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    private function staticPagesForProject(bool $isAv8Fund): array
    {
        return $isAv8Fund ? self::AV8_STATIC_PAGES : self::AUTOAGENT_STATIC_PAGES;
    }

    private function isAv8FundProject(?Project $project, string $baseUrl): bool
    {
        $host = strtolower((string) (parse_url($baseUrl, PHP_URL_HOST) ?: ''));

        return (int) ($project?->id ?? 0) === 12
            || $host === 'av8.fund'
            || str_ends_with($host, '.av8.fund');
    }

    private function resolveProjectByHost(string $host): ?Project
    {
        $normalizedHost = strtolower(trim($host));

        return Project::query()
            ->get()
            ->first(function (Project $project) use ($normalizedHost) {
                $projectHost = $this->extractProjectHost($project);

                return $projectHost !== null && $projectHost === $normalizedHost;
            });
    }

    private function extractProjectBaseUrl(?Project $project): ?string
    {
        if (!$project) {
            return null;
        }

        foreach ([$project->url ?? null, $project->phone ?? null] as $candidate) {
            $normalized = $this->normalizeBaseUrl($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function extractProjectHost(?Project $project): ?string
    {
        $baseUrl = $this->extractProjectBaseUrl($project);
        if ($baseUrl === null) {
            return null;
        }

        return parse_url($baseUrl, PHP_URL_HOST) ?: null;
    }

    private function normalizeBaseUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('~^https?://~i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $parts = parse_url($value);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower((string) $parts['host']);
        if (!$this->isValidProjectHost($host)) {
            return null;
        }

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

    private function appendNewsUrls(string $baseUrl, ?int $fid = null, string $routePrefix = '/news'): string
    {
        if (!class_exists(News::class) || !Schema::hasTable('news')) {
            return '';
        }

        try {
            $query = News::query();

            if (Schema::hasColumn('news', 'view')) {
                $query->where('view', 1);
            }

            if ($fid !== null && Schema::hasColumn('news', 'firma')) {
                $query->where(function ($builder) use ($fid) {
                    $builder->where('firma', $fid)->orWhere('firma', 0);
                });
            }

            $xml = '';
            foreach ($query->orderByDesc('id')->get() as $item) {
                $lastMod = $this->resolveLastMod($item->updated_at ?? null, $item->dt ?? null);
                $xml .= $this->formatUrl($baseUrl . rtrim($routePrefix, '/') . '/' . $item->id, '0.8', 'weekly', $lastMod);
            }

            return $xml;
        } catch (QueryException $e) {
            Log::warning('Skipping sitemap news URLs because the query failed.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Skipping sitemap news URLs because an unexpected error occurred.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        }

        return '';
    }

    private function appendCatalogSectionUrls(string $baseUrl, ?int $fid = null): string
    {
        if (!class_exists(Field::class) || !Schema::hasTable('field')) {
            return '';
        }

        try {
            $tree = Field::getCatalogTree($fid ?: 2, 'ru');
            if (!$tree instanceof Collection || $tree->isEmpty()) {
                return '';
            }

            $xml = '';
            foreach ($tree as $topSection) {
                $topHref = $this->buildSectionHref($topSection, null);
                if ($topHref !== null) {
                    $xml .= $this->formatUrl($baseUrl . $topHref, '0.8', 'weekly');
                }

                foreach (($topSection['children'] ?? []) as $childSection) {
                    $childHref = $this->buildSectionHref($childSection, $topSection);
                    if ($childHref !== null) {
                        $xml .= $this->formatUrl($baseUrl . $childHref, '0.7', 'weekly');
                    }
                }
            }

            return $xml;
        } catch (QueryException $e) {
            Log::warning('Skipping sitemap section URLs because the query failed.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Skipping sitemap section URLs because an unexpected error occurred.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        }

        return '';
    }

    private function appendGoodsUrls(string $baseUrl, ?int $fid = null): string
    {
        if (!class_exists(Goods::class) || !Schema::hasTable('comp')) {
            return '';
        }

        try {
            $query = Goods::query()
                ->where('comp.web', '1');

            if ($fid !== null) {
                $query->where('comp.firma', $fid);
            }

            $select = ['comp.id', 'comp.nickname'];
            if (Schema::hasColumn('comp', 'dt')) {
                $select[] = 'comp.dt';
            }

            $items = $query->select($select)
                ->orderByDesc('comp.top')
                ->orderByDesc('comp.hit')
                ->orderBy('comp.id')
                ->get();

            $xml = '';
            foreach ($items as $item) {
                $identifier = $this->getProductIdentifier($item);
                $lastMod = $this->resolveLastMod(null, $item->dt ?? null);
                $xml .= $this->formatUrl($baseUrl . '/product/' . $identifier, '0.6', 'weekly', $lastMod);
            }

            return $xml;
        } catch (QueryException $e) {
            Log::warning('Skipping sitemap goods URLs because the query failed.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Skipping sitemap goods URLs because an unexpected error occurred.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        }

        return '';
    }

    private function appendEducationUtilityUrls(string $baseUrl, ?int $fid = null): string
    {
        if (!class_exists(EducationUtility::class) || !Schema::hasTable('education_utilities')) {
            return '';
        }

        try {
            $query = EducationUtility::query();

            if ($fid !== null && Schema::hasColumn('education_utilities', 'project_id')) {
                $query->where('project_id', $fid);
            }

            if (Schema::hasColumn('education_utilities', 'is_active')) {
                $query->where('is_active', true);
            }

            $select = ['slug'];
            if (Schema::hasColumn('education_utilities', 'updated_at')) {
                $select[] = 'updated_at';
            }

            $xml = '';
            foreach ($query->select($select)->orderBy('position')->orderBy('id')->get() as $item) {
                $slug = trim((string) ($item->slug ?? ''));
                if ($slug === '') {
                    continue;
                }

                $lastMod = $this->resolveLastMod($item->updated_at ?? null, null);
                $xml .= $this->formatUrl($baseUrl . '/models/' . rawurlencode($slug), '0.8', 'weekly', $lastMod);
            }

            return $xml;
        } catch (QueryException $e) {
            Log::warning('Skipping sitemap education utility URLs because the query failed.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Skipping sitemap education utility URLs because an unexpected error occurred.', [
                'error' => $e->getMessage(),
                'fid' => $fid,
            ]);
        }

        return '';
    }

    private function resolveLastMod(mixed $updatedAt, mixed $legacyDate): string
    {
        if ($updatedAt instanceof \DateTimeInterface) {
            return $updatedAt->format('Y-m-d');
        }

        $legacyDate = trim((string) $legacyDate);
        if ($legacyDate !== '') {
            $formats = ['d-m-Y', 'Y-m-d'];
            foreach ($formats as $format) {
                $parsed = \DateTimeImmutable::createFromFormat($format, $legacyDate);
                if ($parsed instanceof \DateTimeImmutable) {
                    return $parsed->format('Y-m-d');
                }
            }
        }

        return date('Y-m-d');
    }

    private function formatUrl(string $url, string $priority, string $freq, ?string $lastMod = null): string
    {
        return "  <url>\n"
            . '    <loc>' . htmlspecialchars($url) . "</loc>\n"
            . '    <lastmod>' . ($lastMod ?: date('Y-m-d')) . "</lastmod>\n"
            . "    <changefreq>{$freq}</changefreq>\n"
            . "    <priority>{$priority}</priority>\n"
            . "  </url>\n";
    }

    private function buildSectionHref(array $section, ?array $topSection = null): ?string
    {
        $top = $topSection ?? $section;
        $topSegment = $this->getSectionSegment($top);
        if ($topSegment === null) {
            return null;
        }

        if ($topSection === null) {
            return '/goods/' . $topSegment;
        }

        $childSegment = $this->getSectionSegment($section);
        if ($childSegment === null) {
            return '/goods/' . $topSegment;
        }

        return '/goods/' . $topSegment . '/' . $childSegment;
    }

    private function getSectionSegment(array $section): ?string
    {
        $rawLink = trim((string) ($section['link'] ?? ''));
        if ($rawLink !== '') {
            return rawurlencode($rawLink);
        }

        $name = trim((string) ($section['name'] ?? ''));
        if ($name === '') {
            $id = (int) ($section['id'] ?? 0);
            return $id > 0 ? (string) $id : null;
        }

        return rawurlencode($this->slugify($name));
    }

    private function getProductIdentifier(object $item): string
    {
        $nickname = trim((string) ($item->nickname ?? ''));

        return $nickname !== '' ? rawurlencode($nickname) : (string) $item->id;
    }

    private function slugify(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '-', trim($value)) ?? trim($value));
    }
}
