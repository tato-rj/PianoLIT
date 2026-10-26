<?php

namespace Tests\Review;

use App\{Country, Pianist};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail, Redis};
use Stevebauman\Location\Facades\Location;

class PianistPagesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function country($name = 'France', $nationality = 'French')
    {
        return Model::withoutEvents(function () use ($name, $nationality) {
            return Country::firstOrCreate(['name' => $name], ['nationality' => $nationality, 'flag_code' => 'fr']);
        });
    }

    private function pianist($name = 'Alex Martin', array $attributes = [])
    {
        return Model::withoutEvents(function () use ($name, $attributes) {
            return create(Pianist::class, $attributes + [
                'name' => $name, 'slug' => str_slug($name), 'cover_path' => 'app/pianists/portrait.jpg',
                'biography' => "A pianist with a lifelong interest in the piano repertoire.\n\nDiscover the artist’s story and recordings.",
                'country_id' => $this->country()->id, 'date_of_birth' => '1940-04-12', 'date_of_death' => null,
                'itunes_id' => '123456',
            ]);
        });
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    private function preview($response, $name)
    {
        if ($directory = getenv('PIANISTS_PREVIEW_DIR')) file_put_contents($directory.'/'.$name.'.html', $response->getContent());
    }

    public function test_directory_uses_shared_learn_cards_search_country_and_pagination()
    {
        $france = $this->country();
        for ($i = 1; $i <= 13; $i++) $this->pianist(sprintf('Artist %02d', $i));
        $canada = $this->country('Canada', 'Canadian');
        $this->pianist('Other artist', ['country_id' => $canada->id]);
        $this->country('Unused country', 'Unused nationality');
        $response = $this->get('http://learn.localhost/great-pianists?search=Artist&country='.$france->id)
            ->assertOk()->assertSee('Great Pianists')->assertSee('Meet the artists')->assertSee('Join the newsletter')
            ->assertDontSee('Other artist')->assertDontSee('Unused country')->assertDontSee('filterableBy');
        $xpath = $this->xpath($response);
        $this->assertCount(0, $xpath->query('//*[@id="popup-container"]'));
        $this->assertCount(12, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//li[@aria-current="page"]'));
        $this->assertCount(1, $xpath->query('//input[@id="search-pianist" and @name="search" and @value="Artist"]'));
        $this->assertCount(1, $xpath->query('//select[@name="country"]//option[@value="'.$france->id.'" and @selected]'));
        $this->assertCount(1, $xpath->query('//a[contains(@href,"search=Artist") and contains(@href,"country='.$france->id.'") and contains(@href,"page=2") and normalize-space(.)="2"]'));
        $this->get('http://learn.localhost/great-pianists?search=Artist&country='.$france->id.'&page=2')->assertOk()->assertSee('Artist 13');
        $this->get('http://learn.localhost/great-pianists?search=French')->assertOk()->assertSee('Artist 01')->assertDontSee('Other artist');
        $this->get('http://learn.localhost/great-pianists?search=Canada')->assertOk()->assertSee('Other artist')->assertDontSee('Artist 01');
        $this->get('http://learn.localhost/great-pianists?search=Artist%25')->assertOk()->assertSee('No pianists match your search');
        $this->preview($response, 'directory');
        Http::assertNothingSent(); Mail::assertNothingOutgoing();
    }

    public function test_profile_preserves_portrait_biography_dates_recording_id_and_safe_template()
    {
        $artist = $this->pianist('Alex Martin.', ['biography' => "A first paragraph.\n\nDiscover the artist’s story and recordings.", 'date_of_death' => '2001-08-10']);
        $response = $this->get('http://learn.localhost/great-pianists/'.$artist->slug)->assertOk()
            ->assertSee('Biography')->assertSee('A first paragraph.')->assertSee('Apr 12, 1940')->assertSee('Aug 10, 2001')->assertSee('Recordings')
            ->assertDontSee('Born on')->assertDontSee('gift-overlay');
        $xpath = $this->xpath($response);
        $this->assertCount(0, $xpath->query('//*[@id="popup-container"]'));
        $this->assertCount(1, $xpath->query('//h1[normalize-space(.)="Alex Martin"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="http://learn.localhost/great-pianists"]'));
        $this->assertCount(1, $xpath->query('//img[@src="http://learn.localhost/storage/app/pianists/portrait.jpg" and @alt="Portrait of Alex Martin"]'));
        $this->assertCount(1, $xpath->query('//div[@data-pianist-recordings and @data-itunes="123456"]'));
        $this->assertCount(1, $xpath->query('//a[@data-album-link and @target="_blank" and @rel="noopener noreferrer"]'));
        $this->assertCount(1, $xpath->query('//script[contains(@src,"/js/views/pianists.js?id=")]'));
        $this->assertCount(1, $xpath->query('//p[@data-recordings-status and @aria-live="polite"]'));
        $this->preview($response, 'profile');
        $artist->update(['biography' => '<script>unsafe</script>']);
        $this->get('http://learn.localhost/great-pianists/'.$artist->slug)->assertOk()
            ->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)->assertDontSee('<script>unsafe</script>', false);
        Http::assertNothingSent();
    }

    public function test_missing_details_and_unlinked_recordings_do_not_invent_history_or_break_the_page()
    {
        $this->get('http://learn.localhost/great-pianists')->assertOk()->assertSee('New artist profiles are on their way');
        $artist = $this->pianist('Minimal artist', ['country_id' => null, 'date_of_birth' => null, 'date_of_death' => null, 'biography' => '', 'cover_path' => '', 'itunes_id' => null]);
        $response = $this->get('http://learn.localhost/great-pianists/'.$artist->slug)->assertOk()->assertSee('Recordings aren’t available for this profile yet.');
        $xpath = $this->xpath($response);
        $this->assertCount(0, $xpath->query('//dl[contains(@class,"pianist-dates")] | //*[@data-pianist-recordings] | //*[@id="pianist-biography-title"]'));
        $this->get('http://learn.localhost/great-pianists')->assertOk()->assertSee('Minimal artist')->assertSee('Meet the artist');
        $artist->update(['itunes_id' => 'not-an-artist-id']);
        $response = $this->get('http://learn.localhost/great-pianists/'.$artist->slug)->assertOk();
        $this->assertCount(0, $this->xpath($response)->query('//*[@data-pianist-recordings]'));
        Http::assertNothingSent();
    }

    public function test_more_pianists_link_to_distinct_existing_public_profiles()
    {
        $artist = $this->pianist();
        $this->pianist('Jamie Evans');
        $this->pianist('Sam Rivera');
        $response = $this->get('http://learn.localhost/great-pianists/'.$artist->slug)->assertOk()->assertSee('Meet more pianists');
        $xpath = $this->xpath($response);
        $this->assertCount(2, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(0, $xpath->query('//a[contains(@class,"resource-card") and @href="http://learn.localhost/great-pianists/'.$artist->slug.'"]'));
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $node) $ids[] = $node->getAttribute('id');
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_public_reads_leave_artist_data_and_shared_json_contract_unchanged()
    {
        $artist = $this->pianist();
        $before = $artist->fresh()->toArray();
        $count = DB::table('pianists')->count();
        $this->get('http://learn.localhost/great-pianists')->assertOk();
        $this->get('http://learn.localhost/great-pianists/'.$artist->slug)->assertOk();
        $this->assertSame($before, $artist->fresh()->toArray());
        $this->assertSame($count, DB::table('pianists')->count());
        $this->withExceptionHandling()->get('http://learn.localhost/great-pianists?search[]=invalid')->assertStatus(302);
        $this->withExceptionHandling()->get('http://learn.localhost/great-pianists/missing-artist')->assertNotFound();
        Http::assertNothingSent(); Mail::assertNothingOutgoing();
    }
}
