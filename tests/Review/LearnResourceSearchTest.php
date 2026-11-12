<?php

namespace Tests\Review;

use App\Infograph\Infograph;
use App\Pianist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

class LearnResourceSearchTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    private function guide(string $slug, array $attributes = []): Infograph
    {
        return Model::withoutEvents(fn () => create(Infograph::class, $attributes + [
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => 'Saved guide description.',
            'cover_path' => 'infograph/'.$slug.'.jpg',
            'published_at' => now(),
        ]));
    }

    private function artist(string $slug): Pianist
    {
        return Model::withoutEvents(fn () => create(Pianist::class, [
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'date_of_birth' => '1956-12-05',
            'date_of_death' => null,
            'itunes_id' => '49477407',
            'biography' => 'Saved biography.',
        ]));
    }

    private function head($response): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());

        return new \DOMXPath($dom);
    }

    private function sitemap(string $url): array
    {
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->call('HEAD', $url)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getNamespaces()['']);

        return array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
    }

    public function test_sitemaps_contain_public_canonical_urls_on_their_own_hosts()
    {
        $guide = $this->guide('published-guide');
        $artist = $this->artist('public-pianist');
        $draft = $this->guide('draft-guide', ['published_at' => null]);
        $this->assertSame([
            route('tools.circle-of-fifths'),
            route('tools.chord-finder.index'),
            route('resources.infographs.index'),
            route('resources.pianists.index'),
            $guide->showRoute(),
            route('resources.pianists.show', $artist),
        ], $this->sitemap('http://learn.localhost/resource-sitemap.xml'));
        $this->assertSame(['http://metronome.localhost', 'http://metronome.localhost/guide'], $this->sitemap('http://metronome.localhost/sitemap.xml'));
        $this->get($draft->showRoute())->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->withExceptionHandling()->get('http://localhost/resource-sitemap.xml')->assertNotFound();
    }

    public function test_directories_self_canonicalize_pagination_and_exclude_search_results()
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->guide('guide-'.$i);
            $this->artist('artist-'.$i);
        }

        foreach (['infographics' => 'topics=theory', 'great-pianists' => 'search=artist'] as $directory => $filter) {
            $url = 'http://learn.localhost/'.$directory;
            $first = $this->get($url.'?page=1')->assertOk();
            $this->assertSame($url, $this->head($first)->evaluate('string(//link[@rel="canonical"]/@href)'));
            $second = $this->get($url.'?page=2&utm_source=audit')->assertOk()->assertHeaderMissing('X-Robots-Tag');
            $this->assertSame($url.'?page=2', $this->head($second)->evaluate('string(//link[@rel="canonical"]/@href)'));
            $this->assertStringContainsString('Page 2', $this->head($second)->evaluate('string(//title)'));
            foreach ([$filter, 'page=500'] as $query) {
                $excluded = $this->get($url.'?'.$query)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
                $this->assertCount(0, $this->head($excluded)->query('//link[@rel="canonical"] | //script[@type="application/ld+json"]'));
            }
        }
    }

    public function test_audited_artwork_has_native_text_a_descriptive_alt_and_image_metadata()
    {
        foreach (config('infographic-guides') as $slug => $entry) {
            $guide = $this->guide($slug, ['cover_path' => 'infograph/'.$entry['image']]);
            $response = $this->get($guide->showRoute())->assertOk()
                ->assertSeeText($entry['text'])->assertSee($entry['alt']);
            $schema = json_decode($this->head($response)->evaluate('string(//script[@type="application/ld+json"])'), true, 512, JSON_THROW_ON_ERROR);
            $image = $schema['@graph'][1];
            $this->assertSame('ImageObject', $image['@type']);
            $this->assertSame($guide->showRoute(), $image['mainEntityOfPage']);
            $this->assertSame($entry['alt'], $image['caption']);
            if (isset($entry['tool'])) $response->assertSee('href="'.route($entry['tool']['route']).'"', false);
        }

        $guide = Infograph::where('slug', 'the-baroque-period')->firstOrFail();
        $guide->update(['description' => config('infographic-guides.the-baroque-period.previous_description')]);
        $this->get($guide->showRoute())->assertOk()->assertSeeText('An illustrated overview of Baroque music')
            ->assertDontSeeText('A quick glance at the Romantic period');
        $guide->update(['description' => 'A new editorial introduction.']);
        $this->get($guide->showRoute())->assertOk()->assertSeeText('A new editorial introduction.');
        $guide->update(['cover_path' => 'infograph/replacement.jpg']);
        $this->get($guide->showRoute())->assertOk()->assertDontSeeText('Guide in text');
    }

    public function test_pianist_has_verified_profile_facts_and_recording_links_in_initial_html()
    {
        $artist = $this->artist('krystian-zimerman');
        $response = $this->get(route('resources.pianists.show', $artist))->assertOk()
            ->assertSeeText('Where to start listening')->assertSeeText('Polish Festival Orchestra')->assertSeeText('Saved biography.');
        $head = $this->head($response);
        $this->assertCount(1, $head->query('//a[@href="https://music.apple.com/us/artist/49477407"]'));
        $schema = json_decode($head->evaluate('string(//script[@type="application/ld+json"])'), true, 512, JSON_THROW_ON_ERROR);
        $person = $schema['@graph'][1]['mainEntity'];
        $this->assertSame('Person', $person['@type']);
        $this->assertSame('1956-12-05', $person['birthDate']);
        $this->assertArrayNotHasKey('deathDate', $person);
    }

    public function test_public_tools_publish_instructions_and_calculation_responses_stay_out_of_search()
    {
        $root = $this->get('http://metronome.localhost/?utm_source=audit')->assertOk()->assertSee('data-metronome');
        $this->assertSame('Online Metronome', $this->head($root)->evaluate('string(//h1)'));
        $this->assertCount(1, $this->head($root)->query('//a[@href="http://metronome.localhost/guide"]'));
        $this->get('http://metronome.localhost/guide')->assertOk()->assertSeeText('At 60 BPM, you hear one beat per second.')
            ->assertSeeText('Open the metronome');
        $this->get('http://learn.localhost/circle-of-fifths')->assertOk()->assertSeeText('A natural minor');
        $this->get('http://learn.localhost/chord-finder')->assertOk()->assertSeeText('first inversion');
        $url = 'http://learn.localhost/chord-finder/analyse?notes[]=C&notes[]=E&notes[]=G';
        $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->get($url.'&dev=1')->assertOk()->assertHeader('X-Robots-Tag', 'noindex')
            ->assertHeader('Content-Type', 'application/json');
    }
}
