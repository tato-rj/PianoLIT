<?php

namespace Tests\Review;

use App\{Admin, FeedbackSubmission, User};
use App\Mail\AutomaticEmailSettings;
use App\Notifications\FeedbackSubmittedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\{Event, Mail, Queue};

class FeedbackNotificationsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    private function member(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) {
            return create(User::class, array_merge([
                'first_name' => 'Jamie', 'last_name' => 'Rivera',
                'email' => 'arthurvillar@gmail.com',
            ], $attributes));
        });
    }

    /** @dataProvider submissions */
    public function test_each_valid_submission_notifies_every_admin_without_mail_or_a_queue($signedIn, $rating, $label)
    {
        $admins = collect([create(Admin::class), create(Admin::class)]);
        $member = $this->member();
        foreach (app(\App\Services\Admin\AutomaticEmails::class)->all()->keys() as $key) {
            app(AutomaticEmailSettings::class)->setEnabled($key, false, null);
        }
        Queue::fake();
        if ($signedIn) $this->actingAs($member, 'web');

        $this->postJson(route('webapp.feedback.store'), [
            'rating' => $rating, 'user_id' => 999999,
        ])->assertCreated()->assertExactJson(['saved' => true]);

        $feedback = FeedbackSubmission::sole();
        $this->assertSame($signedIn ? $member->id : null, $feedback->user_id);
        $sender = $signedIn ? 'Jamie Rivera' : 'A guest';
        foreach ($admins as $admin) {
            $notification = $admin->fresh()->unreadNotifications->sole();
            $this->assertSame(FeedbackSubmittedNotification::class, $notification->type);
            $this->assertSame('New feedback', $notification->data['title']);
            $this->assertSame('<strong>'.$sender.'</strong> submitted feedback: <strong>'.$label.'</strong>.', $notification->data['message']);
            $this->assertSame(route('admin.feedback.index'), $notification->data['url']);
            $this->assertSame($feedback->id, $notification->data['feedback_id']);
        }
        $this->assertDatabaseCount('notifications', 2);
        $this->assertCount(0, $member->notifications);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public static function submissions()
    {
        return [
            [false, 'bad', 'Bad'], [false, 'not_bad', 'Not bad'], [false, 'good', 'Good'],
            [true, 'bad', 'Bad'], [true, 'not_bad', 'Not bad'], [true, 'good', 'Good'],
        ];
    }

    public function test_feedback_alert_appears_in_the_existing_panel_and_history_and_opens_feedback()
    {
        $admin = create(Admin::class);
        $other = create(Admin::class);
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'not_bad'])->assertCreated();
        $notification = $admin->notifications()->firstOrFail();
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.notifications.index'))->assertOk()
            ->assertSee('admin-notification-dot')->assertSee('1 unread notification')
            ->assertSee('new feedback')->assertSee('A guest')->assertSee('Not bad');
        $history = $this->getJson(route('admin.notifications.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->assertSame($notification->id, $history->json('data.0.id'));

        $this->get(route('admin.notifications.read', [
            'ids' => [$notification->id], 'url' => $notification->data['url'],
        ]))->assertRedirect(route('admin.feedback.index'));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($other->notifications()->firstOrFail()->read_at);
        $this->get(route('admin.feedback.index'))->assertOk()->assertSee('Not bad');
    }

    public function test_member_names_are_escaped_in_the_shared_html_notification_and_notes_stay_in_feedback()
    {
        $admin = create(Admin::class);
        $member = $this->member(['first_name' => '<img src=x onerror=alert(1)>', 'last_name' => 'A&B']);
        $note = '<script>alert("private note")</script>';
        $this->actingAs($member, 'web')->postJson(route('webapp.feedback.store'), [
            'rating' => 'good', 'message' => $note,
        ])->assertCreated();

        $message = $admin->notifications()->firstOrFail()->data['message'];
        $this->assertStringContainsString(e($member->full_name), $message);
        $this->assertStringNotContainsString('<img', $message);
        $this->assertStringNotContainsString($note, $message);
        $this->assertSame($note, FeedbackSubmission::sole()->message);
        $this->actingAs($admin, 'admin')->get(route('admin.notifications.index'))->assertOk()
            ->assertSee(e($member->full_name), false)->assertDontSee($member->full_name, false);
    }

    public function test_invalid_feedback_creates_neither_a_submission_nor_an_alert()
    {
        create(Admin::class);
        $this->withExceptionHandling()->postJson(route('webapp.feedback.store'), ['rating' => 'great'])
            ->assertUnprocessable()->assertJsonValidationErrors('rating');
        $this->assertDatabaseCount('feedback_submissions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_failure_rolls_back_the_submission_and_every_admin_copy()
    {
        create(Admin::class);
        create(Admin::class);
        $delivered = 0;
        Event::listen(NotificationSent::class, function ($event) use (&$delivered) {
            if ($event->notification instanceof FeedbackSubmittedNotification && ++$delivered === 2) {
                throw new \RuntimeException('Simulated database notification failure');
            }
        });

        try {
            $this->postJson(route('webapp.feedback.store'), ['rating' => 'good']);
            $this->fail('The simulated notification failure should reach the request.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated database notification failure', $exception->getMessage());
        }
        $this->assertSame(2, $delivered);
        $this->assertDatabaseCount('feedback_submissions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }
}
