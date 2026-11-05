<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ArticleRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleRenderer $renderer) {}

    public function index(): View
    {
        $articles = Article::query()->orderBy('title')->paginate(100);

        return view('articles.index', ['articles' => $articles]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $canonical = $this->canonicalSlug($slug);

        if ($canonical !== $slug) {
            return redirect()->route('wiki.show', $canonical, 301);
        }

        $article = Article::query()
            ->with('currentRevision.author')
            ->where('slug', $slug)
            ->firstOrFail();

        $revision = $article->currentRevision ?? abort(404);
        $html = $revision->content_html;
        $toc = $this->renderer->extractToc($html);

        return view('articles.show', compact('article', 'revision', 'html', 'toc'));
    }

    public function edit(string $slug): View|RedirectResponse
    {
        $canonical = $this->canonicalSlug($slug);

        if ($canonical !== $slug) {
            return redirect()->route('wiki.edit', $canonical, 301);
        }

        $article = Article::query()->with('currentRevision')->where('slug', $slug)->first()
            ?? new Article(['slug' => $slug, 'title' => $this->titleFromSlug($slug), 'namespace' => 'main']);

        $this->authorizeEdit($article);

        $source = $article->currentRevision?->content ?? '';
        $isNew = ! $article->exists;

        return view('articles.edit', compact('article', 'source', 'isNew'));
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $slug = $this->canonicalSlug($slug);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:200000'],
            'summary' => ['nullable', 'string', 'max:500'],
            'minor' => ['nullable', 'boolean'],
        ]);

        $article = DB::transaction(function () use ($request, $slug, $data): Article {
            $article = Article::query()->firstOrCreate(
                ['slug' => $slug],
                ['title' => $this->titleFromSlug($slug), 'namespace' => 'main'],
            );

            // Serialise concurrent saves so parent_id always points at the true predecessor.
            $article = Article::query()->whereKey($article->getKey())->lockForUpdate()->firstOrFail();

            $this->authorizeEdit($article);

            $revision = $article->revisions()->create([
                'author_id' => $request->user()?->getAuthIdentifier(),
                'content' => $data['content'],
                'content_html' => $this->renderer->render($data['content']),
                'summary' => $data['summary'] ?? null,
                'minor' => $request->boolean('minor'),
                'parent_id' => $article->current_rev,
            ]);

            $article->update(['current_rev' => $revision->getKey()]);

            return $article;
        });

        return redirect()
            ->route('wiki.show', $article)
            ->with('status', 'Your changes have been saved.');
    }

    public function history(string $slug): View|RedirectResponse
    {
        $canonical = $this->canonicalSlug($slug);

        if ($canonical !== $slug) {
            return redirect()->route('wiki.history', $canonical, 301);
        }

        $article = Article::query()->where('slug', $slug)->firstOrFail();
        $revisions = $article->revisions()->with('author')->paginate(50);

        return view('articles.history', compact('article', 'revisions'));
    }

    /**
     * Normalise a URL segment the same way wikilinks are slugged, so /wiki/PHP and /wiki/php agree.
     */
    private function canonicalSlug(string $slug): string
    {
        $canonical = Str::slug($slug);

        if ($canonical === '') {
            abort(404);
        }

        return $canonical;
    }

    private function titleFromSlug(string $slug): string
    {
        return Str::of($slug)->replace('-', ' ')->title()->toString();
    }

    /**
     * Protected articles (protection > 0) can only be edited by signed-in users.
     */
    private function authorizeEdit(Article $article): void
    {
        abort_if($article->protection > 0 && auth()->guest(), 403, 'This article is protected.');
    }
}
