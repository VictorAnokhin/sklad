<?php

namespace Tests\Unit;

use App\Services\AutoAgentSitemapBuildService;
use Tests\TestCase;

class AutoAgentSitemapBuildServiceTest extends TestCase
{
    public function test_local_script_receives_requested_fid_in_source_url(): void
    {
        $scriptPath = sys_get_temp_dir() . '/autoagent-sitemap-test/scripts/build-sitemap.mjs';
        if (!is_dir(dirname($scriptPath))) {
            mkdir(dirname($scriptPath), 0777, true);
        }

        file_put_contents($scriptPath, <<<'PHP'
<?php
echo getenv('SITEMAP_SOURCE_URL');
PHP);

        config()->set('services.autoagent_sitemap.script_path', $scriptPath);
        config()->set('services.autoagent_sitemap.source_url', 'https://av8capital.space/sitemap.xml?fid=2');
        config()->set('services.autoagent_sitemap.output_path', '');
        config()->set('services.autoagent_sitemap.node_binary', PHP_BINARY);

        $result = app(AutoAgentSitemapBuildService::class)->build(7);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame('https://av8capital.space/sitemap.xml?fid=7', $result['output']);
    }

    public function test_missing_script_is_skipped_without_failure(): void
    {
        config()->set('services.autoagent_sitemap.script_path', sys_get_temp_dir() . '/missing-autoagent-sitemap/build-sitemap.mjs');
        config()->set('services.autoagent_sitemap.source_url', 'https://av8capital.space/sitemap.xml');
        config()->set('services.autoagent_sitemap.output_path', '');

        $result = app(AutoAgentSitemapBuildService::class)->build(7);

        $this->assertTrue($result['success']);
        $this->assertSame('skipped', $result['status']);
    }
}
