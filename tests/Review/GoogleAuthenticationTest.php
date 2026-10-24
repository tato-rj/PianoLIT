<?php

namespace Tests\Review;

use App\User;
use App\Auth\Google\{Accounts, Configuration, IdTokenVerifier};
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Auth\Events\{Login, Registered};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Event, Hash, Http, Notification, Password, Schema};

class GoogleAuthenticationTest extends ReviewTestCase
{
    public function test_google_callback_uses_the_parent_session_when_shared_sessions_are_enabled()
    {
        config(['app.short_url' => 'pianolit.test', 'app.url' => 'http://pianolit.test',
            'session.shared.enabled' => true, 'session.shared.domain' => 'pianolit.test', 'session.shared.lock_store' => 'array']);
        $this->app['auth']->forgetGuards(); $this->app['session']->forgetDrivers(); $this->app->forgetInstance('session.store');
        $user = $this->user(); $this->connectRecord($user);
        $query = $this->start('pianolit.test');
        $this->withCookies([\App\Auth\SharedWebSessions::COOKIE => session()->getId()]);
        $response = $this->googleCallback($query, 'pianolit.test')->assertRedirect('http://pianolit.test/');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertInstanceOf(\App\Auth\SharedSessionGuard::class, auth('web'));
        $cookie = collect($response->headers->getCookies())->first(function ($c) { return $c->getName() === \App\Auth\SharedWebSessions::COOKIE; });
        $this->assertSame('pianolit.test', $cookie->getDomain());
        $this->assertDatabaseCount('auth_browser_handoffs', 0);
    }

    private $rsa, $public, $nonce, $googleEmail = 'existing@example.test', $subject = 'google-subject-one';
    private $randomFile, $originalRandomFile;

    public function setUp(): void
    {
        parent::setUp();
        $this->originalRandomFile = getenv('RANDFILE');
        $this->randomFile = tempnam(sys_get_temp_dir(), 'pianolit-google-random-');
        putenv('RANDFILE='.$this->randomFile);
        config(['app.name' => 'PianoLIT', 'services.google_login.client_id' => 'review-client', 'services.google_login.client_secret' => 'review-secret', 'activity.enabled' => false, 'attribution.enabled' => false]);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebLog::class, \App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Notification::fake();
        Event::fake([Registered::class]);
        $this->rsa = openssl_pkey_new(['config' => '/dev/null', 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->public = openssl_pkey_get_details($this->rsa)['key'];
        $this->fakeGoogle();
    }

    public function tearDown(): void
    {
        if ($this->randomFile) unlink($this->randomFile);
        putenv($this->originalRandomFile === false ? 'RANDFILE' : 'RANDFILE='.$this->originalRandomFile);
        parent::tearDown();
    }

    private function user(array $overrides = [])
    {
        return Model::withoutEvents(function () use ($overrides) { return create(User::class, array_merge(['email' => $this->googleEmail, 'password' => Hash::make('existing-password')], $overrides)); });
    }

    private function claims(array $overrides = [])
    {
        return array_merge(['iss' => 'https://accounts.google.com', 'aud' => 'review-client', 'sub' => $this->subject,
            'email' => $this->googleEmail, 'email_verified' => true, 'iat' => time() - 5, 'exp' => time() + 300,
            'nonce' => $this->nonce, 'given_name' => 'Google', 'family_name' => 'Pianist'], $overrides);
    }

    private function jwt(array $claims, array $header = [])
    {
        $encode = function ($v) { return rtrim(strtr(base64_encode(is_array($v) ? json_encode($v) : $v), '+/', '-_'), '='); };
        $body = $encode(array_merge(['alg' => 'RS256', 'kid' => 'review-key', 'typ' => 'JWT'], $header)).'.'.$encode($claims);
        openssl_sign($body, $signature, $this->rsa, OPENSSL_ALGO_SHA256);
        return $body.'.'.$encode($signature);
    }

    private function fakeGoogle(array $claims = [])
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use ($claims) {
            if ($request->url() === 'https://www.googleapis.com/oauth2/v1/certs') return Http::response(['review-key' => $this->public]);
            if ($request->url() === 'https://oauth2.googleapis.com/token') return Http::response(['id_token' => $this->jwt($this->claims($claims)), 'access_token' => 'discarded-access-token']);
            throw new \RuntimeException('Unexpected external request in Google auth tests.');
        });
    }

    private function start($host = 'localhost', $path = '')
    {
        $response = $this->get('http://'.$host.'/auth/google'.$path)->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        return $query;
    }

