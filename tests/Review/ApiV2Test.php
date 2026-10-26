<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, Tutorial, User};
use App\Auth\ApiV2\Sessions;
use App\Services\DiscoverFeed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Hash, Log, Mail, Redis, Route};

class ApiV2Test extends ReviewTestCase
{
    private $user, $other, $pieces;
    private const PASSWORD = 'review password with spaces ';

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->travelTo(carbon('2026-10-26 12:00:00'));
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['email' => 'account-a@example.test', 'first_name' => 'Example', 'last_name' => 'Account', 'password' => Hash::make(self::PASSWORD)]);
            $this->other = create(User::class, ['email' => 'account-b@example.test', 'password' => Hash::make(self::PASSWORD)]);
            $composer = create(Composer::class, ['name' => 'Example Composer', 'gender' => 'female', 'cover_path' => '', 'is_pedagogical' => false]);
            $tags = collect(['level' => 'intermediate', 'period' => 'romantic', 'mood' => 'happy', 'length' => 'short'])
                ->map(function ($name, $type) { return create(Tag::class, compact('name', 'type')); });
            $this->pieces = collect();
            foreach (range(1, 4) as $number) {
                $piece = create(Piece::class, ['name' => 'Example piece '.$number, 'composer_id' => $composer->id, 'cover_path' => null, 'highlighted_at' => now()->subDays($number)]);
                $piece->tags()->attach($tags->pluck('id'));
                create(Tutorial::class, ['piece_id' => $piece->id, 'category' => 'synthesia']);
                $this->pieces->push($piece);
            }
        });
    }

    private function login(?User $user = null)
    {
        return $this->postJson('/api/v2/auth/login', ['email' => ($user ?: $this->user)->email, 'password' => self::PASSWORD]);
    }

    private function headers(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    private function postEmptyJson(string $url, array $headers = [])
    {
        return $this->call('POST', $url, [], [], [], $this->transformHeadersToServerVars(
            $headers + ['Accept' => 'application/json', 'Content-Type' => 'application/json']
        ), '{}');
    }

    private function getApi(string $url, array $headers = [])
    {
        // Laravel 8 getJson sends an artificial [] body; real v2 reads have none.
        return $this->get($url, $headers + ['Accept' => 'application/json']);
    }

    private function fixture(string $name, $response): void
    {
        if (!$directory = getenv('API_V2_FIXTURE_DIR')) return;
        if (!is_dir($directory)) mkdir($directory, 0700, true);
        $data = json_decode($response->getContent());
        $normalize = function (&$value) use (&$normalize) {
            if (is_object($value)) {
                foreach ($value as $key => &$field) {
                    if ($key === 'access_token') $field = 'EXAMPLE_TOKEN_DO_NOT_USE';
                    elseif ($key === 'request_id') $field = '00000000-0000-4000-8000-000000000001';
                    else $normalize($field);
                }
            } elseif (is_array($value)) {
                foreach ($value as &$field) $normalize($field);
            } elseif (is_string($value) && preg_match('#^https?://#', $value)) {
                $value = 'https://example.invalid'.(parse_url($value, PHP_URL_PATH) ?: '/');
            }
        };
        $normalize($data);
        file_put_contents($directory.'/'.$name.'.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    public function test_login_issues_only_an_expiring_hashed_v2_session_and_does_not_log_into_web_or_send_mail()
    {
        $response = $this->postJson('/api/v2/auth/login', ['email' => ' ACCOUNT-A@EXAMPLE.TEST ', 'password' => self::PASSWORD])->assertOk();
        $response->assertJsonPath('data.token_type', 'Bearer')->assertJsonPath('data.account.id', (string) $this->user->id)
            ->assertJsonPath('data.expires_at', now('UTC')->addDays(30)->format('Y-m-d\TH:i:s\Z'));
        $token = $response->json('data.access_token');
        $this->assertMatchesRegularExpression('/^plv2_[a-f0-9]{64}$/D', $token);
        $row = DB::table('api_v2_sessions')->first();
        $this->assertSame(hash('sha256', $token), $row->token_hash);
        $this->assertNotSame($this->user->password, $row->credential_fingerprint);
        $this->assertStringNotContainsString($token, json_encode((array) $row));
        $this->assertSame(['id', 'first_name', 'last_name', 'email'], array_keys($response->json('data.account')));
        $this->assertGuest('web');
        $this->assertSame([], $response->headers->getCookies());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertSame($response->json('meta.request_id'), $response->headers->get('X-Request-ID'));
        Mail::assertNothingSent(); Mail::assertNothingQueued();
        $this->fixture('login', $response);
    }

    public function test_wrong_password_and_unknown_email_have_the_same_credential_error_without_creating_a_session()
    {
        $responses = [];
        foreach (['account-a@example.test', 'missing@example.test'] as $email) {
            $response = $this->postJson('/api/v2/auth/login', ['email' => $email, 'password' => 'wrong secret'])->assertUnauthorized();
            $responses[] = $response->json('error');
            $response->assertJsonPath('error.code', 'invalid_credentials')->assertHeader('WWW-Authenticate', 'Bearer');
            $this->assertStringNotContainsString('wrong secret', $response->getContent());
            $this->fixture('invalid-credentials', $response);
        }
        $this->assertSame($responses[0], $responses[1]);
        $this->assertDatabaseCount('api_v2_sessions', 0);
    }

    public function test_protected_routes_require_a_bearer_even_with_browser_identity_or_submitted_user_id()
    {
        $this->actingAs($this->user, 'web');
        foreach (['/api/v2/discover', '/api/v2/discover?user_id='.$this->user->id, '/api/v2/auth/me'] as $url) {
            $response = $this->get($url, ['Accept' => 'text/html'])->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
            $this->fixture('unauthenticated', $response);
        }
        $this->postJson('/api/v2/auth/logout', [])->assertUnauthorized();
        $this->postJson('/api/v2/pieces/'.$this->pieces[0]->id.'/view', [])->assertUnauthorized();
        foreach (['Bearer fake', 'Basic fake', 'Bearer plv2_'.str_repeat('a', 64), 'Bearer '.str_repeat('a', 500)] as $header) {
            $this->getApi('/api/v2/discover', ['Authorization' => $header])->assertUnauthorized();
        }
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_discover_and_history_use_the_verified_principal_and_reject_identity_overrides()
    {
        $a = $this->login()->assertOk()->json('data.access_token');
        $b = $this->login($this->other)->assertOk()->json('data.access_token');
        $this->actingAs($this->user, 'web');
        $this->postEmptyJson('/api/v2/pieces/'.$this->pieces[0]->id.'/view', $this->headers($a))->assertOk()->assertJsonPath('data.accepted', true);
        $this->postEmptyJson('/api/v2/pieces/'.$this->pieces[1]->id.'/view', $this->headers($b))->assertOk();
        foreach ([$a => $this->user, $b => $this->other] as $token => $user) {
            $this->getApi('/api/v2/auth/me', $this->headers($token))->assertOk()->assertJsonPath('data.account.id', (string) $user->id);
            $response = $this->getApi('/api/v2/discover', $this->headers($token))->assertOk();
            $recent = collect($response->json('data.sections'))->firstWhere('id', 'recently_viewed');
            $this->assertSame([(string) $this->pieces[$user->is($this->user) ? 0 : 1]->id], array_column($recent['items'], 'id'));
            $this->fixture('discover-with-history', $response);
        }
        $this->getApi('/api/v2/discover?user_id='.$this->user->id, $this->headers($b))->assertUnprocessable();
        $this->postJson('/api/v2/pieces/'.$this->pieces[2]->id.'/view', ['user_id' => $this->user->id], $this->headers($b))->assertUnprocessable();
        $this->assertDatabaseCount('recently_viewed_pieces', 2);
        $this->assertSame($this->user->id, auth('web')->id());
    }

    public function test_v2_http_discover_is_the_same_feed_as_the_web_renderer_and_feed_fetch_does_not_record_a_view()
    {
        $token = $this->login()->json('data.access_token');
        $response = $this->getApi('/api/v2/discover', $this->headers($token))->assertOk();
        $expected = new \App\Http\Resources\Api\V2\DiscoverResource(app(DiscoverFeed::class)->forUser($this->user->fresh()));
        $this->assertSame($expected->resolve(request()), $response->json('data'));
        $this->fixture('discover', $response);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->call('HEAD', '/api/v2/discover', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token])->assertOk();
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_logout_revokes_only_the_presented_session_and_expiry_is_rechecked_in_the_same_container()
    {
        $first = $this->login()->json('data.access_token');
        $second = $this->login()->json('data.access_token');
        $this->getApi('/api/v2/auth/me', $this->headers($first))->assertOk();
        $logout = $this->postEmptyJson('/api/v2/auth/logout', $this->headers($first))->assertOk()->assertJsonPath('data.logged_out', true);
        $this->fixture('logout', $logout);
        $this->getApi('/api/v2/auth/me', $this->headers($first))->assertUnauthorized();
        $me = $this->getApi('/api/v2/auth/me', $this->headers($second))->assertOk();
        $this->fixture('account', $me);
        $this->travel(30)->days();
        $this->getApi('/api/v2/auth/me', $this->headers($second))->assertUnauthorized();
        $this->assertDatabaseCount('api_v2_sessions', 1);
    }

    public function test_password_email_and_account_deletion_invalidate_sessions_without_changing_legacy_models()
    {
        $token = $this->login()->json('data.access_token');
        $this->user->update(['password' => Hash::make('changed password')]);
        $this->getApi('/api/v2/auth/me', $this->headers($token))->assertUnauthorized();
        $token = app(Sessions::class)->login($this->user->email, 'changed password')['access_token'];
        $this->user->update(['email' => 'changed@example.test']);
        $this->getApi('/api/v2/auth/me', $this->headers($token))->assertUnauthorized();
        $token = app(Sessions::class)->login('changed@example.test', 'changed password')['access_token'];
        $this->assertDatabaseCount('api_v2_sessions', 3);
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->assertDatabaseCount('api_v2_sessions', 0);
        $this->getApi('/api/v2/auth/me', $this->headers($token))->assertUnauthorized();
    }

    public function test_validation_json_errors_not_found_and_method_errors_always_use_the_v2_envelope()
    {
        $invalid = $this->postJson('/api/v2/auth/login', ['email' => [], 'password' => ''])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password'], 'error.fields');
        $this->fixture('invalid-request', $invalid);
        $this->postJson('/api/v2/auth/login', ['email' => $this->user->email, 'password' => self::PASSWORD, 'user_id' => $this->other->id])->assertUnprocessable();
        $this->call('POST', '/api/v2/auth/login', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{bad')->assertStatus(400)->assertJsonPath('error.code', 'invalid_json');
        $this->call('POST', '/api/v2/auth/login', [], [], [], ['CONTENT_TYPE' => 'application/json'], '[]')->assertUnprocessable();
        $this->call('POST', '/api/v2/auth/login', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'email=example')->assertStatus(415);
        $this->get('/api/v2/missing', ['Accept' => 'text/html'])->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->get('/api/v2/auth/login')->assertStatus(405)->assertHeader('Allow', 'POST')->assertJsonPath('error.code', 'method_not_allowed');
        $token = app(Sessions::class)->login($this->user->email, self::PASSWORD)['access_token'];
        $this->postEmptyJson('/api/v2/pieces/999999/view', $this->headers($token))->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $response = $this->getApi('/api/v2/auth/me', $this->headers($token) + ['X-Request-ID' => 'untrusted-correlation'])->assertOk();
        $this->assertNotSame('untrusted-correlation', $response->json('meta.request_id'));
    }

    public function test_login_and_read_limits_are_separate_from_the_legacy_api()
    {
        config(['api-v2.login_attempts_per_email_ip' => 2, 'api-v2.requests_per_ip' => 1]);
        $token = $this->login()->json('data.access_token');
        $this->login()->assertOk();
        $this->login()->assertStatus(429)->assertJsonPath('error.code', 'rate_limited')->assertHeader('Retry-After');
        $this->getApi('/api/v2/auth/me', $this->headers($token))->assertOk();
        $this->getApi('/api/v2/auth/me', $this->headers($token))->assertStatus(429)->assertHeader('Retry-After');
        $legacy = $this->postJson('/api/users/login', ['email' => $this->user->email, 'password' => self::PASSWORD])->assertOk()->assertJsonPath('id', $this->user->id);
        $this->assertArrayNotHasKey('data', $legacy->json());
        $this->assertArrayNotHasKey('access_token', $legacy->json());
    }

    public function test_https_is_required_outside_local_and_testing_and_no_credential_redirect_is_used()
    {
        $this->app->instance('env', 'production');
        try {
            $this->postJson('/api/v2/auth/login', ['email' => $this->user->email, 'password' => self::PASSWORD])->assertStatus(400)->assertJsonPath('error.code', 'https_required');
            $this->postJson('https://localhost/api/v2/auth/login', ['email' => $this->user->email, 'password' => self::PASSWORD])->assertOk();
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_unexpected_errors_are_redacted_even_with_debug_enabled_and_logging_omits_request_secrets()
    {
        config(['app.debug' => true]);
        Route::get('/api/v2/review/error', function () { throw new \RuntimeException('secret query credential-token password'); });
        $logger = \Mockery::mock();
        Log::shouldReceive('channel')->with('daily')->once()->andReturn($logger);
        $logger->shouldReceive('error')->once()->with('V2 request failed', \Mockery::on(function ($context) {
            return array_keys($context) === ['request_id', 'exception_type'];
        }));
        $response = $this->getApi('/api/v2/review/error', ['Authorization' => 'Bearer secret-token'])->assertStatus(500)->assertJsonPath('error.code', 'server_error');
        $this->assertStringNotContainsString('secret', $response->getContent());
        $this->assertStringNotContainsString('exception', $response->getContent());
        $this->assertEquals(new \stdClass, $response->getData()->error->fields);
    }

    public function test_legacy_routes_and_identity_stack_stay_separate_and_v2_fields_never_enter_user_json()
    {
        $routes = Route::getRoutes();
        foreach (['api.users.login', 'api.discover'] as $name) {
            $this->assertContains('api', $routes->getByName($name)->gatherMiddleware());
        }
        foreach (['api.v2.auth.login', 'api.v2.auth.me', 'api.v2.auth.logout', 'api.v2.discover', 'api.v2.pieces.view'] as $name) {
            $stack = $routes->getByName($name)->gatherMiddleware();
            $this->assertNotContains('api', $stack);
            $this->assertNotContains('web', $stack);
            $this->assertNotContains('log.app', $stack);
            $this->assertNotContains('location.update', $stack);
        }
        $before = $this->user->fresh()->toArray();
        $this->login()->assertOk();
        $this->assertSame($before, $this->user->fresh()->toArray());
        $response = $this->postJson('/api/users/login', ['email' => $this->user->email, 'password' => 'incorrect'])->assertOk();
        $this->assertSame(['error' => ['Sorry, the password is not valid.']], $response->json());
        $this->assertDatabaseCount('activity_events', 0);
    }

    public function test_history_keeps_excluded_accounts_out_and_pruning_only_removes_expired_v2_sessions()
    {
        $this->user->update(['email' => \App\Activity\ExcludedAccounts::emails()[0]]);
        $token = $this->login()->json('data.access_token');
        $this->postEmptyJson('/api/v2/pieces/'.$this->pieces[0]->id.'/view', $this->headers($token))->assertOk();
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        DB::table('api_v2_sessions')->update(['expires_at' => now('UTC')->toDateTimeString()]);
        $this->login($this->other)->assertOk();
        $this->artisan('api-v2:prune-sessions')->assertExitCode(0);
        $this->assertDatabaseCount('api_v2_sessions', 1);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('pieces', 4);
    }

    public function test_an_empty_catalogue_has_valid_json_and_no_account_history_and_debugbar_excludes_v2()
    {
        $token = $this->login()->json('data.access_token');
        DB::table('pieces')->delete();
        $response = $this->getApi('/api/v2/discover', $this->headers($token))->assertOk();
        foreach ($response->json('data.sections') as $section) $this->assertSame([], $section['items']);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->assertContains('api/v2/*', config('debugbar.except'));
        $this->assertNotContains('api/*', config('debugbar.except'));
        $this->fixture('discover-empty', $response);
    }

    public function test_the_actual_legacy_discover_payload_is_unchanged_after_v2_login_and_reads()
    {
        $url = '/api/discover?user_id='.$this->user->id;
        $before = $this->get($url, ['Accept' => 'application/json'])->assertOk()->json();
        $token = $this->login()->assertOk()->json('data.access_token');
        $this->getApi('/api/v2/discover', $this->headers($token))->assertOk();
        $after = $this->get($url, ['Accept' => 'application/json'])->assertOk()->json();
        $public = function ($rows) {
            return collect($rows)->reject(function ($row) { return $row['title'] === 'For you'; })->values()->all();
        };
        $this->assertSame($public($before), $public($after));
        $this->assertSame(['Free weekly pick', 'Composers', 'Latest pieces', 'For you'], array_slice(array_column($after, 'title'), 0, 4));
        $this->assertContains('From women composers', array_column($after, 'title'));
        $this->assertContains('From black composers', array_column($after, 'title'));
        foreach ($after as $row) {
            $this->assertArrayNotHasKey('id', $row);
            $this->assertArrayNotHasKey('web_composer_feature', $row);
        }
    }
}
