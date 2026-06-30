<?php

namespace App\Console\Commands;

use App\Models\TrafficRule;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncTrafficRules extends Command
{
    protected $signature = 'traffic-rules:sync
        {--fid=2 : Project identifier}
        {--source=https://zakon.rada.gov.ua/laws/show/1306-2001-%D0%BF.frame : Official document frame URL}';

    protected $description = 'Synchronize every numbered item of the current Ukrainian traffic rules from zakon.rada.gov.ua';

    public function handle(): int
    {
        $fid = max(1, (int) $this->option('fid'));
        $source = trim((string) $this->option('source'));

        $this->info('Завантаження актуальних ПДР з Верховної Ради України…');

        $response = Http::accept('text/html')
            ->withUserAgent('AutoAgent-Traffic-Rules-Sync/1.0')
            ->timeout(45)
            ->retry(3, 500)
            ->get($source);

        if (! $response->successful()) {
            $this->error("Офіційне джерело повернуло HTTP {$response->status()}.");

            return self::FAILURE;
        }

        try {
            $rules = $this->parseRules($response->body());
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        DB::transaction(function () use ($fid, $rules): void {
            $syncedNumbers = [];

            foreach ($rules as $index => $rule) {
                $number = $rule['section_number'];
                $syncedNumbers[] = $number;

                TrafficRule::query()->updateOrCreate(
                    ['fid' => $fid, 'section_number' => $number],
                    [
                        'title' => $rule['title'],
                        'summary' => $rule['summary'],
                        'content' => $rule['content'],
                        'sort_order' => ($index + 1) * 10,
                        'is_published' => true,
                    ],
                );
            }

            TrafficRule::query()
                ->where('fid', $fid)
                ->whereNotIn('section_number', $syncedNumbers)
                ->delete();
        });

        $this->info(sprintf('Синхронізовано %d пунктів ПДР для fid=%d.', count($rules), $fid));
        $this->line("Джерело: {$source}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{section_number:string,title:string,summary:string,content:string}>
     */
    private function parseRules(string $html): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Не вдалося розібрати HTML офіційного документа.');
        }

        $xpath = new DOMXPath($document);
        $paragraphs = $xpath->query('//p[a[@data-tree]]');
        $currentChapter = '';
        $currentNumber = null;
        $items = [];

        foreach ($paragraphs ?: [] as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $anchor = $xpath->query('./a[@data-tree]', $paragraph)?->item(0);
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $tree = (string) $anchor->getAttribute('data-tree');
            $text = $this->cleanText($paragraph->textContent);

            if ($text === '') {
                continue;
            }

            if (preg_match('/^gl(\d+)$/u', $tree) === 1) {
                $currentChapter = preg_replace('/^\d+\.\s*/u', '', $text) ?: $text;
                $currentNumber = null;
                continue;
            }

            if (preg_match('/(?:^|:)pp(\d+(?:\.\d+)+(?:-\d+)?)/u', $tree, $match) !== 1) {
                continue;
            }

            $number = $match[1];
            $isPrimaryParagraph = str_starts_with($tree, 'pp');

            if ($isPrimaryParagraph || ! isset($items[$number])) {
                $content = preg_replace('/^'.preg_quote($number, '/').'\.\s*/u', '', $text) ?: $text;
                $items[$number] = [
                    'section_number' => $number,
                    'title' => $currentChapter !== '' ? $currentChapter : "Пункт {$number}",
                    'summary' => $this->summary($content),
                    'content' => $content,
                ];
                $currentNumber = $number;
                continue;
            }

            if ($currentNumber === $number && ! str_starts_with($tree, 'cm_')) {
                $items[$number]['content'] .= "\n\n".$text;
            }
        }

        if (count($items) < 100) {
            throw new RuntimeException(sprintf(
                'Офіційний документ розібрано некоректно: знайдено лише %d пунктів.',
                count($items),
            ));
        }

        return array_values($items);
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{00A0}\t ]+/u', ' ', $text) ?: $text;
        $text = preg_replace('/\R+/u', ' ', $text) ?: $text;

        return trim($text);
    }

    private function summary(string $content): string
    {
        if (mb_strlen($content) <= 240) {
            return $content;
        }

        return rtrim(mb_substr($content, 0, 237)).'…';
    }
}
