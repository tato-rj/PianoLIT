<?php

namespace Tests\Review;

use App\{User, Admin};
use App\Auth\SharedWebSessions;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Event, File, Hash, Notification, Route};

class SharedWebSessionTest extends ReviewTestCase
{
    private $directory, $jar = [];

    public function createApplication()
    {
        $old = $_SERVER['APP_URL'];
        $_SERVER['APP_URL'] = 'http://pianolit.test';
        try { return parent::createApplication(); }
        finally { $_SERVER['APP_URL'] = $old; }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/pianolit-shared-session-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        config(['session.shared.enabled' => true, 'session.shared.domain' => 'pianolit.test',
            'session.shared.lock_store' => 'shared-test-locks', 'cache.stores.shared-test-locks' => ['driver' => 'file', 'path' => $this->directory.'/locks'],
            'session.driver' => 'file', 'session.files' => $this->directory.'/sessions', 'session.domain' => null,
            'session.secure' => false, 'session.lottery' => [0, 100], 'activity.enabled' => false, 'attribution.enabled' => false]);
        mkdir($this->directory.'/sessions', 0700);
        Notification::fake();
        Event::fake([\Illuminate\Auth\Events\Registered::class]);
        $this->withoutMiddleware([\App\Http\Middleware\UpdateLocation::class]);
        $this->app->bind(VerifyCsrfToken::class, EnforcedSharedSessionCsrf::class);
        Route::middleware('web')->get('/_shared/probe', function () {
            return ['web' => auth('web')->id(), 'admin' => auth('admin')->id(), 'csrf' => csrf_token(),
                'id' => session()->getId(), 'name' => session()->getName(), 'via_remember' => auth('web')->viaRemember(),
                'guard' => get_class(auth('web')), 'count' => session('shared.form_count', 0)];
        });
        Route::middleware('web')->post('/_shared/form', function () {
            session(['shared.form_count' => session('shared.form_count', 0) + 1]);
            return ['count' => session('shared.form_count')];
        });
    }

