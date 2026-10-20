<?php

namespace Tests\Review;

use App\{EmailCampaignReport, EmailList, EmailLog};
use App\Services\EmailCampaignReports;
use Illuminate\Support\Facades\{Artisan, DB, Http};

class MailgunRecoveryTest extends ReviewTestCase
{
    private $output;
    private function fake($responses = [])
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake($responses ?: ['*' => Http::response([])]);
    }

    private function configure()
    {
        config(['services.mailgun.endpoint' => 'api.mailgun.net', 'services.mailgun.domain' => 'mg.example.test', 'services.mailgun.secret' => 'fake-api-key']);
    }

    private function email($attributes = [])
    {
        return EmailLog::create($attributes + ['message_id' => 'recovery@swift.generated', 'recipient' => 'reader@example.test',
            'list_id' => 'free-pick.1792420806', 'sender_type' => EmailList::class, 'sender_id' => 1]);
    }

    private function event($type, $attributes = [])
    {
        return $attributes + ['id' => 'recovery-'.$type, 'event' => $type, 'timestamp' => 1792430000,
            'recipient' => 'reader@example.test', 'message' => ['headers' => ['message-id' => 'recovery@swift.generated']]];
    }

    private function runRecovery($options = [])
    {
        $this->configure();
        $this->withoutMockingConsoleOutput();
        $result = Artisan::call('email-reports:recover', $options + ['--from' => '2026-10-14', '--to' => '2026-10-21']);
        $this->output = Artisan::output();
        return $result;
    }

    public function test_preview_projects_unique_counts_without_database_writes_or_sending_requests()
    {
        $this->email();
        $events = [$this->event('delivered'), $this->event('opened'), $this->event('opened', ['id' => 'second-open']), $this->event('clicked')];
        $this->fake(['api.mailgun.net/*' => Http::response(['items' => $events])]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->assertSame(0, $this->runRecovery());
        foreach (DB::getQueryLog() as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|truncate)\b/i', $query['query']);
        DB::disableQueryLog();
        $this->assertDatabaseCount('mailgun_event_receipts', 0);
        $this->assertSame(0, EmailCampaignReport::first()->opens_count);
        $this->assertStringContainsString('Projected unique campaign counts:', $this->output);
        $this->assertStringContainsString('Matched events: 4', $this->output);
        $this->assertStringNotContainsString('reader@example.test', $this->output);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET' && strpos($request->url(), 'https://api.mailgun.net/v3/mg.example.test/events?') === 0
                && $request['limit'] === 300 && $request['ascending'] === 'yes';
        });
        $this->assertSame(1, preg_match('/free-pick\.1792420806\s*\|\s*1\s*\|\s*1\s*\|\s*0\s*\|\s*1\s*\|\s*1/', $this->output));
    }

    public function test_apply_recovers_retained_events_across_pages_rebuilds_stale_summaries_and_is_safe_to_repeat()
    {
        $log = $this->email();
        EmailCampaignReport::first()->update(['opens_count' => 99]);
        $this->email(['message_id' => 'other-message', 'list_id' => 'another-campaign.1792420806']);
        $events = [$this->event('delivered'), $this->event('opened'), $this->event('clicked')];
        $next = 'https://api.mailgun.net/v3/mg.example.test/events/page-two';
        $this->fake(['api.mailgun.net/*' => Http::sequence()
            ->push(['items' => [$events[0]], 'paging' => ['next' => $next]])
            ->push(['items' => [$events[1], $events[2], $events[1]]])
            ->push(['items' => $events])]);
        $this->assertSame(0, $this->runRecovery(['--apply' => true, '--campaign' => ['free-pick.1792420806']]));
        $this->assertDatabaseCount('mailgun_event_receipts', 3);
        $report = EmailCampaignReport::where('list_id', $log->list_id)->first();
        foreach (['emails_count', 'delivered_count', 'opens_count', 'clicks_count'] as $column) $this->assertSame(1, $report->{$column});
        $this->assertSame(0, EmailCampaignReport::where('list_id', 'another-campaign.1792420806')->first()->opens_count);
        $this->assertSame(0, $this->runRecovery(['--apply' => true]));
        $this->assertDatabaseCount('mailgun_event_receipts', 3);
        $this->assertSame(1, (int) $log->fresh()->opened);
        $this->assertSame(1, $report->fresh()->opens_count);
        $this->assertStringContainsString('Duplicate events: 3', $this->output);
    }

    public function test_recovery_accepts_logs_timestamps_and_ignores_other_recipients_exclusions_and_temporary_failures()
    {
        $log = $this->email();
        $event = $this->event('opened', ['@timestamp' => '2026-10-19T12:00:00.100Z', 'delivered-at' => 'Mon, 19 Oct 2026 11:55:00 +0000']);
        unset($event['timestamp']);
        $this->fake(['api.mailgun.net/*' => Http::response(['items' => [$event,
            $this->event('failed', ['severity' => 'temporary']), $this->event('opened', ['id' => 'unrelated', 'recipient' => 'unrelated@example.test']),
            $this->event('opened', ['id' => 'excluded', 'recipient' => 'arthurvillar@gmail.com']),
            $this->event('clicked', ['timestamp' => 1600000000]), ['event' => 'accepted']]])]);
        $this->assertSame(0, $this->runRecovery(['--apply' => true]));
        $this->assertDatabaseCount('mailgun_event_receipts', 1);
        $this->assertSame(1, (int) $log->fresh()->unique_delivered);
        $this->assertSame(1, (int) $log->fresh()->unique_opened);
        $this->assertSame(0, (int) $log->fresh()->unique_failed);
        $this->assertStringContainsString('Unmatched events: 1', $this->output);
        $this->assertStringContainsString('Excluded events: 1', $this->output);
    }

    public function test_provider_pagination_cannot_send_credentials_to_another_host_or_endpoint()
    {
        $this->email();
        foreach (['http://api.mailgun.net/v3/mg.example.test/events/next', 'https://other.example/events',
            'https://api.mailgun.net/v3/another.example/events/next', 'https://api.mailgun.net/v3/mg.example.test/events'] as $next) {
            $this->fake(['*' => Http::response(['items' => [$this->event('opened')], 'paging' => ['next' => $next]])]);
            $this->assertSame(1, $this->runRecovery());
            Http::assertSentCount(1);
            $this->assertDatabaseCount('mailgun_event_receipts', 0);
        }
    }

    public function test_invalid_ranges_settings_and_provider_errors_fail_without_mutation()
    {
        $this->email();
        foreach ([['--from' => 'bad'], ['--to' => '2026-10-01'], ['--from' => '2026-01-01'], ['--max-pages' => 0]] as $invalid) {
            $this->fake();
            $this->assertSame(1, $this->runRecovery($invalid));
            Http::assertNothingSent();
        }
        $this->fake(['*' => Http::response('not authorized', 401)]);
        $this->assertSame(1, $this->runRecovery(['--apply' => true]));
        $this->assertDatabaseCount('mailgun_event_receipts', 0);
        $this->assertSame(0, EmailCampaignReport::first()->opens_count);
        $this->assertStringNotContainsString('fake-api-key', $this->output);
    }

    public function test_empty_retention_results_preserve_existing_numbers_and_report_limits_are_explicit()
    {
        $log = $this->email(['opened' => 7, 'unique_opened' => 1]);
        $this->fake(['*' => Http::response(['items' => []])]);
        $this->assertSame(0, $this->runRecovery(['--apply' => true]));
        $this->assertSame(7, (int) $log->fresh()->opened);
        $this->assertSame(1, EmailCampaignReport::first()->opens_count);
        $this->fake(['*' => Http::response(['items' => [$this->event('clicked')],
            'paging' => ['next' => 'https://api.mailgun.net/v3/mg.example.test/events/next']])]);
        $this->assertSame(1, $this->runRecovery(['--max-pages' => 1]));
        $this->assertStringContainsString('incomplete', $this->output);
    }

    public function test_summary_rebuilding_preserves_snapshot_only_history_and_rolls_back_a_failed_repair()
    {
        $log = $this->email(['unique_delivered' => 1]);
        $snapshot = EmailCampaignReport::create(['list_id' => 'old-history.1500000000', 'name' => 'Old history', 'sent_at' => now()->subYear(),
            'emails_count' => 3000, 'delivered_count' => 2700]);
        $report = EmailCampaignReport::where('list_id', $log->list_id)->first();
        $report->update(['delivered_count' => 0]);
        $service = new EmailCampaignReports;
        $this->assertSame(1, (int) $service->rebuild($log->list_id, true)['delivered_count']);
        $this->assertSame(0, $report->fresh()->delivered_count);
        DB::unprepared("CREATE TRIGGER fail_report_rebuild BEFORE UPDATE ON email_campaign_reports BEGIN SELECT RAISE(ABORT, 'forced report failure'); END");
        try { $service->rebuild($log->list_id); $this->fail('Expected report update failure.'); }
        catch (\Illuminate\Database\QueryException $exception) { $this->assertStringContainsString('forced report failure', $exception->getMessage()); }
        $this->assertSame(0, $report->fresh()->delivered_count);
        $this->assertSame(2700, $snapshot->fresh()->delivered_count);
        DB::unprepared('DROP TRIGGER fail_report_rebuild');
        Artisan::call('email-reports:rebuild', ['--campaign' => [$log->list_id], '--dry-run' => true]);
        $this->assertSame(0, $report->fresh()->delivered_count);
        Artisan::call('email-reports:rebuild');
        $this->assertSame(1, $report->fresh()->delivered_count);
        $this->assertSame(2700, $snapshot->fresh()->delivered_count);
        $this->assertDatabaseCount('email_campaign_reports', 2);
    }
}
