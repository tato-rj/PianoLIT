<?php

namespace Tests\Review;

use App\{Admin, FavoriteFolder, User};
use App\Events\Memberships\NewTrial;
use App\Mail\AutomaticEmailSettings;
use App\Notifications\{NewUser, eScoreGeneratedNotification};
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Mail, Queue};

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
            ->assertSee('Jamie')->assertSee('1 unread notification');
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