    private function googleCallback($query, $host = 'localhost', array $overrides = [])
    {
        $this->nonce = $query['nonce'];
        return $this->get('http://'.$host.'/auth/google/callback?'.http_build_query(array_merge(['state' => $query['state'], 'code' => 'review-code'], $overrides)));
    }

    private function connectRecord($user, $subject = null, $unusable = null)
    {
        DB::transaction(function () use ($user, $subject, $unusable) {
            (new Accounts)->link($user, ['sub' => $subject ?: $this->subject, 'email' => $this->googleEmail], $unusable);
        });
    }

    private function proofPost($url, array $data = [])
    {
        return $this->post($url, $data + ['challenge_id' => session('google_auth.challenge.id')]);
    }

    private function proofJson($url, array $data = [])
    {
        return $this->postJson($url, $data + ['challenge_id' => session('google_auth.challenge.id')]);
    }

    private function googleOnly()
    {
        $user = $this->user(['password' => Hash::make(bin2hex(random_bytes(32)))]);
        $this->connectRecord($user, null, $user->password);
        return $user;
    }

    public function test_authorization_is_session_bound_pkce_with_only_identity_scopes_on_both_hosts()
    {
        foreach (['localhost', 'my.localhost'] as $host) {
            $q = $this->start($host);
            $this->assertSame('openid email profile', $q['scope']);
            $this->assertSame('S256', $q['code_challenge_method']);
            $this->assertSame('http://'.$host.'/auth/google/callback', $q['redirect_uri']);
            $this->assertSame('code', $q['response_type']);
            $this->assertArrayNotHasKey('client_secret', $q);
            $this->assertArrayNotHasKey('access_type', $q);
            $this->assertNotEmpty($q['nonce']);
            $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', session('google_auth.oauth.verifier'), true)), '+/', '-_'), '='), $q['code_challenge']);
        }
    }