    public function tearDown(): void
    {
        if ($this->directory) File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function user()
    {
        return Model::withoutEvents(function () { return create(User::class, ['password' => Hash::make('test-password')]); });
    }

    private function browser($method, $host, $path, array $data = [], array $headers = [])
    {
        // Each request gets fresh guard/store objects, just like independent PHP
        // workers. Only real response cookies and the file store carry identity.
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        foreach ($this->app['cookie']->getQueuedCookies() as $cookie) $this->app['cookie']->unqueue($cookie->getName(), $cookie->getPath());
        $cookies = [];
        foreach ($this->jar as $entry) {
            $cookie = $entry['cookie'];
            if (($entry['host'] === $host || ($cookie->getDomain() && ($host === ltrim($cookie->getDomain(), '.') || substr($host, -strlen($cookie->getDomain()) - 1) === '.'.$cookie->getDomain())))
                && (!$cookie->getExpiresTime() || $cookie->getExpiresTime() > time())) $cookies[$cookie->getName()] = $cookie->getValue();
        }
        $scheme = ($headers['HTTPS'] ?? null) === 'on' ? 'https' : 'http';
        $response = $this->call($method, $scheme.'://'.$host.$path, $data, $cookies, [], $headers + ['HTTP_ACCEPT' => 'text/html', 'HTTP_REFERER' => $scheme.'://'.$host.'/']);
        foreach ($response->headers->getCookies() as $cookie) {
            $key = ($cookie->getDomain() ?: $host).'|'.$cookie->getPath().'|'.$cookie->getName();
            $this->jar[$key] = ['host' => $cookie->getDomain() ? null : $host, 'cookie' => $cookie];
        }
        return $response;
    }

    private function login($user, $host = 'learn.pianolit.test')
    {
        $csrf = $this->browser('GET', $host, '/_shared/probe')->json('csrf');
        return $this->browser('POST', $host, '/login', ['email' => $user->email, 'password' => 'test-password', '_token' => $csrf])->assertRedirect();
    }

    public function test_login_on_previsited_learn_is_immediately_shared_with_every_hub_and_logout_is_shared()
    {
        $hosts = app(SharedWebSessions::class)->hosts();
        foreach ($hosts as $host) $this->browser('GET', $host, '/_shared/probe')->assertJsonPath('web', null);
        $user = $this->user(); $login = $this->login($user);
        $cookie = collect($login->headers->getCookies())->first(function ($c) { return $c->getName() === SharedWebSessions::COOKIE; });
        $this->assertSame('pianolit.test', $cookie->getDomain()); $this->assertTrue($cookie->isHttpOnly()); $this->assertSame('lax', $cookie->getSameSite());
        foreach ($hosts as $host) $this->browser('GET', $host, '/_shared/probe')->assertJsonPath('web', $user->id)->assertJsonPath('admin', null);
        $csrf = $this->browser('GET', 'shop.pianolit.test', '/_shared/probe')->json('csrf');
        $this->browser('POST', 'shop.pianolit.test', '/logout', ['_token' => $csrf])->assertRedirect();
        foreach ($hosts as $host) $this->browser('GET', $host, '/_shared/probe')->assertJsonPath('web', null);
    }

    public function test_two_hub_forms_keep_csrf_and_session_state_and_reject_invalid_csrf()
    {
        $this->login($this->user());
        $a = $this->browser('GET', 'learn.pianolit.test', '/_shared/probe');
        $b = $this->browser('GET', 'shop.pianolit.test', '/_shared/probe');
        $this->assertSame($a->json('csrf'), $b->json('csrf'));
        foreach ([$a, $b] as $response) {
            $xsrf = collect($response->headers->getCookies())->first(function ($c) { return $c->getName() === 'XSRF-TOKEN'; });
            $this->assertNull($xsrf->getDomain());
        }
        $this->browser('POST', 'learn.pianolit.test', '/_shared/form', ['_token' => $a->json('csrf')])->assertJsonPath('count', 1);
        $this->browser('POST', 'shop.pianolit.test', '/_shared/form', ['_token' => $b->json('csrf')])->assertJsonPath('count', 2);
        $this->withExceptionHandling();
        $this->browser('POST', 'shop.pianolit.test', '/_shared/form', ['_token' => 'wrong'])->assertStatus(419);
    }

    public function test_an_in_flight_old_session_cannot_overwrite_a_new_login_or_logout_cookie()
    {
        $this->browser('GET', 'learn.pianolit.test', '/_shared/probe');
        $staleGuest = $this->jar;
        $user = $this->user(); $this->login($user); $current = $this->jar;
        $this->jar = $staleGuest; $this->withExceptionHandling();
        $response = $this->browser('GET', 'shop.pianolit.test', '/_shared/probe')->assertStatus(419);
        $this->assertSame([], $response->headers->getCookies());
        $this->jar = $current;
        $probe = $this->browser('GET', 'shop.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id);
        $staleUser = $this->jar;
        $this->browser('POST', 'shop.pianolit.test', '/logout', ['_token' => $probe->json('csrf')]); $current = $this->jar;
        $this->jar = $staleUser;
        $response = $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertStatus(419);
        $this->assertSame([], $response->headers->getCookies());
        $this->jar = $current;
        $this->browser('GET', 'learn.pianolit.test', '/_shared/probe')->assertJsonPath('web', null);
    }

    public function test_explicit_login_recovery_works_if_a_rotated_cookie_response_was_lost()
    {
        $this->browser('GET', 'learn.pianolit.test', '/_shared/probe'); $old = $this->jar;
        $user = $this->user(); $this->login($user);
        $this->jar = $old;
        $this->browser('GET', 'learn.pianolit.test', '/_shared/probe')->assertStatus(419)->assertSee('/login?restart_session=1', false);
        $this->browser('GET', 'learn.pianolit.test', '/login?restart_session=1')->assertOk();
        $this->login($user);
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id);
    }

    public function test_remember_cookie_resumes_on_another_hub_and_old_host_cookies_cannot_resurrect_logout()
    {
        $old = $this->user(); config(['session.shared.enabled' => false]); $this->login($old, 'my.pianolit.test');
        config(['session.shared.enabled' => true]); $user = $this->user(); $this->login($user);
        foreach ($this->jar as $key => $entry) if ($entry['cookie']->getName() === SharedWebSessions::COOKIE) unset($this->jar[$key]);
        $probe = $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id)->assertJsonPath('via_remember', true);
        $this->browser('POST', 'my.pianolit.test', '/logout', ['_token' => $probe->json('csrf')])->assertRedirect();
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', null);
    }

