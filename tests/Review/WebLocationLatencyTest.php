<?php

namespace Tests\Review;

use App\{Location, User};
use App\Http\Middleware\UpdateLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Stevebauman\Location\Facades\Location as LocationApi;

class WebLocationLatencyTest extends ReviewTestCase
{
    private $responseGenerated = false;

    public function setUp(): void
    {
        parent::setUp();
        Route::middleware(['web', UpdateLocation::class])->get('/_review/location', function () {
            $this->responseGenerated = true;
            return response('ready');
        })->name('webapp.location.probe');
    }

    private function user()
    {
        return User::withoutEvents(function () { return create(User::class); });
    }

    private function location()
    {
        return (object) [
            'ip' => '192.0.2.1', 'countryName' => 'Test', 'countryCode' => 'US',
            'regionCode' => 'NY', 'regionName' => 'New York', 'cityName' => 'Test City',
            'latitude' => '0', 'longitude' => '0',
        ];
    }

    public function test_guests_never_trigger_a_location_lookup_even_with_a_submitted_user_id()
    {
        $user = $this->user();
        LocationApi::shouldReceive('get')->never();
        $this->get('/_review/location?user_id='.$user->id)->assertOk();
        $this->assertDatabaseCount('locations', 0);
    }

    public function test_existing_account_location_skips_remote_calls_even_when_primary_keys_differ()
    {
        $this->user();
        $user = $this->user();
        $location = Location::create(['user_id' => $user->id] + (array) $this->location());
        $this->assertNotEquals($location->id, $user->id);
        LocationApi::shouldReceive('get')->never();
        $this->actingAs($user, 'web');
        $this->get('/_review/location')->assertOk();
        $this->get('/_review/location')->assertOk();
        $this->assertDatabaseCount('locations', 1);
    }

    public function test_new_location_is_looked_up_after_response_generation_and_belongs_to_the_session_user()
    {
        $other = $this->user();
        $user = $this->user();
        $this->actingAs($user, 'web');
        LocationApi::shouldReceive('get')->once()->andReturnUsing(function () {
            $this->assertTrue($this->responseGenerated, 'Location must not delay controller/view rendering.');
            return $this->location();
        });
        $this->get('/_review/location?user_id='.$other->id)->assertOk();
        $this->assertDatabaseHas('locations', ['user_id' => $user->id, 'countryCode' => 'US']);
        $this->assertDatabaseMissing('locations', ['user_id' => $other->id]);
        $this->get('/_review/location')->assertOk();
        $this->assertDatabaseCount('locations', 1);
    }

    /** @dataProvider failures */
    public function test_failed_lookups_do_not_break_browsing_and_retry_only_after_the_interval($throws)
    {
        $this->actingAs($this->user(), 'web');
        LocationApi::shouldReceive('get')->twice()->andReturnUsing(function () use ($throws) {
            $this->assertTrue($this->responseGenerated);
            if ($throws) throw new \RuntimeException('Provider unavailable');
            return false;
        });
        $this->get('/_review/location')->assertOk();
        $this->get('/_review/location')->assertOk();
        $this->travel(61)->minutes();
        $this->get('/_review/location')->assertOk();
        $this->assertDatabaseCount('locations', 0);
        $this->travelBack();
    }

    public function failures()
    {
        return [[false], [true]];
    }

    public function test_session_retry_marker_is_scoped_to_the_authenticated_account()
    {
        LocationApi::shouldReceive('get')->twice()->andReturn(false);
        $this->actingAs($this->user(), 'web');
        $this->get('/_review/location')->assertOk();
        $this->actingAs($this->user(), 'web');
        $this->get('/_review/location')->assertOk();
    }

    public function test_handle_returns_before_lookup_and_a_fresh_terminating_instance_runs_it_once()
    {
        $this->actingAs($this->user(), 'web');
        $lookups = 0;
        LocationApi::shouldReceive('get')->once()->andReturnUsing(function () use (&$lookups) {
            $lookups++;
            return $this->location();
        });
        $request = Request::create('/_review/location');
        $request->setRouteResolver(function () { return (new RouteDefinition('GET', '/', []))->name('webapp.probe'); });
        $request->setLaravelSession($this->app['session.store']);
        $response = (new UpdateLocation)->handle($request, function () { return response('ready'); });
        $this->assertSame('ready', $response->getContent());
        $this->assertSame(0, $lookups);
        (new UpdateLocation)->terminate($request, $response);
        $this->assertSame(1, $lookups);
        (new UpdateLocation)->terminate($request, $response);
        $this->assertSame(1, $lookups);
    }

    public function test_another_request_can_fill_the_location_before_termination_without_another_lookup()
    {
        $user = $this->user();
        $this->actingAs($user, 'web');
        LocationApi::shouldReceive('get')->never();
        $request = Request::create('/_review/location');
        $request->setRouteResolver(function () { return (new RouteDefinition('GET', '/', []))->name('webapp.probe'); });
        $request->setLaravelSession($this->app['session.store']);
        $middleware = new UpdateLocation;
        $response = $middleware->handle($request, function () use ($user) {
            Location::create(['user_id' => $user->id] + (array) $this->location());
            return response('ready');
        });
        $middleware->terminate($request, $response);
        $middleware->terminate($request, $response);
        $this->assertDatabaseCount('locations', 1);
    }

    public function test_mobile_lookup_retains_its_existing_synchronous_behavior()
    {
        $this->actingAs($this->user(), 'web');
        $lookedUp = false;
        LocationApi::shouldReceive('get')->once()->andReturnUsing(function () use (&$lookedUp) {
            $lookedUp = true;
            return false;
        });
        $request = Request::create('/api/probe');
        $request->setRouteResolver(function () { return (new RouteDefinition('GET', '/', []))->name('api.probe'); });
        $middleware = new UpdateLocation;
        $response = $middleware->handle($request, function () use (&$lookedUp) {
            $this->assertTrue($lookedUp);
            return response('ready');
        });
        $middleware->terminate($request, $response);
    }
}
