<?php

namespace Tests\Review;

use App\{Composer, Country, Piece};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class ComposersDirectoryTest extends ReviewTestCase
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

    public function test_directory_bulk_loads_work_titles_and_safely_renders_guest_search_data()
    {
        $composers = Model::withoutEvents(function () {
            $country = create(Country::class, ['name' => 'Germany', 'flag_code' => 'de', 'continent' => 'Europe']);
            return collect(['Johann Sebastian Bach', 'Frédéric Chopin', 'William Gillock', 'Florence Price', 'Ludwig van Beethoven', 'Dmitry Kabalevsky', 'Robert Schumann', 'Cécile Chaminade'])->map(function ($name, $index) use ($country) {
                $composer = create(Composer::class, [
                    'name' => $name, 'country_id' => $country->id, 'is_famous' => $index < 3,
                    'created_at' => now()->subDays($index),
                    'period' => 'baroque', 'gender' => 'male',
                    'cover_path' => 'composer/cover_image/pianolit-cecile-chaminade-8811.jpg',
                ]);
                for ($i = 0; $i < 2; $i++) {
                    create(Piece::class, [
                        'composer_id' => $composer->id,
                        'name' => $index === 0 ? 'Prelude "<Test>" & Fugue' : 'Sonata '.$index,
                        'collection_name' => $index === 0 ? 'The Well-Tempered Clavier "<Book>" & Studies' : null,
                    ]);
                }
                return $composer;
            });
        });
        // Ensure composers without enough repertoire remain outside the directory.
        $excluded = Model::withoutEvents(function () { return create(Composer::class, ['name' => 'No repertoire']); });
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->get(route('webapp.composers.index', ['search' => 'prelude', 'user_id' => 123]));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $response->assertOk()->assertViewHas('composers', function ($items) use ($composers) { return $items->count() === $composers->count(); })
            ->assertSee('Prelude &quot;&lt;Test&gt;&quot; &amp; Fugue', false)
            ->assertSee('The Well-Tempered Clavier &quot;&lt;Book&gt;&quot; &amp; Studies', false)
            ->assertSee('data-composer-search="Johann Sebastian Bach Germany Europe Prelude', false)
            ->assertSee('data-composer-regions="Europe"', false)
            ->assertSee('data-composer-period="baroque"', false)
            ->assertSee('data-composer-continent="europe"', false)
            ->assertSee('data-composer-gender="male"', false)
            ->assertSee('aria-label="Sort and filter composers"', false)
            ->assertSee('data-bs-target="#composer-controls"', false)
            ->assertSee('Old to modern')
            ->assertSee('data-composer-facet="period"', false)
            ->assertSee('data-composer-facet="continent"', false)
            ->assertSee('data-composer-facet="gender"', false)
            ->assertDontSee('id="search-controls"', false)
            ->assertDontSee('id="composer-sort"', false)
            ->assertSee('Search composers, countries, continents, or works')
            ->assertDontSee('No repertoire')->assertSee('Recently added')
            ->assertSee(route('webapp.search.results', ['search' => $composers->first()->name]), false);
        $this->assertCount(3, $queries, 'Composer/country/work-title reads stay bounded as the directory grows.');
        $this->assertGuest('web');
        $this->assertSame(8, substr_count($response->getContent(), 'class="col-md-6 col-12 composer-card"'));
        $this->assertSame(0, $excluded->pieces()->count());

        if ($destination = getenv('COMPOSERS_PREVIEW_PATH')) {
            $html = $this->get(route('webapp.composers.index'))->getContent();
            $html = preg_replace('~https?://(?:my\.)?localhost(?=/(?:css|js|images|fonts|storage)/)~', '/assets', $html);
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="/assets/css/', 'src="/assets/js/'], $html);
            file_put_contents($destination, $html);
        }
    }

    public function test_directory_search_metadata_tolerates_missing_countries_and_null_continents()
    {
        Model::withoutEvents(function () {
            $country = create(Country::class, ['name' => 'Unmapped country', 'continent' => null]);
            foreach (['Without country' => null, 'Without continent' => $country->id] as $name => $countryId) {
                $composer = create(Composer::class, ['name' => $name, 'country_id' => $countryId]);
                create(Piece::class, ['composer_id' => $composer->id, 'name' => 'Study', 'collection_name' => null], 2);
            }
        });

        $this->get(route('webapp.composers.index'))->assertOk()
            ->assertSee('data-composer-search="Without country   Study"', false)
            ->assertSee('data-composer-search="Without continent Unmapped country  Study"', false)
            ->assertDontSee('data-composer-facet="continent"', false);
    }

    public function test_continent_options_only_include_available_directory_composers()
    {
        Model::withoutEvents(function () {
            foreach ([['Europe', 'male', 2], ['Europe', 'male', 2], ['Asia', 'female', 2], ['Antarctica', 'male', 0]] as $index => [$continent, $gender, $pieceCount]) {
                $country = create(Country::class, ['continent' => $continent]);
                $composer = create(Composer::class, ['name' => 'Composer '.$index, 'country_id' => $country->id, 'gender' => $gender]);
                if ($pieceCount) create(Piece::class, ['composer_id' => $composer->id], $pieceCount);
            }
            create(Country::class, ['continent' => 'Africa']);
        });

        $response = $this->get(route('webapp.composers.index'))->assertOk()
            ->assertSee('data-composer-value="asia"', false)
            ->assertSee('data-composer-value="europe"', false);
        foreach (['africa', 'antarctica', 'north america', 'oceania', 'south america'] as $continent) {
            $response->assertDontSee('data-composer-value="'.$continent.'"', false);
        }
        $this->assertSame(1, substr_count($response->getContent(), 'data-composer-value="europe"'));
        $this->assertLessThan(strpos($response->getContent(), 'data-composer-value="europe"'), strpos($response->getContent(), 'data-composer-value="asia"'));

        $this->get(route('webapp.composers.index', ['gender' => 'female']))->assertOk()
            ->assertSee('data-composer-value="asia"', false)
            ->assertDontSee('data-composer-value="europe"', false);
    }

    public function test_latin_america_alias_includes_the_americas_except_us_and_canada()
    {
        $countries = [
            ['Brazil', 'br', 'South America', true],
            ['Mexico', 'mx', 'North America', true],
            ['Guatemala', 'gt', 'North America', true],
            ['Cuba', 'cu', 'North America', true],
            ['Jamaica', 'jm', 'North America', true],
            ['Puerto Rico', 'pr', 'North America', true],
            ['United States', ' US ', 'North America', false],
            ['Canada', 'CA', 'North America', false],
            ['United States of America', null, 'North America', false],
            ['Canada', null, 'North America', false],
            ['France', 'fr', 'Europe', false],
            ['Unknown', null, null, false],
        ];
        Model::withoutEvents(function () use ($countries) {
            foreach ($countries as $index => [$name, $code, $continent]) {
                $country = create(Country::class, ['name' => $name, 'flag_code' => $code, 'continent' => $continent]);
                $composer = create(Composer::class, ['name' => 'Composer '.$index, 'country_id' => $country->id]);
                create(Piece::class, ['composer_id' => $composer->id, 'name' => 'Study', 'collection_name' => null], 2);
            }
        });

        $response = $this->get(route('webapp.composers.index'))->assertOk();
        foreach ($countries as $index => [$name, $code, $continent, $matches]) {
            $response->assertSee('data-composer-search="Composer '.$index.' '.$name.' '.$continent.' Study'.($matches ? ' Latin America' : '').'"', false);
        }
        $this->assertSame(6, substr_count($response->getContent(), '|Latin America"'));
        $response->assertSee('data-composer-regions="South America|Latin America"', false)
            ->assertSee('data-composer-regions="North America|Latin America"', false)
            ->assertSee('data-composer-regions="North America"', false)
            ->assertSee('data-composer-regions=""', false);
    }
}
