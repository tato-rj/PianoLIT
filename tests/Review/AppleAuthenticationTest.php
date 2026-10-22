<?php

namespace Tests\Review;

use App\User;
use App\Auth\Apple\{ClientSecret, IdTokenVerifier, Jwt, Provider};
use App\Auth\Google\{Accounts, Challenges};
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Event, Hash, Http, Notification, Schema};
use Illuminate\Validation\ValidationException;

class AppleAuthenticationTest extends ReviewTestCase
{
    private $rsa, $ec, $keyPath, $randomPath, $previousRandom, $nonce;
    private $subject = '000001.review-apple-subject', $email = 'apple@example.test';

    public function setUp(): void
    {
        parent::setUp();
        $this->previousRandom = getenv('RANDFILE');
        $this->randomPath = tempnam(sys_get_temp_dir(), 'pianolit-apple-random-'); putenv('RANDFILE='.$this->randomPath);
        $this->rsa = openssl_pkey_new(['config' => '/dev/null', 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->ec = openssl_pkey_new(['config' => '/dev/null', 'private_key_type' => OPENSSL_KEYTYPE_EC, 'private_key_bits' => 2048, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($this->ec, $pem, null, ['config' => '/dev/null']);
        $this->keyPath = tempnam(sys_get_temp_dir(), 'pianolit-apple-key-'); file_put_contents($this->keyPath, $pem); chmod($this->keyPath, 0600);
        config(['app.name' => 'PianoLIT', 'services.apple_login.client_id' => 'com.pianolit.web', 'services.apple_login.team_id' => 'TEAM123456',
            'services.apple_login.key_id' => 'KEY1234567', 'services.apple_login.private_key_path' => $this->keyPath,
            'services.google_login.client_id' => 'google-review', 'services.google_login.client_secret' => 'google-review-secret',
            'activity.enabled' => false, 'attribution.enabled' => false, 'session.same_site' => 'lax']);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebLog::class, \App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Notification::fake(); Event::fake([Registered::class]); $this->fakeApple();
    }

    public function tearDown(): void
    {
        // Disposable review data only; permit the intentionally guarded rollback.
        if (Schema::hasTable('auth_link_challenges')) DB::table('auth_link_challenges')->delete();
        if (Schema::hasTable('user_auth_providers')) DB::table('user_auth_providers')->delete();
        foreach ([$this->keyPath, $this->randomPath] as $path) if ($path && is_file($path)) unlink($path);
        putenv($this->previousRandom === false ? 'RANDFILE' : 'RANDFILE='.$this->previousRandom);
        parent::tearDown();
    }

    private function user(array $values = [])
    {
        return Model::withoutEvents(function () use ($values) { return create(User::class, $values + ['email' => $this->email, 'password' => Hash::make('existing-password')]); });
    }

    public function withSession(array $data)
    {
        parent::withSession($data);
        // These tests carry a real session ID cookie between requests. Persist
        // injected state before StartSession reloads that ID from the handler.
        $this->app['session']->save();
        return $this;
    }

    private function claims(array $values = [])
    {
        return array_merge(['iss' => 'https://appleid.apple.com', 'aud' => 'com.pianolit.web', 'sub' => $this->subject,
            'email' => $this->email, 'email_verified' => 'true', 'iat' => time() - 5, 'exp' => time() + 300, 'nonce' => $this->nonce], $values);
    }

    private function jwt(array $claims, array $header = [])
    {
        $body = Jwt::encode($header + ['alg' => 'RS256', 'kid' => 'review-apple']).'.'.Jwt::encode($claims);
        openssl_sign($body, $signature, $this->rsa, OPENSSL_ALGO_SHA256);
        return $body.'.'.Jwt::encode($signature);
    }

    private function fakeApple(array $claims = [], array $header = [])
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use ($claims, $header) {
            if ($request->url() === 'https://appleid.apple.com/auth/keys') {
                $rsa = openssl_pkey_get_details($this->rsa)['rsa'];
                return Http::response(['keys' => [['kid' => 'review-apple', 'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'n' => Jwt::encode($rsa['n']), 'e' => Jwt::encode($rsa['e'])]]]);
            }
            if ($request->url() === 'https://appleid.apple.com/auth/token') return Http::response(['id_token' => $this->jwt($this->claims($claims), $header), 'access_token' => 'discarded', 'refresh_token' => 'discarded']);
            throw new \RuntimeException('Unexpected external request in Apple tests.');
        });
    }

    private function start($host = 'localhost', $connect = false)
    {
        $response = $connect ? $this->post('http://'.$host.'/auth/apple/connect')->assertRedirect() : $this->get('http://'.$host.'/auth/apple')->assertRedirect();
        $this->assertStringStartsWith('https://appleid.apple.com/auth/authorize?', $response->headers->get('Location'));
        $this->withCookies([config('session.cookie') => session()->getId()]);
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query); return $query;
    }

    private function receive(array $query, $host = 'localhost', array $values = [])
    {
        $this->nonce = $query['nonce'];
        $cookies = $this->defaultCookies; $this->defaultCookies = [];
        $response = $this->post('http://'.$host.'/auth/apple/callback', $values + ['state' => $query['state'], 'code' => 'apple-review-code',
            'user' => json_encode(['name' => ['firstName' => 'Apple', 'lastName' => 'Pianist'], 'email' => 'untrusted@example.test'])])->assertOk();
        $this->defaultCookies = $cookies;
        $this->assertSame([], $response->headers->getCookies(), 'The cross-site POST must never replace the session cookie.');
        preg_match('#/auth/apple/complete/([a-f0-9-]{36})#', $response->getContent(), $match);
        $this->assertNotEmpty($match[1] ?? null); return $match[1];
    }

    private function appleCallback(array $query, $host = 'localhost', array $values = [])
    {
        $response = $this->get('http://'.$host.'/auth/apple/complete/'.$this->receive($query, $host, $values));
        $this->withCookies([config('session.cookie') => session()->getId()]); return $response;
    }

    private function link(User $user, $provider = 'apple', $hash = null, $email = null, $subject = null)
    {
        DB::transaction(function () use ($user, $provider, $hash, $email, $subject) {
            (new Accounts)->link($user, ['sub' => $subject ?: ($provider === 'apple' ? $this->subject : 'google-review-subject'), 'email' => $email ?: $this->email], $hash, $provider);
        });
    }

    private function prove($password = 'existing-password', $host = 'localhost')
    {
        $response = $this->post('http://'.$host.'/auth/apple/link/password', ['password' => $password, 'challenge_id' => session('apple_auth.challenge.id')]);
        $this->withCookies([config('session.cookie') => session()->getId()]); return $response;
    }

    private function recent(User $user)
    {
        $this->actingAs($user, 'web');
        $this->withSession(['google_auth.recent' => ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password), 'provider' => 'apple']]);
    }

    public function test_authorization_uses_both_canonical_callbacks_and_no_credentials_in_the_browser()
    {
        foreach (['localhost', 'my.localhost'] as $host) {
            $q = $this->start($host);
            $this->assertSame('http://'.$host.'/auth/apple/callback', $q['redirect_uri']);
            $this->assertSame('code', $q['response_type']); $this->assertSame('form_post', $q['response_mode']);
            $this->assertSame('name email', $q['scope']); $this->assertNotEmpty($q['nonce']);
            $this->assertArrayNotHasKey('client_secret', $q); $this->assertArrayNotHasKey('private_key', $q);
        }
    }

    public function test_new_registration_waits_for_explicit_choice_and_creates_an_ordinary_remembered_user()
    {
        $this->appleCallback($this->start('my.localhost'), 'my.localhost')->assertRedirect('/auth/apple/choose');
        $this->assertDatabaseCount('users', 0); Event::assertNotDispatched(Registered::class);
        $this->get('http://my.localhost/auth/apple/choose')->assertOk()->assertSee('I already have a PianoLIT account');
        $response = $this->post('http://my.localhost/auth/apple/register', ['choice_id' => session('apple_auth.choice.id')])->assertRedirect('http://my.localhost/');
        $user = User::firstOrFail(); $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame($this->email, $user->email); $this->assertSame('Apple', $user->first_name); $this->assertSame('Pianist', $user->last_name);
        $this->assertSame('webapp', $user->origin); $this->assertNotNull($user->email_verified_at); $this->assertFalse((bool) $user->super_user);
        $this->assertFalse((new Accounts)->hasPassword($user)); $this->assertFalse($user->hasActiveSubscription());
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $user->id, 'provider' => 'apple', 'provider_email' => $this->email]);
        $remember = array_filter($response->headers->getCookies(), function ($cookie) { return strpos($cookie->getName(), 'remember_web_') === 0; });
        $this->assertCount(1, $remember); $this->assertGreaterThan(time() + 86400 * 365, reset($remember)->getExpiresTime());
        Event::assertDispatched(Registered::class); $this->assertSame('apple', session('google_auth.recent.provider'));
    }

    public function test_matching_email_requires_proof_and_keeps_the_complete_existing_account()
    {
        $user = $this->user(['super_user' => true]); $before = (array) DB::table('users')->where('id', $user->id)->first();
        $this->appleCallback($this->start())->assertRedirect('/auth/apple/link'); $this->assertGuest('web');
        $this->assertDatabaseCount('users', 1); $this->assertDatabaseCount('user_auth_providers', 0);
        $this->get('http://localhost/auth/apple/link')->assertOk()->assertSee('connect Apple')->assertSee('Connect Apple');
        $this->prove('wrong')->assertRedirect(); $this->assertGuest('web');
        $this->prove()->assertRedirect('http://localhost/'); $this->assertAuthenticatedAs($user, 'web');
        $after = (array) DB::table('users')->where('id', $user->id)->first(); foreach (['remember_token', 'updated_at'] as $key) { unset($before[$key], $after[$key]); }
        $this->assertSame($before, $after); $this->assertDatabaseHas('auth_link_challenges', ['provider' => 'apple', 'user_id' => $user->id]);
        $this->assertSame(['apple'], DB::table('auth_provider_audits')->distinct()->pluck('provider')->all());
        $this->assertNotNull(DB::table('auth_link_challenges')->first()->consumed_at);
    }

    public function test_hide_my_email_links_a_different_existing_email_without_creating_a_duplicate()
    {
        $user = $this->user(['email' => 'real@example.test']); $this->fakeApple(['email' => 'relay@privaterelay.appleid.com']);
        $this->appleCallback($this->start())->assertRedirect('/auth/apple/choose'); $id = session('apple_auth.choice.id');
        $this->post('http://localhost/auth/apple/existing', ['choice_id' => $id, 'email' => $user->email])->assertRedirect('/auth/apple/link');
        $this->prove()->assertRedirect(); $this->assertAuthenticatedAs($user, 'web'); $this->assertDatabaseCount('users', 1);
        $this->assertSame('real@example.test', $user->fresh()->email);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $user->id, 'provider' => 'apple', 'provider_email' => 'relay@privaterelay.appleid.com']);
    }

    public function test_linked_subject_logs_in_without_email_or_name_and_keeps_original_metadata()
    {
        $user = $this->user(['first_name' => 'Original']); $this->link($user, 'apple', null, 'saved@privaterelay.appleid.com');
        $this->fakeApple(['email' => null, 'email_verified' => null]);
        $this->appleCallback($this->start(), 'localhost', ['user' => null])->assertRedirect('http://localhost/');
        $this->assertAuthenticatedAs($user, 'web'); $this->assertSame('Original', $user->fresh()->first_name);
        $this->assertDatabaseHas('user_auth_providers', ['provider_email' => 'saved@privaterelay.appleid.com']);
    }

    public function test_new_subject_without_email_requires_a_browser_bound_single_use_email_confirmation()
    {
        $this->fakeApple(['email' => null, 'email_verified' => null]); $this->appleCallback($this->start())->assertRedirect('/auth/apple/choose');
        $choice = session('apple_auth.choice'); $this->get('http://localhost/auth/apple/choose')->assertOk()->assertSee('Verify my email');
        $this->post('http://localhost/auth/apple/register', ['choice_id' => $choice['id']])->assertRedirect(); $this->assertDatabaseCount('users', 0);
        $this->post('http://localhost/auth/apple/signup/email', ['choice_id' => $choice['id'], 'email' => 'verified@example.test'])->assertRedirect();
        $url = null;
        Notification::assertSentOnDemand(ConfirmGoogleAccount::class, function ($notification) use (&$url) { $url = $notification->url; return $notification->provider === 'apple'; });
        $this->get($url)->assertOk(); $this->assertDatabaseCount('users', 0);
        $pending = session('apple_auth.choice'); $this->withSession(['apple_auth.choice' => null]); $this->post($url)->assertRedirect(); $this->assertDatabaseCount('users', 0);
        $this->withSession(['apple_auth.choice' => $pending]); $this->post($url)->assertRedirect('http://localhost/');
        $user = User::firstOrFail(); $this->assertSame('verified@example.test', $user->email);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $user->id, 'provider' => 'apple', 'provider_email' => null]);
        auth('web')->logout(); $this->withSession(['apple_auth.choice' => $pending]); $this->post($url)->assertRedirect(); $this->assertDatabaseCount('users', 1);
    }

    public function test_email_linking_reuses_signed_owner_proof_and_get_does_not_consume_it()
    {
        $user = $this->user(); $this->appleCallback($this->start());
        $this->post('http://localhost/auth/apple/link/email', ['challenge_id' => session('apple_auth.challenge.id')])->assertRedirect();
        $url = null; Notification::assertSentTo($user, ConfirmGoogleAccount::class, function ($notification) use (&$url) { $url = $notification->url; return $notification->provider === 'apple'; });
        $this->get($url)->assertOk()->assertSee('this Apple account'); $this->assertGuest('web'); $this->assertDatabaseCount('user_auth_providers', 0);
        $this->post($url)->assertRedirect(); $this->assertAuthenticatedAs($user, 'web'); $this->assertDatabaseCount('user_auth_providers', 1);
        $this->post($url)->assertRedirect(); $this->assertDatabaseCount('user_auth_providers', 1);
    }

    public function test_google_and_apple_can_coexist_and_either_can_be_disconnected_without_losing_password()
    {
        $user = $this->user(); $password = $user->password; $this->link($user, 'google'); $this->recent($user);
        $this->appleCallback($this->start('my.localhost', true), 'my.localhost')->assertRedirect('/auth/google/accounts');
        $this->assertDatabaseCount('user_auth_providers', 2); $this->assertSame($password, $user->fresh()->password);
        $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Apple')->assertSee('Google');
        $this->delete('http://my.localhost/auth/apple/disconnect')->assertRedirect();
        $this->assertDatabaseHas('user_auth_providers', ['provider' => 'google', 'user_id' => $user->id]); $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseHas('auth_provider_audits', ['provider' => 'apple', 'action' => 'disconnected']);
    }

    public function test_two_provider_only_accounts_keep_the_unusable_password_marker_and_cannot_remove_the_last_method()
    {
        $user = $this->user(['password' => Hash::make(bin2hex(random_bytes(32)))]); $this->link($user, 'google', $user->password);
        $this->recent($user); $this->appleCallback($this->start('localhost', true))->assertRedirect('/auth/google/accounts');
        $this->assertFalse((new Accounts)->hasPassword($user));
        $this->delete('http://localhost/auth/google/disconnect')->assertRedirect();
        $this->assertDatabaseCount('user_auth_providers', 1); $this->assertFalse((new Accounts)->hasPassword($user));
        $this->deleteJson('http://localhost/auth/apple/disconnect')->assertStatus(422); $this->assertDatabaseCount('user_auth_providers', 1);
        $this->get('http://localhost/auth/google/accounts')->assertOk()->assertSee('Set a Password');
    }

    public function test_a_temporarily_disabled_other_provider_does_not_count_as_a_working_last_method()
    {
        $user = $this->user(['password' => Hash::make(bin2hex(random_bytes(32)))]); $this->link($user, 'apple', $user->password); $this->link($user, 'google');
        $this->recent($user); config(['services.google_login.client_id' => null]);
        $this->deleteJson('http://localhost/auth/apple/disconnect')->assertStatus(422); $this->assertDatabaseCount('user_auth_providers', 2);
    }

    public function test_apple_only_password_setup_keeps_identity_and_supports_the_unmodified_ios_login_contract()
    {
        $user = $this->user(['password' => Hash::make(bin2hex(random_bytes(32)))]); $this->link($user, 'apple', $user->password); $this->recent($user);
        $this->post('http://localhost/auth/google/password', ['password' => 'ios-new-password', 'password_confirmation' => 'ios-new-password'])->assertRedirect('/auth/google/accounts');
        $this->assertTrue((new Accounts)->hasPassword($user->fresh())); $this->assertDatabaseHas('user_auth_providers', ['provider' => 'apple', 'unusable_password_hash' => null]);
        auth('web')->logout(); $this->postJson('http://localhost/api/users/login', ['email' => $user->email, 'password' => 'ios-new-password'])->assertOk()->assertExactJson($user->fresh()->toArray());
        $this->assertDatabaseHas('auth_provider_audits', ['provider' => 'apple', 'action' => 'password_set']);
    }

    public function test_a_subject_connected_to_another_account_cannot_be_reassigned()
    {
        $owner = $this->user(); $this->link($owner); $other = $this->user(['email' => 'other@example.test']); $this->recent($other);
        $this->appleCallback($this->start('localhost', true))->assertRedirect('/auth/google/accounts');
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $owner->id, 'provider' => 'apple']);
        $this->assertDatabaseCount('user_auth_providers', 1); $this->assertAuthenticatedAs($other, 'web');
    }

    public function test_provider_challenges_cannot_be_interchanged_and_null_legacy_google_challenges_still_work()
    {
        $user = $this->user(); $this->get('http://localhost/login');
        $google = (new Challenges)->create(request(), $user, 'link', ['sub' => 'google-subject', 'email' => $user->email]);
        DB::table('auth_link_challenges')->where('id', $google->id)->update(['provider' => null]);
        $legacy = session('google_auth.challenge');
        $this->assertSame($google->id, (new Challenges)->current(request())->id);
        $this->withSession(['apple_auth.challenge' => session('google_auth.challenge')]);
        $this->postJson('http://localhost/auth/apple/link/password', ['password' => 'existing-password', 'challenge_id' => $google->id])->assertStatus(422);
        $this->assertNull(DB::table('auth_link_challenges')->where('id', $google->id)->value('consumed_at'));
        $apple = (new Challenges)->create(request(), $user, 'link', ['sub' => $this->subject, 'email' => $user->email], '/', 'apple');
        $this->withSession(['google_auth.challenge' => session('apple_auth.challenge')]);
        $this->postJson('http://localhost/auth/google/link/password', ['password' => 'existing-password', 'challenge_id' => $apple->id, 'provider' => 'apple'])->assertStatus(422);
        $this->assertDatabaseCount('user_auth_providers', 0); $this->assertNull(DB::table('auth_link_challenges')->where('id', $apple->id)->value('consumed_at'));
        $this->withSession(['google_auth.challenge' => $legacy]);
        $this->post('http://localhost/auth/google/link/password', ['password' => 'existing-password', 'challenge_id' => $google->id])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web'); $this->assertDatabaseHas('user_auth_providers', ['provider' => 'google', 'provider_user_id' => 'google-subject']);
        $this->assertDatabaseMissing('user_auth_providers', ['provider' => 'apple']);
    }

    public function test_expired_mismatched_and_replayed_state_is_rejected_without_authentication()
    {
        $q = $this->start(); $this->post('http://localhost/auth/apple/callback', ['state' => str_repeat('a', 64), 'code' => 'wrong'])->assertStatus(400);
        $id = $this->receive($q);
        $this->post('http://localhost/auth/apple/callback', ['state' => $q['state'], 'code' => 'repeat'])->assertStatus(400);
        $this->get('http://localhost/auth/apple/complete/'.$id)->assertRedirect('/auth/apple/choose');
        $this->get('http://localhost/auth/apple/complete/'.$id)->assertRedirect('/login'); $this->assertGuest('web');
        $q = $this->start(); $id = $this->receive($q); $flow = session('apple_auth.oauth'); $flow['expires'] = time() - 1;
        $this->withSession(['apple_auth.oauth' => $flow]); $this->get('http://localhost/auth/apple/complete/'.$id)->assertRedirect('/login'); $this->assertDatabaseCount('users', 0);
    }

    public function test_posted_receipt_is_not_a_login_credential_without_the_original_session()
    {
        $q = $this->start(); $id = $this->receive($q); $flow = session('apple_auth.oauth');
        $store = new \Illuminate\Session\Store('different-browser', new \Illuminate\Session\ArraySessionHandler(120));
        $store->start(); $store->put('apple_auth.oauth', $flow);
        $request = \Illuminate\Http\Request::create('http://localhost/auth/apple/complete/'.$id); $request->setLaravelSession($store);
        try { (new Provider)->consume($request, $id); $this->fail('An unrelated browser accepted a receipt.'); } catch (\UnexpectedValueException $e) {}
        $this->assertNotNull(Cache::get('apple-login.return.'.$id));
        $this->get('http://localhost/auth/apple/complete/'.$id)->assertRedirect('/auth/apple/choose');
    }

    public function test_callback_accepts_only_the_original_host_and_never_consumes_state_for_a_foreign_origin()
    {
        $q = $this->start();
        $this->post('http://my.localhost/auth/apple/callback', ['state' => $q['state'], 'code' => 'wrong-host'])->assertStatus(400);
        $this->withHeader('Origin', 'https://evil.example')->post('http://localhost/auth/apple/callback', ['state' => $q['state'], 'code' => 'wrong-origin'])->assertStatus(400);
        $this->withHeader('Origin', 'https://appleid.apple.com'); $this->appleCallback($q)->assertRedirect('/auth/apple/choose');
    }

    public function test_cancellation_and_a_changed_signed_in_account_do_not_create_or_link_accounts()
    {
        $this->appleCallback($this->start(), 'localhost', ['error' => 'access_denied', 'code' => null])->assertRedirect('/login'); $this->assertDatabaseCount('users', 0);
        $q = $this->start(); $id = $this->receive($q); $this->actingAs($this->user(), 'web');
        $this->get('http://localhost/auth/apple/complete/'.$id)->assertRedirect('/login'); $this->assertDatabaseCount('user_auth_providers', 0);
    }

    public function test_provider_errors_and_registration_validation_keep_safe_retry_states()
    {
        $this->appleCallback($this->start(), 'localhost', ['error' => 'invalid_request', 'code' => null])->assertRedirect('/login');
        $this->assertDatabaseHas('auth_provider_audits', ['provider' => 'apple', 'action' => 'authentication_failed']);
        $this->appleCallback($this->start())->assertRedirect('/auth/apple/choose'); $choice = session('apple_auth.choice');
        $this->post('http://localhost/auth/apple/existing', ['choice_id' => $choice['id'], 'email' => 'bad-email'])->assertRedirect('/auth/apple/choose');
        $this->post('http://localhost/auth/apple/existing', ['choice_id' => $choice['id'], 'email' => 'missing@example.test'])->assertRedirect('/auth/apple/choose');
        $this->assertSame($choice['id'], session('apple_auth.choice.id')); $this->assertDatabaseCount('users', 0);
        $choice['expires'] = time() - 1; $this->withSession(['apple_auth.choice' => $choice]);
        $this->get('http://localhost/auth/apple/choose')->assertRedirect('/login');
    }

    /** @dataProvider invalidClaims */
    public function test_forged_or_invalid_token_claims_do_not_authenticate($claims)
    {
        $this->fakeApple($claims); $this->appleCallback($this->start())->assertRedirect('/login'); $this->assertGuest('web');
        $this->assertDatabaseCount('users', 0); $this->assertDatabaseHas('auth_provider_audits', ['provider' => 'apple', 'action' => 'authentication_failed']);
    }

    public function invalidClaims()
    {
        return [[['iss' => 'https://evil.example']], [['aud' => 'google-review']], [['nonce' => 'wrong']], [['nonce' => null]],
            [['exp' => 1]], [['iat' => PHP_INT_MAX]], [['nbf' => PHP_INT_MAX]], [['email_verified' => false]], [['email_verified' => 'false']],
            [['email_verified' => 1]], [['email' => 'invalid']], [['sub' => '']], [['sub' => ['array']]], [['aud' => ['com.pianolit.web']]]];
    }

    public function test_signature_and_algorithm_are_verified_with_strong_apple_keys_only()
    {
        $this->fakeApple([], ['alg' => 'HS256']); $this->appleCallback($this->start())->assertRedirect('/login');
        $this->fakeApple([], ['kid' => 'unknown']); $this->appleCallback($this->start())->assertRedirect('/login');
        Cache::forget('apple-login.keys'); $this->rsa = openssl_pkey_new(['config' => '/dev/null', 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 1024]);
        $this->fakeApple(); $this->appleCallback($this->start())->assertRedirect('/login'); $this->assertGuest('web');
    }

    public function test_client_secret_has_a_valid_p256_signature_and_short_expiry_without_persisting_credentials()
    {
        [$header, $body, $signature] = explode('.', (new ClientSecret)->generate());
        $h = json_decode(Jwt::decode($header), true); $claims = json_decode(Jwt::decode($body), true);
        $this->assertSame('ES256', $h['alg']); $this->assertSame('KEY1234567', $h['kid']); $this->assertSame('TEAM123456', $claims['iss']);
        $this->assertSame('com.pianolit.web', $claims['sub']); $this->assertSame('https://appleid.apple.com', $claims['aud']);
        $this->assertSame(3600, $claims['exp'] - $claims['iat']);
        $bytes = Jwt::decode($signature); $this->assertSame(64, strlen($bytes)); $der = '';
        foreach ([substr($bytes, 0, 32), substr($bytes, 32)] as $number) { $number = ltrim($number, "\0"); if (ord($number[0]) & 128) $number = "\0".$number; $der .= "\x02".chr(strlen($number)).$number; }
        $der = "\x30".chr(strlen($der)).$der;
        $this->assertSame(1, openssl_verify($header.'.'.$body, $der, openssl_pkey_get_details($this->ec)['key'], OPENSSL_ALGO_SHA256));
        $this->assertDatabaseCount('users', 0); $this->assertDatabaseCount('user_auth_providers', 0);
    }

    public function test_missing_apple_configuration_preserves_google_and_password_authentication()
    {
        config(['services.apple_login.private_key_path' => null]);
        foreach (['localhost', 'my.localhost'] as $host) $this->get('http://'.$host.'/login')->assertOk()->assertSee('Continue with Google')->assertDontSee('Continue with Apple');
        $this->get('http://localhost/auth/apple')->assertRedirect('/login');
        $user = $this->user(); $this->post('http://localhost/login', ['email' => $user->email, 'password' => 'existing-password'])->assertRedirect(); $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_key_configuration_rejects_a_public_file_and_callback_host_overrides()
    {
        config(['services.apple_login.private_key_path' => public_path('css/app.css')]);
        $this->assertNull(app(\App\Auth\Apple\Configuration::class)->privateKeyPath());
        $this->get('http://localhost/login')->assertOk()->assertDontSee('Continue with Apple')->assertSee('Continue with Google');
        config(['services.apple_login.private_key_path' => $this->keyPath, 'services.apple_login.redirect_uri' => 'https://evil.example/callback']);
        $this->get('http://localhost/auth/apple')->assertRedirect('/login');
        $this->withExceptionHandling()->get('http://admin.localhost/auth/apple')->assertNotFound();
    }

    public function test_authenticated_connect_requires_recent_proof_and_rechecks_expiry_after_apple_returns()
    {
        $user = $this->user(); $this->actingAs($user, 'web');
        $this->post('http://localhost/auth/apple/connect')->assertRedirect('/auth/apple/confirm');
        $this->get('http://localhost/auth/apple/confirm')->assertOk(); $this->prove()->assertRedirect();
        // The first proof starts authorization through the shared next action.
        $q = $this->start('localhost', true); $proof = session('google_auth.recent'); $proof['at'] = time() - 601;
        $this->withSession(['google_auth.recent' => $proof]); $this->appleCallback($q)->assertRedirect('/auth/google/accounts'); $this->assertDatabaseCount('user_auth_providers', 0);
    }

    public function test_apple_reauthentication_accepts_only_the_connected_subject()
    {
        $user = $this->user(); $this->link($user); $this->actingAs($user, 'web');
        $response = $this->post('http://localhost/auth/apple/reauthenticate')->assertRedirect(); parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->withCookies([config('session.cookie') => session()->getId()]);
        $this->appleCallback($q)->assertRedirect('/auth/google/accounts'); $this->assertSame('apple', session('google_auth.recent.provider'));
        $this->fakeApple(['sub' => 'other-subject']); $response = $this->post('http://localhost/auth/apple/reauthenticate'); parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);
        $this->appleCallback($q)->assertRedirect('/auth/google/accounts'); $this->assertDatabaseCount('user_auth_providers', 1);
    }

    public function test_linking_preserves_memberships_favorites_folders_and_recent_history()
    {
        $user = $this->user(); $piece = Model::withoutEvents(function () { return create(\App\Piece::class); }); $user->favorites()->attach($piece->id);
        DB::table('favorite_folders')->insert(['user_id' => $user->id, 'name' => 'My repertoire', 'created_at' => now(), 'updated_at' => now()]);
        $stripe = DB::table('stripe_memberships')->insertGetId(['stripe_id' => 'cus_apple_review', 'plan' => 'monthly', 'status' => 'active', 'renews_at' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('memberships')->insert(['user_id' => $user->id, 'source_id' => $stripe, 'source_type' => \App\Billing\Sources\Stripe::class, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('recently_viewed_pieces')->insert(['user_id' => $user->id, 'piece_id' => $piece->id, 'viewed_at' => now()]);
        $before = []; foreach (['memberships', 'stripe_memberships', 'favorites', 'favorite_folders', 'recently_viewed_pieces'] as $table) $before[$table] = DB::table($table)->get()->toJson();
        $this->appleCallback($this->start()); $this->prove()->assertRedirect(); $this->assertTrue($user->fresh()->hasActiveSubscription());
        foreach ($before as $table => $value) $this->assertSame($value, DB::table($table)->get()->toJson());
    }

    public function test_only_the_stateless_callback_is_exempt_from_session_and_csrf_middleware()
    {
        $routes = app('router')->getRoutes(); $callback = $routes->getByName('apple.callback');
        $this->assertSame(['POST'], $callback->methods()); $this->assertContains(\Illuminate\Session\Middleware\StartSession::class, $callback->excludedMiddleware());
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $callback->excludedMiddleware());
        foreach (['apple.complete', 'apple.register', 'apple.existing', 'apple.link.password', 'apple.signup.finish', 'apple.disconnect'] as $name) {
            $this->assertNotContains(\App\Http\Middleware\VerifyCsrfToken::class, $routes->getByName($name)->excludedMiddleware());
            $this->assertNotContains(\Illuminate\Session\Middleware\StartSession::class, $routes->getByName($name)->excludedMiddleware());
        }
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_migration_preserves_google_rows_and_uniqueness_and_refuses_destructive_rollback()
    {
        $user = $this->user(); $this->link($user, 'google'); $before = DB::table('user_auth_providers')->get()->toJson();
        require_once database_path('migrations/2026_10_13_220000_support_apple_authentication_providers.php'); $migration = new \SupportAppleAuthenticationProviders;
        $migration->down(); $this->assertFalse(Schema::hasColumn('auth_link_challenges', 'provider')); $migration->up();
        $this->assertSame($before, DB::table('user_auth_providers')->get()->toJson());
        $this->link($user, 'apple');
        try { $migration->down(); $this->fail('Rollback removed Apple metadata.'); } catch (\RuntimeException $e) { $this->assertStringContainsString('Apple authentication records exist', $e->getMessage()); }
        $this->assertTrue(Schema::hasColumn('auth_link_challenges', 'provider')); $this->assertDatabaseCount('user_auth_providers', 2);
        try { DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => 'apple', 'provider_user_id' => 'another', 'provider_email' => null]); $this->fail('User/provider uniqueness was removed.'); }
        catch (\Illuminate\Database\QueryException $e) {}
    }

    public function test_real_csrf_enforcement_accepts_the_sessionless_post_but_protects_signup_and_connections()
    {
        $q = $this->start();
        $this->app->instance(\App\Http\Middleware\VerifyCsrfToken::class, new class($this->app, $this->app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
        });
        $this->withExceptionHandling();
        $id = $this->receive($q); $this->get('http://localhost/auth/apple/complete/'.$id)->assertRedirect('/auth/apple/choose');
        $this->post('http://localhost/auth/apple/register', ['choice_id' => session('apple_auth.choice.id')])->assertStatus(419);
        $this->post('http://localhost/auth/apple/existing', ['email' => 'any@example.test'])->assertStatus(419);
        $this->post('http://localhost/auth/apple/link/email')->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
        $this->post('http://localhost/auth/apple/register', ['choice_id' => session('apple_auth.choice.id'), '_token' => session()->token()])->assertRedirect();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_an_apple_login_uses_the_existing_two_session_handoff_and_rejects_replay()
    {
        $user = $this->user(); $this->link($user); $this->withSession(['url.intended' => 'http://my.localhost/profile']);
        $response = $this->appleCallback($this->start()); $url = $response->headers->get('Location');
        $row = DB::table('auth_browser_handoffs')->first(); $this->assertSame('http://my.localhost/auth/google/handoff/'.$row->id, $url);
        $source = session()->getId(); $target = str_repeat('A', 40);
        $this->selectSession($target); $approve = $this->get($url)->assertRedirect()->headers->get('Location'); $this->assertGuest('web');
        $this->selectSession($source); $form = $this->get($approve)->assertOk()->getContent(); preg_match('/name="handoff_token" value="([a-f0-9]{64})"/', $form, $match);
        $this->selectSession($target); $this->withHeader('Origin', 'http://evil.example')->post($url, ['handoff_token' => $match[1]])->assertForbidden();
        $this->withHeader('Origin', 'http://localhost')->post($url, ['handoff_token' => $match[1]])->assertRedirect('http://my.localhost/profile');
        $this->assertAuthenticatedAs($user, 'web'); $this->assertNull(session('google_auth.recent'));
        $this->post($url, ['handoff_token' => $match[1]])->assertForbidden(); $this->assertDatabaseCount('users', 1);
    }

    private function selectSession($id)
    {
        session()->save(); session()->flush(); session()->setId($id); session()->start(); auth()->forgetGuards();
        $this->withCookies([config('session.cookie') => $id]);
    }

    public function test_provider_identity_namespaces_and_transaction_rollback_preserve_other_connections()
    {
        $google = $this->user(['email' => 'google@example.test']); $this->link($google, 'google', null, $google->email, 'same-subject');
        $apple = $this->user(); $this->link($apple, 'apple', null, $apple->email, 'same-subject'); $this->assertDatabaseCount('user_auth_providers', 2);
        $new = $this->user(['email' => 'third@example.test']);
        $accounts = \Mockery::mock(Accounts::class)->makePartial(); $accounts->shouldReceive('audit')->with($new->id, 'connected', 'apple')->andThrow(new \RuntimeException('Synthetic audit failure'));
        try { DB::transaction(function () use ($accounts, $new) { $accounts->link($new, ['sub' => 'third-subject', 'email' => $new->email], null, 'apple'); }); $this->fail('The failed link committed.'); }
        catch (\RuntimeException $e) { $this->assertSame('Synthetic audit failure', $e->getMessage()); }
        $this->assertDatabaseCount('user_auth_providers', 2); $this->assertDatabaseMissing('user_auth_providers', ['user_id' => $new->id]);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $google->id, 'provider' => 'google', 'provider_user_id' => 'same-subject']);
    }

    public function test_signature_tampering_foreign_keys_and_unsigned_browser_email_are_not_trusted()
    {
        $this->nonce = 'review-nonce'; $token = $this->jwt($this->claims()); $parts = explode('.', $token);
        $parts[1] = Jwt::encode($this->claims(['sub' => 'forged-subject']));
        try { (new IdTokenVerifier)->verify(implode('.', $parts), $this->nonce); $this->fail('Tampered claims were accepted.'); } catch (\UnexpectedValueException $e) {}
        $other = openssl_pkey_new(['config' => '/dev/null', 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        openssl_sign($parts[0].'.'.$parts[1], $signature, $other, OPENSSL_ALGO_SHA256); $parts[2] = Jwt::encode($signature);
        try { (new IdTokenVerifier)->verify(implode('.', $parts), $this->nonce); $this->fail('A foreign signing key was accepted.'); } catch (\UnexpectedValueException $e) {}
        $this->appleCallback($this->start(), 'localhost', ['user' => json_encode(['email' => 'victim@example.test', 'name' => ['firstName' => '<script>Display</script>', 'lastName' => 'Name']])])->assertRedirect('/auth/apple/choose');
        $this->assertSame($this->email, session('apple_auth.choice.identity.email')); $this->assertSame('Display', session('apple_auth.choice.identity.first_name'));
    }

    public function test_apple_only_expired_confirmation_uses_apple_or_email_and_missing_migration_hides_only_apple()
    {
        $user = $this->user(['password' => Hash::make(bin2hex(random_bytes(32)))]); $this->link($user, 'apple', $user->password); $this->actingAs($user, 'web');
        $this->get('http://localhost/auth/google/password')->assertRedirect('/auth/google/confirm');
        $this->get('http://localhost/auth/google/confirm')->assertOk()->assertSee('Confirm with Apple')->assertSee('/auth/apple/link/email', false);
        $this->assertDatabaseHas('auth_link_challenges', ['provider' => 'apple', 'purpose' => 'reauth']);
        DB::table('auth_link_challenges')->delete(); DB::table('user_auth_providers')->delete(); auth('web')->logout();
        require_once database_path('migrations/2026_10_13_220000_support_apple_authentication_providers.php'); (new \SupportAppleAuthenticationProviders)->down();
        $this->get('http://localhost/login')->assertOk()->assertSee('Continue with Google')->assertDontSee('Continue with Apple');
        (new \SupportAppleAuthenticationProviders)->up();
    }

    public function test_real_registration_listeners_keep_newsletter_and_source_without_requiring_an_ios_update()
    {
        $dispatcher = new \Illuminate\Events\Dispatcher($this->app);
        $dispatcher->listen(Registered::class, \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class);
        $dispatcher->listen(Registered::class, \App\Listeners\SubscribeUser::class); Event::swap($dispatcher);
        $this->appleCallback($this->start());
        $this->post('http://localhost/auth/apple/register', ['choice_id' => session('apple_auth.choice.id')])->assertRedirect();
        $user = User::firstOrFail(); $this->assertSame('web', $user->origin); $this->assertDatabaseHas('subscriptions', ['email' => $this->email]);
        Notification::assertNotSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_provider_buttons_and_shared_settings_render_in_all_enabled_states()
    {
        $directory = getenv('PIANOLIT_APPLE_AUTH_PREVIEW');
        if ($directory && !is_dir($directory)) mkdir($directory, 0700, true);
        $save = function ($name, $response) use ($directory) {
            if (!$directory) return;
            $html = preg_replace_callback('#<script\b[^>]*>[\s\S]*?</script>#i', function ($match) {
                return preg_match('#googletagmanager|fbq|facebook|recaptcha#i', $match[0]) ? '' : $match[0];
            }, $response->getContent());
            $html = str_replace(['http://my.localhost/', 'http://localhost/'], '/', $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        };
        foreach (['both' => [true, true], 'google' => [true, false], 'apple' => [false, true], 'none' => [false, false]] as $state => $enabled) {
            config(['services.google_login.client_id' => $enabled[0] ? 'google-review' : null, 'services.apple_login.client_id' => $enabled[1] ? 'com.pianolit.web' : null]);
            foreach (['localhost' => 'web', 'my.localhost' => 'my'] as $host => $site) foreach (['login', 'register'] as $page) {
                $response = $this->get('http://'.$host.'/'.$page)->assertOk()->assertSee('name="email"', false)->assertSee('name="password"', false);
                if ($enabled[0]) $response->assertSee('Continue with Google'); else $response->assertDontSee('Continue with Google');
                if ($enabled[1]) $response->assertSee('Continue with Apple'); else $response->assertDontSee('Continue with Apple');
                // Every host uses the same standalone page with one provider group.
                $groups = 1;
                $this->assertSame($enabled[0] || $enabled[1] ? $groups : 0, substr_count($response->getContent(), 'By continuing, you agree'));
                $save($page.'-'.$site.'-'.$state, $response);
            }
        }
        config(['services.google_login.client_id' => 'google-review', 'services.apple_login.client_id' => 'com.pianolit.web']);
        $this->fakeApple(['email' => 'relay@privaterelay.appleid.com']); $this->appleCallback($this->start());
        $save('choose-relay', $this->get('http://localhost/auth/apple/choose')->assertOk());
        $this->fakeApple(['email' => null, 'email_verified' => null]); $this->appleCallback($this->start());
        $save('choose-email', $this->get('http://localhost/auth/apple/choose')->assertOk());
        $user = $this->user(['first_name' => 'Review', 'last_name' => 'Pianist']);
        $this->fakeApple(); $this->appleCallback($this->start());
        $save('confirm-apple-link', $this->get('http://localhost/auth/apple/link')->assertOk());
        $this->recent($user);
        $save('accounts-none', $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Connect Apple')->assertSee('Connect Google'));
        $this->link($user, 'google'); $this->link($user, 'apple', null, 'relay@privaterelay.appleid.com');
        $save('accounts-both', $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Private email'));
        DB::table('user_auth_providers')->where('provider', 'google')->delete();
        DB::table('user_auth_providers')->update(['unusable_password_hash' => $user->password]);
        $save('accounts-apple-only', $this->get('http://my.localhost/auth/google/accounts')->assertOk()->assertSee('Set a Password'));
        $save('confirm-apple-only', $this->get('http://my.localhost/auth/google/confirm')->assertOk()->assertSee('Confirm with Apple'));
    }
}
