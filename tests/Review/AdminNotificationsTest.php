<?php

namespace Tests\Review;

use App\{Admin, FavoriteFolder, User};
use App\Events\Memberships\NewTrial;
use App\Mail\AutomaticEmailSettings;
use App\Notifications\{NewUser, eScoreGeneratedNotification};
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Mail, Queue};
use Illuminate\Support\Str;

class AdminNotificationsTest extends ReviewTestCase
{
    private function user()
    {
        return Model::withoutEvents(function () {
            return create(User::class, ['first_name' => 'Jamie', 'origin' => 'webapp']);
        });
    }

    public function test_database_alerts_reach_every_admin_even_with_mail_disabled_and_the_queue_stopped()
    {
        $admins = collect([create(Admin::class), create(Admin::class)]);
        $user = $this->user();
        foreach (app(\App\Services\Admin\AutomaticEmails::class)->all()->keys() as $key) {
            app(AutomaticEmailSettings::class)->setEnabled($key, false, null);
        }
        Queue::fake();

        // Use the real event dispatcher, channel manager and database channel.
        event(new Registered($user));
        Admin::notifyAll(new NewUser($user));
        event(new NewTrial($user));

        foreach ($admins as $admin) {
            $this->assertCount(3, $admin->fresh()->unreadNotifications);
            $this->assertSame(['New subscriber', 'New user', 'New user on trial'],
                $admin->notifications()->get()->pluck('data.title')->sort()->values()->all());
        }
        $this->assertDatabaseCount('notifications', 6);
        $this->assertCount(0, $user->notifications);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_new_alerts_render_in_the_bell_panel_and_notification_history_for_the_admin_session()
    {
        $admin = create(Admin::class);
        $other = create(Admin::class);
        $user = $this->user();
        $admin->notify(new NewUser($user));
        $other->notify(new NewUser($user));
        $this->actingAs($user, 'web')->actingAs($admin, 'admin');

        $response = $this->get(route('admin.notifications.index'))->assertOk();
        $response->assertSee('Notifications (1 unread)')->assertSee('admin-notification-dot')
            ->assertDontSee('Jamie')->assertSee('Loading notifications');
        $panel = $this->getJson(route('admin.notifications.panel'))->assertOk()->assertJsonPath('unreadCount', 1);
        $this->assertStringContainsString('Jamie', $panel->json('html'));
        $history = $this->getJson(route('admin.notifications.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $history->assertJsonPath('recordsTotal', 1);
        $this->assertSame($admin->notifications()->first()->id, $history->json('data.0.id'));
        $this->assertDatabaseHas('notifications', ['id' => $admin->notifications()->first()->id, 'read_at' => null]);
    }

    public function test_reading_an_alert_only_changes_the_authenticated_admins_copy()
    {
        $admin = create(Admin::class);
        $other = create(Admin::class);
        Admin::notifyAll(new NewUser($this->user()));
        $id = $admin->notifications()->first()->id;
        $this->actingAs($admin, 'admin');

        $this->getJson(route('admin.notifications.read', ['ids' => [$id]]))->assertOk();
        $this->assertNotNull($admin->notifications()->first()->read_at);
        $this->assertNull($other->notifications()->first()->read_at);
        $this->getJson(route('admin.notifications.unread', ['ids' => [$id]]))->assertOk();
        $this->assertNull($admin->notifications()->first()->read_at);
    }

    private function alerts(Admin $admin, $count, $read = false)
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'id' => (string) Str::uuid(), 'type' => NewUser::class,
                'notifiable_id' => $admin->id, 'notifiable_type' => Admin::class,
                'data' => json_encode(['title' => 'Test alert', 'message' => 'Alert '.$i, 'url' => route('admin.home')]),
                'read_at' => $read ? now()->subDay() : null,
                'created_at' => now()->subSeconds($count - $i), 'updated_at' => now()->subDay(),
            ];
        }
        DB::table('notifications')->insert($rows);
        return $rows;
    }

    private function notificationQueries()
    {
        return collect(DB::getQueryLog())->filter(function ($query) {
            return strpos($query['query'], '"notifications"') !== false;
        })->values();
    }

    public function test_admin_pages_only_count_alerts_and_the_panel_reads_at_most_twelve_rows()
    {
        $admin = create(Admin::class);
        $this->alerts($admin, 250);
        $this->alerts($admin, 30, true);
        $this->alerts(create(Admin::class), 10);
        $this->actingAs($admin, 'admin');

        DB::enableQueryLog(); DB::flushQueryLog();
        $this->get(route('admin.notifications.index'))->assertOk()->assertSee('Notifications (250 unread)');
        $queries = $this->notificationQueries();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('count(*)', $queries[0]['query']);

        DB::flushQueryLog();
        $response = $this->getJson(route('admin.notifications.panel'))->assertOk()->assertJsonPath('unreadCount', 250);
        $queries = $this->notificationQueries(); DB::disableQueryLog();
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('limit 12', $queries[1]['query']);
        $this->assertSame(12, substr_count($response->json('html'), 'class="notification-item '));
        $this->assertStringContainsString('Alert 249', $response->json('html'));
        $this->assertStringNotContainsString('Alert 237<', $response->json('html'));
    }

