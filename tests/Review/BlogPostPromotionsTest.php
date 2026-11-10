<?php

namespace Tests\Review;

use App\Admin;
use App\Blog\{Post, Topic};
use App\Services\Blog\PostPromotions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Storage};

class BlogPostPromotionsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    private function article()
    {
        return Model::withoutEvents(fn () => create(Post::class, [
            'title' => 'Selected promotion article', 'slug' => 'selected-promotion-article',
            'description' => 'Notes on reading piano music.', 'content' => '<p>Article content stays unchanged.</p>',
            'cover_path' => null, 'published_at' => now(), 'creator_id' => auth('admin')->id(),
        ]));
    }

    private function fields(Post $post): array
    {
        return ['title' => $post->title, 'description' => $post->description, 'content' => $post->content];
    }

    private function xpath($response): \DOMXPath
    {
        $document = new \DOMDocument; @$document->loadHTML($response->getContent());
        return new \DOMXPath($document);
    }

    public function test_existing_posts_keep_default_cards_without_writing_settings_on_reads()
    {
        $post = $this->article();
        $service = app(PostPromotions::class);
        $this->assertSame('default', $service->choiceFor($post));
        $editor = $this->get(route('admin.posts.edit', $post))->assertOk()->assertSee('Promotion');
        $this->assertCount(1, $this->xpath($editor)->query('//select[@name="promotion"]/option[@value="default" and @selected]'));
        auth()->shouldUse('web');
        $page = $this->get(route('posts.show', $post))->assertOk();
        $this->assertCount(3, $this->xpath($page)->query('//aside//article[contains(@class,"promo-card")]'));
        $this->assertDatabaseCount('blog_post_promotions', 0);
        if ($directory = getenv('BLOG_PROMOTION_PREVIEW_DIR')) {
            file_put_contents($directory.'/default.html', $page->getContent());
        }
    }

    public static function destinations(): array
    {
        return [['shop', 'shop.home'], ['games', 'games.home'], ['learn', 'learn.home'],
            ['webapp', 'webapp.discover'], ['metronome', 'metronome.home']];
    }

    /** @dataProvider destinations */
    public function test_selected_hub_is_the_only_article_promotion_and_mobile_output_stays_unchanged($choice, $route)
    {
        $post = $this->article();
        $before = $post->fresh()->toArray();
        $service = app(PostPromotions::class);
        $service->save($post, $choice);
        $this->assertSame($choice, $service->choiceFor($post));
        $this->assertSame($before, $post->fresh()->toArray());
        auth()->shouldUse('web');
        $page = $this->get(route('posts.show', $post))->assertOk()->assertSee('Article content stays unchanged.');
        $xpath = $this->xpath($page);
        $this->assertCount(1, $xpath->query('//aside//article[contains(@class,"promo-card")]'));
        $this->assertCount(2, $xpath->query('//aside//article[contains(@class,"promo-card")]//a[@href="'.route($route).'"]'));
        $embedded = $this->get(route('posts.app', $post))->assertOk()->assertSee('Article content stays unchanged.');
        $this->assertCount(0, $this->xpath($embedded)->query('//article[contains(@class,"promo-card")]'));
        Http::assertNothingSent();
        if ($choice === 'games' && $directory = getenv('BLOG_PROMOTION_PREVIEW_DIR')) {
            $html = $this->get(route('admin.posts.edit', $post))->assertOk()->getContent();
            file_put_contents($directory.'/editor.html', str_replace(['http://admin.localhost', 'http://localhost'], 'http://127.0.0.1:8773', $html));
            file_put_contents($directory.'/article.html', str_replace(['http://blog.localhost', 'http://localhost'], 'http://127.0.0.1:8773', $page->getContent()));
        }
    }

    public function test_none_hides_the_sidebar_and_default_restores_the_three_cards()
    {
        $post = $this->article(); $service = app(PostPromotions::class);
        $service->save($post, 'none');
        auth()->shouldUse('web');
        $page = $this->get(route('posts.show', $post))->assertOk();
        $xpath = $this->xpath($page);
        $this->assertCount(0, $xpath->query('//aside'));
        $page->assertDontSee('data-hub-promotions')->assertDontSee('js/components/hub-promotions.js');
        $this->assertCount(1, $xpath->query('//div[contains(@class,"col-12") and contains(@class,"editorial-layout__content")]'));
        if ($directory = getenv('BLOG_PROMOTION_PREVIEW_DIR')) {
            file_put_contents($directory.'/none.html', $page->getContent());
        }
        $service->save($post, 'default');
        $page = $this->get(route('posts.show', $post))->assertOk();
        $this->assertCount(3, $this->xpath($page)->query('//aside//article[contains(@class,"promo-card")]'));

        $post->update(['published_at' => null]);
        $draft = $this->get(route('posts.show', $post))->assertOk()->assertSee('not published');
        $this->assertCount(1, $this->xpath($draft)->query('//aside//div[@data-hub-promotions]'));
        $this->assertCount(0, $this->xpath($draft)->query('//aside//*[@data-ds-preview]'));
    }

    public function test_native_create_and_edit_save_the_choice_and_older_forms_preserve_it()
    {
        $this->get(route('admin.posts.create'))->assertOk()->assertSee('post-promotion-destination');
        $this->post(route('admin.posts.store'), ['title' => 'New promotion post', 'description' => 'New description.', 'content' => '<p>New content.</p>', 'promotion' => 'games'])
            ->assertRedirect(route('admin.posts.index'))->assertSessionHas('status', 'Post created.');
        $post = Post::where('title', 'New promotion post')->firstOrFail();
        $this->assertDatabaseHas('blog_post_promotions', ['post_id' => $post->id, 'hub' => 'games']);
        $this->patch(route('admin.posts.update', $post), $this->fields($post))
            ->assertRedirect(route('admin.posts.edit', $post))->assertSessionHas('status', 'Post saved.');
        $this->assertSame('games', app(PostPromotions::class)->choiceFor($post));
        $this->patch(route('admin.posts.update', $post), $this->fields($post) + ['promotion' => 'none'])
            ->assertRedirect(route('admin.posts.edit', $post));
        $editor = $this->get(route('admin.posts.edit', $post))->assertOk();
        $this->assertCount(1, $this->xpath($editor)->query('//select[@name="promotion"]/option[@value="none" and @selected]'));
    }

    public function test_invalid_destinations_are_rejected_and_validation_keeps_the_unsaved_choice()
    {
        $post = $this->article(); app(PostPromotions::class)->save($post, 'shop');
        $this->withExceptionHandling();
        foreach (['https://example.test', 'unknown', ['games'], null, ''] as $promotion) {
            $this->patchJson(route('admin.posts.update', $post), $this->fields($post) + compact('promotion'))
                ->assertUnprocessable()->assertJsonValidationErrors('promotion');
            $this->assertSame('shop', app(PostPromotions::class)->choiceFor($post));
        }
        $url = route('admin.posts.edit', ['post' => $post, 'search' => 'piano']);
        $data = $this->fields($post); $data['title'] = ''; $data['promotion'] = 'games';
        $this->from($url)->patch(route('admin.posts.update', $post), $data)->assertRedirect($url)->assertSessionHasErrors('title');
        $page = $this->get($url)->assertOk();
        $this->assertCount(1, $this->xpath($page)->query('//select[@name="promotion"]/option[@value="games" and @selected]'));
        $this->assertSame('shop', app(PostPromotions::class)->choiceFor($post));
    }

    public function test_promotion_failure_rolls_back_post_fields_topics_and_the_setting()
    {
        $post = $this->article(); app(PostPromotions::class)->save($post, 'shop');
        $topic = Topic::create(['name' => 'Technique', 'slug' => 'technique', 'creator_id' => auth('admin')->id()]);
        $post->topics()->attach($topic);
        $service = \Mockery::mock(PostPromotions::class);
        $service->shouldReceive('save')->once()->andReturnUsing(function ($post) {
            DB::table('blog_post_promotions')->where('post_id', $post->id)->update(['hub' => 'games']);
            throw new \RuntimeException('Simulated save failure');
        });
        $this->app->instance(PostPromotions::class, $service);
        $fields = $this->fields($post); $fields['title'] = 'Changed title'; $fields['topics'] = []; $fields['promotion'] = 'games';
        try {
            $this->patch(route('admin.posts.update', $post), $fields);
            $this->fail('Expected the save to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated save failure', $exception->getMessage());
        }
        $this->assertSame($post->title, $post->fresh()->title);
        $this->assertSame(1, $post->topics()->count());
        $this->assertDatabaseHas('blog_post_promotions', ['post_id' => $post->id, 'hub' => 'shop']);
    }

    public function test_post_deletion_cleans_the_setting_and_guest_mutations_require_admin_authentication()
    {
        $post = $this->article(); app(PostPromotions::class)->save($post, 'games');
        $this->withExceptionHandling();
        auth('admin')->logout();
        $this->patch(route('admin.posts.update', $post), $this->fields($post) + ['promotion' => 'none'])->assertRedirect(route('admin.login.show'));
        $this->assertDatabaseHas('blog_post_promotions', ['post_id' => $post->id, 'hub' => 'games']);
        $post->delete();
        $this->assertDatabaseCount('blog_post_promotions', 0);
    }
}
