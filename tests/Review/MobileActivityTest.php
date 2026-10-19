<?php

namespace Tests\Review;

use App\Http\Middleware\Logs\RecordAppLog;
use App\Log\LogFactory;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MobileActivityTest extends ReviewTestCase
{
    private function user()
    {
        return Model::withoutEvents(function () { return create(User::class); });
    }

    public function test_existing_mobile_get_and_post_requests_keep_their_json_contract_and_write_sql()
    {
        $user = $this->user();
        $url = route('api.users.favorites.show', ['user_id' => $user->id]);
        $this->getJson($url)->assertOk()->assertExactJson([]);
        $this->postJson(route('api.users.favorites.show'), ['user_id' => $user->id])->assertOk()->assertExactJson([]);
        $events = DB::table('activity_events')->orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertSame(['GET', 'POST'], $events->pluck('method')->all());
        $this->assertSame(['app'], $events->pluck('platform')->unique()->all());
        $this->assertSame([$user->id], $events->pluck('user_id')->unique()->all());
        $this->assertSame('api.users.favorites.show', $events[0]->route_name);
        $this->assertDatabaseCount('activity_visits', 0);
        $serialized = $user->fresh()->toArray();
        $this->assertSame(2, $serialized['logs_count']);
        $this->assertArrayHasKey('last_active_at', $serialized);
        $login = $this->postJson(route('api.users.login'), ['email' => $user->email, 'password' => 'secret']);
        $login->assertOk()->assertExactJson($serialized);
        $this->assertArrayNotHasKey('password', $login->json());
        $this->assertSame(2, (new LogFactory)->count($user->id, 'app'));
        $this->assertNotNull((new LogFactory)->last($user->id, 'app'));
    }

    public function test_logging_failure_does_not_change_the_mobile_response_or_fall_back_to_redis()
    {
        $user = $this->user();
        DB::statement("CREATE TRIGGER mobile_activity_fail BEFORE INSERT ON activity_events WHEN NEW.platform = 'app' BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        $this->getJson(route('api.users.favorites.show', ['user_id' => $user->id]))->assertOk()->assertExactJson([]);
        $this->assertDatabaseCount('activity_events', 0);
    }

    public function test_failed_mobile_actions_still_record_the_attempt_without_claiming_success()
    {
        $user = $this->user();
        $this->withExceptionHandling()->getJson(route('api.pieces.find', ['user_id' => $user->id, 'search' => 999999]))->assertNotFound();
        $this->assertDatabaseHas('activity_events', ['platform' => 'app', 'route_name' => 'api.pieces.find', 'user_id' => $user->id, 'status' => null]);
        $event = DB::table('activity_events')->first();
        $this->assertSame('999999', json_decode($event->metadata, true)['data']['search']);
    }

    public function test_existing_mobile_exclusions_and_absent_or_malformed_identifiers_are_not_logged()
    {
        $middleware = new RecordAppLog;
        foreach ([null, '', 0, -1, [], ['id' => 123], 'abc', '4294967296', 284, 260, 196, 249, 928] as $id) {
            $this->assertFalse((bool) $middleware->shouldContinue(\Illuminate\Http\Request::create('/api/example', 'GET', ['user_id' => $id])));
        }
        $this->assertTrue((bool) $middleware->shouldContinue(\Illuminate\Http\Request::create('/api/example', 'GET', ['user_id' => '123'])));
        $this->getJson(route('api.users.favorites.show', ['user_id' => 284]))->assertOk()->assertExactJson(['User not found']);
        $this->assertDatabaseCount('activity_events', 0);
    }

    public function test_account_creation_deletion_and_retired_maintenance_commands_do_not_use_redis_activity_indexes()
    {
        $user = create(User::class);
        $this->assertSame(0, $user->logs_count);
        $this->assertNull($user->last_active_at);
        $user->delete();
        $this->artisan('redis:rebuild-user-log-index')->assertExitCode(0);
        $this->artisan('redis:refresh-daily-logs')->assertExitCode(0);
    }

    public function test_admin_analytics_and_log_factory_read_sql_including_imported_history()
    {
        $user = $this->user();
        $this->getJson(route('api.users.favorites.show', ['user_id' => $user->id]))->assertOk();
        $first = DB::table('activity_events')->first();
        $copy = (array) $first;
        unset($copy['id']);
        $copy['event_id'] = (string) \Illuminate\Support\Str::uuid();
        $copy['source'] = 'redis'; $copy['type'] = 'legacy_unparsed'; $copy['url'] = null; $copy['occurred_at'] = null;
        $copy['legacy_payload'] = '<script>legacy</script>';
        DB::table('activity_events')->insert($copy);
        $this->actingAs(create(\App\Admin::class), 'admin');
        $this->get(route('admin.users.show', $user))->assertOk()->assertSee('Logs & analytics', false)
            ->assertDontSee('App Logs (')->assertDontSee('legacy_user_id');
        $response = $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits', 'range' => 'all', 'platform' => 'app']))
            ->assertOk()->assertSee('Ungrouped activity')->assertDontSee('<script>legacy</script>', false);
        $this->assertSame(2, $response->viewData('stats')->ungrouped);
        $this->assertSame(1, $response->viewData('history')->total(), 'Undated raw imports stay out of dated analytics lists.');
        $this->assertDatabaseHas('activity_events', ['source' => 'redis', 'legacy_payload' => '<script>legacy</script>', 'occurred_at' => null]);
        $this->assertSame(2, (new LogFactory)->total());
        $this->assertCount(2, (new LogFactory)->get($user->id, 'app'));
    }

    public function test_mobile_activity_can_be_disabled_without_a_redis_fallback()
    {
        config(['activity.enabled' => false]);
        $user = $this->user();
        $this->getJson(route('api.users.favorites.show', ['user_id' => $user->id]))->assertOk()->assertExactJson([]);
        $this->assertDatabaseCount('activity_events', 0);
    }
}
