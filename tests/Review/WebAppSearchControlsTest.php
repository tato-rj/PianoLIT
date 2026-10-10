<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Services\WebApp\SearchOptions;

class WebAppSearchControlsTest extends ReviewTestCase
{
    private $pieces;
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $tags = collect(['elementary' => 'level', 'baroque' => 'period', 'short' => 'length', 'happy' => 'mood', '4 hands' => 'ensemble', 'advanced' => 'level', 'romantic' => 'period', 'long' => 'length'])
                ->map(function ($type, $name) { return create(Tag::class, compact('type', 'name')); });
            $composer = create(Composer::class);
            $this->pieces = collect();
            for ($i = 0; $i < 14; $i++) {
                $piece = create(Piece::class, ['name' => sprintf('Piece %02d', $i), 'composer_id' => $composer->id, 'created_at' => now()->subMinutes($i),
                    'audio_path' => $i >= 10 ? 'fixture.mp3' : null, 'score_path' => $i >= 10 ? 'fixture.pdf' : null, 'score_url' => null]);
                $piece->tags()->attach($tags->only($i >= 10 ? ['advanced', 'romantic', 'long', 'happy'] : ['elementary', 'baroque', 'short', 'happy', '4 hands'])->pluck('id'));
                $this->pieces->push($piece);
            }
        });
    }

    public function test_panel_replaces_search_buttons_and_reuses_bottom_sheet()
    {
        $response = $this->get(route('webapp.search.results', ['search' => 'happy']))->assertOk()
            ->assertSee('id="search-controls"', false)->assertSee('data-bs-target="#search-controls"', false)
            ->assertSee('bottom-sheet-handle')->assertSee('>Solo</button>', false)
            ->assertDontSee('> Sort by</button>', false)->assertDontSee('id="options-container"', false);
        if (getenv('SEARCH_CONTROLS_PREVIEW')) file_put_contents(getenv('SEARCH_CONTROLS_PREVIEW'), $response->getContent());
    }

    public function test_combined_filters_run_before_guest_limit_and_support_solo()
    {
        $response = $this->getJson(route('webapp.search.results', ['search' => 'happy', 'audio_only' => 1, 'score_only' => 1,
            'facets' => ['level' => ['advanced'], 'length' => ['long'], 'ensemble' => ['solo']], 'sort' => 'title_desc']))->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), 'data-sort-name='));
        foreach ([13, 12, 11] as $index) $response->assertSee($this->pieces[$index]->name);
        $response->assertDontSee($this->pieces[10]->name)->assertDontSee($this->pieces[0]->name);
        $this->assertLessThan(strpos($response->getContent(), 'Piece 12'), strpos($response->getContent(), 'Piece 13'));
    }

    public function test_sort_is_global_and_stable_across_signed_in_pages()
    {
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        foreach ([1 => [13, 4], 2 => [3, 0]] as $page => [$first, $last]) {
            $response = $this->getJson(route('webapp.search.results', ['search' => 'happy', 'sort' => 'title_desc', 'lazy-load' => '', 'page' => $page]))->assertOk();
            $response->assertSee($this->pieces[$first]->name)->assertSee($this->pieces[$last]->name);
            $this->assertLessThan(strpos($response->getContent(), $this->pieces[$last]->name), strpos($response->getContent(), $this->pieces[$first]->name));
        }
        foreach (['level', 'period'] as $sort) {
            $controls = new SearchOptions(new Request(['sort' => $sort]));
            $this->assertSame($this->pieces->pluck('id')->all(), $controls->sortQuery(Piece::query())->pluck('pieces.id')->all());
        }
    }

    public function test_catalogue_filters_and_media_availability_are_supported()
    {
        $this->pieces[13]->update(['score_url' => 'https://example.test/buy']);
        $response = $this->getJson(route('webapp.search.results', ['catalogue' => 1, 'sort' => 'title_desc', 'score_only' => 1]))->assertOk();
        $response->assertSee('Piece 12')->assertDontSee('Piece 13')->assertDontSee('Piece 00');
        $this->withExceptionHandling();
        foreach ([['sort' => 'name; DROP TABLE pieces'], ['facets' => ['unknown' => ['anything']]], ['facets' => ['level' => ['invalid']]], ['audio_only' => 'invalid']] as $invalid) {
            $this->getJson(route('webapp.search.results', array_merge(['search' => 'happy'], $invalid)))->assertStatus(422);
        }
    }

    public function test_facets_allow_alternatives_and_composer_sort_uses_the_full_name()
    {
        $controls = new SearchOptions(new Request(['facets' => ['ensemble' => ['solo', '4 hands'], 'length' => ['short', 'long']]]));
        $this->assertSame(14, $controls->filterQuery(Piece::query())->count());
        Model::withoutEvents(function () {
            $this->pieces[0]->composer->update(['name' => 'Zora Composer']);
            $composer = create(Composer::class, ['name' => 'Aaron Composer']);
            $this->pieces[13]->update(['composer_id' => $composer->id]);
        });
        $controls = new SearchOptions(new Request(['sort' => 'composer']));
        $this->assertSame($this->pieces[13]->id, $controls->sortQuery(Piece::query())->first()->id);
    }

    public function test_scout_filters_and_sort_scan_before_pagination()
    {
        $builder = \Mockery::mock(\Laravel\Scout\Builder::class);
        foreach ([1 => $this->pieces->take(10), 2 => $this->pieces->slice(10)] as $page => $pieces) {
            $builder->shouldReceive('paginateRaw')->once()->with(100, 'page', $page)->andReturn(
                new \Illuminate\Pagination\LengthAwarePaginator(['hits' => $pieces->map(function ($piece) { return ['objectID' => (string) $piece->id]; })->values()->all(), 'nbPages' => 2], 5000, 100, $page));
        }
        $controls = new SearchOptions(new Request(['sort' => 'title_desc', 'audio_only' => 1]));
        $this->assertSame($this->pieces->reverse()->take(3)->pluck('id')->values()->all(), $controls->results($builder)->pluck('id')->all());
    }

    public function test_mobile_search_ignores_browser_controls()
    {
        $request = new Request(['search' => 'happy', 'model' => Tag::class, 'sort' => 'title_desc', 'audio_only' => 1, 'facets' => ['ensemble' => ['solo']]]);
        $this->assertCount(14, (new \App\Api\Search($request))->query()->filtered()->get());
    }
}
