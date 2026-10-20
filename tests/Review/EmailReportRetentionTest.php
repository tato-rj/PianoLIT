<?php

namespace Tests\Review;

use App\Services\{EmailCampaignReports, EmailReportRetention, MailgunEvents};
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\{Artisan, DB, Http, Mail};

class EmailReportRetentionTest extends ReviewTestCase
{
    public $mockConsoleOutput = false;
    private $sequence = 0;

    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-05-31 03:00:00'));
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function report($list, $sentAt)
    {
        return DB::table('email_campaign_reports')->insertGetId([
            'list_id' => $list, 'name' => 'Free Pick', 'sent_at' => $sentAt,
            'emails_count' => 10, 'delivered_count' => 8, 'opens_count' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function log($list, $createdAt)
    {
        return DB::table('email_logs')->insertGetId([
            'message_id' => 'message-'.(++$this->sequence).'@test', 'list_id' => $list,
            'sender_type' => \App\EmailList::class, 'sender_id' => 1,
            'recipient' => 'reader@example.test', 'created_at' => $createdAt, 'updated_at' => now(),
        ]);
    }

    private function receipt($log, $createdAt = null)
    {
        $key = hash('sha256', 'receipt-'.(++$this->sequence));
        DB::table('mailgun_event_receipts')->insert([
            'event_key' => $key, 'email_log_id' => $log, 'event' => 'opened',
            'occurred_at' => $createdAt ?? now(), 'created_at' => $createdAt ?? now(),
        ]);
        return $key;
    }

    public function test_preview_is_read_only_and_batched_cleanup_preserves_complete_recent_campaigns_and_subscribers()
    {
        $cutoff = now()->subMonthsNoOverflow(3);
        $old = $cutoff->copy()->subSecond();
        $this->report('expired', $old);
        $this->report('expired-snapshot', $old);
        $recentReport = $this->report('recent', $cutoff);
        foreach ([$this->log('expired', $old), $this->log('expired', now()),
            $this->log('legacy-without-summary', $old), $this->log(null, $old)] as $id) {
            $this->receipt($id);
        }
        // Even a legacy row timestamp must not punch holes in a retained campaign.
        $recentLog = $this->log('recent', $old);
        $recentReceipt = $this->receipt($recentLog, $old);
        $boundaryLog = $this->log(null, $cutoff);
        $this->receipt($boundaryLog);
        $this->receipt(999999); // Orphan left by an earlier manual report deletion.
        $list = \App\EmailList::create(['name' => 'Retention test', 'description' => 'Keep this list']);
        $subscriber = \App\Subscription::create(['email' => 'subscriber@example.test']);
        $subscriber->lists()->attach($list);

        DB::enableQueryLog();
        $preview = app(EmailReportRetention::class)->run($cutoff);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame(['reports' => 2, 'logs' => 4, 'receipts' => 5], $preview);
        foreach ($queries as $query) $this->assertMatchesRegularExpression('/^select\b/i', $query['query']);
        $this->assertDatabaseCount('email_logs', 6);

        $this->assertSame($preview, app(EmailReportRetention::class)->run($cutoff, true, 1));
        $this->assertDatabaseCount('email_campaign_reports', 1);
        $this->assertDatabaseHas('email_campaign_reports', ['id' => $recentReport, 'emails_count' => 10, 'opens_count' => 3]);
        $this->assertDatabaseCount('email_logs', 2);
        $this->assertDatabaseHas('email_logs', ['id' => $recentLog]);
        $this->assertDatabaseHas('email_logs', ['id' => $boundaryLog]);
        $this->assertDatabaseCount('mailgun_event_receipts', 2);
        $this->assertDatabaseHas('mailgun_event_receipts', ['event_key' => $recentReceipt]);
        $this->assertDatabaseHas('email_lists', ['id' => $list->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscriber->id]);
        $this->assertTrue($subscriber->lists()->where('email_lists.id', $list->id)->exists());
        $this->assertSame(['reports' => 0, 'logs' => 0, 'receipts' => 0], app(EmailReportRetention::class)->run($cutoff, true, 1));

        $events = app(MailgunEvents::class);
        $event = $events->normalize(['id' => 'recent-open', 'event' => 'opened', 'timestamp' => now()->timestamp,
            'recipient' => 'reader@example.test', 'message' => ['headers' => [
                'message-id' => DB::table('email_logs')->where('id', $recentLog)->value('message_id'),
            ]]]);
        $this->assertSame('applied', $events->apply($event)['status']);
        $this->assertSame('duplicate', $events->apply($event)['status']);
        $this->assertDatabaseHas('email_campaign_reports', ['id' => $recentReport, 'emails_count' => 10, 'opens_count' => 4]);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Http::assertNothingSent();
    }

    public function test_command_uses_three_calendar_months_without_month_end_overflow_and_defaults_to_preview()
    {
        $expired = $this->report('expired', '2026-02-28 02:59:59');
        $retained = $this->report('boundary', '2026-02-28 03:00:00');
        $this->assertSame(0, Artisan::call('email-reports:prune'));
        $output = Artisan::output();
        $this->assertStringContainsString('PREVIEW ONLY', $output);
        $this->assertStringContainsString('2026-02-28 03:00:00', $output);
        $this->assertDatabaseHas('email_campaign_reports', ['id' => $expired]);
        $this->assertSame(0, Artisan::call('email-reports:prune', ['--execute' => true]));
        $this->assertDatabaseMissing('email_campaign_reports', ['id' => $expired]);
        $this->assertDatabaseHas('email_campaign_reports', ['id' => $retained]);
    }

    public function test_recipient_deletion_failure_restores_receipts_and_preserves_the_summary()
    {
        $report = $this->report('expired', now()->subYear());
        $log = $this->log('expired', now()->subYear());
        $receipt = $this->receipt($log);
        DB::unprepared("CREATE TRIGGER fail_retention BEFORE DELETE ON email_logs BEGIN SELECT RAISE(ABORT, 'forced retention failure'); END");
        try {
            app(EmailReportRetention::class)->run(now()->subMonthsNoOverflow(3), true);
            $this->fail('Expected recipient deletion failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced retention failure', $exception->getMessage());
        }
        $this->assertDatabaseHas('email_campaign_reports', ['id' => $report]);
        $this->assertDatabaseHas('email_logs', ['id' => $log]);
        $this->assertDatabaseHas('mailgun_event_receipts', ['event_key' => $receipt]);
    }

    public function test_summary_deletion_failure_rolls_back_its_final_recipient_batch_and_can_be_retried()
    {
        $this->report('expired', now()->subYear());
        $log = $this->log('expired', now()->subYear());
        $this->receipt($log);
        DB::unprepared("CREATE TRIGGER fail_retention_summary BEFORE DELETE ON email_campaign_reports BEGIN SELECT RAISE(ABORT, 'forced summary failure'); END");
        try {
            app(EmailReportRetention::class)->run(now()->subMonthsNoOverflow(3), true);
            $this->fail('Expected summary deletion failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced summary failure', $exception->getMessage());
        }
        foreach (['email_campaign_reports', 'email_logs', 'mailgun_event_receipts'] as $table) $this->assertDatabaseCount($table, 1);
        DB::unprepared('DROP TRIGGER fail_retention_summary');
        $this->assertSame(['reports' => 1, 'logs' => 1, 'receipts' => 1],
            app(EmailReportRetention::class)->run(now()->subMonthsNoOverflow(3), true));
    }

    public function test_an_interrupted_run_resumes_after_previously_committed_batches()
    {
        $this->report('expired', now()->subYear());
        $first = $this->log('expired', now()->subYear());
        $second = $this->log('expired', now()->subYear());
        $this->receipt($first);
        $this->receipt($second);
        DB::unprepared("CREATE TRIGGER fail_second_batch BEFORE DELETE ON email_logs WHEN OLD.id = ".(int) $second." BEGIN SELECT RAISE(ABORT, 'second batch failure'); END");
        try {
            app(EmailReportRetention::class)->run(now()->subMonthsNoOverflow(3), true, 1);
            $this->fail('Expected second batch failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('second batch failure', $exception->getMessage());
        }
        $this->assertDatabaseMissing('email_logs', ['id' => $first]);
        $this->assertDatabaseHas('email_logs', ['id' => $second]);
        $this->assertDatabaseCount('mailgun_event_receipts', 1);
        $this->assertDatabaseCount('email_campaign_reports', 1);
        DB::unprepared('DROP TRIGGER fail_second_batch');
        $this->assertSame(['reports' => 1, 'logs' => 1, 'receipts' => 1],
            app(EmailReportRetention::class)->run(now()->subMonthsNoOverflow(3), true, 1));
    }

    public function test_late_provider_events_and_summary_rebuild_do_not_recreate_pruned_history()
    {
        $this->report('expired', now()->subYear());
        $log = $this->log('expired', now()->subYear());
        $message = DB::table('email_logs')->where('id', $log)->value('message_id');
        app(EmailReportRetention::class)->run(now()->subMonthsNoOverflow(3), true);
        $events = app(MailgunEvents::class);
        $event = $events->normalize(['id' => 'late-open', 'event' => 'opened', 'timestamp' => now()->timestamp,
            'recipient' => 'reader@example.test', 'message' => ['headers' => ['message-id' => $message]]]);
        $this->assertSame('unmatched', $events->apply($event)['status']);
        $this->assertNull(app(EmailCampaignReports::class)->rebuild('expired'));
        foreach (['email_campaign_reports', 'email_logs', 'mailgun_event_receipts'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_invalid_batch_options_do_not_delete_anything()
    {
        $this->report('expired', now()->subYear());
        foreach (['0', '-1', '1001', 'bad', '1.5'] as $batch) {
            $this->assertSame(1, Artisan::call('email-reports:prune', ['--execute' => true, '--batch' => $batch]));
        }
        $this->assertDatabaseCount('email_campaign_reports', 1);
    }

    public function test_scheduler_runs_the_executing_command_monthly_in_the_background_without_overlap()
    {
        $event = collect(app(Schedule::class)->events())->first(function ($event) {
            return strpos($event->command ?? '', 'email-reports:prune') !== false;
        });
        $this->assertNotNull($event);
        $this->assertStringContainsString('email-reports:prune --execute', $event->command);
        $this->assertSame('0 3 1 * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->runInBackground);
    }
}
