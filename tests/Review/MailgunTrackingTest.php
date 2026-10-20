<?php

namespace Tests\Review;

use App\{EmailCampaignReport, EmailList, EmailLog};
use App\Services\MailgunEvents;
use Illuminate\Support\Facades\DB;

class MailgunTrackingTest extends ReviewTestCase
{
    private function email($attributes = [])
    {
        return EmailLog::create($attributes + [
            'message_id' => 'tracked@swift.generated', 'recipient' => 'reader@example.test',
            'list_id' => 'free-pick.1792420806', 'sender_type' => EmailList::class, 'sender_id' => 1,
        ]);
    }

    private function event($type = 'delivered', $attributes = [])
    {
        return $attributes + ['id' => 'event-'.$type, 'event' => $type, 'timestamp' => now()->subMinute()->timestamp,
            'recipient' => 'reader@example.test', 'message' => ['headers' => ['message-id' => 'tracked@swift.generated']]];
    }

    private function postEvent($event, $secondsAgo = 60, $token = 'test-token', $key = 'webhook-key')
    {
        $timestamp = (string) (time() - $secondsAgo);
        config(['services.mailgun.webhook_signing_key' => 'webhook-key', 'services.mailgun.secret' => 'different-sending-api-key']);
        return $this->postJson(route('webhooks.mailgun'), ['signature' => [
            'token' => $token, 'timestamp' => $timestamp, 'signature' => hash_hmac('sha256', $timestamp.$token, $key),
        ], 'event-data' => $event]);
    }

    public function test_valid_events_use_the_signing_key_accept_delays_and_update_raw_records_and_summaries()
    {
        $log = $this->email();
        $event = $this->event();
        $at = $event['timestamp'];
        $this->postEvent($event)->assertOk();
        $this->assertSame(1, (int) $log->fresh()->unique_delivered);
        $this->assertSame($at, $log->fresh()->delivered_at->timestamp);
        $report = EmailCampaignReport::first();
        $this->assertSame(1, $report->emails_count);
        $this->assertSame(1, $report->delivered_count);
        foreach (['opened', 'clicked', 'failed'] as $type) $this->postEvent($this->event($type), 120, 'token-'.$type)->assertOk();
        $report = $report->fresh();
        foreach (['delivered_count', 'failed_count', 'opens_count', 'clicks_count'] as $column) $this->assertSame(1, $report->{$column});
    }

    public function test_duplicate_webhook_and_recovery_events_are_idempotent_but_distinct_opens_count()
    {
        $log = $this->email();
        $this->postEvent($this->event('opened'))->assertOk();
        $this->postEvent($this->event('opened'))->assertOk();
        $this->postEvent($this->event('opened'), 60, 'new-delivery-token')->assertOk();
        $this->assertSame(1, (int) $log->fresh()->opened);
        $this->postEvent($this->event('opened', ['id' => 'second-open']), 60, 'second-open-token')->assertOk();
        $this->assertSame(2, (int) $log->fresh()->opened);
        $this->assertSame(1, EmailCampaignReport::first()->opens_count);
        $this->assertDatabaseCount('mailgun_event_receipts', 2);
        $service = app(MailgunEvents::class);
        $this->assertSame('duplicate', $service->apply($service->normalize($this->event('opened')), false, true)['status']);
        $this->assertSame(2, (int) $log->fresh()->opened);
    }

    public function test_message_id_brackets_and_recipient_case_are_normalized_without_guessing_a_recipient()
    {
        $this->withExceptionHandling();
        $log = $this->email(['message_id' => '<tracked@swift.generated>', 'recipient' => 'Reader@Example.Test']);
        $this->email(['recipient' => 'other@example.test']);
        $this->postEvent($this->event())->assertOk();
        $this->assertSame(1, (int) $log->fresh()->unique_delivered);
        $this->assertSame(0, (int) EmailLog::where('recipient', 'other@example.test')->first()->unique_delivered);
        $this->postEvent($this->event('opened', ['recipient' => 'missing@example.test']), 60, 'unmatched-token')->assertOk();
        $this->email(['recipient' => 'Reader@Example.Test']);
        $this->postEvent($this->event('clicked'), 60, 'ambiguous-token')->assertStatus(503);
        $this->assertDatabaseCount('mailgun_event_receipts', 1);
    }

    public function test_invalid_signatures_old_or_future_requests_and_malformed_payloads_do_not_write()
    {
        $this->email(); $this->withExceptionHandling();
        $this->postEvent($this->event(), 60, 'token', 'different-sending-api-key')->assertNotFound();
        $this->postEvent($this->event(), 86401)->assertNotFound();
        $this->postEvent($this->event(), -301)->assertNotFound();
        foreach ([[], ['signature' => ['token' => [], 'timestamp' => [], 'signature' => []]], ['signature' => 'bad']] as $data) {
            $this->postJson(route('webhooks.mailgun'), $data)->assertNotFound();
        }
        foreach ([['id' => []], ['timestamp' => null], ['recipient' => []], ['message' => []]] as $invalid) {
            $this->postEvent($this->event('delivered', $invalid))->assertUnprocessable();
        }
        $this->assertSame(0, (int) EmailLog::first()->unique_delivered);
        $this->assertDatabaseCount('mailgun_event_receipts', 0);
    }

