<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use App\Api\Api;
use App\Services\DiscoverRefresh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Redis};

class DiscoverComposerFeatureTest extends ReviewTestCase
{
    private $women, $black, $latinAmerican, $prefixes = [];

    private const FEATURE_TITLES = ['From women composers', 'From black composers', 'From Latin American composers'];

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
            $this->women = create(Composer::class, ['name' => 'Woman Composer Fixture', 'gender' => 'female', 'ethnicity' => 'white', 'cover_path' => '']);
            $this->black = create(Composer::class, ['name' => 'Black Composer Fixture', 'gender' => 'male', 'ethnicity' => 'black', 'cover_path' => '']);
            $this->latinAmerican = create(Composer::class, ['name' => 'Latin American Composer Fixture', 'gender' => 'male', 'ethnicity' => 'latin american', 'cover_path' => '']);
            $level = create(Tag::class, ['name' => 'intermediate', 'type' => 'level']);
            $period = create(Tag::class, ['name' => 'romantic', 'type' => 'period']);
            foreach ([$this->women, $this->black, $this->latinAmerican] as $composer) {
                $piece = create(Piece::class, ['name' => $composer->name.' piece', 'composer_id' => $composer->id,
                    'cover_path' => null, 'highlighted_at' => now()->subWeek()]);
                $piece->tags()->attach([$level->id, $period->id]);
            }
        });
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
        ];
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
                $this->assertSame($group, $key === 'women' ? $piece->composer->gender : $piece->composer->ethnicity);
            }
            $response->assertSeeInOrder([$key.'-composers-heading', 'past-highlights-heading']);
            $titles = $response->viewData('rows')->pluck('title')->values();
            $this->assertSame($titles->search($title) + 1, $titles->search('Equivalent to the Suzuki series'));
            foreach (['women', 'black', 'latin-american'] as $otherKey) {
                if ($otherKey !== $key) $response->assertDontSee('id="'.$otherKey.'-composers-heading"', false);
            }
            $response->assertSee('aria-label="Pieces from '.($key === 'black' ? 'Black' : ($key === 'latin-american' ? 'Latin American' : 'women')).' composers"', false);
            $composer = ['women' => $this->women, 'black' => $this->black, 'latin-american' => $this->latinAmerican][$key];
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
        $this->assertNull($mobile->firstWhere('title', 'From Latin American composers'));
        foreach ((new Api)->discover() as $row) $this->assertArrayNotHasKey('web_composer_feature', $row);
    }

    public function test_admin_refresh_rebuilds_the_choice_and_falls_back_to_the_available_group()
    {
        $this->selectCachedTitle('From women composers');
        $oldKey = $this->cacheKey();
        $this->women->updateQuietly(['gender' => 'male']);
        $this->black->updateQuietly(['ethnicity' => 'white']);
        $this->assertSame('From women composers', $this->selectedTitle((new Api)->for('webapp')->discover()));
        app(\App\Http\Controllers\Admin\RedisController::class)->update(app(DiscoverRefresh::class));
        $this->assertNotSame($oldKey, $this->cacheKey());
        $this->assertSame('From Latin American composers', $this->selectedTitle((new Api)->for('webapp')->discover()));
        $this->assertSame('From women composers', $this->selectedTitle(Cache::get($oldKey)));
    }

    public function test_daily_expiry_rebuilds_the_choice_without_an_admin_refresh()
    {
        $this->selectCachedTitle('From women composers');
        $this->women->updateQuietly(['gender' => 'male']);
        $this->black->updateQuietly(['ethnicity' => 'white']);
        $this->travel(25)->hours();
        $this->assertSame('From Latin American composers', $this->selectedTitle((new Api)->for('webapp')->discover()));
    }

    /** @dataProvider featureChoices */
    public function test_a_single_group_or_an_empty_catalogue_does_not_produce_an_empty_random_choice($title, $key, $group)
    {
        if ($key !== 'women') $this->women->updateQuietly(['gender' => 'male']);
        if ($key !== 'black') $this->black->updateQuietly(['ethnicity' => 'white']);
        if ($key !== 'latin-american') $this->latinAmerican->updateQuietly(['ethnicity' => 'white']);
        $this->assertSame($title, $this->selectedTitle((new Api)->for('webapp')->discover()));
        $this->women->updateQuietly(['gender' => 'male']);
        $this->black->updateQuietly(['ethnicity' => 'white']);
        $this->latinAmerican->updateQuietly(['ethnicity' => 'white']);
        Cache::forget($this->cacheKey());
        $this->assertNull($this->selectedTitle((new Api)->for('webapp')->discover()));
        $this->get(route('webapp.discover'))->assertOk()
            ->assertDontSee('women-composers-heading')->assertDontSee('black-composers-heading')->assertDontSee('latin-american-composers-heading');
    }
}
