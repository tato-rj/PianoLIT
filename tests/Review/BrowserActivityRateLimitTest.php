<?php

namespace Tests\Review;

use App\Billing\Stripe\MembershipBilling;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Crypt, DB, RateLimiter, Route};
use Illuminate\Support\Str;

class BrowserActivityRateLimitTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['activity.minimum_visit_seconds' => 0, 'attribution.enabled' => false]);
        $this->travelTo(now()->startOfSecond());
        $this->withExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\UpdateLocation::class);
        Route::domain('my.'.config('app.short_url'))->middleware(['web', 'log.webapp'])
            ->get('/review/browser-limits', function () { return response('<html>Activity page</html>'); });
    }

    private function url($path)
    {
        return 'http://my.'.config('app.short_url').$path;
    }

    private function browser($signedIn)
    {
        if ($signedIn) {
            $user = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
            $this->actingAs($user, 'web');
        }
        $this->get($this->url('/review/browser-limits'))->assertOk();
        $page = DB::table('activity_pages')->first();
        return ['page_token' => Crypt::encryptString($page->id), 'events' => [[
            'id' => (string) Str::uuid(), 'type' => 'page_ready', 'elapsed_ms' => 0,
            'sequence' => 1, 'metadata' => ['visible_ms' => 0],
        ]]];
    }

    public function test_background_activity_cannot_use_up_the_card_preparation_allowance()
    {
        $payload = $this->browser(true);
        for ($i = 0; $i < 20; $i++) {
            $this->postJson($this->url('/activity/events'), $payload)->assertNoContent();
        }

        $operation = (string) Str::uuid();
        $billing = \Mockery::mock(MembershipBilling::class);
        $billing->shouldReceive('prepare')->once()->with(auth('web')->user(), $operation, 'card')
            ->andReturn(['operation_id' => $operation]);
        $this->app->instance(MembershipBilling::class, $billing);

        $this->postJson(route('webapp.membership.prepare.card'), ['operation_id' => $operation])
            ->assertOk()->assertExactJson(['operation_id' => $operation])
            ->assertHeader('X-RateLimit-Limit', 20)->assertHeader('X-RateLimit-Remaining', 19);
    }

    /** @dataProvider audiences */
    public function test_other_request_counters_cannot_block_activity($signedIn)
    {
        $payload = $this->browser($signedIn);
        // Existing unprefixed throttles use the account ID, or the route's
        // domain and IP. The host-independent activity route has no domain.
        $sharedKey = sha1($signedIn ? (string) auth('web')->id() : '|127.0.0.1');
        for ($i = 0; $i < 120; $i++) RateLimiter::hit($sharedKey, 60);

        $this->postJson($this->url('/activity/events'), $payload)->assertNoContent()
            ->assertHeader('X-RateLimit-Limit', 120)->assertHeader('X-RateLimit-Remaining', 119);
    }

    /** @dataProvider audiences */
    public function test_activity_keeps_its_own_limit_and_recovers_after_the_window($signedIn)
    {
        $payload = $this->browser($signedIn);
        for ($i = 0; $i < 120; $i++) {
            $this->postJson($this->url('/activity/events'), $payload)->assertNoContent();
        }
        $this->postJson($this->url('/activity/events'), $payload)->assertStatus(429)
            ->assertHeader('Retry-After', 60)->assertHeader('X-RateLimit-Remaining', 0);
        $this->travel(61)->seconds();
        $this->postJson($this->url('/activity/events'), $payload)->assertNoContent()
            ->assertHeader('X-RateLimit-Remaining', 119);
        $this->assertSame(1, DB::table('activity_events')->where('type', 'page_ready')->count(), 'Retries remain idempotent.');
    }

    public static function audiences()
    {
        return ['guest' => [false], 'account' => [true]];
    }
}
