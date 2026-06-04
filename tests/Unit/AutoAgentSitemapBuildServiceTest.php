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
echo getenv('SITEMAP_SOURCE_URL') . "\n" . getenv('SITEMAP_OUTPUT_PATH');
PHP);

        config()->set('services.autoagent_sitemap.script_path', $scriptPath);
        config()->set('services.autoagent_sitemap.source_url', 'https://av8capital.space/sitemap.xml?fid=2');
        config()->set('services.autoagent_sitemap.output_path', '/tmp/autoagent-sitemap.xml');
        config()->set('services.autoagent_sitemap.node_binary', PHP_BINARY);

        $result = app(AutoAgentSitemapBuildService::class)->build(7);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame("https://av8capital.space/sitemap.xml?fid=7\n/tmp/autoagent-sitemap.xml", $result['output']);
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

    public function test_missing_script_copies_generated_sitemap_to_output_path(): void
    {
        $sourcePath = sys_get_temp_dir() . '/autoagent-generated-sitemap.xml';
        $outputPath = sys_get_temp_dir() . '/autoagent-domain/sitemap.xml';
        file_put_contents($sourcePath, '<?xml version="1.0"?><urlset></urlset>');
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        config()->set('services.autoagent_sitemap.script_path', sys_get_temp_dir() . '/missing-autoagent-sitemap/build-sitemap.mjs');
        config()->set('services.autoagent_sitemap.output_path', $outputPath);

        $result = app(AutoAgentSitemapBuildService::class)->build(7, $sourcePath);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame('file_copy', $result['mode']);
        $this->assertFileExists($outputPath);
        $this->assertSame(file_get_contents($sourcePath), file_get_contents($outputPath));
    }
}
