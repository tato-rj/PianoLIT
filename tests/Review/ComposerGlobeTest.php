<?php

namespace Tests\Review;

use App\{Composer, Country, Piece};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class ComposerGlobeTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    private function catalogue()
    {
        return Model::withoutEvents(function () {
            $germany = create(Country::class, ['name' => 'Germany', 'flag_code' => 'de', 'continent' => 'Europe']);
            $japan = create(Country::class, ['name' => 'Japan', 'flag_code' => 'jp', 'continent' => 'Asia']);
            $unknown = create(Country::class, ['name' => 'Unmapped country', 'flag_code' => null, 'continent' => null]);
            $empty = create(Country::class, ['name' => 'Canada', 'flag_code' => 'ca', 'continent' => 'North America']);
            foreach ([[$germany, 3], [$germany, 2], [$japan, 4], [$unknown, 2], [null, 2], [$empty, 0]] as $index => [$country, $count]) {
                $composer = create(Composer::class, ['country_id' => optional($country)->id, 'name' => 'Globe composer '.$index]);
                if ($count) create(Piece::class, ['composer_id' => $composer->id], $count);
            }
            return compact('germany', 'japan', 'unknown', 'empty');
        });
    }

    public function test_guest_globe_counts_catalogue_with_one_query_and_no_mobile_serialization()
    {
        $countries = $this->catalogue();
        DB::enableQueryLog(); DB::flushQueryLog();
        $response = $this->getJson(route('webapp.composers.globe', ['user_id' => 123, 'gender' => 'female', 'country' => $countries['japan']->id]));
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $response->assertOk()->assertJsonPath('totals.composers', 5)->assertJsonPath('totals.pieces', 13)
            ->assertJsonPath('unmapped.composers', 2)->assertJsonPath('unmapped.pieces', 4)
            ->assertJsonCount(7, 'continents')->assertJsonCount(3, 'countries');
        $data = $response->json();
        $germany = collect($data['countries'])->firstWhere('code', 'DE');
        $this->assertSame(2, $germany['composers']);
        $this->assertSame(5, $germany['pieces']);
        $this->assertSame(route('webapp.composers.index', ['country' => $countries['germany']->id]), $germany['url']);
        $europe = collect($data['continents'])->firstWhere('name', 'Europe');
        $this->assertSame(2, $europe['composers']); $this->assertSame(5, $europe['pieces']);
        $this->assertSame(1, $europe['countries']);
        $this->assertSame(0, collect($data['continents'])->firstWhere('name', 'North America')['pieces']);
        $this->assertCount(1, $queries, 'Globe counts use one aggregate read, independent of catalogue size.');
        $this->assertGuest('web');
        $this->assertArrayNotHasKey('continent', $countries['germany']->toArray());
        $response->assertDontSee('cover_image')->assertDontSee('audio')->assertDontSee('user_id');
    }

    public function test_continent_and_country_links_match_the_globe_counts_and_validate_continents()
    {
        $countries = $this->catalogue();
        $this->get(route('webapp.composers.index', ['continent' => 'Europe']))->assertOk()
            ->assertViewHas('composers', function ($items) { return $items->count() === 2 && $items->sum('pieces_count') === 5; })
            ->assertSee('Globe composer 0')->assertDontSee('Globe composer 2');
        $this->get(route('webapp.composers.index', ['country' => $countries['japan']->id]))->assertOk()
            ->assertViewHas('composers', function ($items) { return $items->count() === 1 && $items->sum('pieces_count') === 4; });
        $this->withExceptionHandling();
        $this->getJson(route('webapp.composers.index', ['continent' => 'arbitrary']))->assertUnprocessable()->assertJsonValidationErrors('continent');
        $this->getJson(route('webapp.composers.index', ['continent' => ['Europe']]))->assertUnprocessable()->assertJsonValidationErrors('continent');
    }

    public function test_empty_catalogue_has_zero_counts_and_the_page_only_loads_the_globe_controller()
    {
        $this->getJson(route('webapp.composers.globe'))->assertOk()->assertJsonPath('totals.composers', 0)
            ->assertJsonPath('totals.pieces', 0)->assertJsonCount(0, 'countries')->assertJsonCount(7, 'continents');
        $this->get(route('webapp.composers.index'))->assertOk()->assertSee('data-globe-library=', false)
            ->assertSee('data-globe-catalogue=', false)->assertSee('composer-globe.js')
            ->assertDontSee('<script src="'.mix('js/vendor/globe.gl.min.js').'"', false);
    }
}
