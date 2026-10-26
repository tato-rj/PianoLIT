<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, User};
use App\Billing\Sources\{Apple, Stripe};
use App\Mail\Admin\AdminReport;
use App\Services\Admin\WeeklySnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\{DB, Http, Mail};
use Illuminate\Support\Str;

class WeeklySnapshotTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-25 22:30:00'));
    }

    private function user(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) {
            return create(User::class, $attributes + ['created_at' => '2026-10-01 12:00:00', 'origin' => 'webapp']);
        });
    }

    private function piece()
    {
        return Model::withoutEvents(function () {
            $composer = create(Composer::class, ['name' => 'Example Composer']);
            return create(Piece::class, ['name' => 'Example Piece', 'composer_id' => $composer->id, 'cover_path' => 'piece/example.jpg']);
        });
    }

    private function event($user, string $at, array $attributes = [])
    {
        return DB::table('activity_events')->insertGetId($attributes + [
            'event_id' => (string) Str::uuid(), 'user_id' => $user ? $user->id : null,
            'platform' => 'app', 'type' => 'request', 'source' => 'server',
            'occurred_at' => $at, 'received_at' => now(), 'metadata' => '{}',
        ]);
    }

    private function web($user, string $at, array $attributes = [])
    {
        $visit = (string) Str::uuid();
        DB::table('activity_visits')->insert([
            'id' => $visit, 'user_id' => $user ? $user->id : null, 'visitor_id' => (string) Str::uuid(),
            'platform' => $attributes['platform'] ?? 'webapp', 'started_at' => $at, 'last_activity_at' => $at,
        ]);
        $this->event($user, $at, $attributes + ['platform' => 'webapp', 'type' => 'page_view', 'visit_id' => $visit]);
        return $visit;
    }

    public function test_counts_people_once_across_platforms_and_uses_disjoint_complete_weeks()
    {
        $returning = $this->user(['created_at' => '2026-10-11 00:00:00']);
        $newWeb = $this->user(['created_at' => '2026-10-18 00:00:00']);
        $newIos = $this->user(['created_at' => '2026-10-24 23:59:59', 'origin' => 'ios']);
        $this->user(['created_at' => '2026-10-25 00:00:00']);
        $websiteOnly = $this->user();
        $billingOnly = $this->user();
        $piece = $this->piece();
        $this->event($returning, '2026-10-17 23:59:59');
        $this->web($returning, '2026-10-18 00:00:00');
        $this->event($returning, '2026-10-18 00:00:00');
        $this->web($newWeb, '2026-10-20 12:00:00', ['resource_type' => 'piece', 'resource_id' => $piece->id]);
        $this->web($newWeb, '2026-10-20 12:01:00', ['resource_type' => 'piece', 'resource_id' => $piece->id]);
        $this->event($newWeb, '2026-10-20 12:02:00', ['route_name' => 'api.pieces.find', 'metadata' => json_encode(['data' => ['search' => $piece->id]])]);
        $this->event($newIos, '2026-10-24 23:59:59', ['route_name' => 'api.pieces.find', 'metadata' => json_encode(['data' => ['search' => $piece->id]])]);
        $this->web($websiteOnly, '2026-10-20 12:00:00', ['platform' => 'web']);
        $this->event($billingOnly, '2026-10-20 12:00:00', ['platform' => 'webapp', 'type' => 'subscription_paid']);
        $outside = $this->user();
        $this->event($outside, '2026-10-25 00:00:00');
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame('2026-10-18', $snapshot['period']['start']);
        $this->assertSame('2026-10-24', $snapshot['period']['end']);
        $this->assertSame(['total' => 2, 'previous' => 1, 'change' => 1], $snapshot['new_accounts']);
        $this->assertSame(['total' => 3, 'previous' => 1, 'change' => 2], $snapshot['active_accounts']);
        $this->assertSame(['total' => 1, 'base' => 1, 'percent' => 100], $snapshot['returning']);
        $this->assertSame(['total' => 2, 'base' => 2, 'percent' => 100], $snapshot['activated']);
        $this->assertSame(2, $snapshot['web']['active']);
        $this->assertSame(3, $snapshot['ios']['active']);
        $this->assertSame(2, $snapshot['pieces'][0]['total']);
        $this->assertSame(1, $snapshot['pieces'][0]['web']);
        $this->assertSame(2, $snapshot['pieces'][0]['ios']);
        $this->assertSame([1, 0, 1, 0, 0, 0, 1], array_column($snapshot['days'], 'total'));
        Http::assertNothingSent(); Mail::assertNothingOutgoing();
    }

    public function test_excludes_private_accounts_guests_unknown_ids_and_linked_anonymous_history()
    {
        $excluded = $this->user(['email' => ' Elena.Dare@Gmail.com ', 'created_at' => '2026-10-20 12:00:00']);
        $user = $this->user();
        $this->event($excluded, '2026-10-20 12:00:00');
        $this->web($excluded, '2026-10-20 12:00:00');
        $this->event(null, '2026-10-20 12:00:00', ['user_id' => 999999]);
        $this->web(null, '2026-10-20 12:00:00');
        foreach ([$excluded, $user] as $linked) {
            $visit = $this->web(null, '2026-10-20 12:00:00');
            DB::table('activity_visit_links')->insert(['visit_id' => $visit, 'user_id' => $linked->id, 'reason' => 'login', 'linked_at_utc' => '2026-10-20 16:00:00']);
        }
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame(0, $snapshot['new_accounts']['total']);
        $this->assertSame(1, $snapshot['active_accounts']['total']);
        $this->assertSame(1, $snapshot['web']['active']);
        $this->assertSame(0, $snapshot['ios']['active']);
        $this->assertStringNotContainsString($excluded->email, json_encode($snapshot));
    }

    public function test_measures_confirmed_web_behavior_and_labels_ios_attempts_without_inventing_success()
    {
        $user = $this->user(); $piece = $this->piece();
        foreach ([['media_play', 'browser', []], ['media_play', 'browser', []], ['score_opened', 'browser', []],
            ['favorite_changed', 'server', ['result' => true]], ['favorite_changed', 'server', ['result' => false]]] as [$type, $source, $metadata]) {
            $this->web($user, '2026-10-20 12:00:00', ['type' => $type, 'source' => $source, 'metadata' => json_encode($metadata)]);
        }
        $this->event($user, '2026-10-20 12:00:00', ['route_name' => 'api.pieces.find', 'metadata' => json_encode(['data' => ['search' => $piece->id.'bad']])]);
        $this->event($user, '2026-10-20 12:00:00', ['url' => 'https://pianolit.com/api/pieces/find?search='.$piece->id, 'type' => 'legacy_request', 'source' => 'redis', 'metadata' => json_encode(['data' => ['search' => $piece->id]])]);
        $this->event($user, '2026-10-20 12:00:00', ['route_name' => 'api.search']);
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame(['active' => 1, 'explorers' => 0, 'players' => 1, 'readers' => 1, 'savers' => 1], $snapshot['web']);
        $this->assertSame(['active' => 1, 'requests' => 3, 'piece_requests' => 2, 'search_requests' => 1], $snapshot['ios']);
        $this->assertSame(1, $snapshot['pieces'][0]['total']);
    }

    public function test_utc_boundaries_and_dst_days_do_not_drop_the_last_hour()
    {
        $this->travelTo(carbon('2026-11-08 22:30:00'));
        $user = $this->user();
        // Clock repeats 01:30 on Nov 1; both observations belong to Sunday.
        $this->event($user, '2026-11-01 01:30:00', ['occurred_at_utc' => '2026-11-01 05:30:00']);
        $this->event($user, '2026-11-01 01:30:00', ['occurred_at_utc' => '2026-11-01 06:30:00']);
        $this->event($user, '2026-11-07 23:59:59', ['occurred_at_utc' => '2026-11-08 04:59:59']);
        $outside = $this->user();
        $this->event($outside, '2026-11-01 00:00:00', ['occurred_at_utc' => '2026-11-01 03:59:59']);
        $this->event($outside, '2026-11-07 23:59:59', ['occurred_at_utc' => '2026-11-08 05:00:00']);
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame(1, $snapshot['active_accounts']['total']);
        $this->assertSame(3, $snapshot['ios']['requests']);
        $this->assertSame([1, 0, 0, 0, 0, 0, 1], array_column($snapshot['days'], 'total'));
    }

    public function test_membership_snapshot_reuses_saved_status_and_does_not_count_complimentary_access_as_paid()
    {
        foreach ([['active', []], ['trialing', []], ['active', ['membership_ends_at' => '2026-10-30', 'renews_at' => '2026-10-01']],
            ['active', ['ended_at' => '2026-10-01']], ['past_due', []]] as [$status, $extra]) {
            $user = $this->user();
            $source = Stripe::create($extra + ['stripe_id' => 'cus_test_'.$user->id, 'status' => $status, 'renews_at' => '2026-11-01', 'plan' => 'monthly']);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
            $this->event($user, '2026-10-20 12:00:00');
        }
        $complimentary = $this->user(['super_user' => true]);
        $this->event($complimentary, '2026-10-20 12:00:00');
        $appleUser = $this->user();
        $apple = Apple::create(['plan' => 'monthly', 'latest_receipt' => 'test-only', 'password' => 'test-only']);
        $appleUser->membership()->create(['source_type' => Apple::class, 'source_id' => $apple->id]);
        // Unchecked Apple receipt is review, never active paid membership.
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame(['total' => 3, 'engaged' => 3, 'percent' => 100, 'active' => 1, 'trial' => 1, 'ending' => 1, 'review' => 2], $snapshot['members']);
        Http::assertNothingSent(); Mail::assertNothingOutgoing();
    }

    public function test_empty_data_is_zero_with_no_invented_rates_or_rankings()
    {
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame(['total' => 0, 'previous' => 0, 'change' => 0], $snapshot['active_accounts']);
        $this->assertNull($snapshot['activated']['percent']);
        $this->assertNull($snapshot['returning']['percent']);
        $this->assertNull($snapshot['members']['percent']);
        $this->assertSame([], $snapshot['pieces']);
        $mail = new AdminReport($snapshot, new Admin(['name' => 'Preview']));
        $mail->build();
        $renderer = clone app(Markdown::class);
        $renderer->theme($mail->theme);
        $this->assertStringContainsString('No piece exploration recorded this week', (string) $renderer->render($mail->markdown, $mail->buildViewData()));
    }

    public function test_verified_apple_statuses_and_private_memberships_follow_the_existing_directory()
    {
        foreach (['active', 'trial', 'ending', 'grace_period', 'sandbox', 'stale', 'excluded'] as $status) {
            $user = $this->user($status === 'excluded' ? ['email' => ' ARTHURVILLAR@GMAIL.COM '] : []);
            $source = Apple::create(['plan' => 'monthly', 'latest_receipt' => 'test-only', 'password' => 'test-only']);
            $user->membership()->create(['source_type' => Apple::class, 'source_id' => $source->id]);
            DB::table('apple_membership_checks')->insert([
                'apple_membership_id' => $source->id, 'status' => $status === 'trial' ? 'trial' : 'active',
                'environment' => $status === 'sandbox' ? 'Sandbox' : 'Production',
                'expires_at' => $status === 'grace_period' ? now()->subDay() : now()->addMonth(),
                'grace_expires_at' => $status === 'grace_period' ? now()->addDay() : null,
                'auto_renews' => $status !== 'ending', 'source_updated_at' => $source->updated_at,
                'checked_at' => $status === 'stale' ? now()->subHours(49) : now(),
            ]);
            $this->event($user, '2026-10-20 12:00:00');
        }
        $snapshot = (new WeeklySnapshot)->build();
        $this->assertSame(['total' => 4, 'engaged' => 4, 'percent' => 100, 'active' => 2, 'trial' => 1, 'ending' => 1, 'review' => 1], $snapshot['members']);
        Http::assertNothingSent();
    }

    public function test_old_queued_reports_remain_renderable_and_the_editorial_theme_does_not_leak()
    {
        $renderer = app(Markdown::class);
        $before = (new \ReflectionProperty(Markdown::class, 'theme'));
        $before->setAccessible(true);
        $theme = $before->getValue($renderer);
        $snapshot = (new \App\Services\Admin\WeeklySnapshotPreview)->data();
        $mail = new class($snapshot, new Admin(['name' => 'Preview'])) extends AdminReport {
            public function views() { $this->build(); return $this->buildMarkdownView(); }
        };
        $views = $mail->views();
        $this->assertStringContainsString('The week at PianoLIT', (string) $views['html']);
        $this->assertSame($theme, $before->getValue($renderer));
        $legacy = new AdminReport([], new Admin(['name' => 'Preview']));
        $legacy->build();
        $this->assertSame('default', $legacy->theme);
        $this->assertStringContainsString('Hi Preview!', (string) (clone $renderer)->render($legacy->markdown, $legacy->buildViewData()));
    }

    public function test_html_and_text_render_the_same_snapshot_with_safe_links_and_no_recipient_lists()
    {
        config(['app.url' => 'pianolit.com']);
        $snapshot = (new \App\Services\Admin\WeeklySnapshotPreview)->data();
        $snapshot['pieces'][0]['title'] = 'Example <script>unsafe</script>';
        $mail = new AdminReport($snapshot, new Admin(['name' => 'Private Admin', 'email' => 'private@example.test']));
        $mail->build();
        $renderer = clone app(Markdown::class);
        $renderer->theme($mail->theme);
        $html = (string) $renderer->render($mail->markdown, $mail->buildViewData());
        $text = (string) $renderer->renderText($mail->markdown, $mail->buildViewData());
        foreach ([$html, $text] as $body) {
            $this->assertStringContainsString('The week at PianoLIT', $body);
            $this->assertStringContainsString('326', $body);
            $this->assertStringContainsString('unverified', $body);
            $this->assertStringNotContainsString('private@example.test', $body);
            $this->assertStringNotContainsString('Unsubscribe', $body);
            $this->assertStringNotContainsString('@if', $body);
        }
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('https://pianolit.com/images/emails/welcome/logo.png', $html);
        $this->assertStringContainsString('snapshot-chart', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('from=2026-10-18', $html);
        $this->assertLessThan(102400, strlen($html), 'Avoid Gmail clipping.');
    }

    public function test_command_queues_one_identical_snapshot_per_manager_without_campaign_tracking()
    {
        create(Admin::class, ['role' => 'manager']);
        create(Admin::class, ['role' => 'manager']);
        create(Admin::class, ['role' => 'editor']);
        $this->artisan('pianolit:admin-report')->assertExitCode(0);
        Mail::assertQueued(AdminReport::class, 2);
        $reports = Mail::queued(AdminReport::class);
        $this->assertSame($reports[0]->reports, $reports[1]->reports);
        $this->assertDatabaseCount('email_logs', 0);
        $this->assertDatabaseCount('activity_events', 0);
        Http::assertNothingSent();
    }

    public function test_disabled_weekly_snapshot_does_not_generate_or_queue_a_report()
    {
        app(\App\Mail\AutomaticEmailSettings::class)->setEnabled('admin-weekly-snapshot', false, null);
        $this->mock(WeeklySnapshot::class)->shouldNotReceive('build');
        $this->artisan('pianolit:admin-report')->expectsOutput('Weekly admin snapshot sending is off.')->assertExitCode(0);
        Mail::assertNothingOutgoing();
    }
}
