<?php

namespace Tests\Review;

use App\{Composer, FreePick, Piece, Tag, Tutorial, User};
use App\Auth\ApiV2\Sessions;
use App\Services\RecentlyViewedPieces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Hash, Mail, Redis};

/** Generates supplemental .3 fixtures without changing the immutable handoff. */
class ApiV2HandoffDeliveryTest extends ReviewTestCase
{
    private $user, $pieces, $token, $index = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(carbon('2026-10-26 12:00:00'));
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['first_name' => 'Synthetic', 'last_name' => 'Account', 'email' => 'synthetic@example.test',
                'password' => Hash::make('isolated-fixture-proof'), 'super_user' => false]);
            $composers = collect(['One', 'Two'])->map(function ($number) {
                return create(Composer::class, ['name' => 'Fixture Composer '.$number, 'gender' => $number === 'One' ? 'female' : 'male',
                    'ethnicity' => $number === 'One' ? 'white' : 'black', 'cover_path' => '', 'biography' => '<p>Synthetic biography for offline testing.</p>',
                    'is_pedagogical' => false, 'date_of_birth' => '1800-01-01', 'date_of_death' => '1870-01-01']);
            });
            $tags = collect(['level' => 'intermediate', 'period' => 'romantic', 'length' => 'short', 'mood' => 'happy'])
                ->map(function ($name, $type) { return create(Tag::class, compact('name', 'type')); });
            $this->pieces = collect();
            foreach (range(0, 23) as $number) {
                $piece = create(Piece::class, ['name' => sprintf('Fixture Piece %02d', $number + 1), 'composer_id' => $composers[$number % 2]->id,
                    'catalogue_name' => null, 'catalogue_number' => null, 'collection_name' => null, 'collection_number' => null, 'key' => 'C major',
                    'cover_path' => null, 'audio_path' => $number % 2 ? null : 'fixtures/audio.mp3',
                    'score_path' => $number % 3 ? null : 'fixtures/score.pdf', 'score_url' => null,
                    'highlighted_at' => $number < 10 ? now()->subDays(10 - $number) : null,
                    'created_at' => now()->subMinutes(24 - $number)]);
                $piece->tags()->attach($tags->pluck('id'));
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Synthesia', 'category' => 'synthesia',
                    'video_url' => 'https://example.invalid/fixtures/synthesia.mp4', 'filename' => 'synthetic', 'description' => 'Synthetic tutorial']);
                if ($number % 2 === 0) create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'category' => 'performance',
                    'video_url' => 'https://example.invalid/fixtures/performance.mp4', 'filename' => 'synthetic', 'description' => 'Synthetic performance']);
                $this->pieces->push($piece);
            }
            foreach (['rcm', 'abrsm', 'suzuki'] as $system) foreach ([10, 2, 1] as $number) {
                $tag = create(Tag::class, ['name' => $system.' '.$number, 'type' => 'ranking']);
                $tag->pieces()->attach($this->pieces->take(8)->pluck('id'));
            }
        });
        FreePick::create(['piece_id' => $this->pieces[0]->id, 'scheduled_at' => now('UTC')->subHour(), 'activated_at' => now('UTC')->subHour()]);
        (new RecentlyViewedPieces)->record($this->user, $this->pieces[2]);
        $this->token = app(Sessions::class)->login($this->user->email, 'isolated-fixture-proof')['access_token'];
    }

    private function callFixture(string $name, string $method, string $path, ?string $body = null, int $status = 200,
        ?string $schema = null, bool $authenticated = true, string $contentType = 'application/json')
    {
        $headers = ['Accept' => 'application/json'];
        if ($body !== null) $headers['Content-Type'] = $contentType;
        if ($authenticated) $headers['Authorization'] = 'Bearer '.$this->token;
        $response = $this->call($method, $path, [], [], [], $this->transformHeadersToServerVars($headers), $body);
        $response->assertStatus($status);
        $entry = ['request' => ['method' => $method, 'path' => $path, 'headers' => $headers], 'http_status' => $status,
            'response_file' => $name.'.json', 'schema' => $schema, 'category' => strpos($name, 'legacy-') === 0 ? 'legacy_bridge_response' : 'v2_response'];
        if ($authenticated) $entry['request']['headers']['Authorization'] = 'Bearer EXAMPLE_TOKEN_DO_NOT_USE';
        if ($body !== null) {
            $entry['request']['body'] = $body;
            $decoded = json_decode($body);
            if ($decoded instanceof \stdClass && isset($decoded->password) && is_string($decoded->password)) {
                $decoded->password = 'REDACTED_NOT_A_PASSWORD';
                $entry['request']['body'] = $decoded;
            }
        }
        $entry['response_headers'] = array_intersect_key($response->headers->all(), array_flip(['content-type', 'cache-control', 'www-authenticate', 'retry-after', 'allow']));
        $this->index[$name] = $entry;
        if ($directory = getenv('API_V2_DELIVERY_FIXTURE_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            $value = json_decode($response->getContent());
            $clean = function (&$item) use (&$clean) {
                if (is_object($item)) foreach ($item as $key => &$field) {
                    if ($key === 'access_token') $field = 'EXAMPLE_TOKEN_DO_NOT_USE';
                    elseif ($key === 'request_id') $field = '00000000-0000-4000-8000-000000000001';
                    else $clean($field);
                }
                elseif (is_array($item)) foreach ($item as &$field) $clean($field);
                elseif (is_string($item) && preg_match('#^https?://#', $item)) $item = 'https://example.invalid'.(parse_url($item, PHP_URL_PATH) ?: '/');
            };
            $clean($value);
            file_put_contents($directory.'/'.$name.'.json', json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        }
        return $response;
    }

    public function test_complete_offline_responses_cover_every_kind_and_real_history_semantics()
    {
        $this->callFixture('login-success', 'POST', '/api/v2/auth/login', json_encode(['email' => $this->user->email, 'password' => 'isolated-fixture-proof']), 200, 'LoginEnvelope', false);
        $this->callFixture('login-invalid-credentials', 'POST', '/api/v2/auth/login', '{"email":"missing@example.test","password":"REDACTED_NOT_A_PASSWORD"}', 401, 'ErrorEnvelope', false);
        $this->callFixture('login-validation-failure', 'POST', '/api/v2/auth/login', '{"email":"invalid","password":null}', 422, 'ErrorEnvelope', false);
        $this->callFixture('login-malformed-json', 'POST', '/api/v2/auth/login', '{bad', 400, 'ErrorEnvelope', false);
        $this->callFixture('account-success', 'GET', '/api/v2/auth/me', null, 200, 'AccountEnvelope');
        $feed = $this->callFixture('discover-complete', 'GET', '/api/v2/discover', null, 200, 'DiscoverEnvelope');
        $sections = collect($feed->json('data.sections'));
        $this->assertSame(['free_pick', 'composers', 'recently_viewed', 'latest_pieces', 'for_you', 'composer_feature', 'levels', 'past_highlights', 'mood', 'similar_to_free_pick'], $sections->pluck('id')->all());
        $this->assertCount(7, $sections->pluck('kind')->unique());
        $this->assertCount(6, $sections->firstWhere('id', 'for_you')['items']);
        $this->assertTrue($sections->firstWhere('id', 'free_pick')['items'][0]['is_free_pick']);
        $this->assertSame(['1', '2', '10'], array_column($sections->firstWhere('id', 'levels')['systems'][0]['items'], 'number'));
        $this->assertNull($sections->firstWhere('id', 'free_pick')['items'][0]['attribution']);
        foreach (['women' => 'From women composers', 'black' => 'From black composers'] as $group => $title) {
            $key = \App\Api\Api::freePickCacheKey('app.discover'.\App\Api\Api::WEB_DISCOVER_CACHE_SUFFIX);
            Cache::put($key, Cache::get($key)->map(function ($row) use ($title) {
                if ($row['title'] === 'From women composers') $row['web_composer_feature'] = $title;
                return $row;
            }), 3600);
            $variant = $this->callFixture('discover-feature-'.$group, 'GET', '/api/v2/discover', null, 200, 'DiscoverEnvelope');
            $this->assertSame($group, collect($variant->json('data.sections'))->firstWhere('id', 'composer_feature')['group']);
        }

        $this->callFixture('history-missing-bearer', 'POST', '/api/v2/pieces/4/view', '{}', 401, 'ErrorEnvelope', false);
        $this->callFixture('history-missing-piece', 'POST', '/api/v2/pieces/999999/view', '{}', 404, 'ErrorEnvelope');
        $this->callFixture('history-identity-rejected', 'POST', '/api/v2/pieces/4/view', '{"user_id":999}', 422, 'ErrorEnvelope');
        $this->callFixture('history-malformed-json', 'POST', '/api/v2/pieces/4/view', '{bad', 400, 'ErrorEnvelope');
        $this->callFixture('history-wrong-content-type', 'POST', '/api/v2/pieces/4/view', 'invalid', 415, 'ErrorEnvelope', true, 'text/plain');
        $this->callFixture('history-success', 'POST', '/api/v2/pieces/4/view', '{}', 200, 'PieceViewEnvelope');
        $firstViewed = DB::table('recently_viewed_pieces')->where('user_id', $this->user->id)->where('piece_id', 4)->value('viewed_at');
        $this->travel(2)->seconds();
        $this->callFixture('history-retry-success', 'POST', '/api/v2/pieces/4/view', '{}', 200, 'PieceViewEnvelope');
        $this->assertDatabaseCount('recently_viewed_pieces', 2);
        $this->assertNotSame($firstViewed, DB::table('recently_viewed_pieces')->where('user_id', $this->user->id)->where('piece_id', 4)->value('viewed_at'));
        $after = $this->callFixture('discover-after-open', 'GET', '/api/v2/discover', null, 200, 'DiscoverEnvelope');
        $this->assertSame('4', collect($after->json('data.sections'))->firstWhere('id', 'recently_viewed')['items'][0]['id']);

        // Exercise only DB-backed synthetic legacy reads; never Apple/Stripe calls.
        $this->user->update(['super_user' => true]);
        $this->assertSame('active', $this->callFixture('legacy-status-active-override', 'POST', '/api/memberships/status', '{"user_id":'.$this->user->id.'}', 200, null, false)->json());
        $this->user->update(['super_user' => false]);
        $this->assertSame('expired', $this->callFixture('legacy-status-no-membership', 'POST', '/api/memberships/status', '{"user_id":'.$this->user->id.'}', 200, null, false)->json());
        $this->callFixture('legacy-piece-detail', 'POST', '/api/pieces/find', '{"search":4,"user_id":'.$this->user->id.'}', 200, null, false);
        $this->callFixture('legacy-composer-results', 'GET', '/api/search?search=Fixture%20Composer%20One&model=App%5CComposer&user_id='.$this->user->id, null, 200, null, false);
        $this->callFixture('legacy-level-results', 'GET', '/api/search?search=rcm%201&model=App%5CTag&user_id='.$this->user->id, null, 200, null, false);

        $this->pieces[23]->tags()->detach(Tag::where('type', 'level')->pluck('id'));
        Cache::flush();
        $nullable = $this->callFixture('discover-null-fields', 'GET', '/api/v2/discover', null, 200, 'DiscoverEnvelope');
        $nullPiece = collect($nullable->json('data.sections'))->firstWhere('id', 'latest_pieces')['items'][0];
        $this->assertNull($nullPiece['difficulty']['name']);
        $this->assertNull($nullPiece['difficulty']['label']);

        config(['api-v2.requests_per_ip' => 1]);
        $this->callFixture('history-rate-limited', 'POST', '/api/v2/pieces/4/view', '{}', 429, 'ErrorEnvelope');
        config(['api-v2.requests_per_ip' => 120]);
        DB::table('api_v2_sessions')->where('token_hash', hash('sha256', $this->token))->update(['expires_at' => now('UTC')->toDateTimeString()]);
        $this->callFixture('history-expired-bearer', 'POST', '/api/v2/pieces/4/view', '{}', 401, 'ErrorEnvelope');
        $this->token = app(Sessions::class)->login($this->user->email, 'isolated-fixture-proof')['access_token'];
        $this->callFixture('logout-success', 'POST', '/api/v2/auth/logout', '{}', 200, 'LogoutEnvelope');
        $this->callFixture('discover-revoked-bearer', 'GET', '/api/v2/discover', null, 401, 'ErrorEnvelope');

        config(['api-v2.login_attempts_per_email_ip' => 1]);
        $this->postJson('/api/v2/auth/login', ['email' => $this->user->email, 'password' => 'isolated-fixture-proof'])->assertOk();
        $this->callFixture('login-rate-limited', 'POST', '/api/v2/auth/login', json_encode(['email' => $this->user->email, 'password' => 'isolated-fixture-proof']), 429, 'ErrorEnvelope', false);

        $this->token = app(Sessions::class)->login($this->user->email, 'isolated-fixture-proof')['access_token'];
        DB::table('pieces')->delete(); Cache::flush();
        $this->callFixture('discover-empty', 'GET', '/api/v2/discover', null, 200, 'DiscoverEnvelope');
        // Simulated dependency failures exercise the actual v2 error boundary.
        $logger = \Mockery::mock();
        \Illuminate\Support\Facades\Log::shouldReceive('channel')->with('daily')->andReturn($logger);
        $logger->shouldReceive('error')->andReturnNull();
        foreach (['discover-server-error' => new \RuntimeException('isolated failure'),
            'discover-temporarily-unavailable' => new \Illuminate\Contracts\Cache\LockTimeoutException] as $name => $error) {
            $feed = \Mockery::mock(\App\Services\DiscoverFeed::class);
            $feed->shouldReceive('forUser')->andThrow($error);
            $this->app->instance(\App\Services\DiscoverFeed::class, $feed);
            $this->callFixture($name, 'GET', '/api/v2/discover', null, $name === 'discover-server-error' ? 500 : 503, 'ErrorEnvelope');
            $this->index[$name]['fault_injection'] = 'Synthetic dependency exception; real HTTP exception renderer';
        }
        $this->app->forgetInstance(\App\Services\DiscoverFeed::class);
        if ($directory = getenv('API_V2_DELIVERY_FIXTURE_DIR')) {
            file_put_contents($directory.'/http-index.json', json_encode(['revision' => '2026-10-26.3', 'synthetic' => true, 'cases' => $this->index], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        }
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }
}
