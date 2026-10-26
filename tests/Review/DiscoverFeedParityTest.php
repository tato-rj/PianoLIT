<?php

namespace Tests\Review;

use App\{Composer, FreePick, Piece, Tag, Tutorial, User};
use App\Api\Api;
use App\Http\Resources\Api\V2\DiscoverResource;
use App\Services\{DiscoverFeed, DiscoverRefresh, RecentlyViewedPieces};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Redis};

class DiscoverFeedParityTest extends ReviewTestCase
{
    private $user, $other, $pieces, $prefixes = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-26 12:00:00'));
        Redis::shouldReceive('get')->andReturnUsing(function ($key) { return $this->prefixes[$key] ?? null; });
        Redis::shouldReceive('set')->andReturnUsing(function ($key, $value) { $this->prefixes[$key] = $value; return true; });
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class);
            $this->other = create(User::class);
            $women = create(Composer::class, ['name' => 'Woman Composer Fixture', 'gender' => 'female', 'ethnicity' => 'white', 'is_pedagogical' => false, 'cover_path' => '']);
            $black = create(Composer::class, ['name' => 'Black Composer Fixture', 'gender' => 'male', 'ethnicity' => 'black', 'is_pedagogical' => false, 'cover_path' => '']);
            $tags = collect(['level' => 'intermediate', 'period' => 'romantic', 'mood' => 'happy'])
                ->map(function ($name, $type) { return create(Tag::class, compact('name', 'type')); });
            $this->pieces = collect();
            for ($i = 0; $i < 24; $i++) {
                $piece = create(Piece::class, [
                    'name' => 'Parity piece '.$i, 'composer_id' => $i % 2 ? $women->id : $black->id,
                    'cover_path' => null, 'audio_path' => null, 'score_path' => null,
                    'highlighted_at' => $i < 10 ? now()->subDays(10 - $i) : null,
                ]);
                $piece->tags()->attach($tags->pluck('id'));
                create(Tutorial::class, ['piece_id' => $piece->id, 'category' => 'synthesia']);
                $this->pieces->push($piece);
            }
            foreach (['suzuki', 'rcm', 'abrsm'] as $system) {
                foreach ([10, 2, 1] as $number) {
                    $tag = create(Tag::class, ['type' => 'ranking', 'name' => $system.' '.$number]);
                    $tag->pieces()->attach($this->pieces->take(6)->pluck('id'));
                }
            }
        });
        (new RecentlyViewedPieces)->record($this->user, $this->pieces[5]);
        (new RecentlyViewedPieces)->record($this->other, $this->pieces[6]);
    }

    private function v2(User $user): array
    {
        return (new DiscoverResource(app(DiscoverFeed::class)->forUser($user)))->resolve(request());
    }

    private function publicKey(): string
    {
        return Api::freePickCacheKey(($this->prefixes['app.discover'] ?? 'app.discover').Api::WEB_DISCOVER_CACHE_SUFFIX);
    }

    private function choose($title): void
    {
        (new Api)->for('webapp')->discover();
        Cache::put($this->publicKey(), Cache::get($this->publicKey())->map(function ($row) use ($title) {
            if ($row['title'] === 'From women composers') $row['web_composer_feature'] = $title;
            return $row;
        }), days(1));
    }

    public function groups()
    {
        return [['From women composers', 'women'], ['From black composers', 'black']];
    }

    /** @dataProvider groups */
    public function test_web_and_v2_present_the_same_ordered_content_for_both_composer_groups($title, $group)
    {
        $this->choose($title);
        $legacy = serialize((new Api)->discover()->reject(function ($row) { return $row['title'] === 'For you'; }));
        $this->actingAs($this->user, 'web');
        $web = $this->get(route('webapp.discover'))->assertOk();
        $json = collect($this->v2($this->user->fresh())['sections']);
        $webIds = [];
        foreach ($web->viewData('rows') as $row) {
            if (strpos($row['id'], 'levels_') === 0) {
                if (!in_array('levels', $webIds)) $webIds[] = 'levels';
                continue;
            }
            $webIds[] = $row['id'];
            $section = $json->firstWhere('id', $row['id']);
            $this->assertSame($row['display_title'], $section['title']);
            $this->assertSame(collect($row['content'])->map(function ($item) { return (string) $item->id; })->all(), array_column($section['items'], 'id'));
            if ($row['type'] === 'piece') $this->assertSame($row['content']->pluck('discover_artwork_url')->all(), array_column($section['items'], 'artwork_url'));
        }
        $this->assertSame($webIds, $json->pluck('id')->all());
        $this->assertSame($group, $json->firstWhere('id', 'composer_feature')['group']);
        $this->assertCount(6, $json->firstWhere('id', 'for_you')['items']);
        $this->assertSame([(string) $this->pieces[5]->id], array_column($json->firstWhere('id', 'recently_viewed')['items'], 'id'));
        $this->assertLessThan($json->pluck('id')->search('latest_pieces'), $json->pluck('id')->search('recently_viewed'));
        foreach ($json->firstWhere('id', 'levels')['systems'] as $system) {
            $this->assertSame(['1', '2', '10'], array_column($system['items'], 'number'));
        }
        $this->assertSame($json->all(), $this->v2($this->user->fresh())['sections']);
        $this->assertSame($legacy, serialize((new Api)->discover()->reject(function ($row) { return $row['title'] === 'For you'; })));
        $this->assertFalse(Cache::get($this->publicKey())->contains('title', 'Recently viewed'));
        $this->assertFalse(Cache::get($this->publicKey())->contains('title', 'For you'));
        foreach (Cache::get($this->publicKey()) as $row) {
            foreach ($row['content'] as $item) $this->assertArrayNotHasKey('webapp_has_synthesia', $item->getAttributes());
        }
    }

    public function test_native_content_uses_its_explicit_principal_independent_of_browser_or_submitted_identity()
    {
        $this->actingAs($this->user, 'web');
        request()->merge(['user_id' => $this->user->id]);
        $json = collect($this->v2($this->other)['sections']);
        $this->assertSame([(string) $this->pieces[6]->id], array_column($json->firstWhere('id', 'recently_viewed')['items'], 'id'));
        $guest = app(DiscoverFeed::class)->forUser(null);
        $this->assertFalse($guest->contains('id', 'for_you'));
        $this->assertFalse($guest->contains('id', 'recently_viewed'));
        $this->assertDatabaseCount('recently_viewed_pieces', 2);
    }

    public function test_favorite_changes_invalidate_the_account_selection_and_public_refresh_reselects_the_group()
    {
        $first = collect($this->v2($this->user)['sections'])->firstWhere('id', 'for_you')['items'][0]['id'];
        $this->user->favorites()->attach($first);
        $updated = collect($this->v2($this->user->fresh())['sections']);
        $this->assertNotContains($first, array_column($updated->firstWhere('id', 'for_you')['items'], 'id'));

        $this->choose('From women composers');
        Composer::where('gender', 'female')->update(['gender' => 'male']);
        app(DiscoverRefresh::class)->refresh();
        $json = collect($this->v2($this->user->fresh())['sections']);
        $this->assertSame('black', $json->firstWhere('id', 'composer_feature')['group']);
        $this->actingAs($this->user, 'web');
        $web = $this->get(route('webapp.discover'))->assertOk();
        $this->assertSame($json->firstWhere('id', 'composer_feature')['composer']['id'], (string) $web->viewData('rows')->firstWhere('id', 'composer_feature')['featured_composer']->id);
    }

    public function test_due_free_pick_rotation_reaches_both_renderers_without_a_worker()
    {
        FreePick::create(['piece_id' => $this->pieces[0]->id, 'scheduled_at' => now('UTC')->subHour(), 'reserved_at' => null]);
        FreePick::create(['piece_id' => $this->pieces[1]->id, 'scheduled_at' => now('UTC')->addHour(), 'reserved_at' => now('UTC')]);
        foreach ([$this->pieces[0], $this->pieces[1]] as $index => $pick) {
            if ($index) $this->travel(1)->hours();
            $json = collect($this->v2($this->user->fresh())['sections']);
            $this->assertSame([(string) $pick->id], array_column($json->firstWhere('id', 'free_pick')['items'], 'id'));
            $this->assertNotContains((string) $pick->id, array_column($json->firstWhere('id', 'past_highlights')['items'], 'id'));
            $this->actingAs($this->user, 'web');
            $web = $this->get(route('webapp.discover'))->assertOk();
            $this->assertSame([$pick->id], $web->viewData('rows')->firstWhere('id', 'free_pick')['content']->pluck('id')->all());
        }
    }

    public function test_v2_json_is_an_allowlist_without_html_styles_legacy_sources_or_media_entitlements()
    {
        $http = (new DiscoverResource(app(DiscoverFeed::class)->forUser($this->user)))->response();
        $this->assertStringContainsString('no-store', $http->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $http->headers->get('Cache-Control'));
        $response = $http->getData(true);
        $this->assertArrayHasKey('sections', $response['data']);
        $piece = collect($response['data']['sections'])->firstWhere('id', 'latest_pieces')['items'][0];
        $this->assertIsString($piece['id']);
        $this->assertIsBool($piece['is_new']);
        $this->assertSame(['video' => false, 'audio' => false, 'score' => false, 'synthesia' => true], $piece['available_media']);
        foreach (['source', 'color', 'row', 'score_path', 'audio_path', 'user_id', 'webapp_has_synthesia', 'hasWebMediaAccess'] as $field) {
            $this->assertArrayNotHasKey($field, $piece);
        }
        if ($path = getenv('DISCOVER_V2_RESPONSE_PATH')) file_put_contents($path, json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_the_shared_personalization_content_switch_hides_both_account_rows_without_changing_media_data()
    {
        $policy = app(\App\Services\WebApp\AccessPolicy::class);
        $rules = $policy->snapshot()['rules'];
        $rules['account']['personalization']['registered'] = 'blocked';
        $this->assertTrue($policy->save($rules, 0, \App\Admin::firstOrFail()->id));
        $json = collect($this->v2($this->user->fresh())['sections']);
        $this->assertFalse($json->contains('id', 'for_you'));
        $this->assertFalse($json->contains('id', 'recently_viewed'));
        $this->assertTrue($json->firstWhere('id', 'latest_pieces')['items'][0]['available_media']['synthesia']);
        $this->actingAs($this->user, 'web');
        $web = $this->get(route('webapp.discover'))->assertOk();
        $this->assertFalse($web->viewData('rows')->contains('id', 'for_you'));
        $this->assertFalse($web->viewData('rows')->contains('id', 'recently_viewed'));
    }

    public function test_v2_omits_an_unavailable_composer_feature_and_empty_level_systems()
    {
        Composer::query()->update(['gender' => 'male', 'ethnicity' => 'white']);
        \Illuminate\Support\Facades\DB::table('tags')->where('type', 'ranking')->update(['type' => 'technique']);
        $json = collect($this->v2($this->user)['sections']);
        $this->assertFalse($json->contains('id', 'composer_feature'));
        $this->assertFalse($json->contains('id', 'levels'));
    }
}
