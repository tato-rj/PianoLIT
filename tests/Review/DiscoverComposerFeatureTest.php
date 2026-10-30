<?php

namespace Tests\Review;

use App\{Composer, Country, Piece, Tag, User};
use App\Api\Api;
use App\Services\DiscoverRefresh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Redis};

class DiscoverComposerFeatureTest extends ReviewTestCase
{
    private $composers = [], $prefixes = [];

    private const FEATURE_TITLES = ['From women composers', 'From black composers', 'From Latin American composers',
        'From American composers', 'From Italian composers', 'From Russian composers', 'From Ukrainian composers'];

    private const FEATURE_LABELS = ['women' => 'women', 'black' => 'Black', 'latin-american' => 'Latin American',
        'american' => 'American', 'italian' => 'Italian', 'russian' => 'Russian', 'ukrainian' => 'Ukrainian'];

    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-24 12:00:00'));
        Redis::shouldReceive('get')->andReturnUsing(function ($key) { return $this->prefixes[$key] ?? null; });
        Redis::shouldReceive('set')->andReturnUsing(function ($key, $value) {
            $this->prefixes[$key] = $value;
            return true;
        });
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $otherCountry = create(Country::class, ['name' => 'France', 'nationality' => 'French', 'flag_code' => 'fr']);
            $this->composers['women'] = create(Composer::class, ['name' => 'Woman Composer Fixture', 'gender' => 'female', 'ethnicity' => 'white', 'cover_path' => '', 'country_id' => $otherCountry->id]);
            $this->composers['black'] = create(Composer::class, ['name' => 'Black Composer Fixture', 'gender' => 'male', 'ethnicity' => 'black', 'cover_path' => '', 'country_id' => $otherCountry->id]);
            $this->composers['latin-american'] = create(Composer::class, ['name' => 'Latin American Composer Fixture', 'gender' => 'male', 'ethnicity' => 'latin american', 'cover_path' => '', 'country_id' => $otherCountry->id]);
            foreach (['american' => ['United States', 'us'], 'italian' => ['Italy', 'it'], 'russian' => ['Russia', 'ru'], 'ukrainian' => ['Ukraine', 'ua']] as $key => [$name, $code]) {
                $country = create(Country::class, ['name' => $name, 'nationality' => self::FEATURE_LABELS[$key], 'flag_code' => $code]);
                $this->composers[$key] = create(Composer::class, ['name' => self::FEATURE_LABELS[$key].' Composer Fixture', 'gender' => 'male', 'ethnicity' => 'white', 'cover_path' => '', 'country_id' => $country->id]);
            }
            $level = create(Tag::class, ['name' => 'intermediate', 'type' => 'level']);
            $period = create(Tag::class, ['name' => 'romantic', 'type' => 'period']);
            foreach ($this->composers as $composer) {
                $piece = create(Piece::class, ['name' => $composer->name.' piece', 'composer_id' => $composer->id,
                    'cover_path' => null, 'highlighted_at' => now()->subWeek()]);
                $piece->tags()->attach([$level->id, $period->id]);
            }
        });
    }

    private function keepOnlyGroup(?string $key)
    {
        foreach ($this->composers as $group => $composer) {
            if ($group !== $key) $composer->updateQuietly(['gender' => 'male', 'ethnicity' => 'white', 'country_id' => null]);
        }
    }

    private function cacheKey()
    {
        return Api::freePickCacheKey(($this->prefixes['app.discover'] ?? 'app.discover').Api::WEB_DISCOVER_CACHE_SUFFIX);
    }

    private function selectedTitle($rows)
    {
        return $rows->firstWhere('title', 'From women composers')['web_composer_feature'];
    }

    private function selectCachedTitle($title)
    {
        (new Api)->for('webapp')->discover();
        Cache::put($this->cacheKey(), Cache::get($this->cacheKey())->map(function ($row) use ($title) {
            if ($row['title'] === 'From women composers') $row['web_composer_feature'] = $title;
            return $row;
        }), days(1));
    }

    public function featureChoices()
    {
        return [
            ['From women composers', 'women', 'female'],
            ['From black composers', 'black', 'black'],
            ['From Latin American composers', 'latin-american', 'latin american'],
            ['From American composers', 'american', 'us'],
            ['From Italian composers', 'italian', 'it'],
            ['From Russian composers', 'russian', 'ru'],
            ['From Ukrainian composers', 'ukrainian', 'ua'],
        ];
    }

    public function countryChoices()
    {
        return array_slice($this->featureChoices(), 3);
    }

    /** @dataProvider countryChoices */
    public function test_country_features_use_the_recorded_country_and_retain_the_twelve_piece_limit($title, $key, $code)
    {
        $this->keepOnlyGroup($key);
        Model::withoutEvents(function () use ($key) {
            // A matching name/nationality label on another country must not
            // classify this composer as belonging to the selected country.
            $otherCountry = create(Country::class, ['name' => 'Canada', 'nationality' => self::FEATURE_LABELS[$key], 'flag_code' => 'ca']);
            $otherComposer = create(Composer::class, ['name' => self::FEATURE_LABELS[$key].' Composer', 'gender' => 'male', 'ethnicity' => 'white', 'country_id' => $otherCountry->id]);
            create(Piece::class, ['composer_id' => $otherComposer->id]);
            create(Piece::class, ['composer_id' => $this->composers['women']->id]);
            create(Piece::class, ['composer_id' => $this->composers[$key]->id], 13);
        });
        $rows = (new Api)->for('webapp')->discover(false);
        $this->assertSame($title, $this->selectedTitle($rows));
        $pieces = collect($rows->firstWhere('title', $title)['content']);
        $this->assertCount(12, $pieces);
        $this->assertSame([$this->composers[$key]->id], $pieces->pluck('composer_id')->unique()->values()->all());
        $this->assertSame($code, $pieces->first()->composer->country->flag_code);
    }

    /** @dataProvider featureChoices */
    public function test_each_choice_renders_one_matching_panel_in_the_same_slot_and_keeps_past_highlights($title, $key, $group)
    {
        $this->selectCachedTitle($title);
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
            $response = $this->get(route('webapp.discover'))->assertOk();
            $features = $response->viewData('rows')->whereIn('title', self::FEATURE_TITLES);
            $this->assertCount(1, $features);
            $this->assertSame($title, $features->first()['title']);
            foreach ($features->first()['content'] as $piece) {
                $actual = $key === 'women' ? $piece->composer->gender : ($key === 'black' || $key === 'latin-american' ? $piece->composer->ethnicity : $piece->composer->country->flag_code);
                $this->assertSame($group, $actual);
            }
            $response->assertSeeInOrder([$key.'-composers-heading', 'past-highlights-heading']);
            $titles = $response->viewData('rows')->pluck('title')->values();
            $this->assertSame($titles->search($title) + 1, $titles->search('Equivalent to the Suzuki series'));
            foreach (array_keys(self::FEATURE_LABELS) as $otherKey) {
                if ($otherKey !== $key) $response->assertDontSee('id="'.$otherKey.'-composers-heading"', false);
            }
            $response->assertSee('aria-label="Pieces from '.self::FEATURE_LABELS[$key].' composers"', false);
            $composer = $this->composers[$key];
            $response->assertSee(e(route('webapp.search.results', ['search' => $composer->name, 'model' => Composer::class])), false);
            $this->assertSame($title, $this->selectedTitle(Cache::get($this->cacheKey())));
            $this->assertNull(Cache::get($this->cacheKey())->firstWhere('title', 'Past highlights'));
            if ($signedIn) {
                $token = app(\App\Services\Api\V2\Tokens::class)->issue(auth('web')->user())['access_token'];
                $native = $this->getJson('/api/v2/discover', ['Authorization' => 'Bearer '.$token])->assertOk();
                $section = collect($native->json('data.sections'))->where('kind', 'composer_feature')->sole();
                $this->assertSame(str_replace('-', '_', $key).'_composers', $section['id']);
                $this->assertSame($key === 'black' ? 'From Black composers' : $title, $section['title']);
                $this->assertSame((string) $composer->id, $section['featured_composer']['id']);
                $this->assertSame(collect($features->sole()['content'])->map(function ($piece) { return (string) $piece->id; })->all(), array_column($section['items'], 'id'));
            }
            if ($preview = getenv('DISCOVER_COMPOSER_PREVIEW_DIR')) {
                file_put_contents($preview.'/'.$key.'.html', $response->getContent());
            }
        }
    }

    public function test_choice_is_cached_across_browser_reloads_and_never_added_to_mobile_rows()
    {
        $mobile = (new Api)->discover();
        $before = serialize(Cache::get(Api::freePickCacheKey('app.discover')));
        $rows = (new Api)->for('webapp')->discover();
        $title = $this->selectedTitle($rows);
        $this->assertContains($title, self::FEATURE_TITLES);
        for ($visit = 0; $visit < 3; $visit++) {
            $response = $this->get(route('webapp.discover'))->assertOk();
            $this->assertSame($title, $response->viewData('rows')->whereIn('title', self::FEATURE_TITLES)->sole()['title']);
        }
        $this->assertSame($before, serialize(Cache::get(Api::freePickCacheKey('app.discover'))));
        $this->assertNotNull($mobile->firstWhere('title', 'From women composers'));
        $this->assertNotNull($mobile->firstWhere('title', 'From black composers'));
        foreach (array_slice(self::FEATURE_TITLES, 2) as $title) $this->assertNull($mobile->firstWhere('title', $title));
        foreach ((new Api)->discover() as $row) $this->assertArrayNotHasKey('web_composer_feature', $row);
    }

    public function test_admin_refresh_rebuilds_the_choice_and_falls_back_to_the_available_group()
    {
        $this->selectCachedTitle('From women composers');
        $oldKey = $this->cacheKey();
        $this->keepOnlyGroup('ukrainian');
        $this->assertSame('From women composers', $this->selectedTitle((new Api)->for('webapp')->discover()));
        app(\App\Http\Controllers\Admin\RedisController::class)->update(app(DiscoverRefresh::class));
        $this->assertNotSame($oldKey, $this->cacheKey());
        $this->assertSame('From Ukrainian composers', $this->selectedTitle((new Api)->for('webapp')->discover()));
        $this->assertSame('From women composers', $this->selectedTitle(Cache::get($oldKey)));
    }

    public function test_daily_expiry_rebuilds_the_choice_without_an_admin_refresh()
    {
        $this->selectCachedTitle('From women composers');
        $this->keepOnlyGroup('american');
        $this->travel(25)->hours();
        $this->assertSame('From American composers', $this->selectedTitle((new Api)->for('webapp')->discover()));
    }

    /** @dataProvider featureChoices */
    public function test_a_single_group_or_an_empty_catalogue_does_not_produce_an_empty_random_choice($title, $key, $group)
    {
        $this->keepOnlyGroup($key);
        $this->assertSame($title, $this->selectedTitle((new Api)->for('webapp')->discover()));
        $this->keepOnlyGroup(null);
        Cache::forget($this->cacheKey());
        $this->assertNull($this->selectedTitle((new Api)->for('webapp')->discover()));
        $response = $this->get(route('webapp.discover'))->assertOk();
        foreach (array_keys(self::FEATURE_LABELS) as $key) $response->assertDontSee($key.'-composers-heading');
    }
}
