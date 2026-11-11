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
        $this->assertCount(3, $this->xpath($editor)->query('//input[@name="promotions[]" and @checked]'));
        $this->assertSame(['shop', 'games', 'learn'], $service->selectedFor($post));
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
        $this->assertSame([$choice], $service->selectedFor($post));
        $this->assertSame($before, $post->fresh()->toArray());
        auth()->shouldUse('web');
        $page = $this->get(route('posts.show', $post))->assertOk()->assertSee('Article content stays unchanged.');
        $xpath = $this->xpath($page);
        $this->assertCount(1, $xpath->query('//aside//article[contains(@class,"promo-card")]'));
        $this->assertCount(2, $xpath->query('//aside//article[contains(@class,"promo-card")]//a[@href="'.route($route).'"]'));
        $this->assertCount(1, $xpath->query('//aside//a[contains(@class,"pastel-card__action")]//i[contains(@class,"icon-arrow-right")]'));
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
        $this->get(route('admin.posts.create'))->assertOk()->assertSee('post-promotion-shop');
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
        $this->assertCount(0, $this->xpath($editor)->query('//input[@name="promotions[]" and @checked]'));
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
        $this->assertCount(1, $this->xpath($page)->query('//input[@name="promotions[]" and @value="games" and @checked]'));
        $this->assertSame('shop', app(PostPromotions::class)->choiceFor($post));
    }

    public function test_promotion_failure_rolls_back_post_fields_topics_and_the_setting()
    {
        $post = $this->article(); app(PostPromotions::class)->save($post, 'shop');
        $topic = Topic::create(['name' => 'Technique', 'slug' => 'technique', 'creator_id' => auth('admin')->id()]);
        $post->topics()->attach($topic);
        $service = \Mockery::mock(PostPromotions::class);
        $service->shouldReceive('save')->once()->andReturnUsing(function ($post) {
            DB::table('blog_post_promotions')->where('post_id', $post->id)->update(['hub' => 'default', 'hubs' => json_encode(['games', 'webapp'])]);
            throw new \RuntimeException('Simulated save failure');
        });
        $this->app->instance(PostPromotions::class, $service);
        $fields = $this->fields($post); $fields['title'] = 'Changed title'; $fields['topics'] = []; $fields['promotions'] = ['games', 'webapp'];
        try {
            $this->patch(route('admin.posts.update', $post), $fields);
            $this->fail('Expected the save to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated save failure', $exception->getMessage());
        }
        $this->assertSame($post->title, $post->fresh()->title);
        $this->assertSame(1, $post->topics()->count());
        $this->assertDatabaseHas('blog_post_promotions', ['post_id' => $post->id, 'hub' => 'shop', 'hubs' => null]);
    }

    public function test_multiple_hubs_save_in_catalog_order_and_clearing_all_hides_the_sidebar()
    {
        $post = $this->article(); $service = app(PostPromotions::class);
        $this->patch(route('admin.posts.update', $post), $this->fields($post) + [
            'promotions_present' => '1', 'promotions' => ['metronome', 'shop', 'learn'],
        ])->assertRedirect(route('admin.posts.edit', $post));
        $this->assertSame(['shop', 'learn', 'metronome'], $service->selectedFor($post));
        $this->assertSame(['shop', 'learn', 'metronome'], json_decode(DB::table('blog_post_promotions')->where('post_id', $post->id)->value('hubs'), true));
        $before = $post->fresh()->toArray();
        $editor = $this->get(route('admin.posts.edit', $post))->assertOk();
        $this->assertCount(3, $this->xpath($editor)->query('//input[@name="promotions[]" and @checked]'));
        $this->assertSame($before, $post->fresh()->toArray());
        auth()->shouldUse('web');
        $page = $this->get(route('posts.show', $post))->assertOk();
        $this->assertCount(3, $this->xpath($page)->query('//aside//article[contains(@class,"promo-card")]'));
        foreach (['shop.home', 'learn.home', 'metronome.home'] as $route) {
            $this->assertCount(1, $this->xpath($page)->query('//aside//a[contains(@class,"pastel-card__action") and @href="'.route($route).'"]'));
        }
        if ($directory = getenv('BLOG_PROMOTION_PREVIEW_DIR')) {
            file_put_contents($directory.'/multiple-article.html', $page->getContent());
        }
        $this->assertCount(0, $this->xpath($page)->query('//aside//a[@href="'.route('games.home').'"]'));
        $this->get(route('posts.app', $post))->assertOk()->assertDontSee('promo-card');
        $this->patch(route('admin.posts.update', $post), $this->fields($post))->assertRedirect();
        $this->assertSame(['shop', 'learn', 'metronome'], $service->selectedFor($post));
        $this->patch(route('admin.posts.update', $post), $this->fields($post) + ['promotions_present' => '1'])->assertRedirect();
        $this->assertSame([], $service->selectedFor($post));
        $this->assertSame('[]', DB::table('blog_post_promotions')->where('post_id', $post->id)->value('hubs'));
        auth()->shouldUse('web');
        $this->assertCount(0, $this->xpath($this->get(route('posts.show', $post))->assertOk())->query('//aside'));
    }

    public function test_create_can_select_every_hub_and_validation_retains_selections_including_none()
    {
        $choices = array_keys(PostPromotions::HUB_CHOICES);
        $this->post(route('admin.posts.store'), [
            'title' => 'Multiple promotion post', 'description' => 'Selected destinations.', 'content' => '<p>Article content.</p>',
            'promotions_present' => '1', 'promotions' => array_reverse($choices),
        ])->assertRedirect(route('admin.posts.index'));
        $post = Post::where('title', 'Multiple promotion post')->firstOrFail();
        $this->assertSame($choices, app(PostPromotions::class)->selectedFor($post));
        $this->withExceptionHandling();
        $url = route('admin.posts.edit', $post);
        foreach ([['learn', 'webapp'], []] as $selection) {
            $data = $this->fields($post); $data['title'] = ''; $data['promotions_present'] = '1';
            if ($selection) $data['promotions'] = $selection;
            $this->from($url)->patch(route('admin.posts.update', $post), $data)->assertRedirect($url)->assertSessionHasErrors('title');
            $editor = $this->get($url)->assertOk();
            $this->assertCount(count($selection), $this->xpath($editor)->query('//input[@name="promotions[]" and @checked]'));
            foreach ($selection as $choice) $this->assertCount(1, $this->xpath($editor)->query('//input[@name="promotions[]" and @checked and @value="'.$choice.'"]'));
            $this->assertSame($choices, app(PostPromotions::class)->selectedFor($post));
        }
        if ($directory = getenv('BLOG_PROMOTION_PREVIEW_DIR')) {
            session()->forget(['_old_input', 'errors']);
            file_put_contents($directory.'/multiple-editor.html', $this->get($url)->assertOk()->getContent());
        }
    }

    public function test_invalid_multiple_selections_cannot_change_the_saved_setting()
    {
        $post = $this->article(); $service = app(PostPromotions::class); $service->save($post, ['games', 'learn']);
        $this->withExceptionHandling();
        foreach (['shop', null, ['unknown'], ['default'], ['none'], ['shop', 'shop'], [['shop']], [1]] as $selection) {
            $response = $this->patchJson(route('admin.posts.update', $post), $this->fields($post) + ['promotions' => $selection]);
            $response->assertUnprocessable();
            $this->assertNotEmpty($response->json('errors'));
            $this->assertSame(['games', 'learn'], $service->selectedFor($post));
        }
        $this->patchJson(route('admin.posts.update', $post), $this->fields($post) + ['promotions_present' => '0'])
            ->assertUnprocessable()->assertJsonValidationErrors('promotions_present');
        $this->assertSame(['games', 'learn'], $service->selectedFor($post));
    }

    public function test_post_deletion_cleans_the_setting_and_guest_mutations_require_admin_authentication()
    {
        $post = $this->article(); app(PostPromotions::class)->save($post, 'games');
        $this->withExceptionHandling();
        auth('admin')->logout();
        $this->patch(route('admin.posts.update', $post), $this->fields($post) + ['promotions_present' => '1'])->assertRedirect(route('admin.login.show'));
        $this->assertDatabaseHas('blog_post_promotions', ['post_id' => $post->id, 'hub' => 'games']);
        $post->delete();
        $this->assertDatabaseCount('blog_post_promotions', 0);
    }
}
