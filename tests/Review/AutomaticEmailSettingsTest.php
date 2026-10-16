<?php

namespace Tests\Review;

use App\{Admin, Composer, Performance, Piece, TutorialRequest, User};
use App\Mail\{AccountWelcome, AutomaticEmailSettings, SharePieceEmail};
use App\Mail\Admin\EmailPreview;
use App\Mail\Memberships\MembershipConfirmed;
use App\Mail\Performances\{PerformanceApprovedEmail, PerformanceSubmittedEmail};
use App\Mail\Shop\ConfirmPurchase;
use App\Mail\Tutorials\{NewRequestEmail, RequestPublishedEmail};
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\{ResetPassword, VerifyEmail};
use Illuminate\Mail\{Mailer, Mailable, Markdown, SendQueuedMailable};
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\{DB, Mail, Notification};

class AutomaticEmailSettingsTest extends ReviewTestCase
{
    private function settings()
    {
        return app(AutomaticEmailSettings::class);
    }

    private function mailer()
    {
        $transport = new ArrayTransport;
        $mailer = new Mailer('automatic-email-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('preview@example.com', 'PianoLIT');
        return [$mailer, $transport];
    }

    private function fixtures()
    {
        $user = new User(['id' => 0, 'first_name' => 'Jamie', 'last_name' => 'Parker', 'email' => 'jamie@example.com']);
        $piece = new Piece(['id' => 0, 'name' => 'Prelude', 'key' => 'E minor']);
        $piece->setRelation('composer', new Composer(['name' => 'Frédéric Chopin']))->setRelation('tags', collect());
        $request = (new TutorialRequest)->setRelation('user', $user)->setRelation('piece', $piece);
        return [$user, $piece, $request];
    }

    public function test_switches_save_explicit_states_and_preserve_defaults_across_admins_and_requests()
    {
        $admin = create(Admin::class);
        $this->actingAs($admin, 'admin');
        $this->assertFalse($this->settings()->enabled('account-welcome'));
        $this->assertTrue($this->settings()->enabled('reset-password'));
        $this->assertDatabaseCount('automatic_email_settings', 0);
        $url = route('admin.emails.sending', 'reset-password');
        foreach ([0, 0, 1, 1] as $state) {
            $this->patch($url, ['enabled' => $state])->assertRedirect(route('admin.emails.index'));
            $this->assertDatabaseHas('automatic_email_settings', ['email_key' => 'reset-password', 'enabled' => $state, 'updated_by_admin_id' => $admin->id]);
            $this->assertDatabaseCount('automatic_email_settings', 1);
            $this->assertSame((bool) $state, (new AutomaticEmailSettings)->enabled('reset-password'));
        }
        $this->actingAs(create(Admin::class), 'admin')->patch($url, ['enabled' => 0])->assertRedirect();
        $page = $this->get(route('admin.emails.index'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($page->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//button[@role="switch" and @aria-label="Automatic sending: Reset password" and @aria-checked="false"]'));
        $this->assertCount(15, $xpath->query('//button[@role="switch"]/ancestor::form/input[@name="_token"]'));
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_switch_writes_require_admin_csrf_a_known_key_and_a_boolean_state()
    {
        $this->withExceptionHandling();
        $url = route('admin.emails.sending', 'reset-password');
        $this->patchJson($url, ['enabled' => 0])->assertUnauthorized();
        $this->actingAs($this->fixtures()[0], 'web')->patchJson($url, ['enabled' => 0])->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->patchJson(route('admin.emails.sending', 'arbitrary-class'), ['enabled' => 0])->assertNotFound();
        foreach ([[], ['enabled' => []], ['enabled' => 'false'], ['enabled' => 2]] as $data) $this->patchJson($url, $data)->assertUnprocessable();
        $this->assertDatabaseCount('automatic_email_settings', 0);
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'email-switch'])->patchJson($url, ['enabled' => 0])->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'email-switch')->patch($url, ['enabled' => 0])->assertRedirect();
        $this->app['env'] = 'testing';
        $this->assertFalse($this->settings()->enabled('reset-password'));
    }

    public function test_every_transactional_mailable_is_blocked_at_delivery_and_reenabled_without_a_restart()
    {
        [$user, $piece, $request] = $this->fixtures();
        $purchase = new ConfirmPurchase((object) ['title' => 'Practice Companion']); $purchase->user = $user;
        $mails = [
            'account-welcome' => new AccountWelcome('Jamie'), 'purchase-confirmation' => $purchase,
            'membership-confirmation' => new MembershipConfirmed('Jamie', 'monthly', true, null),
            'tutorial-request' => new NewRequestEmail($request), 'tutorial-published' => new RequestPublishedEmail($request),
            'performance-submitted' => new PerformanceSubmittedEmail($piece), 'performance-approved' => new PerformanceApprovedEmail($piece),
            'piece-shared' => new SharePieceEmail($piece, $user),
        ];
        foreach ($mails as $key => $mail) {
            $mail->to('recipient@example.com');
            $this->assertSame($key, $this->settings()->mailableKey($mail));
            [$mailer, $transport] = $this->mailer();
            $this->settings()->setEnabled($key, false, null);
            $mail->send($mailer);
            $this->assertCount(0, $transport->messages(), $key);
            $this->settings()->setEnabled($key, true, null);
            $mail->send($mailer);
            $this->assertCount(1, $transport->messages(), $key);
        }
    }

    public function test_all_account_notification_variants_are_independent_and_other_channels_are_unchanged()
    {
        [$user] = $this->fixtures();
        [$mailer, $transport] = $this->mailer();
        $factory = \Mockery::mock(\Illuminate\Contracts\Mail\Factory::class);
        $factory->shouldReceive('mailer')->andReturn($mailer);
        $manager = new ChannelManager($this->app);
        $manager->extend('mail', function () use ($factory) { return new MailChannel($factory, app(Markdown::class)); });
        Notification::swap($manager);
        $notifications = [
            'verify-email' => new class extends VerifyEmail { protected function verificationUrl($notifiable) { return '#preview'; } },
            'reset-password' => new class('preview') extends ResetPassword { protected function resetUrl($notifiable) { return '#preview'; } },
        ];
        foreach (['google-link', 'google-reauth', 'apple-link', 'apple-reauth', 'apple-signup'] as $key) {
            [$provider, $purpose] = explode('-', $key);
            $notifications[$key] = new ConfirmGoogleAccount('#preview', $purpose, $provider);
        }
        foreach ($notifications as $key => $notification) {
            $this->assertSame($key, $this->settings()->notificationKey($notification));
            $this->settings()->setEnabled($key, false, null);
            $transport->flush();
            $manager->sendNow($user, $notification);
            $this->assertCount(0, $transport->messages(), $key);
            $this->assertNotSame(false, $this->app['events']->until(new NotificationSending($user, $notification, 'database')));
            $this->settings()->setEnabled($key, true, null);
            $manager->sendNow($user, $notification);
            $this->assertCount(1, $transport->messages(), $key);
        }
        $this->settings()->setEnabled('google-link', false, null);
        $transport->flush();
        $manager->sendNow($user, new ConfirmGoogleAccount('#preview', 'link', 'apple'));
        $this->assertCount(1, $transport->messages(), 'Disabling Google linking must not disable Apple linking.');
    }

    public function test_an_existing_queued_message_obeys_the_latest_setting_and_previews_still_send()
    {
        $mail = (new MembershipConfirmed('Jamie', 'monthly', false, null))->to('recipient@example.com');
        $serializedJob = serialize(new SendQueuedMailable($mail));
        $this->settings()->setEnabled('membership-confirmation', false, null);
        [$mailer, $transport] = $this->mailer();
        $factory = \Mockery::mock(\Illuminate\Contracts\Mail\Factory::class);
        $factory->shouldReceive('mailer')->andReturn($mailer);
        unserialize($serializedJob)->handle($factory);
        $this->assertCount(0, $transport->messages());
        $this->settings()->setEnabled('membership-confirmation', true, null);
        unserialize($serializedJob)->handle($factory);
        $this->assertCount(1, $transport->messages());

        $this->actingAs(create(Admin::class), 'admin');
        $this->settings()->setEnabled('membership-confirmation', false, null);
        $this->post(route('admin.emails.send'), ['email_key' => 'membership-confirmation', 'email' => 'preview@example.com'])->assertRedirect();
        Mail::assertSent(EmailPreview::class, 1);
        $this->get(route('admin.emails.preview', 'membership-confirmation'))->assertOk();
        $preview = (new EmailPreview('Test', '<p>Preview</p>', 'Preview'))->to('preview@example.com');
        $preview->send($mailer);
        $this->assertCount(2, $transport->messages());
        $unmanaged = new class extends Mailable { public function build() { return $this->subject('Other mail')->html('<p>Other</p>'); } };
        $unmanaged->to('other@example.com')->send($mailer);
        $this->assertCount(3, $transport->messages());
    }

    public function test_disabled_sources_do_not_enqueue_mail_and_keep_admin_notifications()
    {
        [$user, $piece, $request] = $this->fixtures();
        $this->actingAs($user, 'web');
        $admin = create(Admin::class); Notification::fake();
        $performance = (new Performance)->setRelation('piece', $piece);
        $purchase = (new \App\Merchandise\Purchase)->setRelation('item', new \App\Shop\eBook(['price' => 10, 'title' => 'Practice Companion']));
        $sources = [
            'tutorial-request' => [new \App\Listeners\Tutorials\NewRequestListener, new \App\Events\Tutorials\NewRequest($request)],
            'tutorial-published' => [new \App\Listeners\Tutorials\RequestPublishedListener, new \App\Events\Tutorials\RequestPublished($request)],
            'performance-submitted' => [new \App\Listeners\Performances\PerformanceSubmittedListener, new \App\Events\Performances\PerformanceSubmitted($performance)],
            'performance-approved' => [new \App\Listeners\Performances\PerformanceApprovedListener, new \App\Events\Performances\PerformanceApproved($performance)],
            'piece-shared' => [new \App\Listeners\PieceSharedListener, new \App\Events\PieceShared($piece, 'recipient@example.com')],
            'purchase-confirmation' => [new \App\Listeners\NewPurchaseListener, new \App\Events\PurchaseMade($purchase)],
        ];
        foreach ($sources as $key => [$listener, $event]) {
            $this->settings()->setEnabled($key, false, null);
            $listener->handle($event);
        }
        Mail::assertNothingSent(); Mail::assertNothingQueued();
        Notification::assertSentTo($admin, \App\Notifications\Tutorials\NewRequestNotification::class);
        Notification::assertSentTo($admin, \App\Notifications\Performances\PerformanceSubmittedNotification::class);
        Notification::assertSentTo($admin, \App\Notifications\PieceSharedNotification::class);
    }

    public function test_standalone_welcome_starts_off_and_can_be_enabled_without_joining_a_newsletter()
    {
        [$user] = $this->fixtures();
        $listener = new \App\Listeners\Users\SendAccountWelcome;
        $listener->handle(new Registered($user));
        Mail::assertNothingQueued();
        $this->settings()->setEnabled('account-welcome', true, null);
        $listener->handle(new Registered($user));
        Mail::assertQueued(AccountWelcome::class, function ($mail) use ($user) { return $mail->hasTo($user->email) && $mail->firstName === 'Jamie'; });
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertContains(\App\Listeners\Users\SendAccountWelcome::class, \Illuminate\Support\Arr::flatten($this->app->getProvider(\App\Providers\EventServiceProvider::class)->listens()));
    }
}