    public function test_signature_token_reuse_and_query_string_substitution_cannot_update_another_event()
    {
        $log = $this->email(); $this->withExceptionHandling();
        $this->postEvent($this->event('opened'))->assertOk();
        $this->postEvent($this->event('clicked'))->assertUnprocessable();
        $this->assertSame(0, (int) $log->fresh()->clicked);
        $timestamp = (string) time();
        $query = http_build_query(['signature' => ['token' => 'query-token', 'timestamp' => $timestamp,
            'signature' => hash_hmac('sha256', $timestamp.'query-token', 'webhook-key')]]);
        $this->postJson(route('webhooks.mailgun').'?'.$query, ['event-data' => $this->event('clicked')])->assertNotFound();
    }

    public function test_temporary_failures_unsupported_events_and_excluded_accounts_are_not_collected()
    {
        $log = $this->email();
        $this->postEvent($this->event('failed', ['severity' => 'temporary']))->assertOk();
        $this->postEvent(['event' => 'complained'])->assertOk();
        foreach ([' ARTHURVILLAR@gmail.com ', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $email) {
            $this->postEvent($this->event('opened', ['recipient' => trim($email)]))->assertOk();
        }
        $this->assertDatabaseCount('mailgun_event_receipts', 0);
        $this->assertSame(0, (int) $log->fresh()->unique_failed);
    }

    public function test_recovery_dry_run_does_not_write_and_recovery_preserves_existing_counts_and_event_time()
    {
        $log = $this->email(['opened' => 5, 'unique_opened' => 1]);
        $service = app(MailgunEvents::class);
        $event = $service->normalize($this->event('opened', ['delivered-at' => now()->subDay()->toRfc2822String()]));
        $this->assertSame('matched', $service->apply($event, true, true)['status']);
        $this->assertDatabaseCount('mailgun_event_receipts', 0);
        $this->assertSame('applied', $service->apply($event, false, true)['status']);
        $this->assertSame(5, (int) $log->fresh()->opened);
        $this->assertSame(1, (int) $log->fresh()->unique_delivered);
        $this->assertSame(1, EmailCampaignReport::first()->opens_count);
        $this->assertSame(1, EmailCampaignReport::first()->delivered_count);
        $this->assertSame('unmatched', $service->apply($service->normalize($this->event('clicked')), true, true, null, ['another-campaign'])['status']);
        $failed = $service->normalize($this->event('failed', ['delivered-at' => now()->subDay()->timestamp]));
        $this->assertSame(['failed'], $service->apply($failed, true, true)['new_flags']);
    }

    public function test_event_and_summary_changes_roll_back_together_and_the_event_can_be_retried()
    {
        $log = $this->email();
        DB::unprepared("CREATE TRIGGER fail_email_update BEFORE UPDATE ON email_logs BEGIN SELECT RAISE(ABORT, 'forced tracking failure'); END");
        try {
            $this->postEvent($this->event());
            $this->fail('Expected email update failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced tracking failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('mailgun_event_receipts', 0);
        $this->assertSame(0, EmailCampaignReport::first()->delivered_count);
        DB::unprepared('DROP TRIGGER fail_email_update');
        $this->postEvent($this->event())->assertOk();
        $this->assertSame(1, (int) $log->fresh()->unique_delivered);
    }

    public function test_real_campaign_messages_have_a_stable_provider_tag_but_previews_and_exclusions_do_not()
    {
        $list = EmailList::create(['name' => 'Test', 'description' => 'Test campaign']);
        $subscription = \App\Subscription::create(['email' => 'reader@example.test']);
        $transport = new \Illuminate\Mail\Transport\ArrayTransport;
        $mailer = new \Illuminate\Mail\Mailer('tag-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('preview@example.test', 'PianoLIT');
        foreach ([$subscription, null, \App\Subscription::create(['email' => 'elena.dare@gmail.com'])] as $recipient) {
            $mail = new \App\Mail\TestEmail('test.1792420806', $recipient);
            $mail->to($recipient ? $recipient->email : 'preview@example.test')->send($mailer);
            $header = $transport->messages()->last()->getHeaders()->get('X-Mailgun-Tag');
            if ($recipient === $subscription) {
                $this->assertSame(MailgunEvents::campaignTag('test.1792420806'), $header->getFieldBody());
            } else {
                $this->assertNull($header);
            }
        }
        $this->assertDatabaseCount('email_logs', 1);
    }

    public function test_sending_tracking_record_and_summary_creation_roll_back_together()
    {
        EmailList::create(['name' => 'Test', 'description' => 'Test campaign']);
        $subscription = \App\Subscription::create(['email' => 'reader@example.test']);
        $transport = new \Illuminate\Mail\Transport\ArrayTransport;
        $mailer = new \Illuminate\Mail\Mailer('tag-rollback-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('preview@example.test', 'PianoLIT');
        DB::unprepared("CREATE TRIGGER fail_report_create BEFORE INSERT ON email_campaign_reports BEGIN SELECT RAISE(ABORT, 'forced report creation failure'); END");
        try {
            (new \App\Mail\TestEmail('test.1792420806', $subscription))->to($subscription->email)->send($mailer);
            $this->fail('Expected report creation failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced report creation failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('email_logs', 0);
        $this->assertDatabaseCount('email_campaign_reports', 0);
        $this->assertCount(0, $transport->messages());
    }
}
