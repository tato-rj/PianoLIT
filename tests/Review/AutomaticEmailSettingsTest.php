<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, SynthesiaRequest, User};
use App\Mail\{AccountWelcome, AutomaticEmailSettings, IosAccountWelcome, WebsiteAccountWelcome};
use App\Mail\Admin\EmailPreview;
use App\Mail\Memberships\MembershipConfirmed;
use App\Mail\Shop\ConfirmPurchase;
use App\Mail\SynthesiaRequests\{NewRequestEmail, RequestPublishedEmail};
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
        $request = (new SynthesiaRequest)->setRelation('user', $user)->setRelation('piece', $piece);
        return [$user, $piece, $request];
    }

    public function test_switches_save_explicit_states_and_preserve_defaults_across_admins_and_requests()
    {
        $admin = create(Admin::class);
        $this->actingAs($admin, 'admin');
        $this->assertFalse($this->settings()->enabled('account-welcome'));
        $this->assertFalse($this->settings()->enabled('account-welcome-ios'));
        $this->assertFalse($this->settings()->enabled('account-welcome-website'));
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
        $this->assertCount(1, $xpath->query('//input[@role="switch" and @aria-label="Automatic sending: Reset password" and not(@checked)]'));
        $this->assertCount(18, $xpath->query('//input[@role="switch"]/ancestor::form/input[@name="_token"]'));
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
            'admin-weekly-snapshot' => new \App\Mail\Admin\AdminReport((new \App\Services\Admin\WeeklySnapshotPreview)->data(), new Admin(['name' => 'Preview Admin'])),
            'account-welcome-website' => new WebsiteAccountWelcome('Jamie'), 'account-welcome' => new AccountWelcome('Jamie'), 'account-welcome-ios' => new IosAccountWelcome('Jamie'), 'purchase-confirmation' => $purchase,
            'membership-confirmation' => new MembershipConfirmed('Jamie', 'monthly', true, null),
            'synthesia-request' => new NewRequestEmail($request), 'synthesia-published' => new RequestPublishedEmail($request),
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
        $purchase = (new \App\Merchandise\Purchase)->setRelation('item', new \App\Shop\eBook(['price' => 10, 'title' => 'Practice Companion']));
        $sources = [
            'synthesia-request' => [new \App\Listeners\SynthesiaRequests\NewRequestListener, new \App\Events\SynthesiaRequests\NewRequest($request)],
            'synthesia-published' => [new \App\Listeners\SynthesiaRequests\RequestPublishedListener, new \App\Events\SynthesiaRequests\RequestPublished($request)],
            'purchase-confirmation' => [new \App\Listeners\NewPurchaseListener, new \App\Events\PurchaseMade($purchase)],
        ];
        foreach ($sources as $key => [$listener, $event]) {
            $this->settings()->setEnabled($key, false, null);
            $listener->handle($event);
        }
        Mail::assertNothingSent(); Mail::assertNothingQueued();
        Notification::assertSentTo($admin, \App\Notifications\SynthesiaRequests\NewRequestNotification::class);
    }

    public function test_standalone_welcome_starts_off_and_can_be_enabled_without_joining_a_newsletter()
    {
        [$user] = $this->fixtures();
        $listener = new \App\Listeners\Users\SendAccountWelcome;
        $user->origin = 'webapp';
        $listener->handle(new Registered($user));
        Mail::assertNothingQueued();
        $this->settings()->setEnabled('account-welcome', true, null);
        $listener->handle(new Registered($user));
        Mail::assertQueued(AccountWelcome::class, function ($mail) use ($user) { return $mail->hasTo($user->email) && $mail->firstName === 'Jamie'; });
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertContains(\App\Listeners\Users\SendAccountWelcome::class, \Illuminate\Support\Arr::flatten($this->app->getProvider(\App\Providers\EventServiceProvider::class)->listens()));
    }

    public function test_welcome_variants_share_the_minimal_layout_and_highlight_the_other_platforms()
    {
        $catalog = app(\App\Services\Admin\AutomaticEmails::class);
        foreach (['account-welcome-website' => 5, 'account-welcome' => 4, 'account-welcome-ios' => 4] as $key => $buttonCount) {
            $sample = $catalog->render($key);
            $this->assertSame('Welcome to PianoLIT!', $sample['subject']);
            $dom = new \DOMDocument; @$dom->loadHTML($sample['html']);
            $xpath = new \DOMXPath($dom);
            $this->assertCount(4, $xpath->query('//h2'));
            $this->assertCount($key === 'account-welcome-website' ? 17 : 16, $xpath->query('//img[@alt and @width]'));
            $buttons = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " welcome-button ") and not(ancestor::td[contains(@class,"welcome-footer")])]');
            $this->assertCount($buttonCount, $buttons);
            $this->assertSame(route('webapp.explore'), $buttons[$buttonCount - 1]->getAttribute('href'));
            $this->assertSame('Browse the PianoLIT library', $buttons[$buttonCount - 1]->textContent);
            $appUrls = array_map(function ($button) { return $button->getAttribute('href'); }, iterator_to_array($buttons));
            $this->assertSame($key !== 'account-welcome', in_array(route('webapp.discover'), $appUrls));
            $this->assertSame($key !== 'account-welcome-ios', in_array(config('app.stores.ios'), $appUrls));
            foreach ($xpath->query('//h2 | //h3') as $heading) {
                $this->assertStringContainsString($heading->textContent, $sample['text']);
            }
            foreach (['Welcome, Jamie', 'same', 'account', 'theory tools', 'quiz', 'blog', 'contact@pianolit.com'] as $copy) {
                $this->assertStringContainsString($copy, $sample['html']);
                $this->assertStringContainsString($copy, $sample['text']);
            }
            $this->assertStringContainsString('background-color: #0055fe', $sample['html']);
            $this->assertStringNotContainsString('<table', $sample['text']);
            $this->assertStringNotContainsString('<br', $sample['text']);
        }
    }

    public function test_welcome_assets_are_absolute_on_admin_and_normalize_a_schemeless_app_url()
    {
        [$mailer] = $this->mailer();
        $this->app->instance('mailer', $mailer);
        config(['app.url' => 'pianolit.example']);
        $this->app['url']->setRequest(\Illuminate\Http\Request::create('https://admin.pianolit.example'));
        $html = (new WebsiteAccountWelcome('<Jamie>'))->render();
        $this->assertStringContainsString('Welcome, &lt;Jamie&gt;', $html);
        $this->assertStringContainsString('href="https://pianolit.example"', $html);
        $this->assertStringContainsString('src="https://pianolit.example/images/emails/welcome/', $html);
        $this->assertStringNotContainsString('admin.pianolit.example', $html);
    }

    public function test_edits_to_a_welcome_view_update_html_and_plain_text_without_changing_other_variants()
    {
        $directory = sys_get_temp_dir().'/pianolit-email-view-'.bin2hex(random_bytes(6));
        mkdir($directory.'/emails/welcome', 0700, true);
        $file = $directory.'/emails/welcome/webapp.blade.php';
        $view = file_get_contents(resource_path('views/emails/welcome/webapp.blade.php'));
        file_put_contents($file, str_replace(
            ['PianoLIT is where pianists discover new pieces and find inspiration', 'Take PianoLIT with you.', 'Get the iOS app'],
            ['Your edited welcome introduction.', 'Your edited app paragraph.', 'Your edited button'], $view
        ));
        $finder = app('view')->getFinder();
        $paths = $finder->getPaths();
        $finder->prependLocation($directory);
        $finder->flush();
        try {
            $sample = app(\App\Services\Admin\AutomaticEmails::class)->render('account-welcome');
            foreach (['html', 'text'] as $format) {
                foreach (['Your edited welcome introduction.', 'Your edited app paragraph.', 'Your edited button'] as $copy) {
                    $this->assertStringContainsString($copy, $sample[$format]);
                }
            }
            foreach (['account-welcome-website', 'account-welcome-ios'] as $key) {
                $other = app(\App\Services\Admin\AutomaticEmails::class)->render($key);
                $this->assertStringNotContainsString('Your edited', $other['html']);
                $this->assertStringNotContainsString('Your edited', $other['text']);
            }
        } finally {
            $finder->setPaths($paths);
            $finder->flush();
            unlink($file); rmdir($directory.'/emails/welcome'); rmdir($directory.'/emails'); rmdir($directory);
        }
    }

    public function test_local_welcome_previews_use_the_available_scheme_for_all_three_variants()
    {
        foreach (['http', 'https'] as $scheme) {
            config(['app.url' => 'pianolit.test']);
            $this->app->instance('request', \Illuminate\Http\Request::create($scheme.'://admin.pianolit.test'));
            foreach (['account-welcome-website', 'account-welcome', 'account-welcome-ios'] as $key) {
                $sample = app(\App\Services\Admin\AutomaticEmails::class)->render($key);
                $dom = new \DOMDocument; @$dom->loadHTML($sample['html']);
                $images = (new \DOMXPath($dom))->query('//img');
                $this->assertCount($key === 'account-welcome-website' ? 17 : 16, $images);
                foreach ($images as $image) {
                    $url = $image->getAttribute('src');
                    $this->assertStringStartsWith($scheme.'://pianolit.test/images/emails/', $url);
                    if (basename(parse_url($url, PHP_URL_PATH)) !== 'youtube.png') {
                        $this->assertNotEmpty(parse_url($url, PHP_URL_QUERY));
                    }
                    $this->assertFileExists(public_path(parse_url($url, PHP_URL_PATH)));
                }
                $this->assertStringContainsString('href="'.$scheme.'://pianolit.test"', $sample['html']);
                $this->assertStringContainsString($scheme.'://pianolit.test', $sample['text']);
            }
        }
    }

    public function test_welcome_preserves_explicit_schemes_and_production_https_fallback()
    {
        $this->app->instance('request', \Illuminate\Http\Request::create('http://admin.pianolit.test'));
        foreach (['http://pianolit.test', 'https://pianolit.test', 'https://pianolit.com', 'pianolit.com'] as $configured) {
            config(['app.url' => $configured]);
            $expected = $configured === 'pianolit.com' ? 'https://pianolit.com' : $configured;
            $html = app(\App\Services\Admin\AutomaticEmails::class)->render('account-welcome-website')['html'];
            $this->assertStringContainsString('href="'.$expected.'"', $html);
            $this->assertStringContainsString('src="'.$expected.'/images/emails/welcome/', $html);
        }
    }

    public function test_a_welcome_does_not_change_the_next_email_theme_in_a_long_lived_worker()
    {
        [$mailer] = $this->mailer();
        $this->app->instance('mailer', $mailer);
        $ordinary = new \App\Mail\ProfileEmailChange('#email-preview');
        $before = $ordinary->render();
        (new WebsiteAccountWelcome('Jamie'))->render();
        $this->assertSame($before, (new \App\Mail\ProfileEmailChange('#email-preview'))->render());
        $membership = (new MembershipConfirmed('Jamie', 'monthly', false, null))->render();
        $this->assertStringContainsString('welcome-wrapper', $membership);
        $this->assertSame($before, (new \App\Mail\ProfileEmailChange('#email-preview'))->render());
    }

    public function test_welcome_routing_uses_signup_origin_and_each_sending_switch_independently()
    {
        [$user] = $this->fixtures();
        $listener = new \App\Listeners\Users\SendAccountWelcome;
        foreach ([[false, false, false], [true, false, false], [false, true, false], [false, false, true], [true, true, false], [true, false, true], [false, true, true], [true, true, true]] as [$webEnabled, $iosEnabled, $websiteEnabled]) {
            $this->settings()->setEnabled('account-welcome', $webEnabled, null);
            $this->settings()->setEnabled('account-welcome-ios', $iosEnabled, null);
            $this->settings()->setEnabled('account-welcome-website', $websiteEnabled, null);
            foreach (['web', 'webapp', 'ios', 'android', null] as $origin) {
                Mail::fake();
                $user->origin = $origin;
                request()->merge(['origin' => $origin === 'ios' ? 'webapp' : 'ios']);
                $listener->handle(new Registered($user));
                $expectedClass = $origin === 'ios' && $iosEnabled ? IosAccountWelcome::class
                    : ($origin === 'web' && $websiteEnabled ? WebsiteAccountWelcome::class
                    : ($origin === 'webapp' && $webEnabled ? AccountWelcome::class : null));
                if ($expectedClass) {
                    Mail::assertQueued($expectedClass, function ($mail) use ($user, $expectedClass) {
                        return get_class($mail) === $expectedClass && $mail->hasTo($user->email);
                    });
                    Mail::assertQueued($expectedClass, 1);
                    foreach ([AccountWelcome::class, IosAccountWelcome::class, WebsiteAccountWelcome::class] as $otherClass) {
                        if ($otherClass === $expectedClass) continue;
                        Mail::assertNotQueued($otherClass, function ($mail) use ($otherClass) { return get_class($mail) === $otherClass; });
                    }
                } else {
                    Mail::assertNothingQueued();
                }
            }
        }
    }

    public function test_ios_api_signup_queues_its_own_welcome_without_changing_the_mobile_response()
    {
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
        Notification::fake();
        $this->settings()->setEnabled('account-welcome', true, null);
        $this->settings()->setEnabled('account-welcome-ios', true, null);
        $response = $this->postJson(route('api.users.store'), [
            'first_name' => 'Mobile', 'last_name' => 'Pianist', 'email' => 'mobile@example.com',
            'password' => 'mobile-password', 'password_confirmation' => 'mobile-password', 'origin' => 'ios',
        ])->assertCreated();
        $user = User::where('email', 'mobile@example.com')->firstOrFail();
        $this->assertSame('ios', $user->origin);
        $response->assertExactJson(\Illuminate\Support\Arr::only($user->fresh()->toArray(), [
            'first_name', 'last_name', 'email', 'locale', 'origin', 'updated_at', 'created_at', 'id', 'full_name', 'last_active_at', 'logs_count',
        ]));
        Mail::assertQueued(IosAccountWelcome::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->firstName === 'Mobile';
        });
        Mail::assertQueued(IosAccountWelcome::class, 1);
        Mail::assertNotQueued(AccountWelcome::class, function ($mail) { return get_class($mail) === AccountWelcome::class; });
    }
}
