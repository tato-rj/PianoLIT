<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Mail\Admin\EmailPreview;
use App\Services\Admin\AutomaticEmails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail, Notification, Queue};

class AdminEmailsTest extends ReviewTestCase
{
    private function admin()
    {
        $admin = create(Admin::class, ['email' => 'admin@example.com']);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_all_email_routes_require_an_admin_session()
    {
        $this->withExceptionHandling();
        foreach ([null, Model::withoutEvents(function () { return create(User::class); })] as $user) {
            if ($user) $this->actingAs($user, 'web');
            foreach (['index' => [], 'preview' => ['verify-email'], 'content' => ['verify-email']] as $action => $parameters) {
                $this->getJson(route('admin.emails.'.$action, $parameters))->assertUnauthorized();
            }
            $this->postJson(route('admin.emails.send'), ['email_key' => 'verify-email', 'email' => 'recipient@example.com'])->assertUnauthorized();
        }
        Mail::assertNothingSent();
    }

    public function test_catalog_lists_all_variants_and_shares_one_editable_recipient_form()
    {
        $admin = $this->admin();
        $response = $this->get(route('admin.emails.index'))->assertOk()->assertDontSee('Not currently sent')->assertSee('Membership confirmation')->assertSee('Sending');
        $xpath = $this->xpath($response);
        $this->assertCount(16, $xpath->query('//table[@id="automatic-emails"]/tbody/tr'));
        $this->assertCount(1, $xpath->query('//a[@href="'.route('admin.emails.index').'" and @aria-current="page"]'));
        $this->assertCount(1, $xpath->query('//ul[@id="settings" and contains(@class, "show")]'));
        $this->assertCount(1, $xpath->query('//form[@id="email-preview-send"]//input[@name="email" and @value="'.$admin->email.'"]'));
        $this->assertCount(1, $xpath->query('//form[@id="email-preview-send"]//input[@name="_token"]'));
        $this->assertCount(16, $xpath->query('//button[@form="email-preview-send" and @name="email_key"]'));
        $this->assertCount(16, $xpath->query('//table[@id="automatic-emails"]//a[@target="_blank" and @rel="noopener noreferrer"]'));
        $this->assertCount(14, $xpath->query('//button[@role="switch" and @aria-checked="true" and @value="0"]'));
        foreach (['Web app welcome', 'iOS app welcome'] as $name) {
            $this->assertCount(1, $xpath->query('//button[@role="switch" and @aria-checked="false" and @value="1" and @aria-label="Automatic sending: '.$name.'"]'));
        }
        $response->assertDontSee('data-admin-datatable')->assertHeader('Cache-Control', 'no-store, private');
        Mail::assertNothingSent();
    }

    public function test_every_browser_preview_renders_real_templates_with_no_account_or_billing_side_effects()
    {
        $this->admin();
        Notification::fake(); Queue::fake();
        DB::enableQueryLog(); DB::flushQueryLog();
        foreach (app(AutomaticEmails::class)->all() as $key => $email) {
            $url = route('admin.emails.preview', $key);
            $preview = $this->get($url)->assertOk()->assertSee(config('app.name'))
                ->assertDontSee('admin@example.com')->assertDontSee('admin-shell')->assertDontSee('css/admin.css')->assertDontSee('js/admin.js')
                ->assertHeader('Cache-Control', 'no-store, private');
            $this->assertCount(0, $this->xpath($preview)->query('//iframe | //form | //*[@id="admin-main"] | //*[@id="navbarResponsive"]'));
            $this->assertStringContainsString('sandbox;', $preview->headers->get('Content-Security-Policy'));
            $content = $this->get(route('admin.emails.content', $key))->assertOk()->assertSee(config('app.name'));
            $this->assertStringContainsString('sandbox;', $content->headers->get('Content-Security-Policy'));
            $this->assertStringNotContainsString('signature=', $content->getContent());
            $this->assertStringNotContainsString('/password/reset/', $content->getContent());
            $this->assertStringNotContainsString('unsubscribe', $content->getContent());
            $this->assertStringContainsString('/emails/'.$key.'/preview', $url, 'URL generator must be restored after rendering.');
            $this->assertSame('admin.localhost', parse_url(route('admin.emails.index'), PHP_URL_HOST));
        }
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
            $this->assertDoesNotMatchRegularExpression('/\b(users|pieces|password_resets|auth_link_challenges|memberships|purchases)\b/i', $query['query']);
        }
        Mail::assertNothingSent(); Queue::assertNothingPushed(); Notification::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_sample_account_links_are_inert_and_shop_and_brand_links_use_the_public_host()
    {
        $this->admin();
        $catalog = app(AutomaticEmails::class);
        foreach (['verify-email', 'reset-password', 'google-link', 'google-reauth', 'apple-link', 'apple-reauth', 'apple-signup', 'piece-shared'] as $key) {
            $sample = $catalog->render($key);
            $this->assertStringContainsString('href="#email-preview"', $sample['html']);
            $this->assertNotEmpty($sample['text']);
        }
        $sample = $catalog->render('purchase-confirmation');
        $this->assertStringContainsString('http://localhost/my-profile', $sample['html']);
        $this->assertStringNotContainsString('admin.localhost', $sample['html']);
        $this->assertStringContainsString('Jamie', $sample['html']);
        $this->assertStringContainsString('Chopin', $catalog->render('tutorial-request')['html']);
    }

    public function test_each_send_uses_only_the_entered_recipient_and_an_immediate_rendered_preview()
    {
        $this->admin(); Notification::fake(); Queue::fake();
        foreach (app(AutomaticEmails::class)->all() as $key => $email) {
            Mail::fake();
            $this->post(route('admin.emails.send'), ['email_key' => $key, 'email' => 'chosen@example.com', 'return_to' => 'preview'])
                ->assertRedirect(route('admin.emails.index'))->assertSessionHas('status', 'Preview sent to chosen@example.com.');
            Mail::assertSent(EmailPreview::class, function ($mail) use ($key) {
                $sample = app(AutomaticEmails::class)->render($key);
                $this->assertSame('[Preview] '.$sample['subject'], $mail->subject);
                // The existing tutorial-ready template chooses a random emoji per render.
                $withoutEmoji = function ($text) { return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text); };
                $this->assertSame($withoutEmoji($sample['text']), $withoutEmoji((string) $mail->textView));
                // Explicit transport: this app's legacy mail.driver overrides named mailers.
                $transport = new \Illuminate\Mail\Transport\ArrayTransport;
                $mailer = new \Illuminate\Mail\Mailer('preview-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
                $mailer->alwaysFrom('preview@example.com', 'PianoLIT');
                $mail->send($mailer);
                $delivered = $transport->messages()->last();
                $this->assertSame($withoutEmoji($sample['html']), $withoutEmoji($delivered->getBody()));
                $this->assertSame((string) $mail->textView, $delivered->getChildren()[0]->getBody());
                return $mail->hasTo('chosen@example.com') && count($mail->to) === 1 && !$mail->cc && !$mail->bcc;
            });
            Mail::assertSent(EmailPreview::class, 1); Mail::assertNothingQueued();
            // Exercise each template without exhausting the intentionally shared limiter.
            $this->app['cache']->flush();
        }
        Notification::assertNothingSent(); Queue::assertNothingPushed(); Http::assertNothingSent();
    }

    public function test_unknown_templates_invalid_recipients_and_csrf_fail_before_sending()
    {
        $this->admin(); $this->withExceptionHandling();
        foreach (['missing', 'App-Mail-Gift', 'membership-ended'] as $key) {
            $this->getJson(route('admin.emails.preview', $key))->assertNotFound();
            $this->getJson(route('admin.emails.content', $key))->assertNotFound();
        }
        foreach ([[], ['email' => 'bad'], ['email' => ['a@example.com']], ['email' => 'one@example.com,two@example.com'],
            ['email_key' => 'missing'], ['email_key' => ['reset-password']], ['return_to' => 'https://example.com']] as $invalid) {
            $data = $invalid ? array_merge(['email_key' => 'verify-email', 'email' => 'test@example.com'], $invalid) : [];
            $this->postJson(route('admin.emails.send'), $data)->assertUnprocessable();
        }
        $this->app['cache']->flush();
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'email-csrf'])->postJson(route('admin.emails.send'), ['email_key' => 'verify-email', 'email' => 'test@example.com'])->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'email-csrf')->post(route('admin.emails.send'), ['email_key' => 'verify-email', 'email' => 'test@example.com'])->assertRedirect(route('admin.emails.index'));
        $this->app['env'] = 'testing';
        Mail::assertSent(EmailPreview::class, 1);
    }

    public function test_send_failure_keeps_the_recipient_and_does_not_report_success()
    {
        $this->admin();
        $pending = \Mockery::mock();
        $pending->shouldReceive('send')->once()->andThrow(new \RuntimeException('mail service unavailable'));
        Mail::shouldReceive('to')->with('chosen@example.com')->once()->andReturn($pending);
        $this->post(route('admin.emails.send'), ['email_key' => 'reset-password', 'email' => 'chosen@example.com'])
            ->assertRedirect(route('admin.emails.index'))->assertSessionHas('error')->assertSessionMissing('status')->assertSessionHas('_old_input.email', 'chosen@example.com');
        $this->get(route('admin.emails.index'))->assertOk()->assertSee('chosen@example.com');
    }

    public function test_preview_sends_are_rate_limited_and_malformed_old_input_is_safe()
    {
        $this->admin(); $this->withExceptionHandling();
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('admin.emails.send'), ['email_key' => 'verify-email', 'email' => 'test@example.com'])->assertRedirect();
        }
        $this->postJson(route('admin.emails.send'), ['email_key' => 'verify-email', 'email' => 'test@example.com'])->assertStatus(429);
        Mail::assertSent(EmailPreview::class, 10);
        $this->withSession(['_old_input' => ['email' => ['invalid']]])->get(route('admin.emails.index'))->assertOk()->assertSee('admin@example.com');
    }
}