    public function test_admin_login_logout_and_csrf_are_independent_of_shared_web_identity()
    {
        $user = $this->user(); $this->login($user);
        $admin = create(Admin::class, ['password' => Hash::make('test-password')]);
        $probe = $this->browser('GET', 'admin.pianolit.test', '/_shared/probe')->assertJsonPath('web', null)->assertJsonPath('admin', null);
        $this->browser('POST', 'admin.pianolit.test', '/login', ['email' => $admin->email, 'password' => 'test-password', '_token' => $probe->json('csrf')])->assertRedirect();
        $probe = $this->browser('GET', 'admin.pianolit.test', '/_shared/probe')->assertJsonPath('admin', $admin->id);
        $this->browser('GET', 'pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id)->assertJsonPath('admin', null);
        $this->browser('POST', 'admin.pianolit.test', '/logout', ['_token' => $probe->json('csrf')])->assertRedirect();
        $this->browser('GET', 'pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id);
    }

    public function test_mobile_password_and_registration_contracts_keep_the_original_session_guard()
    {
        $user = $this->user();
        $this->browser('POST', 'pianolit.test', '/api/users/login', ['email' => $user->email, 'password' => 'test-password'])->assertExactJson($user->fresh()->toArray());
        $this->assertSame(\Illuminate\Auth\SessionGuard::class, get_class(auth('web')));
        $response = $this->browser('POST', 'pianolit.test', '/register', ['origin' => 'ios', 'first_name' => 'Native', 'last_name' => 'Tester', 'email' => 'native@example.test', 'password' => 'test-password', 'password_confirmation' => 'test-password'])->assertStatus(201);
        $cookies = collect($response->headers->getCookies())->map->getName()->all();
        $this->assertNotContains(SharedWebSessions::COOKIE, $cookies);
        $this->assertContains(config('session.cookie'), $cookies);
    }

    public function test_admin_impersonation_sets_only_the_public_guard_and_preserves_admin_session()
    {
        $admin = create(Admin::class, ['password' => Hash::make('test-password')]);
        $probe = $this->browser('GET', 'admin.pianolit.test', '/_shared/probe');
        $this->browser('POST', 'admin.pianolit.test', '/login', ['email' => $admin->email, 'password' => 'test-password', '_token' => $probe->json('csrf')]);
        $user = $this->user();
        $this->browser('GET', 'admin.pianolit.test', '/impersonate/'.$user->id)->assertRedirect('http://pianolit.test/');
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id)->assertJsonPath('admin', null);
        $this->browser('GET', 'admin.pianolit.test', '/_shared/probe')->assertJsonPath('admin', $admin->id)->assertJsonPath('web', null);
        $this->withExceptionHandling();
        $this->browser('GET', 'learn.pianolit.test', '/impersonate/'.$user->id)->assertForbidden();
    }

    public function test_campaign_cookies_remain_host_only_and_unknown_hosts_do_not_join_the_session()
    {
        Route::middleware('web')->get('/_shared/cookie', function () {
            cookie()->queue(cookie('host-local-example', 'test', 30));
            return 'ok';
        });
        $response = $this->browser('GET', 'learn.pianolit.test', '/_shared/cookie');
        $cookie = collect($response->headers->getCookies())->first(function ($c) { return $c->getName() === 'host-local-example'; });
        $this->assertNull($cookie->getDomain());
        $this->login($this->user());
        $this->browser('GET', 'unknown.pianolit.test', '/_shared/probe')->assertJsonPath('web', null)->assertJsonPath('name', config('session.cookie'));
    }

    public function test_parallel_hub_form_requests_serialize_their_real_file_session_writes()
    {
        if (!function_exists('pcntl_fork')) $this->markTestSkipped('Requires independent CLI workers.');
        Route::middleware('web')->post('/_shared/slow-form', function () {
            $count = session('shared.form_count', 0);
            usleep(150000);
            session(['shared.form_count' => $count + 1]);
            return ['count' => $count + 1];
        });
        $csrf = $this->browser('GET', 'learn.pianolit.test', '/_shared/probe')->json('csrf');
        $workers = [];
        foreach (['learn.pianolit.test', 'shop.pianolit.test'] as $index => $host) {
            $pid = pcntl_fork();
            if ($pid === -1) $this->fail('Could not start a disposable worker.');
            if ($pid === 0) {
                try {
                    $response = $this->browser('POST', $host, '/_shared/slow-form', ['_token' => $csrf]);
                    file_put_contents($this->directory.'/worker-'.$index, json_encode(['status' => $response->status(), 'count' => $response->json('count')]));
                    exit(0);
                } catch (\Throwable $exception) { exit(1); }
            }
            $workers[] = $pid;
        }
        foreach ($workers as $pid) { pcntl_waitpid($pid, $status); $this->assertSame(0, pcntl_wexitstatus($status)); }
        $counts = [];
        foreach ([0, 1] as $index) {
            $result = json_decode(file_get_contents($this->directory.'/worker-'.$index), true);
            $this->assertSame(200, $result['status']); $counts[] = $result['count'];
        }
        sort($counts); $this->assertSame([1, 2], $counts);
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('count', 2);
    }

    public function test_shared_web_registration_keeps_the_original_laravel_user_and_logs_in_the_hubs()
    {
        $this->withoutMiddleware([\App\Http\Middleware\ValidateRegistrationForm::class]);
        $this->browser('POST', 'learn.pianolit.test', '/register', ['origin' => 'web', 'first_name' => 'Web', 'last_name' => 'Tester',
            'email' => 'web-signup@example.test', 'password' => 'test-password', 'password_confirmation' => 'test-password'])->assertRedirect();
        $user = User::where('email', 'web-signup@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('test-password', $user->password));
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id);
        $this->browser('POST', 'pianolit.test', '/api/users/login', ['email' => $user->email, 'password' => 'test-password'])->assertJsonPath('id', $user->id);
    }

    public function test_production_http_requests_cannot_issue_shared_cookies()
    {
        $this->app->instance('env', 'production'); $this->withExceptionHandling();
        $response = $this->browser('GET', 'learn.pianolit.test', '/_shared/probe')->assertStatus(400);
        $this->assertSame([], $response->headers->getCookies());
        $this->app->instance('env', 'testing');
    }

    public function test_existing_password_reset_form_logs_into_the_shared_session_and_preserves_mobile_password_login()
    {
        $user = $this->user();
        $token = \Password::broker('users')->createToken($user);
        $csrf = $this->browser('GET', 'shop.pianolit.test', '/_shared/probe')->json('csrf');
        $this->browser('POST', 'shop.pianolit.test', '/password/reset', ['_token' => $csrf, 'token' => $token,
            'email' => $user->email, 'password' => 'reset-password', 'password_confirmation' => 'reset-password'])->assertRedirect();
        $this->assertTrue(Hash::check('reset-password', $user->fresh()->password));
        $this->assertFalse(\Password::broker('users')->tokenExists($user, $token));
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id);
        $this->browser('POST', 'pianolit.test', '/api/users/login', ['email' => $user->email, 'password' => 'reset-password'])->assertJsonPath('id', $user->id);
    }

