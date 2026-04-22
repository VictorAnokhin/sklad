<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--fid= : Project ID for sitemap generation}';
    protected $description = 'Генерация sitemap.xml для выбранного проекта';

    public function handle(SitemapService $sitemapService)
    {
        $this->info('Начало генерации sitemap...');
        $fid = (int) $this->option('fid');

        try {
            $result = $sitemapService->generate($fid > 0 ? $fid : null);
            $this->info("Sitemap успешно сохранен по адресу: {$result['path']}");
            $this->info("Публичная ссылка: {$result['url']}");
            if (!empty($result['frontend_url'])) {
                $this->info("Frontend URL: {$result['frontend_url']}");
            }
        } catch (\Exception $e) {
            $this->error("Ошибка при сохранении sitemap: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
