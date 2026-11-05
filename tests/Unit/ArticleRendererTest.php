<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Article;
use App\Services\ArticleRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticleRendererTest extends TestCase
{
    use RefreshDatabase;

    private ArticleRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new ArticleRenderer;
    }

    public function test_existing_wikilinks_are_blue_and_missing_ones_are_red(): void
    {
        Article::create(['slug' => 'laravel', 'title' => 'Laravel']);

        $html = $this->renderer->render('See [[Laravel]] and [[Hypertext Preprocessor]].');

        $this->assertStringContainsString('<a href="/wiki/laravel" class="wikilink">Laravel</a>', $html);
        $this->assertStringContainsString('<a href="/wiki/hypertext-preprocessor" class="wikilink redlink">Hypertext Preprocessor</a>', $html);
    }

    public function test_piped_wikilink_uses_display_text_and_escapes_it(): void
    {
        $html = $this->renderer->render('[[Laravel|the <b>framework</b>]]');

        $this->assertStringContainsString('href="/wiki/laravel"', $html);
        $this->assertStringContainsString('the &lt;b&gt;framework&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_wikilinks_in_code_stay_literal(): void
    {
        $html = $this->renderer->render("Inline `[[Laravel]]` here.\n\n```php\n\$a[[0]];\n[[Laravel]]\n```\n");

        $this->assertStringNotContainsString('wikilink', $html);
        $this->assertSame(2, substr_count($html, '[[Laravel]]'));
    }

    public function test_one_query_resolves_all_links(): void
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->renderer->render('[[A]] [[B]] [[C]] [[A|again]]');

        $this->assertSame(1, $queries);
    }

    public function test_raw_html_and_unsafe_links_are_neutralised(): void
    {
        $html = $this->renderer->render("<script>alert(1)</script>\n\n[x](javascript:alert(1))\n\n<img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_external_links_open_in_a_new_tab_safely(): void
    {
        $html = $this->renderer->render('[ex](https://example.com)');

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('noopener', $html);
    }

    public function test_tables_and_strikethrough_render(): void
    {
        $html = $this->renderer->render("| a | b |\n|---|---|\n| 1 | 2 |\n\n~~gone~~");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<del>gone</del>', $html);
    }

    public function test_headings_get_unique_ids_that_avoid_page_chrome_ids(): void
    {
        $html = $this->renderer->render("## Usage\n\n## Usage\n\n### Usage\n\n## Content\n\n## !!!");

        $this->assertStringContainsString('<h2 id="usage">', $html);
        $this->assertStringContainsString('<h2 id="usage-2">', $html);
        $this->assertStringContainsString('<h3 id="usage-3">', $html);
        $this->assertStringContainsString('<h2 id="content-2">', $html);
        $this->assertStringContainsString('<h2 id="section">', $html);
    }

    public function test_extract_toc_returns_h2_and_h3_in_document_order(): void
    {
        $html = $this->renderer->render("# Title\n\n## One\n\n### One &amp; a half\n\n## Two with [[Link]]\n\n#### Deep");

        $this->assertSame([
            ['level' => 2, 'id' => 'one', 'text' => 'One'],
            ['level' => 3, 'id' => 'one-a-half', 'text' => 'One & a half'],
            ['level' => 2, 'id' => 'two-with-link', 'text' => 'Two with Link'],
        ], $this->renderer->extractToc($html));
    }
}
