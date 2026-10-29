<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use App\Api\Api;
use App\Services\Api\V2\Tokens;
use App\Services\RecentlyViewedPieces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Hash, Mail, Redis};

class ApiV2DiscoverTest extends ReviewTestCase
{
    private $user, $other, $piece, $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(carbon('2026-10-28 12:00:00'));
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['email' => 'native-v2@example.test', 'password' => Hash::make('same-existing-password')]);
            $this->other = create(User::class);
            $composer = create(Composer::class, ['name' => 'Shared Composer', 'cover_path' => 'fixture.jpg']);
            $this->piece = create(Piece::class, ['composer_id' => $composer->id, 'name' => 'Shared Discover piece', 'cover_path' => 'fixture.jpg', 'highlighted_at' => now()->subWeek()]);
            $this->piece->tags()->attach(create(Tag::class, ['type' => 'level', 'name' => 'intermediate']));
        });
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
        Cache::put('app.discover'.Api::WEB_DISCOVER_CACHE_SUFFIX, collect([
            ['title' => 'Free weekly pick', 'row' => 'freepick', 'type' => 'piece', 'content' => []],
            ['title' => 'Composers', 'row' => 'composers', 'type' => 'composer', 'content' => [$this->piece->composer]],
            ['title' => 'Latest pieces', 'row' => 'gallery', 'type' => 'piece', 'content' => [$this->piece]],
            ['title' => 'From women composers', 'row' => 'gallery', 'type' => 'piece', 'content' => [$this->piece], 'web_composer_feature' => 'From black composers'],
            ['title' => 'Equivalent to the Suzuki series', 'row' => 'gallery', 'type' => 'collection', 'content' => [(new Tag)->forceFill(['name' => 'Suzuki Book 2', 'pieces_count' => 9])]],
            ['title' => 'Equivalent to the RCM levels', 'row' => 'gallery', 'type' => 'collection', 'content' => collect([10, 2])->map(function ($i) { return (new Tag)->forceFill(['name' => 'RCM '.$i, 'pieces_count' => 5]); })],
            ['title' => 'From black composers', 'row' => 'gallery', 'type' => 'piece', 'content' => [$this->piece]],
            ['title' => 'Pieces that are calm', 'row' => 'gallery', 'type' => 'piece', 'content' => [$this->piece]],
            ['title' => "Like today's free pick", 'row' => 'gallery', 'type' => 'piece', 'content' => [$this->piece]],
        ]), 3600);
    }

    private function feed(array $query = [], ?string $token = null)
    {
        return $this->getJson('/api/v2/discover?'.http_build_query($query), ['Authorization' => 'Bearer '.($token ?? $this->token)]);
    }

    public function test_same_existing_password_issues_a_hashed_v2_credential_without_changing_legacy_login()
    {
        $response = $this->postJson('/api/v2/auth/login', ['email' => $this->user->email, 'password' => 'same-existing-password'])
            ->assertOk()->assertJsonPath('data.user.id', (string) $this->user->id)->assertJsonPath('data.token_type', 'Bearer');
        $token = $response->json('data.access_token');
        $this->assertDatabaseHas('api_v2_tokens', ['token_hash' => hash('sha256', $token), 'user_id' => $this->user->id]);
        $this->assertStringNotContainsString($token, json_encode(DB::table('api_v2_tokens')->get()));
        $this->assertSame($this->user->id, app(Tokens::class)->resolve($token)->id);
        $this->postJson('/api/users/login', ['email' => $this->user->email, 'password' => 'same-existing-password'])
            ->assertOk()->assertJsonPath('id', $this->user->id)->assertJsonMissing(['data'])->assertJsonMissing(['access_token']);
        Mail::assertNothingSent();
        $this->assertGuest('web');
    }

    public function test_missing_invalid_and_query_credentials_cannot_use_browser_or_submitted_identity()
    {
        $this->actingAs($this->user, 'web');
        foreach ([[], ['user_id' => $this->user->id], ['access_token' => $this->token], ['api_token' => $this->token]] as $query) {
            $this->getJson('/api/v2/discover?'.http_build_query($query))->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated')->assertJsonMissing(['data']);
        }
        $this->feed([], 'invalid')->assertUnauthorized();
        $this->feed([], str_repeat('a', 64))->assertUnauthorized();
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_logout_expiry_password_changes_and_account_deletion_revoke_access()
    {
        $this->postJson('/api/v2/auth/logout', [], ['Authorization' => 'Bearer '.$this->token])->assertNoContent();
        $this->feed()->assertUnauthorized();
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
        $this->travel(31)->days();
        $this->feed()->assertUnauthorized();
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
        $this->user->updateQuietly(['password' => Hash::make('changed-password')]);
        $this->feed()->assertUnauthorized();
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->feed()->assertUnauthorized();
        $this->assertDatabaseCount('api_v2_tokens', 0);
    }

    public function test_errors_are_json_without_accept_headers_and_login_is_rate_limited()
    {
        $this->post('/api/v2/auth/login', [])->assertStatus(422)->assertJsonPath('error.code', 'invalid_request')->assertJsonStructure(['meta' => ['request_id']]);
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v2/auth/login', ['email' => 'unknown@example.test', 'password' => 'bad'])->assertUnauthorized()->assertJsonPath('error.code', 'invalid_credentials');
        }
        $this->postJson('/api/v2/auth/login', ['email' => $this->user->email, 'password' => 'same-existing-password'])
            ->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('error.code', 'rate_limited');
        $this->get('/api/v2/discover')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->get('/api/v2/missing')->assertNotFound()->assertJsonStructure(['error' => ['code', 'message', 'fields'], 'meta' => ['request_id']]);
    }

    public function test_web_and_v2_receive_the_same_sections_and_piece_ids_with_shared_personalization()
    {
        app(RecentlyViewedPieces::class)->record($this->user, $this->piece);
        $cached = serialize(Cache::get('app.discover'.Api::WEB_DISCOVER_CACHE_SUFFIX));
        $response = $this->feed(['user_id' => $this->other->id])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeaderMissing('Set-Cookie');
        if ($fixture = getenv('API_V2_DISCOVER_FIXTURE')) file_put_contents($fixture, json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $sections = collect($response->json('data.sections'))->keyBy('id');
        $this->assertSame(['composers', 'recently_viewed', 'latest_pieces', 'for_you', 'black_composers', 'levels', 'past_highlights', 'mood', 'similar_to_free_pick'], $sections->keys()->all());
        $this->assertSame([2, 10], array_column($sections['levels']['groups'][0]['items'], 'number'));
        $this->assertSame(['rcm', 'abrsm', 'suzuki'], array_column($sections['levels']['groups'], 'id'));
        $this->assertSame([(string) $this->piece->id], array_column($sections['recently_viewed']['items'], 'id'));
        $this->assertGuest('web');
        $this->assertSame($cached, serialize(Cache::get('app.discover'.Api::WEB_DISCOVER_CACHE_SUFFIX)));
        $this->actingAs($this->user, 'web');
        $rows = $this->get(route('webapp.discover'))->assertOk()->viewData('rows');
        foreach (['Latest pieces' => 'latest_pieces', 'For you' => 'for_you', 'Recently viewed' => 'recently_viewed', 'From black composers' => 'black_composers', 'Past highlights' => 'past_highlights'] as $title => $id) {
            $this->assertSame(collect($rows->firstWhere('title', $title)['content'])->map(function ($piece) { return (string) $piece->id; })->values()->all(), array_column($sections[$id]['items'], 'id'));
        }
        $card = $sections['latest_pieces']['items'][0];
        $this->assertSame($rows->firstWhere('title', 'Latest pieces')['content']->first()->discover_artwork_url, $card['artwork_url']);
        $this->assertSame('piece', $card['target']['kind']);
        $this->assertArrayHasKey('performance', $card['media']);
        $this->assertFalse($card['media']['performance'], 'The compatibility field cannot advertise retired user submissions.');
        foreach (['source', 'user_id', 'score', 'audio', 'password', 'favorites', 'webapp_has_synthesia'] as $field) $this->assertArrayNotHasKey($field, $card);
    }

    public function test_history_is_account_owned_and_only_confirmed_opens_write_it()
    {
        $this->feed()->assertOk();
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->postJson('/api/v2/pieces/'.$this->piece->id.'/view', ['user_id' => $this->other->id], ['Authorization' => 'Bearer '.$this->token])->assertNoContent();
        $this->postJson('/api/v2/pieces/'.$this->piece->id.'/view', [], ['Authorization' => 'Bearer '.$this->token])->assertNoContent();
        $this->assertDatabaseCount('recently_viewed_pieces', 1);
        $this->assertDatabaseHas('recently_viewed_pieces', ['user_id' => $this->user->id]);
        $otherToken = app(Tokens::class)->issue($this->other)['access_token'];
        $otherSections = $this->feed(['user_id' => $this->user->id], $otherToken)->assertOk()->json('data.sections');
        $this->assertNotContains('recently_viewed', array_column($otherSections, 'id'));
        $this->postJson('/api/v2/pieces/99999/view', [], ['Authorization' => 'Bearer '.$this->token])->assertNotFound()->assertJsonPath('error.code', 'not_found');
    }

    public function test_scheduled_free_pick_rotates_for_both_clients_without_a_worker_or_changing_legacy_cache()
    {
        $schedule = app(\App\Services\FreePickSchedule::class);
        $schedule->schedule($this->piece);
        $next = Model::withoutEvents(function () {
            return create(Piece::class, ['name' => 'Next scheduled pick', 'composer_id' => $this->piece->composer_id, 'cover_path' => 'next.jpg', 'highlighted_at' => null]);
        });
        $schedule->schedule($next, now()->addDay());
        foreach ([$this->piece, $next] as $pick) {
            $response = $this->feed()->assertOk();
            $sections = collect($response->json('data.sections'))->keyBy('id');
            $this->assertSame((string) $pick->id, $sections['free_pick']['items'][0]['id']);
            $this->assertTrue($sections['free_pick']['items'][0]['is_free_pick']);
            $this->assertNotContains((string) $pick->id, array_column($sections->get('past_highlights')['items'] ?? [], 'id'));
            $this->actingAs($this->user, 'web');
            $rows = $this->get(route('webapp.discover'))->assertOk()->viewData('rows');
            $this->assertSame($pick->id, collect($rows->firstWhere('title', 'Free weekly pick')['content'])->first()->id);
            $legacy = (new Api)->discover();
            $legacyCache = serialize(Cache::get(Api::freePickCacheKey('app.discover')));
            $this->feed()->assertOk();
            $this->assertSame($legacyCache, serialize(Cache::get(Api::freePickCacheKey('app.discover'))));
            $this->assertNotNull($legacy->firstWhere('title', 'From black composers'));
            foreach ($legacy as $row) foreach ($row['content'] as $item) {
                $this->assertArrayNotHasKey('discover_artwork_url', $item->getAttributes());
                $this->assertArrayNotHasKey('webapp_has_synthesia', $item->getAttributes());
            }
            $this->travel(1)->days();
        }
        Mail::assertNothingSent();
    }

    public function test_favorites_changes_invalidate_the_shared_personal_recommendations()
    {
        $before = app(\App\Services\Discover\Feed::class)->rows($this->user)->firstWhere('title', 'For you')['content'];
        $this->assertTrue($before->contains('id', $this->piece->id));
        $this->user->favorites()->attach($this->piece);
        $user = $this->user->fresh();
        $after = app(\App\Services\Discover\Feed::class)->rows($user)->firstWhere('title', 'For you')['content'];
        $this->assertFalse($after->contains('id', $this->piece->id));
    }

    public function test_empty_catalogue_returns_an_explicit_empty_sections_array()
    {
        DB::table('pieces')->delete();
        Cache::put('app.discover'.Api::WEB_DISCOVER_CACHE_SUFFIX, collect(), 60);
        $this->feed()->assertOk()->assertExactJson(['data' => ['sections' => []], 'meta' => ['request_id' => \Illuminate\Support\Facades\Request::instance()->attributes->get('api_v2_request_id')]]);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }
}
