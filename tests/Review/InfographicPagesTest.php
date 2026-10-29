<?php

namespace Tests\Review;

use App\User;
use App\Events\PurchaseMade;
use App\Infograph\{Infograph, Topic};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Event, Mail, Redis, Storage};
use Stevebauman\Location\Facades\Location;

class InfographicPagesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        Event::fake([PurchaseMade::class]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function infographic($name = 'Circle of fifths', $attributes = [])
    {
        return Model::withoutEvents(function () use ($name, $attributes) {
            return create(Infograph::class, array_merge([
                'name' => $name, 'slug' => str_slug($name), 'description' => 'A clear visual reference for your piano practice.',
                'cover_path' => 'infograph/'.str_slug($name).'.jpg', 'published_at' => now(), 'width' => 2480, 'height' => 3508,
            ], $attributes));
        });
    }

    private function account()
    {
        return Model::withoutEvents(function () { return create(User::class); });
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    private function preview($response, $name)
    {
        if ($directory = getenv('INFOGRAPHIC_PREVIEW_DIR')) {
            $html = preg_replace('#http://(?:learn\.)?localhost/(css|js|images)/#', 'http://pianolit.test/$1/', $response->getContent());
            $html = preg_replace('#http://learn\.localhost/storage/infograph/(?:thumbnails/)?([^"?]+)\.jpg#', 'http://127.0.0.1:8876/posters/$1.jpg', $html);
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="http://pianolit.test/css/', 'src="http://pianolit.test/js/'], $html);
            $html = str_replace('id="countdown-seconds"', 'id="preview-countdown-seconds"', $html);
            $html = preg_replace('#http://learn\.localhost/infographics/[^" ]+/update-score#', 'http://127.0.0.1:8876/review-unavailable', $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }

    public function test_directory_filters_paginates_and_preserves_published_guides_in_the_shared_shell()
    {
        $topic = Model::withoutEvents(function () { return create(Topic::class, ['name' => 'Theory', 'slug' => 'theory']); });
        for ($i = 1; $i <= 13; $i++) {
            $guide = $this->infographic('Theory guide '.$i, ['created_at' => now()->subMinutes($i)]);
            $guide->topics()->attach($topic);
        }
        $draft = $this->infographic('Unpublished guide', ['published_at' => null]);
        $draftTopic = Model::withoutEvents(function () { return create(Topic::class, ['name' => 'Drafts', 'slug' => 'drafts']); });
        $draft->topics()->attach($draftTopic);
        $this->infographic('Composer portraits');
        $response = $this->get('http://learn.localhost/infographics?topics=theory')->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="http://learn.localhost"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Infographic topics"]//a[@aria-current="page" and contains(.,"Theory")]'));
        $response->assertDontSee('Unpublished guide')->assertDontSee('Composer portraits')->assertDontSee('>Drafts<', false)
            ->assertSee('topics=theory', false)->assertDontSee('class="grid row', false)->assertDontSee('newsletter-invitation-title');
        $second = $this->get('http://learn.localhost/infographics?topics=theory&page=2')->assertOk();
        $this->assertCount(1, $this->xpath($second)->query('//a[contains(@class,"resource-card")]'));
        $second->assertSee('Theory guide 13');
        $this->get('http://learn.localhost/infographics?topics=unknown')->assertOk()->assertSee('No infographics match these topics');
    }

    public function test_guest_detail_and_catalog_have_breadcrumbs_previews_and_an_authenticated_download_destination()
    {
        $guide = $this->infographic();
        $response = $this->get($guide->showRoute())->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//h1[contains(.,"Circle of fifths")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="http://learn.localhost/infographics"]'));
        $this->assertCount(1, $xpath->query('//a[@id="auth-only" and @data-infographic-download and @href="http://learn.localhost/infographics/circle-of-fifths/download"]'));
        $response->assertSee('2,480')->assertSee('3,508')->assertSee('Free for personal and commercial use with attribution');
        $this->preview($response, 'guest');
        $this->withExceptionHandling()->get(route('infographs.download', $guide))->assertRedirect('http://learn.localhost/login');
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_first_download_keeps_confirmation_and_repeat_button_streams_the_original_file_without_another_purchase()
    {
        $guide = $this->infographic();
        $user = $this->account();
        Storage::fake('public');
        Storage::disk('public')->put($guide->cover_path, 'Original infographic bytes');
        $this->actingAs($user, 'web');
        $detail = $this->get($guide->showRoute())->assertOk()->assertSee('Download for free');
        $detail->assertSee('action="'.route('infographs.download', $guide).'"', false);
        $first = $this->get(route('infographs.download', $guide));
        $purchase = $user->purchases()->firstOrFail();
        $first->assertRedirect(route('shop.success', ['purchase' => $purchase, 'type' => 'free']));
        $success = $this->get($first->headers->get('Location'))->assertOk();
        $this->assertCount(1, $this->xpath($success)->query('//nav[@aria-label="Breadcrumb"]//*[@aria-current="page" and contains(.,"Download")]'));
        $success->assertSee('href="'.$guide->autoDownload().'"', false);
        $this->preview($success, 'confirmation');
        $repeat = $this->get($guide->showRoute())->assertOk()->assertSee('Download again');
        $xpath = $this->xpath($repeat);
        $button = $xpath->query('//a[@data-infographic-download]')->item(0);
        $this->assertSame($guide->autoDownload(), $button->getAttribute('href'));
        $this->assertStringNotContainsString('/purchases', $button->getAttribute('href'));
        $this->preview($repeat, 'download-again');
        $file = $this->get($button->getAttribute('href'))->assertOk()->assertDownload('circle-of-fifths.jpg');
        $this->assertSame('Original infographic bytes', $file->streamedContent());
        $this->get($button->getAttribute('href'))->assertOk()->assertDownload('circle-of-fifths.jpg');
        $this->assertSame(1, $user->purchasesOf($guide)->count());
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_legacy_download_endpoint_still_serves_the_same_original_file()
    {
        $guide = $this->infographic();
        Storage::fake('public');
        Storage::disk('public')->put($guide->cover_path, 'Legacy infographic bytes');
        $this->actingAs($this->account(), 'web');
        $file = $this->get('http://localhost/infographs/'.$guide->slug.'/download?type=download')
            ->assertOk()->assertDownload('circle-of-fifths.jpg');
        $this->assertSame('Legacy infographic bytes', $file->streamedContent());
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_feedback_rejects_malformed_votes_without_changing_the_score()
    {
        $guide = $this->infographic();
        $this->withExceptionHandling()->postJson(route('infographs.update-score', $guide), ['liked' => ['invalid']])->assertStatus(422);
        $this->assertSame(0, $guide->fresh()->score);
    }

    public function test_unfiltered_directory_selects_all_guides_and_keeps_one_header_signup()
    {
        $topic = Model::withoutEvents(function () { return create(Topic::class, ['name' => 'Theory', 'slug' => 'theory']); });
        foreach (['Circle of fifths', 'Intervals at a glance', 'Musical ornaments', 'Tempo markings'] as $name) {
            $guide = $this->infographic($name);
            $guide->topics()->attach($topic);
        }
        $response = $this->get('http://learn.localhost/infographics')->assertOk();
        $this->assertCount(4, $this->xpath($response)->query('//a[contains(@class,"resource-card")]'));
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Infographic topics"]//a[@aria-current="page" and contains(.,"All infographics")]'));
        $this->assertCount(1, $xpath->query('//header[contains(@class,"site-header")]//button[@data-view="newsletter-subscription"]'));
        $this->preview($response, 'directory');
    }
}
