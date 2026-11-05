<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Revision;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_seeded_articles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Laravel', 'PHP', 'Readability']);
    }

    public function test_article_renders_with_toc_and_correct_link_colours(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get(route('wiki.show', 'php'))->assertOk();

        $response->assertSee('<aside class="toc" aria-label="Contents">', false);
        $response->assertSee('class="page has-toc"', false);
        $response->assertSee('<a href="/wiki/laravel" class="wikilink">Laravel</a>', false);
        $response->assertSee('class="wikilink redlink">Hypertext Preprocessor</a>', false);
    }

    public function test_missing_article_is_404_with_create_link(): void
    {
        $this->get('/wiki/does-not-exist')
            ->assertNotFound()
            ->assertSee(route('wiki.edit', 'does-not-exist'), false);
    }

    public function test_url_is_canonicalised(): void
    {
        $this->get('/wiki/Some%20Title')->assertRedirect('/wiki/some-title');
    }

    public function test_edit_form_is_prefilled_for_existing_and_empty_for_new(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('wiki.edit', 'php'))
            ->assertOk()
            ->assertSee('Editing PHP')
            ->assertSee('**PHP** is a general-purpose', false);

        $this->get(route('wiki.edit', 'brand-new'))
            ->assertOk()
            ->assertSee('Creating Brand New');
    }

    public function test_saving_creates_a_revision_chain_and_redirects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('wiki.update', 'fresh'), [
            'content' => "## First\n\nHello [[Fresh]].",
            'summary' => 'Start',
        ])->assertRedirect(route('wiki.show', 'fresh'))->assertSessionHas('status');

        $this->put(route('wiki.update', 'fresh'), [
            'content' => "## First\n\nHello again.",
            'minor' => '1',
        ])->assertRedirect(route('wiki.show', 'fresh'));

        $article = Article::where('slug', 'fresh')->firstOrFail();
        $revisions = $article->revisions;

        $this->assertCount(2, $revisions);
        $this->assertSame($revisions[0]->id, $article->current_rev);
        $this->assertSame($revisions[1]->id, $revisions[0]->parent_id);
        $this->assertNull($revisions[1]->parent_id);
        $this->assertTrue($revisions[0]->minor);
        $this->assertFalse($revisions[1]->minor);
        $this->assertSame($user->id, $revisions[1]->author_id);
        $this->assertStringContainsString('<h2 id="first">First</h2>', $revisions[0]->content_html);
        $this->assertStringContainsString('class="wikilink"', $revisions[1]->content_html);
    }

    public function test_validation_rejects_empty_and_oversized_content(): void
    {
        $this->put(route('wiki.update', 'x'), ['content' => ''])->assertSessionHasErrors('content');
        $this->put(route('wiki.update', 'x'), ['content' => str_repeat('a', 200001)])->assertSessionHasErrors('content');
        $this->put(route('wiki.update', 'x'), ['content' => 'ok', 'summary' => str_repeat('s', 501)])->assertSessionHasErrors('summary');

        $this->assertSame(0, Article::count());
    }

    public function test_history_lists_newest_first(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('wiki.history', 'php'))
            ->assertOk()
            ->assertSeeInOrder(['Expand history, add syntax and ecosystem sections', 'Create stub'])
            ->assertSee('class="history"', false);
    }

    public function test_history_shows_no_summary_placeholder_and_minor_badge(): void
    {
        $article = Article::create(['slug' => 'a', 'title' => 'A']);
        Revision::create(['article_id' => $article->id, 'content' => 'x', 'content_html' => '<p>x</p>', 'minor' => true]);

        $this->get(route('wiki.history', 'a'))
            ->assertSee('(no summary)')
            ->assertSee('minor');
    }

    public function test_protected_articles_reject_anonymous_edits(): void
    {
        Article::create(['slug' => 'locked', 'title' => 'Locked', 'protection' => 1]);

        $this->get(route('wiki.edit', 'locked'))->assertForbidden();
        $this->put(route('wiki.update', 'locked'), ['content' => 'nope'])->assertForbidden();
        $this->assertSame(0, Revision::count());

        $this->actingAs(User::factory()->create())
            ->put(route('wiki.update', 'locked'), ['content' => 'fine'])
            ->assertRedirect();
    }
}
