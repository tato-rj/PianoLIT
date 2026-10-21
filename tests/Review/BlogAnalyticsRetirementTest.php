<?php

namespace Tests\Review;

use App\Admin;
use App\Blog\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class BlogAnalyticsRetirementTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
    }

    public function test_retired_blog_analytics_route_is_gone_and_blog_management_remains()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $this->withExceptionHandling()->get('http://admin.localhost/analytics/blog')->assertNotFound();
        $this->assertNull(app('router')->getRoutes()->getByName('admin.analytics.blog'));
        $response = $this->get(route('admin.analytics.composers'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//ul[@id="analytics"]//a[contains(@href, "/analytics/blog")]')->length);
        $this->assertSame(1, $xpath->query('//ul[@id="blog"]//a[@href="'.route('admin.posts.index').'"]')->length);
    }

    public function test_public_blog_reads_stop_incrementing_views_and_omit_counter_badges()
    {
        $post = Model::withoutEvents(function () {
            return create(Post::class, ['title' => 'Published blog article', 'content' => '<p>Article content remains available.</p>', 'views' => 987654321, 'published_at' => now(), 'gift_path' => null]);
        });
        $this->assertTrue(traffic()->isRealVisitor());
        $this->assertGuest('web'); $this->assertGuest('admin');
        $this->get(route('webapp.blog.show', $post->slug))->assertStatus(301)->assertRedirect(route('posts.show', $post->slug));
        foreach (['posts.show', 'posts.app'] as $route) {
            for ($visit = 0; $visit < 2; $visit++) {
                $this->get(route($route, $post->slug))->assertOk()->assertSee('Published blog article')
                    ->assertSee('Article content remains available.')->assertDontSee('987654321');
                $this->assertSame(987654321, (int) DB::table('posts')->where('id', $post->id)->value('views'));
            }
        }
        $this->get(route('posts.index'))->assertOk()->assertSee('Published blog article')->assertDontSee(shortNumber(987654321));
        $this->assertSame(987654321, (int) DB::table('posts')->where('id', $post->id)->value('views'));
    }

    public function test_unpublished_post_preview_still_requires_admin_and_never_counts_a_view()
    {
        $post = Model::withoutEvents(function () { return create(Post::class, ['title' => 'Draft article', 'views' => 27, 'published_at' => null, 'gift_path' => null]); });
        $this->withExceptionHandling()->get(route('posts.show', $post->slug))->assertNotFound();
        $this->actingAs(create(Admin::class), 'admin');
        auth()->shouldUse('web'); // Public requests keep the web guard as their default.
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Draft article')->assertSee('not published');
        $this->assertSame(27, (int) DB::table('posts')->where('id', $post->id)->value('views'));
    }
}
