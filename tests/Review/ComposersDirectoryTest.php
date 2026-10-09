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
            ->assertSee('Search composers, countries, continents, or works')
            ->assertDontSee('No repertoire')->assertSee('Recently added')
            ->assertSee(route('webapp.composers.show', $composers->first()), false);
        $this->assertCount(3, $queries, 'Composer/country/work-title reads stay bounded as the directory grows.');
        $this->assertGuest('web');
        $this->assertSame(8, substr_count($response->getContent(), 'class="col-xl-4 col-md-6 col-12 composer-card"'));
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
            ->assertSee('data-composer-search="Without continent Unmapped country  Study"', false);
    }
}