    public function test_production_https_uses_secure_shared_cookies()
    {
        $this->app->instance('env', 'production');
        try {
            $response = $this->browser('GET', 'learn.pianolit.test', '/_shared/probe', [], ['HTTPS' => 'on'])->assertOk();
            $cookie = collect($response->headers->getCookies())->first(function ($c) { return $c->getName() === SharedWebSessions::COOKIE; });
            $this->assertTrue($cookie->isSecure());
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_a_busy_session_returns_a_retryable_response_without_overwriting_cookies()
    {
        $lock = \Mockery::mock();
        $lock->shouldReceive('betweenBlockedAttemptsSleepFor')->with(50)->andReturnSelf();
        $lock->shouldReceive('block')->with(15)->andThrow(new \Illuminate\Contracts\Cache\LockTimeoutException);
        $lock->shouldReceive('release')->once();
        $store = \Mockery::mock();
        $store->shouldReceive('lock')->with(\Mockery::type('string'), 120)->andReturn($lock);
        \Cache::shouldReceive('driver')->with('shared-test-locks')->andReturn($store);
        $this->withExceptionHandling();
        $response = $this->browser('GET', 'learn.pianolit.test', '/_shared/probe')->assertStatus(503)->assertHeader('Retry-After', '1');
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_social_login_destination_uses_the_shared_session_without_a_two_session_handoff()
    {
        $user = $this->user();
        Route::middleware('web')->get('/_shared/social', function (\Illuminate\Http\Request $request) use ($user) {
            return app(\App\Http\Controllers\Auth\GoogleHandoffController::class)->login($request, $user, 'http://my.pianolit.test/discover', 'apple');
        });
        $this->browser('GET', 'pianolit.test', '/_shared/social')->assertRedirect('http://my.pianolit.test/discover');
        $this->assertDatabaseCount('auth_browser_handoffs', 0);
        $this->browser('GET', 'my.pianolit.test', '/_shared/probe')->assertJsonPath('web', $user->id);
    }
}

class EnforcedSharedSessionCsrf extends VerifyCsrfToken
{
    protected function runningUnitTests() { return false; }
}