    public function test_email_match_requires_proof_and_password_link_keeps_id_password_and_account_data()
    {
        $user = $this->user(['super_user' => true, 'occupation' => 'Pianist']);
        $original = (array) DB::table('users')->where('id', $user->id)->first();
        unset($original['remember_token'], $original['updated_at']);
        $q = $this->start();
        $this->googleCallback($q)->assertRedirect('/auth/google/link');
        $this->assertGuest('web');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_auth_providers', 0);
        $this->get('http://localhost/auth/google/link')->assertOk()->assertSee('Your PianoLIT account already exists')->assertSee('Send me a confirmation email');
        $this->proofJson('http://localhost/auth/google/link/password', ['password' => 'wrong', 'user_id' => 999])->assertStatus(422);
        $this->assertDatabaseCount('user_auth_providers', 0);
        $this->proofPost('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertRedirect('http://localhost/');
        $this->assertAuthenticatedAs($user, 'web');
        $after = (array) DB::table('users')->where('id', $user->id)->first();
        unset($after['remember_token'], $after['updated_at']);
        $this->assertSame($original, $after);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $user->id, 'provider_user_id' => $this->subject]);
        $this->assertDatabaseHas('auth_provider_audits', ['user_id' => $user->id, 'action' => 'connected']);
        $this->assertDatabaseCount('auth_link_challenges', 1);
        $this->assertNotNull(DB::table('auth_link_challenges')->first()->consumed_at);
    }

    public function test_matching_email_with_a_different_connected_subject_never_logs_into_that_account()
    {
        $user = $this->user();
        $this->connectRecord($user, 'different-subject');
        $q = $this->start();
        $this->googleCallback($q)->assertRedirect('/auth/google/link');
        $this->assertGuest('web');
        $this->proofJson('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertStatus(422);
        $this->assertGuest('web');
        $this->assertDatabaseCount('user_auth_providers', 1);
    }

    public function test_linked_identity_signs_in_by_subject_after_google_email_changes()
    {
        $user = $this->user();
        $this->connectRecord($user);
        $this->googleEmail = 'changed@example.test';
        $q = $this->start();
        $oldSession = session()->getId();
        $this->googleCallback($q)->assertRedirect('http://localhost/');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame('existing@example.test', $user->fresh()->email);
        $this->assertDatabaseHas('user_auth_providers', ['provider_email' => $this->googleEmail]);
        $this->assertNotSame($oldSession, session()->getId());
        Http::assertSent(function ($request) { return $request->url() === 'https://oauth2.googleapis.com/token' && strlen($request['code_verifier']) >= 43 && $request['client_secret'] === 'review-secret'; });
    }

    public function test_new_google_signup_creates_one_standard_verified_account_without_a_known_password()
    {
        $q = $this->start('my.localhost');
        $this->googleCallback($q, 'my.localhost')->assertRedirect('http://my.localhost/');
        $this->assertDatabaseCount('users', 1);
        $user = User::first();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame('webapp', $user->origin);
        $this->assertSame('Google', $user->first_name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->super_user);
        $this->assertFalse((new Accounts)->hasPassword($user));
        $this->assertFalse(Hash::check('password', $user->password));
        Event::assertDispatched(Registered::class);
        $this->assertDatabaseCount('memberships', 0);
        $this->assertArrayNotHasKey('auth_providers', $user->toArray());
        $this->assertArrayNotHasKey('unusable_password_hash', $user->toArray());
        $this->googleCallback($q, 'my.localhost')->assertRedirect('/login');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_only_user_sets_password_and_legacy_ios_authentication_returns_the_same_json()
    {
        $user = $this->googleOnly();
        $this->actingAs($user, 'web');
        $this->withSession(['google_auth.recent' => ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password)]]);
        $this->get('http://my.localhost/auth/google/password')->assertOk()->assertSee('existing PianoLIT iOS app');
        $this->post('http://my.localhost/auth/google/password', ['password' => 'new-ios-password', 'password_confirmation' => 'new-ios-password'])->assertRedirect('/auth/google/accounts');
        $this->assertTrue((new Accounts)->hasPassword($user->fresh()));
        $this->assertTrue(Hash::check('new-ios-password', $user->fresh()->password));
        $expected = $user->fresh()->toArray();
        $this->postJson('http://localhost/api/users/login', ['email' => $user->email, 'password' => 'new-ios-password'])->assertOk()->assertExactJson($expected);
        $this->postJson('http://localhost/api/users/login', ['email' => $user->email, 'password' => 'incorrect'])->assertOk()->assertExactJson(['error' => ['Sorry, the password is not valid.']]);
        $this->postJson('http://localhost/api/users/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertOk()->assertExactJson(['error' => ['This email is not registered.']]);
    }

    public function test_signed_email_link_is_browser_bound_single_use_and_not_consumed_by_get()
    {
        $user = $this->user();
        $this->googleCallback($this->start())->assertRedirect('/auth/google/link');
        $this->proofPost('http://localhost/auth/google/link/email')->assertRedirect();
        $url = null;
        Notification::assertSentTo($user, ConfirmGoogleAccount::class, function ($notification) use (&$url) { $url = $notification->url; return true; });
        $this->get($url)->assertOk();
        $this->assertNull(DB::table('auth_link_challenges')->first()->consumed_at);
        $this->post($url)->assertRedirect('http://localhost/');
        $this->assertAuthenticatedAs($user, 'web');
        $this->postJson($url)->assertStatus(422);
        $this->assertDatabaseCount('user_auth_providers', 1);
        $row = DB::table('auth_link_challenges')->first();
        $this->assertNull($row->email_token_hash);
    }

    public function test_email_links_expire_and_reject_other_browsers_and_changed_account_email()
    {
        $user = $this->user();
        $this->googleCallback($this->start())->assertRedirect('/auth/google/link');
        $this->proofPost('http://localhost/auth/google/link/email');
        $url = null;
        Notification::assertSentTo($user, ConfirmGoogleAccount::class, function ($n) use (&$url) { $url = $n->url; return true; });
        $original = session('google_auth.challenge');
        $this->withSession(['google_auth.challenge' => ['id' => $original['id'], 'secret' => 'another-browser']]);
        $this->postJson($url)->assertStatus(422);
        $this->withSession(['google_auth.challenge' => $original]);
        $user->update(['email' => 'new-primary@example.test']);
        $this->postJson($url)->assertStatus(422);
        $user->update(['email' => $this->googleEmail]);
        DB::table('auth_link_challenges')->update(['expires_at' => now()->subMinute()]);
        $this->postJson($url)->assertStatus(422);
        $this->assertGuest('web');
        $this->assertDatabaseCount('user_auth_providers', 0);
    }

    public function test_settings_connect_different_email_requires_recent_proof_and_does_not_replace_email_or_password()
    {
        $user = $this->user(['email' => 'pianolit@example.test']);
        $hash = $user->password;
        $this->actingAs($user, 'web');
        $this->post('http://my.localhost/auth/google/connect')->assertRedirect('/auth/google/confirm');
        $this->get('http://my.localhost/auth/google/confirm')->assertOk();
        $response = $this->proofPost('http://my.localhost/auth/google/link/password', ['password' => 'existing-password'])->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->googleCallback($q, 'my.localhost')->assertRedirect('/auth/google/accounts');
        $this->assertSame('pianolit@example.test', $user->fresh()->email);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $user->id, 'provider_email' => $this->googleEmail]);
    }

    public function test_identity_uniqueness_and_disconnect_safeguards_preserve_data_and_session()
    {
        $user = $this->googleOnly();
        $other = $this->user(['email' => 'other@example.test']);
        $this->actingAs($user, 'web')->withSession(['google_auth.recent' => ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password)]]);
        $this->deleteJson('http://my.localhost/auth/google/disconnect')->assertStatus(422);
        $this->assertDatabaseCount('user_auth_providers', 1);
        $this->assertAuthenticatedAs($user, 'web');
        $user->update(['password' => Hash::make('reset-through-existing-broker')]);
        $this->assertTrue((new Accounts)->hasPassword($user));
        $this->withSession(['google_auth.recent' => ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password)]]);
        $this->delete('http://my.localhost/auth/google/disconnect')->assertRedirect();
        $this->assertDatabaseCount('user_auth_providers', 0);
        $this->assertDatabaseCount('users', 2);
        $this->assertAuthenticatedAs($user, 'web');
        $this->connectRecord($other);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->connectRecord($user);
    }

    public function test_forged_cancelled_wrong_host_expired_and_replayed_callbacks_do_not_create_accounts()
    {
        $q = $this->start();
        $this->googleCallback($q, 'localhost', ['state' => 'forged'])->assertRedirect('/login');
        $this->assertDatabaseCount('users', 0);
        $q = $this->start();
        $this->googleCallback($q, 'localhost', ['error' => 'access_denied'])->assertRedirect('/login');
        Http::assertNothingSent();
        $q = $this->start();
        $this->googleCallback($q, 'my.localhost')->assertRedirect('/login');
        $this->assertDatabaseCount('users', 0);
        $q = $this->start();
        $flow = session('google_auth.oauth');
        $flow['expires'] = time() - 1;
        $this->withSession(['google_auth.oauth' => $flow]);
        $this->googleCallback($q)->assertRedirect('/login');
        $this->assertDatabaseCount('users', 0);
    }

    /** @dataProvider invalidClaims */
    public function test_invalid_signed_identity_claims_are_rejected($claims)
    {
        $this->fakeGoogle($claims);
        $this->googleCallback($this->start())->assertRedirect('/login');
        $this->assertGuest('web');
        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidClaims()
    {
        return [[['iss' => 'https://attacker.test']], [['aud' => 'wrong-client']], [['azp' => 'wrong-client']], [['exp' => 1]], [['iat' => PHP_INT_MAX]], [['nbf' => PHP_INT_MAX]], [['email_verified' => false]], [['email_verified' => 'true']], [['nonce' => 'wrong-nonce']], [['sub' => '']], [['email' => 'invalid']]];
    }

    public function test_signature_algorithm_and_weak_keys_are_rejected()
    {
        $verifier = new IdTokenVerifier;
        $claims = $this->claims(['nonce' => 'review-nonce']);
        foreach (['signature', 'algorithm', 'weak'] as $kind) {
            Cache::flush();
            $key = $this->rsa;
            if ($kind === 'weak') {
                $this->rsa = openssl_pkey_new(['config' => '/dev/null', 'private_key_bits' => 1024, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
                $this->public = openssl_pkey_get_details($this->rsa)['key'];
            }
            $jwt = $this->jwt($claims, $kind === 'algorithm' ? ['alg' => 'none'] : []);
            if ($kind === 'signature') $jwt = substr($jwt, 0, -8).'forgedAB';
            try { $verifier->verify($jwt, 'review-nonce'); $this->fail('Invalid JWT accepted: '.$kind); }
            catch (\UnexpectedValueException $expected) { $this->addToAssertionCount(1); }
            $this->rsa = $key;
        }
    }

    public function test_missing_credentials_and_tables_preserve_regular_web_password_login()
    {
        $user = $this->user();
        config(['services.google_login.client_id' => null]);
        $this->get('http://localhost/login')->assertOk()->assertDontSee('Continue with Google');
        $this->get('http://my.localhost/login')->assertOk()->assertDontSee('Continue with Google');
        $this->get('http://localhost/auth/google')->assertRedirect('/login');
        $this->post('http://localhost/login', ['email' => $user->email, 'password' => 'existing-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
        $this->withExceptionHandling()->get('http://admin.localhost/auth/google')->assertStatus(404);
    }

    public function test_safe_destination_allows_only_the_two_hosts_and_rejects_open_redirects()
    {
        $request = \Illuminate\Http\Request::create('http://localhost/login');
        $config = new Configuration;
        foreach (['https://evil.test/', '//evil.test', 'http://localhost@evil.test/', 'http://localhost:8080/', "http://localhost/\nother", 'http://my.localhost/auth/google/callback'] as $url) $this->assertSame('http://localhost/', $config->destination($url, $request));
        $this->assertSame('http://my.localhost/pieces/123', $config->destination('http://my.localhost/pieces/123', $request));
        $this->assertSame('http://localhost/my-profile', $config->destination('/my-profile', $request));
    }

    private function selectSession($id)
    {
        session()->save();
        session()->flush();
        session()->setId($id);
        session()->start();
        auth()->forgetGuards();
        $this->withCookies([config('session.cookie') => $id]);
    }

    public function test_cross_host_handoff_requires_both_browser_sessions_and_an_origin_checked_single_use_post()
    {
        $user = $this->user();
        $this->connectRecord($user);
        $this->withSession(['url.intended' => 'http://my.localhost/profile']);
        $q = $this->start();
        $response = $this->googleCallback($q)->assertRedirect();
        $url = $response->headers->get('Location');
        $row = DB::table('auth_browser_handoffs')->first();
        $this->assertSame('http://my.localhost/auth/google/handoff/'.$row->id, $url);
        $this->assertNull($row->token_hash);
        $sourceId = session()->getId();
        $targetId = str_repeat('T', 40);
        $this->selectSession($targetId);
        $approve = $this->get($url)->assertRedirect()->headers->get('Location');
        $this->assertGuest('web');
        $this->selectSession($sourceId);
        $page = $this->get($approve)->assertOk()->assertHeader('Referrer-Policy', 'origin')->assertHeader('Cache-Control', 'no-store, private');
        $dom = new \DOMDocument;
        @$dom->loadHTML($page->getContent());
        $token = (new \DOMXPath($dom))->query('//input[@name="handoff_token"]')->item(0)->getAttribute('value');
        $this->assertStringNotContainsString($token, $approve);
        $this->selectSession(str_repeat('X', 40));
        $this->withHeaders(['Origin' => 'http://localhost'])->withExceptionHandling()->post($url, ['handoff_token' => $token])->assertForbidden();
        $this->assertGuest('web');
        $this->selectSession($targetId);
        $this->withHeaders(['Origin' => 'http://attacker.test'])->post($url, ['handoff_token' => $token])->assertForbidden();
        $this->withHeaders(['Origin' => 'http://localhost'])->post($url, ['handoff_token' => $token])->assertRedirect('http://my.localhost/profile');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNull(session('google_auth.recent'));
        $this->assertNotNull(DB::table('auth_browser_handoffs')->first()->consumed_at);
        $this->post($url, ['handoff_token' => $token])->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_password_reset_broker_establishes_a_local_password_without_changing_google_or_api_contracts()
    {
        $user = $this->googleOnly();
        $token = Password::broker('users')->createToken($user);
        $result = Password::broker('users')->reset(['email' => $user->email, 'token' => $token, 'password' => 'broker-ios-password', 'password_confirmation' => 'broker-ios-password'], function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
        });
        $this->assertSame(Password::PASSWORD_RESET, $result);
        $this->assertTrue((new Accounts)->hasPassword($user->fresh()));
        $this->assertFalse(Password::broker('users')->tokenExists($user, $token));
        $this->postJson('http://localhost/api/users/login', ['email' => $user->email, 'password' => 'broker-ios-password'])->assertOk()->assertExactJson($user->fresh()->toArray());
        $this->assertDatabaseCount('user_auth_providers', 1);
    }

    public function test_email_and_password_challenges_are_rate_limited_without_touching_account_data()
    {
        $user = $this->user();
        $this->googleCallback($this->start());
        for ($i = 0; $i < 3; $i++) $this->proofPost('http://localhost/auth/google/link/email')->assertRedirect();
        $this->withExceptionHandling()->post('http://localhost/auth/google/link/email')->assertStatus(429);
        Notification::assertSentToTimes($user, ConfirmGoogleAccount::class, 1);
        for ($i = 0; $i < 6; $i++) $this->proofJson('http://localhost/auth/google/link/password', ['password' => 'wrong'])->assertStatus(422);
        $this->proofJson('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertStatus(429);
        $this->assertDatabaseCount('user_auth_providers', 0);
        $this->assertGuest('web');
    }

    public function test_duplicate_database_constraints_and_link_rollback_preserve_the_existing_user()
    {
        $user = $this->user();
        $other = $this->user(['email' => 'another@example.test']);
        $this->connectRecord($user);
        $this->assertDatabaseCount('auth_provider_audits', 1);
        foreach ([['user_id' => $other->id, 'provider_user_id' => $this->subject], ['user_id' => $user->id, 'provider_user_id' => 'another-google-subject']] as $data) {
            try {
                DB::transaction(function () use ($data) {
                    DB::table('user_auth_providers')->insert($data + ['provider' => 'google', 'provider_email' => 'same@example.test', 'created_at' => now(), 'updated_at' => now()]);
                });
                $this->fail('Duplicate provider link accepted.');
            } catch (\Illuminate\Database\QueryException $expected) { $this->addToAssertionCount(1); }
        }
        try {
            DB::transaction(function () use ($other) {
                (new Accounts)->link($other, ['sub' => 'temporary-subject', 'email' => 'temporary@example.test']);
                throw new \RuntimeException('Simulated write failure.');
            });
        } catch (\RuntimeException $expected) {}
        $this->assertDatabaseCount('user_auth_providers', 1);
        $this->assertDatabaseCount('auth_provider_audits', 1);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_linking_keeps_membership_favorites_and_folders_and_both_login_methods_work()
    {
        $user = $this->user(['email_verified_at' => now()]);
        $piece = Model::withoutEvents(function () { return create(\App\Piece::class); });
        $user->favorites()->attach($piece->id);
        DB::table('favorite_folders')->insert(['user_id' => $user->id, 'name' => 'My repertoire', 'created_at' => now(), 'updated_at' => now()]);
        $stripeId = DB::table('stripe_memberships')->insertGetId(['stripe_id' => 'cus_review_google', 'plan' => 'monthly', 'status' => 'active', 'renews_at' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('memberships')->insert(['user_id' => $user->id, 'source_id' => $stripeId, 'source_type' => \App\Billing\Sources\Stripe::class, 'created_at' => now(), 'updated_at' => now()]);
        $before = [];
        foreach (['memberships', 'stripe_memberships', 'favorites', 'favorite_folders'] as $table) $before[$table] = DB::table($table)->get()->toJson();
        $this->googleCallback($this->start());
        $this->proofPost('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertRedirect();
        $this->assertTrue($user->fresh()->hasActiveSubscription());
        foreach ($before as $table => $rows) $this->assertSame($rows, DB::table($table)->get()->toJson());
        auth('web')->logout();
        $this->post('http://localhost/login', ['email' => $user->email, 'password' => 'existing-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull(session('google_auth.recent'));
        auth('web')->logout();
        $this->googleCallback($this->start())->assertRedirect('http://localhost/');
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_real_registered_listener_keeps_newsletter_signup_and_web_origin_without_verification_mail()
    {
        Event::swap($this->app->make('events'));
        // Restore the framework dispatcher, retaining the project's listeners.
        $dispatcher = new \Illuminate\Events\Dispatcher($this->app);
        $dispatcher->listen(Registered::class, \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class);
        $dispatcher->listen(Registered::class, \App\Listeners\SubscribeUser::class);
        $dispatcher->listen(Login::class, \App\Listeners\Users\RecordRecentPasswordLogin::class);
        Event::swap($dispatcher);
        $this->googleCallback($this->start())->assertRedirect('http://localhost/');
        $user = User::first();
        $this->assertSame('web', $user->origin);
        $this->assertDatabaseHas('subscriptions', ['email' => $user->email]);
        Notification::assertNotSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_google_only_reauthentication_and_last_method_controls_use_the_linked_subject()
    {
        $user = $this->googleOnly();
        $this->actingAs($user, 'web');
        $this->get('http://my.localhost/auth/google/password')->assertRedirect('/auth/google/confirm');
        $this->get('http://my.localhost/auth/google/confirm')->assertOk()->assertSee('Confirm with Google');
        $response = $this->post('http://my.localhost/auth/google/reauthenticate')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->googleCallback($q, 'my.localhost')->assertRedirect('/auth/google/password');
        $this->get('http://my.localhost/auth/google/password')->assertOk();
        $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Set a Password')->assertSee('Google — Connected');
        $this->withSession(['google_auth.recent' => null]);
        $this->fakeGoogle(['sub' => 'another-google-identity']);
        $response = $this->post('http://my.localhost/auth/google/reauthenticate')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->googleCallback($q, 'my.localhost')->assertRedirect('/auth/google/accounts');
        $this->assertNull(session('google_auth.recent'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_sensitive_forms_have_csrf_and_only_the_bound_handoff_post_has_a_scoped_exception()
    {
        $user = $this->user();
        $this->actingAs($user, 'web');
        $this->app->instance(\App\Http\Middleware\VerifyCsrfToken::class, new class($this->app, $this->app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
        });
        $this->withExceptionHandling()->post('http://localhost/auth/google/connect')->assertStatus(419);
        $this->delete('http://localhost/auth/google/disconnect')->assertStatus(419);
        $this->post('http://localhost/auth/google/password', ['password' => 'new-password'])->assertStatus(419);
        $this->proofPost('http://localhost/auth/google/link/email')->assertStatus(419);
        $this->post('http://localhost/auth/google/handoff/unknown', ['handoff_token' => 'fake'])->assertForbidden();
        $this->assertDatabaseCount('user_auth_providers', 0);
    }

    public function test_legacy_ios_registration_routes_guards_and_payload_are_unchanged()
    {
        $response = $this->postJson('http://localhost/api/users', ['first_name' => 'Mobile', 'last_name' => 'Pianist', 'email' => 'mobile@example.test', 'password' => 'mobile-password', 'password_confirmation' => 'mobile-password', 'origin' => 'ios'])->assertCreated();
        $user = User::where('email', 'mobile@example.test')->firstOrFail();
        $this->assertSame('ios', $user->origin);
        $response->assertExactJson(\Illuminate\Support\Arr::only($user->fresh()->toArray(), ['first_name', 'last_name', 'email', 'locale', 'origin', 'updated_at', 'created_at', 'id', 'full_name', 'last_active_at', 'logs_count']));
        $this->postJson('http://localhost/api/users/login', ['email' => $user->email, 'password' => 'mobile-password'])->assertOk()->assertExactJson($user->fresh()->toArray());
        $this->assertDatabaseCount('user_auth_providers', 0);
        $this->assertSame(['driver' => 'token', 'provider' => 'users', 'hash' => false], config('auth.guards.api'));
        $route = app('router')->getRoutes()->getByName('api.users.login');
        $this->assertSame('api/users/login', $route->uri());
        $this->assertSame('App\\Http\\Controllers\\Auth\\Api\\LoginController@login', $route->getActionName());
        $this->assertContains('api', $route->middleware());
        $this->assertNotContains('web', $route->middleware());
    }

    public function test_missing_migration_and_oauth_configuration_leave_password_authentication_available()
    {
        Schema::drop('user_auth_providers');
        $this->get('http://localhost/login')->assertOk()->assertDontSee('Continue with Google');
        $this->get('http://my.localhost/register')->assertOk()->assertDontSee('Continue with Google');
        $this->get('http://localhost/auth/google')->assertRedirect('/login');
        $user = $this->user();
        $this->post('http://localhost/login', ['email' => $user->email, 'password' => 'existing-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_enabled_authentication_and_account_views_render_with_existing_fields_and_safe_previews()
    {
        $directory = getenv('PIANOLIT_GOOGLE_AUTH_PREVIEW');
        if ($directory) { if (!is_dir($directory)) mkdir($directory, 0700, true); }
        $save = function ($name, $response) use ($directory) {
            if (!$directory) return;
            $html = $response->getContent();
            preg_match('#<script>\s*\(function \(\) \{[\s\S]*?</script>#', $html, $buttonScript);
            $html = preg_replace('#<script\b[^>]*>[\s\S]*?</script>#i', '', $html);
            $html = str_replace(['http://my.localhost/', 'http://localhost/'], '/', $html);
            $html = str_replace('</body>', ($buttonScript[0] ?? '').'</body>', $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        };
        foreach (['localhost' => 'web', 'my.localhost' => 'my'] as $host => $suffix) {
            foreach (['login', 'register'] as $page) {
                $response = $this->get('http://'.$host.'/'.$page)->assertOk()->assertSee('Continue with Google')->assertSee('name="email"', false)->assertSee('name="password"', false)->assertSee('name="_token"', false);
                $save($page.'-'.$suffix, $response);
            }
        }
        $user = $this->user(['first_name' => 'Review', 'last_name' => 'Pianist']);
        $this->actingAs($user, 'web');
        $save('accounts-unconnected', $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Connect Google'));
        $this->connectRecord($user);
        $save('accounts-connected', $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Disconnect'));
        DB::table('user_auth_providers')->update(['unusable_password_hash' => $user->password]);
        $save('accounts-google-only', $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Set a Password'));
        $this->post('http://my.localhost/auth/google/connect')->assertRedirect('/auth/google/confirm');
        $save('confirm-google-only', $this->get('http://my.localhost/auth/google/confirm')->assertOk()->assertSee('Confirm with Google'));
        $this->withSession(['google_auth.recent' => ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password)]]);
        $save('set-password', $this->get('http://my.localhost/auth/google/password')->assertOk());
    }

    public function test_expired_signed_email_and_cancelled_settings_flow_have_safe_messages_and_keep_login()
    {
        $user = $this->user();
        $this->googleCallback($this->start());
        $this->proofPost('http://localhost/auth/google/link/email');
        $row = DB::table('auth_link_challenges')->first();
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('google.verify', now()->subMinute(), ['challenge' => $row->id, 'token' => str_repeat('a', 64)]);
        $this->get($url)->assertForbidden()->assertSee('confirmation link is invalid or expired')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->actingAs($user, 'web')->withSession(['google_auth.recent' => ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password)]]);
        $response = $this->post('http://localhost/auth/google/connect')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->googleCallback($q, 'localhost', ['error' => 'access_denied'])->assertRedirect('/auth/google/accounts');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseCount('user_auth_providers', 0);
    }

    public function test_existing_migration_can_roll_back_and_reapply_without_changing_user_schema_or_data()
    {
        $user = $this->user();
        $before = DB::table('users')->where('id', $user->id)->first();
        $columns = Schema::getColumnListing('users');
        $migration = new \CreateGoogleAuthenticationTables;
        $migration->down();
        $this->assertFalse(Schema::hasTable('user_auth_providers'));
        $this->assertSame($columns, Schema::getColumnListing('users'));
        $this->assertEquals($before, DB::table('users')->where('id', $user->id)->first());
        $migration->up();
        $this->assertTrue(Schema::hasTable('user_auth_providers'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_old_web_profile_cannot_bypass_recent_proof_by_replacing_email_or_password()
    {
        $user = $this->user();
        $before = $user->password;
        $this->actingAs($user, 'web');
        $this->patch('http://localhost/my-profile/'.$user->id, ['first_name' => 'Updated', 'last_name' => 'Pianist', 'email' => 'attacker@example.test', 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertRedirect('/auth/google/confirm');
        $this->assertSame($this->googleEmail, $user->fresh()->email);
        $this->assertSame($before, $user->fresh()->password);
        $this->get('http://localhost/auth/google/confirm')->assertOk();
        $this->proofPost('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertRedirect('/my-profile');
        $this->patch('http://localhost/my-profile/'.$user->id, ['first_name' => 'Updated', 'last_name' => 'Pianist', 'email' => $user->email, 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertRedirect();
        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertTrue((new Accounts)->recent(request(), $user->fresh()));
    }

    public function test_google_only_profile_email_and_password_changes_require_owner_confirmation()
    {
        $user = $this->googleOnly();
        $this->actingAs($user, 'web');
        $this->patch('http://my.localhost/my-profile/'.$user->id, ['first_name' => 'Updated', 'last_name' => 'Pianist', 'email' => 'replacement@example.test'])->assertRedirect('/auth/google/confirm');
        $this->assertSame($this->googleEmail, $user->fresh()->email);
        $this->patch('http://my.localhost/my-profile/'.$user->id, ['first_name' => 'Updated', 'last_name' => 'Pianist', 'email' => $user->email, 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertRedirect('/auth/google/password');
        $this->assertFalse((new Accounts)->hasPassword($user->fresh()));
        $this->get('http://my.localhost/auth/google/password')->assertRedirect('/auth/google/confirm');
    }

    public function test_an_older_linking_tab_cannot_verify_or_email_a_newer_google_attempt()
    {
        $first = $this->user();
        $second = $this->user(['email' => 'second@example.test']);
        $this->googleCallback($this->start())->assertRedirect('/auth/google/link');
        $oldId = session('google_auth.challenge.id');
        $this->get('http://localhost/auth/google/link')->assertSee('name="challenge_id"', false)->assertSee($oldId);
        $this->googleEmail = $second->email;
        $this->subject = 'second-google-subject';
        $this->googleCallback($this->start())->assertRedirect('/auth/google/link');
        $this->assertNotSame($oldId, session('google_auth.challenge.id'));
        // Both accounts deliberately share a password; the stale form still
        // must not authorize the newer account/identity in the same session.
        $this->postJson('http://localhost/auth/google/link/password', ['challenge_id' => $oldId, 'password' => 'existing-password'])->assertStatus(422);
        $this->postJson('http://localhost/auth/google/link/email', ['challenge_id' => $oldId])->assertStatus(422);
        $this->postJson('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertStatus(422);
        $this->assertGuest('web');
        $this->assertDatabaseCount('user_auth_providers', 0);
        Notification::assertNothingSent();
        $this->proofPost('http://localhost/auth/google/link/password', ['password' => 'existing-password'])->assertRedirect('http://localhost/');
        $this->assertAuthenticatedAs($second, 'web');
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $second->id, 'provider_user_id' => 'second-google-subject']);
        $this->assertDatabaseMissing('user_auth_providers', ['user_id' => $first->id]);
    }
}
