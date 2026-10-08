<?php

namespace Tests\Review;

use App\{Composer, Country, Piece, Tag, User};
use App\Api\Api;
use App\Services\WebApp\Explore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class WebAppExploreTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordAppLog::class,
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    private function catalogue()
    {
        return Model::withoutEvents(function () {
            $tags = collect();
            foreach ([
                'period' => ['baroque', 'classical', 'romantic', 'impressionist', 'modern', 'contemporary'],
                'genre' => ['jazz', 'transcription'],
                'length' => ['short'],
                'mood' => ['calm', 'dreamy', 'playful', 'dramatic', 'melancholic'],
                'level' => ['elementary', 'beginner', 'intermediate', 'advanced'],
                'sublevel' => ['early beginner', 'late beginner', 'early intermediate', 'late intermediate'],
            ] as $type => $names) {
                foreach ($names as $index => $name) {
                    $tags[$name] = Tag::create(['name' => $name, 'type' => $type, 'order' => 0]);
                }
            }
            $country = create(Country::class, ['name' => 'France']);
            $pieces = collect();
            foreach (['Claude Debussy', 'Frédéric Chopin', 'Clara Schumann'] as $index => $name) {
                $composer = create(Composer::class, ['name' => $name, 'country_id' => $country->id, 'cover_path' => 'explore-composer-'.$index.'.jpg']);
                $piece = create(Piece::class, ['name' => ['Tristesse', 'In May', 'Roly Poly'][$index], 'composer_id' => $composer->id, 'highlighted_at' => now()->subDays($index), 'cover_path' => 'explore-piece-'.$index.'.jpg', 'is_free' => $index === 0, 'catalogue_name' => null, 'catalogue_number' => null, 'collection_name' => null, 'collection_number' => null, 'key' => 'C major']);
                $piece->tags()->attach($tags->only(['short', 'calm', 'dreamy', 'elementary', ['baroque', 'classical', 'romantic'][$index]])->pluck('id'));
                $pieces->push($piece);
            }
            $older = create(Piece::class, ['name' => 'Older pick', 'highlighted_at' => now()->subDays(30)]);
            $older->tags()->attach($tags->only(['short', 'advanced', 'romantic'])->pluck('id'));
            create(Piece::class, ['name' => 'Never featured', 'highlighted_at' => null]);
            return $pieces;
        });
    }

    public function test_guest_layout_uses_real_counts_browse_links_and_three_chronological_picks()
    {
        $pieces = $this->catalogue();
        $response = $this->get(route('webapp.explore', ['user_id' => 123]));
        $response->assertOk()->assertSeeInOrder(['Periods &amp; styles', 'Explore a mood', 'Browse by level', 'Explore composers', 'Recent free picks'], false)
            ->assertSee('3 pieces')->assertSee('Browse repertoire filters')
            ->assertSee(route('webapp.composers.index', ['search' => 'France']), false)
            ->assertSee(route('webapp.search.results', ['search' => 'jazz']), false)
            ->assertSee(route('webapp.search.results', ['search' => 'women composers']), false)
            ->assertDontSee('Older pick')->assertDontSee('Never featured');
        $response->assertViewHas('freePicks', function ($picks) use ($pieces) { return $picks->pluck('id')->all() === $pieces->pluck('id')->all(); });
        $response->assertViewHas('levels', function ($levels) { return $levels->pluck('name')->values()->all() === ['elementary', 'early beginner', 'late beginner', 'early intermediate', 'late intermediate', 'advanced']; });
        $this->assertGuest('web');
        if ($destination = getenv('EXPLORE_PREVIEW_PATH')) file_put_contents($destination, $response->getContent());
    }

    public function test_web_read_preserves_cached_mobile_feed_and_access_rules()
    {
        $pieces = $this->catalogue();
        $before = serialize(app(Api::class)->explore());
        $this->get(route('webapp.explore'))->assertOk();
        $this->assertSame($before, serialize(app(Api::class)->explore()));
        $this->assertTrue($pieces[0]->hasWebMediaAccess());
        $this->assertFalse($pieces[1]->hasWebMediaAccess());
        $user = Model::withoutEvents(function () { return create(User::class); });
        $response = $this->actingAs($user, 'web')->get(route('webapp.explore'))->assertOk();
        if ($destination = getenv('EXPLORE_SIGNED_PREVIEW_PATH')) file_put_contents($destination, $response->getContent());
    }

    public function test_empty_catalogue_is_browseable_and_does_not_need_a_featured_composer_or_post()
    {
        $this->get(route('webapp.explore'))->assertOk()->assertSee('New picks are on their way')->assertSee('No countries to browse yet')->assertSee('Dreamy')->assertSee('Contemporary');
        $this->assertCount(0, app(Explore::class)->data()['freePicks']);
    }
}
