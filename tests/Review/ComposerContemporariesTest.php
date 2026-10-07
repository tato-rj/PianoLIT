<?php

namespace Tests\Review;

use App\{Composer, Country, Piece};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class ComposerContemporariesTest extends ReviewTestCase
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

    protected function composer($name, $birth, $death, $attributes = [], $hasPiece = true)
    {
        return Model::withoutEvents(function () use ($name, $birth, $death, $attributes, $hasPiece) {
            $composer = create(Composer::class, array_merge([
                'name' => $name, 'date_of_birth' => $birth, 'date_of_death' => $death,
                'is_famous' => true, 'country_id' => null,
            ], $attributes));
            if ($hasPiece) {
                create(Piece::class, ['composer_id' => $composer->id]);
            }
            return $composer;
        });
    }

    public function test_profile_lists_four_relevant_composers_with_safe_metadata_and_profile_links()
    {
        $country = Model::withoutEvents(function () {
            return create(Country::class, ['name' => 'Germany', 'flag_code' => 'de']);
        });
        $composer = $this->composer('Giovanni Sgambati', '1841-05-28', '1914-12-14', ['country_id' => $country->id]);
        $liszt = $this->composer('Franz Liszt', '1811-10-22', '1886-07-31');
        $wagner = $this->composer('Richard Wagner', '1813-05-22', '1883-02-13', ['country_id' => $country->id]);
        $brahms = $this->composer('Johannes <Brahms> & friends', '1833-05-07', '1897-04-03');
        $grieg = $this->composer('Edvard Grieg', '1843-06-15', '1907-09-04');
        $this->composer('Fifth distant peer', '1811-01-01', '1880-01-01');
        $this->composer('Closer nonfamous peer', '1841-01-01', '1910-01-01', ['is_famous' => false]);
        $this->composer('Died before Sgambati', '1820-01-01', '1840-01-01');
        $this->composer('Born after Sgambati', '1915-01-01', null);
        $this->composer('Outside birth window', '1810-12-31', '1880-01-01');
        $this->composer('Unknown birthday', null, '1880-01-01');
        $this->composer('No repertoire', '1840-01-01', '1910-01-01', [], false);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->get(route('webapp.composers.show', $composer));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(4, $queries, 'Profile and contemporary country data use bulk reads.');
        $response->assertOk()
            ->assertViewHas('contemporaries', function ($items) use ($liszt, $wagner, $brahms, $grieg) {
                return $items->pluck('id')->all() === [$liszt->id, $wagner->id, $brahms->id, $grieg->id];
            })
            ->assertSee('Contemporary composers')->assertSee('1813 - 1883')->assertSee('Germany')
            ->assertSee('Johannes &lt;Brahms&gt; &amp; friends', false)
            ->assertDontSee('No repertoire')->assertDontSee('Died before Sgambati');
        foreach ([$liszt, $wagner, $brahms, $grieg] as $peer) {
            $response->assertSee(route('webapp.composers.show', $peer), false);
        }
        $this->assertGuest('web');
    }

    public function test_living_composers_and_missing_country_render_without_a_curiosity()
    {
        $composer = $this->composer('Living composer', '1985-05-28', null, ['curiosity' => null]);
        $peer = $this->composer('Living peer', '1986-05-28', null);
        $this->composer('Future composer', today()->addYear()->toDateString(), null);
        $this->get(route('webapp.composers.show', $composer))->assertOk()
            ->assertViewHas('contemporaries', function ($items) use ($peer) {
                return $items->pluck('id')->all() === [$peer->id];
            })
            ->assertSee('Contemporary composers')->assertSee('1986 - now')
            ->assertDontSee('Did you know?');
    }

    public function test_unknown_birth_or_no_matches_omits_the_box_and_keeps_full_width_biography()
    {
        $composer = $this->composer('Unknown composer', null, null, ['curiosity' => null]);
        $this->composer('Other era', '1700-01-01', '1750-01-01');
        $url = route('webapp.composers.show', $composer);
        $this->get($url)->assertOk()->assertDontSee('Contemporary composers');
        $composer->update(['date_of_birth' => '1900-01-01', 'date_of_death' => '1960-01-01']);
        $this->get($url)->assertOk()->assertDontSee('Contemporary composers');
    }
}
