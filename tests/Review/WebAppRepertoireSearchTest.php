<?php

namespace Tests\Review;

use App\{Composer, Piece, Playlist, Tag, Tutorial, User};
use App\Services\WebApp\AccessPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};
use Stevebauman\Location\Facades\Location;

class WebAppRepertoireSearchTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    private function piece(array $attributes = []): Piece
    {
        return Model::withoutEvents(function () use ($attributes) {
            $piece = create(Piece::class, $attributes + [
                'name' => 'Winter Prelude',
                'key' => 'E minor',
                'composer_id' => Composer::first()?->id ?? create(Composer::class, ['name' => 'Full Composer Name'])->id,
                'description' => 'The left hand carries a repeating figure.',
            ]);
            $piece->tags()->attach(Tag::firstOrCreate(['type' => 'level', 'name' => 'elementary']));
            $piece->tags()->attach(Tag::firstOrCreate(['type' => 'period', 'name' => 'romantic']));

            return $piece;
        });
    }

    private function xpath($response): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());

        return new \DOMXPath($dom);
    }

    private function schema($response): array
    {
        $script = $this->xpath($response)->evaluate('string(//head/script[@type="application/ld+json"])');

        return json_decode($script, true, 512, JSON_THROW_ON_ERROR)['@graph'][0];
    }

    private function sitemap(): array
    {
        $response = $this->get(route('webapp.sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());

        return array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
    }

    public function test_canonical_piece_uses_visible_catalogue_facts_and_keeps_media_fragments_out_of_search()
    {
        $piece = $this->piece(['score_path' => 'prelude.pdf']);
        $canonical = route('webapp.pieces.show', $piece);
        $response = $this->get($canonical.'?utm_source=audit')->assertOk()->assertHeaderMissing('X-Robots-Tag')
            ->assertSeeText('Piece details')->assertSeeText('E minor')->assertSeeText('The left hand carries a repeating figure.');
        $this->assertSame($canonical, $this->xpath($response)->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertStringContainsString('Full Composer Name', $this->xpath($response)->evaluate('string(//title)'));
        $entity = $this->schema($response)['mainEntity'];
        $this->assertSame('MusicComposition', $entity['@type']);
        $this->assertSame('E minor', $entity['musicalKey']);
        $this->assertSame('Full Composer Name', $entity['composer']['name']);
        $this->assertStringNotContainsString('prelude.pdf', json_encode($entity));
        $this->get(route('webapp.pieces.audio', $piece))->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $legacyPath = route('pieces.show', ['piece' => $piece, 'utm_source' => 'audit'], false);
        $this->get(rtrim(config('app.url'), '/').$legacyPath)
            ->assertStatus(301)->assertRedirect($canonical.'?utm_source=audit');
    }

    public function test_catalogue_and_sitemap_include_pieces_with_content_and_paginate_without_javascript()
    {
        $empty = $this->piece(['name' => 'Unfilled entry', 'description' => null]);
        $public = $this->piece(['name' => 'AAA Study']);
        for ($i = 1; $i <= 24; $i++) {
            $this->piece(['name' => sprintf('Study %02d', $i)]);
        }

        $urls = $this->sitemap();
        $this->assertCount(29, $urls);
        $this->assertContains(route('webapp.pieces.show', $public), $urls);
        $this->assertNotContains(route('webapp.pieces.show', $empty), $urls);
        $this->get(route('webapp.pieces.show', $empty))->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $first = $this->get(route('webapp.pieces.index'))->assertOk()->assertSeeText('AAA Study')->assertDontSeeText('Unfilled entry');
        $this->assertStringContainsString('page=2', $first->getContent());
        $secondUrl = route('webapp.pieces.index', ['page' => 2]);
        $second = $this->get($secondUrl.'&utm_source=audit')->assertOk()->assertSeeText('Study 24');
        $this->assertSame($secondUrl, $this->xpath($second)->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->get(route('webapp.pieces.index', ['page' => 100]))->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $welcome = $this->get(route('webapp.welcome'))->assertOk();
        $this->assertSame(route('webapp.discover'), $this->xpath($welcome)->evaluate('string(//link[@rel="canonical"]/@href)'));
    }

    public function test_piece_details_omit_redundant_resources_and_metadata_does_not_promote_lessons()
    {
        $piece = $this->piece(['score_path' => 'score.pdf', 'audio_path' => 'recording.mp3']);
        $performance = Model::withoutEvents(fn () => create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance']));
        Model::withoutEvents(fn () => create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Tutorial']));
        $performance->moments()->create(['title' => 'Theme', 'start_time' => 1, 'end_time' => 8, 'sort_order' => 0, 'comment' => 'Listen for the inner voice.']);
        $metadata = app(\App\Services\WebApp\PublicRepertoire::class)->pieceMetadata($piece->fresh());
        $this->assertSame(['sheet music', 'audio recording', 'performance video', 'timed video commentary'], $metadata['resources']);
        $response = $this->get(route('webapp.pieces.show', $piece))->assertOk()->assertSeeText('Piece details')
            ->assertDontSeeText('Available resources')->assertDontSeeText('video lessons');
        $this->assertCount(0, $this->xpath($response)->query('//dt[text()="Resources"]'));
        $performance->moments()->update(['comment' => '   ']);
        $metadata = app(\App\Services\WebApp\PublicRepertoire::class)->pieceMetadata($piece->fresh());
        $this->assertNotContains('timed video commentary', $metadata['resources']);
    }

    public function test_only_the_clean_search_landing_is_indexable()
    {
        $landing = $this->get(route('webapp.search.results', ['utm_source' => 'audit']))->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->assertSame(route('webapp.search.results'), $this->xpath($landing)->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame('Search piano pieces', $this->xpath($landing)->evaluate('string(//main//h1)'));
        $landing->assertSee('href="'.route('webapp.pieces.index').'"', false);
        $query = '<img src=x onerror=alert(1)>';
        $results = $this->get(route('webapp.search.results', ['for' => $query]))->assertOk()->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee($query)->assertDontSee($query, false);
        $this->assertCount(0, $this->xpath($results)->query('//main//h1 | //link[@rel="canonical"]'));
        $this->assertSame($query, $this->xpath($results)->evaluate('string(//form[@id="search-form"]/div/input[@name="for"]/@value)'));
        $fragment = $this->getJson(route('webapp.search.results', ['catalogue' => 1, 'page' => 2]))
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->assertSame('', $fragment->getContent());
    }

    public function test_saved_search_urls_redirect_with_the_term_and_existing_options()
    {
        $options = ['facets' => ['level' => ['advanced']], 'page' => 2, 'utm_source' => 'audit'];
        $oldUrl = route('webapp.search.results', ['search' => '  Mozart & sonata ', 'lazy-load' => ''] + $options);
        $newUrl = route('webapp.search.results', ['for' => 'Mozart & sonata'] + $options);
        $this->get($oldUrl)->assertStatus(301)->assertRedirect($newUrl);
        $this->get($newUrl)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->getJson($oldUrl)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->get(route('webapp.search.results', ['search' => 'old', 'for' => 'new']))
            ->assertStatus(301)->assertRedirect(route('webapp.search.results', ['for' => 'new']));
    }

    public function test_collection_markup_and_sitemap_follow_guest_permissions()
    {
        $playlist = Model::withoutEvents(fn () => create(Playlist::class, [
            'name' => 'Piano Preludes', 'subtitle' => 'Six pieces for practicing voicing.', 'published_at' => now(),
        ]));
        for ($i = 1; $i <= 6; $i++) {
            $piece = $this->piece(['name' => 'Prelude '.$i]);
            Model::withoutEvents(fn () => create(Tutorial::class, ['piece_id' => $piece->id]));
            $playlist->pieces()->attach($piece);
        }
        $url = route('webapp.playlists.show', $playlist);
        $this->get(route('webapp.playlists'))->assertOk()->assertSeeText('Six pieces for practicing voicing.');
        $this->get($url)->assertRedirect(route('webapp.playlists'))->assertSessionHas('collection_access_prompt', '#signup-collections-modal');
        $this->assertNotContains($url, $this->sitemap());

        $this->actingAs(Model::withoutEvents(fn () => create(User::class)), 'web');
        $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->assertNotContains($url, $this->sitemap());

        $rules = AccessPolicy::defaults();
        $rules['account']['collections']['guest'] = 'limited';
        DB::table('web_access_policies')->insert(['id' => 1, 'revision' => 1, 'rules' => json_encode($rules)]);
        $this->app->forgetInstance(AccessPolicy::class);
        auth('web')->logout();
        $this->assertContains($url, $this->sitemap());
        $public = $this->get($url)->assertOk()->assertHeaderMissing('X-Robots-Tag')->assertSeeText('Showing 3 of 6 pieces');
        $this->assertCount(3, $this->schema($public)['mainEntity']['itemListElement']);
    }
}