    public function test_mark_all_is_one_scoped_update_without_a_dashboard_reload_or_rewriting_read_timestamps()
    {
        $admin = create(Admin::class);
        $other = create(Admin::class);
        $this->alerts($admin, 250);
        $read = $this->alerts($admin, 1, true)[0];
        $this->alerts($other, 1);
        $this->actingAs($admin, 'admin');
        $this->mock(\App\Services\Admin\Dashboard::class)->shouldNotReceive('data');

        DB::enableQueryLog(); DB::flushQueryLog();
        $response = $this->postJson(route('admin.notifications.read'))->assertOk()->assertJsonPath('unreadCount', 0);
        $queries = $this->notificationQueries();
        $allQueries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(2, $queries); // One UPDATE plus COUNT; no notification hydration.
        $this->assertStringStartsWith('update ', $queries[0]['query']);
        $this->assertStringContainsString('"read_at" is null', $queries[0]['query']);
        $this->assertStringNotContainsString('activity_events', implode(' ', array_column($allQueries, 'query')));
        $this->assertStringContainsString('No new notifications', $response->json('html'));
        $this->assertFalse($response->headers->has('Location'));
        $this->assertDatabaseHas('notifications', ['id' => $read['id'], 'read_at' => $read['read_at'], 'updated_at' => $read['updated_at']]);
        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_bulk_ids_ignore_other_admins_and_missing_alerts_and_empty_ids_do_not_mean_all()
    {
        $admin = create(Admin::class);
        $own = $this->alerts($admin, 3);
        $foreign = $this->alerts(create(Admin::class), 1)[0];
        $this->actingAs($admin, 'admin');
        $ids = [$own[0]['id'], $own[1]['id'], $foreign['id'], (string) Str::uuid()];

        $this->postJson(route('admin.notifications.read'), ['ids' => []])->assertOk()->assertJsonPath('unreadCount', 3);
        $this->postJson(route('admin.notifications.read'), ['ids' => $ids])->assertOk()->assertJsonPath('unreadCount', 1);
        $this->assertDatabaseHas('notifications', ['id' => $foreign['id'], 'read_at' => null]);
        $this->postJson(route('admin.notifications.unread'), ['ids' => $ids])->assertOk()->assertJsonPath('unreadCount', 3);
        $this->assertDatabaseHas('notifications', ['id' => $foreign['id'], 'read_at' => null]);
    }

    public function test_notification_requests_validate_ids_and_require_an_admin_session()
    {
        $admin = create(Admin::class);
        $this->alerts($admin, 1);
        $this->actingAs($admin, 'admin')->withExceptionHandling();
        foreach (['read', 'unread'] as $action) {
            foreach (['bad', ['bad'], [null]] as $ids) {
                $this->postJson(route('admin.notifications.'.$action), ['ids' => $ids])->assertStatus(422);
            }
        }
        auth('admin')->logout();
        $this->getJson(route('admin.notifications.panel'))->assertUnauthorized();
        $this->postJson(route('admin.notifications.read'))->assertUnauthorized();
        $this->assertSame(1, $admin->unreadNotifications()->count());
    }

    public function test_mark_all_requires_the_admin_session_csrf_token()
    {
        $admin = create(Admin::class);
        $this->alerts($admin, 1);
        $this->actingAs($admin, 'admin')->withExceptionHandling()->withSession(['_token' => 'notification-csrf']);
        $this->app['env'] = 'review-csrf';
        $this->postJson(route('admin.notifications.read'))->assertStatus(419);
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->withHeader('X-CSRF-TOKEN', 'notification-csrf')->postJson(route('admin.notifications.read'))
            ->assertOk()->assertJsonPath('unreadCount', 0);
    }

    /** @dataProvider notificationSessions */
    public function test_escore_notification_uses_its_event_user_instead_of_the_browser_session($session)
    {
        $admin = create(Admin::class);
        $user = $this->user();
        $folder = FavoriteFolder::create(['user_id' => $user->id, 'name' => 'Practice']);
        if ($session === 'admin') $this->actingAs($admin, 'admin');
        if ($session === 'other-user') $this->actingAs($this->user(), 'web');

        event(new \App\Events\eScoreGenerated($user, $folder));

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame(eScoreGeneratedNotification::class, $notification->type);
        $this->assertSame(route('admin.users.show', $user), $notification->data['url']);
        $this->assertNull($notification->read_at);
    }

    public static function notificationSessions()
    {
        return [['guest'], ['admin'], ['other-user']];
    }
}
