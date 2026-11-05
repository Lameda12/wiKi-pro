<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;

/**
 * Turns wiki source (Markdown + [[wikilinks]]) into sanitised HTML.
 */
final class ArticleRenderer
{
    /** Ids already used by the page chrome; headings must never claim them. */
    private const RESERVED_IDS = ['content', 'reading-controls', 'rc-trigger', 'rc-title'];

    /**
     * One pass over the source. Fenced blocks and inline code spans are matched first
     * (and returned untouched) so wikilink syntax inside code stays literal.
     */
    private const TOKEN_PATTERN = '/
        (?<code>
            (?:^|\n)[ ]{0,3}(?<fence>`{3,}|~{3,})[^\n]*\n.*?(?:\n[ ]{0,3}\k<fence>[`~]*[ \t]*(?=\n|$)|\z)
          | (?<tick>`+)(?!`)(?:(?!\n\n).)+?(?<!`)\k<tick>(?!`)
        )
      | \[\[(?<target>[^\[\]|\n]+?)(?:\|(?<label>[^\[\]\n]*))?\]\]
    /xsu';

    private readonly CommonMarkConverter $markdown;

    private readonly HTMLPurifier $purifier;

    public function __construct()
    {
        $this->markdown = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ]);

        // The core converter has no tables or ~~strikethrough~~; the allow-list below expects both.
        $environment = $this->markdown->getEnvironment();
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new StrikethroughExtension);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,del,code,pre,blockquote,ul,ol,li,kbd,h1,h2,h3,h4,h5,h6,a[href|class|title],img[src|alt|title|width|height],table,thead,tbody,tr,th,td,hr,sup,sub,span[class]');
        $config->set('Attr.AllowedClasses', ['wikilink', 'redlink']);
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Cache.DefinitionImpl', null);

        $this->purifier = new HTMLPurifier($config);
    }

    public function render(string $source): string
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);

        // 1-2. Swap wikilinks for opaque sentinels so Markdown cannot mangle them.
        [$source, $links] = $this->extractWikilinks($source);

        // 3. Markdown -> HTML.
        $html = $this->markdown->convert($source)->getContent();

        // 4. Sentinels -> anchors.
        $html = $this->restoreWikilinks($html, $links);

        // 5. Sanitise.
        $html = $this->purifier->purify($html);

        // 6-7. Heading anchors (after purifying, which strips `id`).
        return $this->addHeadingIds($html);
    }

    /**
     * @return array<int, array{level: int, id: string, text: string}>
     */
    public function extractToc(string $html): array
    {
        preg_match_all('#<h([23])\s[^>]*?id="([^"]+)"[^>]*>(.*?)</h\1>#is', $html, $matches, PREG_SET_ORDER);

        return array_map(static fn (array $m): array => [
            'level' => (int) $m[1],
            'id' => $m[2],
            'text' => trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ], $matches);
    }

    /**
     * @return array{0: string, 1: array<string, array{slug: string, display: string, exists: bool}>}
     */
    private function extractWikilinks(string $source): array
    {
        $token = 'WL'.bin2hex(random_bytes(6));
        $found = [];

        $source = preg_replace_callback(
            self::TOKEN_PATTERN,
            function (array $m) use ($token, &$found): string {
                if (($m['code'] ?? '') !== '') {
                    return $m[0];
                }

                $target = trim($m['target']);
                $slug = Str::slug($target);

                if ($slug === '') {
                    return $m[0];
                }

                $label = trim($m['label'] ?? '');
                $sentinel = $token.'N'.count($found).'N';
                $found[$sentinel] = [
                    'slug' => $slug,
                    'display' => $label !== '' ? $label : $target,
                    'exists' => false,
                ];

                return $sentinel;
            },
            $source,
        ) ?? $source;

        // One query for every link on the page.
        $existing = array_flip(Article::existingSlugs(array_column($found, 'slug')));

        foreach ($found as $sentinel => $link) {
            $found[$sentinel]['exists'] = isset($existing[$link['slug']]);
        }

        return [$source, $found];
    }

    /**
     * @param  array<string, array{slug: string, display: string, exists: bool}>  $links
     */
    private function restoreWikilinks(string $html, array $links): string
    {
        $replacements = [];

        foreach ($links as $sentinel => $link) {
            $class = $link['exists'] ? 'wikilink' : 'wikilink redlink';
            $replacements[$sentinel] = sprintf(
                '<a href="/wiki/%s" class="%s">%s</a>',
                rawurlencode($link['slug']),
                $class,
                htmlspecialchars($link['display'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return $replacements === [] ? $html : strtr($html, $replacements);
    }

    /**
     * Give every heading a slug id, suffixing -2, -3 ... on collisions.
     */
    private function addHeadingIds(string $html): string
    {
        $used = array_fill_keys(self::RESERVED_IDS, true);

        return preg_replace_callback(
            '#<h([1-6])>(.*?)</h\1>#is',
            static function (array $m) use (&$used): string {
                $text = html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $base = Str::slug($text) ?: 'section';
                $id = $base;

                for ($n = 2; isset($used[$id]); $n++) {
                    $id = "{$base}-{$n}";
                }

                $used[$id] = true;

                return "<h{$m[1]} id=\"{$id}\">{$m[2]}</h{$m[1]}>";
            },
            $html,
        ) ?? $html;
    }
}
