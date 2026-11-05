<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Services\ArticleRenderer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function __construct(private readonly ArticleRenderer $renderer) {}

    public function run(): void
    {
        // Link targets are resolved when an article is saved, so articles are created
        // in an order that makes every link render correctly: "PHP" first as a stub (so
        // the others can link to it), and its full text last, once [[Laravel]] and
        // [[Readability]] exist. [[Hypertext Preprocessor]] has no article and stays red.
        $this->publish('php', 'PHP', "PHP is a programming language for the web.\n\n## History\n\nStub, to be expanded.", 'Create stub');
        $this->publish('laravel', 'Laravel', $this->laravel(), 'Initial article');
        $this->publish('readability', 'Readability', $this->readability(), 'Initial article');
        $this->publish('php', 'PHP', $this->php(), 'Expand history, add syntax and ecosystem sections');
    }

    private function publish(string $slug, string $title, string $content, string $summary): void
    {
        DB::transaction(function () use ($slug, $title, $content, $summary): void {
            $article = Article::query()->firstOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'namespace' => 'main'],
            );

            $revision = $article->revisions()->create([
                'author_id' => null,
                'content' => $content,
                'content_html' => $this->renderer->render($content),
                'summary' => $summary,
                'minor' => false,
                'parent_id' => $article->current_rev,
            ]);

            $article->update(['current_rev' => $revision->getKey()]);
        });
    }

    private function php(): string
    {
        return <<<'MD'
**PHP** is a general-purpose scripting language built for the web. It started in 1994 as a set of small tools for maintaining a personal home page and grew into the engine behind a large share of the sites you have visited. The name is a recursive backronym, historically *PHP: Hypertext Preprocessor*; see [[Hypertext Preprocessor]] for the full story.

## History

Rasmus Lerdorf wrote the first version as a handful of C programs that logged visits to his online résumé. Others asked for the tools, he released them, and a community rewrote the parser twice in the following years. PHP 3 (1998) is recognisably the language we have today; PHP 7 (2015) roughly doubled its speed; PHP 8 (2020) added a JIT compiler, union types, attributes and match expressions.

The language earned a reputation for inconsistency in its early standard library. Most of that is long behind it, and modern PHP is a typed, object-oriented language with a mature package ecosystem.

## Syntax

PHP code lives between `<?php` tags and can be mixed freely with HTML, though modern projects keep the two apart. Variables start with a dollar sign, and since PHP 7 functions can declare strict parameter and return types.

```php
<?php

declare(strict_types=1);

function greet(string $name, int $times = 1): string
{
    return str_repeat("Hello, {$name}! ", $times);
}

echo greet('wiki', 2);
```

Small features add up. Named arguments let you skip optional parameters, `readonly` properties make value objects safe, and `enum` types replace piles of string constants.

### Types

Scalar types are `int`, `float`, `string` and `bool`. Composite types include `array`, `object`, `callable` and `iterable`, and a parameter may accept several at once using a union such as `int|string`.

### Operators

Comparison deserves care. The loose `==` operator converts types before comparing; the strict `===` does not. Reach for `===` unless you have a reason not to.

## Ecosystem

Dependencies are managed by Composer, and the community agrees on shared standards through the PHP-FIG. The best known framework is [[Laravel]], which is a good place to see modern PHP in practice. Documentation for the language itself is unusually good, and the clarity of its prose is a matter of [[Readability|readable design]] as much as accuracy.

| Version | Released | Notable additions                |
| ------- | -------- | -------------------------------- |
| 5.0     | 2004     | Object model rewrite             |
| 7.0     | 2015     | Scalar types, large speed gains  |
| 8.0     | 2020     | JIT, attributes, match, union types |
| 8.3     | 2023     | Typed class constants, `json_validate()` |

## Further reading

- The official manual at [php.net](https://www.php.net/manual/en/)
- The PHP-FIG standards, known as PSRs
- Articles on [[Readability]] for how to write documentation people finish
MD;
    }

    private function laravel(): string
    {
        return <<<'MD'
**Laravel** is a web application framework for [[PHP]] that favours expressive, readable code. It handles the repetitive parts of building a site, namely routing, database access, validation and sessions, so you can spend your time on what makes the application different.

## Philosophy

The framework aims for developer happiness. Code should read close to how you would describe the intent aloud, and conventions replace configuration wherever a sensible default exists. Whether it fully lives up to that depends on taste, but the goal shapes every API.

## Core concepts

### Routing

Routes map a URL and an HTTP verb to a closure or controller method. Route parameters are passed to the handler, and models can be resolved automatically from them.

```php
Route::get('/wiki/{slug}', [ArticleController::class, 'show'])->name('wiki.show');
```

### Eloquent

Eloquent is the object-relational mapper. Each table has a model, and relationships such as `hasMany` and `belongsTo` are declared as methods. Eager loading avoids the classic N+1 query problem.

### Blade

Blade is the templating language. It compiles to plain PHP, escapes output by default, and supports layouts through `@extends` and `@section`.

## Ecosystem

Official packages cover authentication, queues, search and deployment. Because most of the interesting decisions are made for you, two Laravel projects tend to look alike, which helps new contributors become productive quickly. That consistency is a quiet form of [[Readability]]: the structure of a codebase is something people read too.

## Testing

Laravel ships with PHPUnit integration and expressive helpers for HTTP tests, database assertions and fakes for mail, queues and events. A feature test reads like a script of what a user does.
MD;
    }

    private function readability(): string
    {
        return <<<'MD'
**Readability** is how easily a reader can take in a text and understand it. It depends on the writing, but it also depends on how the text is set on the page, and the second half is where most websites lose their readers.

## Why it matters

A reader decides within seconds whether a page is worth the effort. Dense, cramped or low-contrast text raises that effort, and people leave long before they find out whether the content was good. For readers with dyslexia, low vision or tired eyes, poor typography is not an inconvenience but a barrier.

## Line length

Lines that are too long make the eye lose its place when it returns to the start of the next line. Lines that are too short break the rhythm of reading into constant jumps. A measure of roughly 45 to 75 characters is widely recommended for body text, which is why this site sets prose at about 68 characters.

## Leading and size

Leading, the space between lines, should grow with line length. A body line-height near 1.5 to 1.7 gives each line room to breathe. Text size is best left generous: small type is the most common accessibility failure on the web, and readers should be able to enlarge it without breaking the layout.

## Contrast

Text needs enough contrast against its background. The Web Content Accessibility Guidelines set a minimum ratio of 4.5 to 1 for body text. Pure black on pure white exceeds it but can shimmer for some readers, so softer pairings and sepia or dark themes are worth offering.

## Structure

Headings, short paragraphs and lists let readers scan before they commit. A table of contents that follows the reader down the page shows where they are in a long article. Links should look like links, and a link to a page that does not exist yet should say so; wikis traditionally colour those links red.

See also [[PHP]] and [[Laravel]] for examples of long technical articles.
MD;
    }
}
