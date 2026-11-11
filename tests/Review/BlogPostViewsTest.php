<?php

namespace Tests\Review;

use App\Admin;
use App\Blog\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\{DB, Http, Redis};

class BlogPostViewsTest extends ReviewTestCase
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REPORT_URL = 'https://analyticsdata.googleapis.com/v1beta/properties/399721623:runReport';

    private $credentials;

    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('America/New_York')->setDate(2026, 11, 11)->setTime(17, 0));
        Redis::shouldReceive('get')->andReturn(null);
        $this->credentials = tempnam(sys_get_temp_dir(), 'blog-ga-');
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        file_put_contents($this->credentials, json_encode([
            'type' => 'service_account', 'client_email' => 'blog-report@example.test', 'private_key' => $privateKey,
        ]));
        config(['google-analytics.report_credentials' => $this->credentials]);
        Http::swap(new Factory);
        Http::fake([self::TOKEN_URL => Http::response(['access_token' => 'fake-read-only-token', 'expires_in' => 3600])]);
    }

    protected function tearDown(): void
    {
        if ($this->credentials) unlink($this->credentials);
        parent::tearDown();
    }

    public function test_daily_sync_supplies_the_admin_and_public_blog_without_request_time_google_calls()
    {
        $draft = $this->postRecord('draft', false, 54321);
        $unseen = $this->postRecord('unseen');
        $viewed = $this->postRecord('viewed', true, 98765);
        Http::fake([
            self::REPORT_URL => Http::sequence()->push($this->reportBody(['/viewed' => '12345']))
                ->push($this->reportBody(['/viewed' => '12344', '/unseen' => '1'])),
            '*' => Http::response([], 500),
        ]);

        $this->get(route('posts.show', $viewed))->assertOk()->assertSeeText('98,765 views');
        $this->get(route('posts.show', $unseen))->assertOk()->assertDontSeeText('0 views');
        Http::assertNothingSent();
        $this->artisan('analytics:sync-blog-views')
            ->expectsOutput('Updated 2 of 2 published posts through 2026-11-10.')->assertExitCode(0);
        $this->assertDatabaseCount('blog_post_view_counts', 2);
        $this->assertDatabaseHas('blog_post_view_counts', [
            'post_id' => $viewed->id, 'slug' => 'viewed', 'views' => 12345, 'reported_through' => '2026-11-10',
        ]);
        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            if ($request->url() !== self::TOKEN_URL) return false;
            parse_str($request->body(), $form);
            $claims = json_decode(base64_decode(strtr(explode('.', $form['assertion'])[1], '-_', '+/')), true);
            return $claims['scope'] === 'https://www.googleapis.com/auth/analytics.readonly';
        });
        Http::assertSent(function ($request) {
            if ($request->url() !== self::REPORT_URL) return false;
            $filters = $request['dimensionFilter']['andGroup']['expressions'];
            return $request->hasHeader('Authorization', 'Bearer fake-read-only-token')
                && $request['metrics'] === [['name' => 'screenPageViews']]
                && $request['dimensions'] === [['name' => 'pagePath']]
                && $request['dateRanges'] === [['startDate' => '2026-10-21', 'endDate' => '2026-11-10']]
                && $filters[0]['filter']['fieldName'] === 'hostName'
                && $filters[0]['filter']['stringFilter']['value'] === 'blog.pianolit.com'
                && $filters[1]['filter']['inListFilter']['values'] === ['/unseen', '/viewed'];
        });

        $this->get(route('posts.index'))->assertOk()->assertSeeText('111,110 views')->assertSeeText('0 views')->assertDontSeeText('54,321 views');
        $this->get(route('posts.show', $viewed))->assertOk()->assertSeeText('111,110 views');
        $this->get(route('posts.app', $viewed))->assertOk()->assertDontSeeText('111,110 views')->assertDontSeeText('98,765 views');
        $this->actingAs(create(Admin::class), 'admin');
        $page = $this->get(route('admin.posts.index'))->assertOk()->assertSeeText('Views');
        $this->assertEquals([$viewed->id => 12345, $unseen->id => 0], $page->viewData('postViews'));
        $this->assertSame('12,345', $this->cell($page, 'viewed'));
        $this->assertSame('0', $this->cell($page, 'unseen'));
        $this->assertSame('—Draft', $this->cell($page, 'draft'));
        $this->get(route('admin.posts.index', ['search' => 'viewed']))->assertOk();
        Http::assertSentCount(2);

        $this->travel(1)->days();
        $this->artisan('analytics:sync-blog-views')->assertExitCode(0);
        $this->assertDatabaseCount('blog_post_view_counts', 2);
        $this->assertDatabaseHas('blog_post_view_counts', [
            'post_id' => $viewed->id, 'views' => 12344, 'reported_through' => '2026-11-11',
        ]);
        auth()->shouldUse('web');
        $this->get(route('posts.show', $unseen))->assertOk()->assertSeeText('1 view');
        $this->get(route('posts.show', $viewed))->assertOk()->assertSeeText('111,109 views');
        $this->assertSame(98765, (int) $viewed->fresh()->views);
        $this->assertSame(54321, (int) $draft->fresh()->views);
        $this->assertArrayNotHasKey('reported_through', $viewed->fresh()->toArray());
    }

    public function test_a_failed_later_report_preserves_the_entire_previous_snapshot()
    {
        $post = $this->postRecord('article');
        $this->savedCount($post, 73);
        foreach (range(1, 100) as $i) $this->postRecord('article-'.$i);
        Http::fake([
            self::REPORT_URL => Http::sequence()->push($this->reportBody(['/article' => '200']))
                ->push(['error' => ['message' => 'test upstream error']], 429),
            '*' => Http::response([], 500),
        ]);

        $this->artisan('analytics:sync-blog-views')->assertExitCode(1);
        Http::assertSentCount(3);
        $this->assertDatabaseCount('blog_post_view_counts', 1);
        $this->get(route('posts.show', $post))->assertOk()->assertSeeText('73 views')->assertDontSeeText('200 views');
        Http::assertSentCount(3);
    }

    public function test_thresholded_rows_and_a_concurrent_slug_change_preserve_only_matching_counts()
    {
        $viewed = $this->postRecord('viewed');
        $omitted = $this->postRecord('omitted');
        $renamed = $this->postRecord('renamed', true, 120);
        $this->savedCount($omitted, 89);
        $this->savedCount($renamed, 90);
        Http::fake([
            self::REPORT_URL => function () use ($renamed) {
                $renamed->update(['slug' => 'new-slug']);
                return $this->report(['/viewed' => '23', '/renamed' => '45'], ['subjectToThresholding' => true]);
            },
            '*' => Http::response([], 500),
        ]);

        $this->artisan('analytics:sync-blog-views')
            ->expectsOutput('Updated 1 of 3 published posts through 2026-11-10.')->assertExitCode(0);
        $this->get(route('posts.show', $renamed->fresh()))->assertOk()->assertSeeText('120 views')->assertDontSeeText('210 views');
        $this->actingAs(create(Admin::class), 'admin');
        $page = $this->get(route('admin.posts.index'))->assertOk();
        $this->assertEquals([$viewed->id => 23, $omitted->id => 89], $page->viewData('postViews'));
        $this->assertSame('Unavailable', $this->cell($page, 'renamed'));
        Http::assertSentCount(2);
    }

    public function test_no_google_requests_for_an_empty_published_catalog_or_missing_credentials()
    {
        Http::fake(['*' => Http::response([], 500)]);
        $this->postRecord('draft', false);
        $this->artisan('analytics:sync-blog-views')->assertExitCode(0);
        $this->postRecord('published');
        config(['google-analytics.report_credentials' => null]);
        $this->artisan('analytics:sync-blog-views')->assertExitCode(1);
        $this->actingAs(create(Admin::class), 'admin');
        $page = $this->get(route('admin.posts.index'))->assertOk();
        $this->assertSame('Unavailable', $this->cell($page, 'published'));
        Http::assertNothingSent();
    }

    private function savedCount(Post $post, int $count): void
    {
        DB::table('blog_post_view_counts')->insert([
            'post_id' => $post->id, 'slug' => $post->slug, 'views' => $count, 'reported_through' => '2026-11-09',
        ]);
    }

    private function postRecord(string $slug, bool $published = true, int $historical = 0)
    {
        return Model::withoutEvents(fn () => create(Post::class, [
            'title' => $slug, 'slug' => $slug, 'content' => '<p>Article content.</p>',
            'published_at' => $published ? now() : null, 'views' => $historical,
        ]));
    }

    private function report(array $views, array $metadata = [])
    {
        return Http::response($this->reportBody($views, $metadata));
    }

    private function reportBody(array $views, array $metadata = []): array
    {
        $response = [
            'dimensionHeaders' => [['name' => 'pagePath']],
            'metricHeaders' => [['name' => 'screenPageViews', 'type' => 'TYPE_INTEGER']],
            'metadata' => $metadata,
        ];
        if ($views) {
            $response['rowCount'] = count($views);
            $response['rows'] = [];
            foreach ($views as $path => $count) {
                $response['rows'][] = [
                    'dimensionValues' => [['value' => $path]], 'metricValues' => [['value' => $count]],
                ];
            }
        }
        return $response;
    }

    private function cell($response, string $title): string
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        return $xpath->evaluate('normalize-space(//table[@id="posts-table"]/tbody/tr[td[2]//*[normalize-space(text())="'.$title.'"]]/td[3])');
    }
}
