<?php

namespace Tests\Review;

use App\User;
use App\Services\{CampaignAttributionReport, CampaignParameters};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail};
use Illuminate\Support\Str;

class CampaignWeeklyReportTest extends ReviewTestCase
{
    private function visit(string $at, array $extra = [])
    {
        $parameters = CampaignParameters::fromQuery($extra + ['utm_source' => 'youtube.com', 'utm_campaign' => 'weekly_music'])
            ?: array_fill_keys(CampaignParameters::FIELDS, '');
        $id = (string) Str::uuid();
        DB::table('campaign_visits')->insert($parameters + [
            'id' => $id, 'campaign' => CampaignParameters::key($parameters),
            'consented_at' => $extra['consented_at'] ?? null, 'user_id' => $extra['user_id'] ?? null,
            'landing_url' => $extra['landing_url'] ?? '', 'created_at' => $at, 'expires_at' => '2026-11-30 00:00:00',
        ]);
        return $id;
    }

    private function subscription(string $visit, string $identity, ?string $trial = null)
    {
        DB::table('campaign_subscriptions')->insert([
            'visit_id' => $visit, 'stripe_subscription_id' => 'sub_'.$identity, 'stripe_customer_id' => 'cus_'.$identity,
            'billing_account' => 'us', 'plan' => 'monthly', 'trial_started_at' => $trial, 'subscribed_at' => '2026-10-01 00:00:00',
        ]);
    }

    private function payment(string $identity, string $at, array $extra = [])
    {
        DB::table('stripe_payment_receipts')->insert($extra + [
            'kind' => 'invoice', 'stripe_object_id' => (string) Str::uuid(),
            'stripe_subscription_id' => 'sub_'.$identity, 'stripe_customer_id' => 'cus_'.$identity,
            'currency' => 'usd', 'amount' => 999, 'paid_at' => $at, 'created_at' => now(),
        ]);
    }

    private function report()
    {
        return (new CampaignAttributionReport)->weekly(carbon('2026-10-18 00:00:00'), carbon('2026-10-25 00:00:00'));
    }

    public function test_counts_events_in_the_week_including_outcomes_of_earlier_arrivals_without_renewals()
    {
        $old = $this->visit('2026-10-01 00:00:00', ['consented_at' => '2026-10-01 00:00:00']);
        $this->subscription($old, 'old', '2026-10-18 00:00:00');
        $this->payment('old', '2026-10-24 23:59:59');
        $this->payment('old', '2026-10-26 00:00:00');
        $renewal = $this->visit('2026-10-01 00:00:00', ['consented_at' => '2026-10-01 00:00:00']);
        $this->subscription($renewal, 'renewal', '2026-10-17 23:59:59');
        $this->payment('renewal', '2026-10-17 23:59:59');
        $this->payment('renewal', '2026-10-20 12:00:00');
        $future = $this->visit('2026-10-18 00:00:00', ['consented_at' => '2026-10-18 00:00:00']);
        $this->subscription($future, 'future', '2026-10-25 00:00:00');
        $this->payment('future', '2026-10-25 00:00:00');
        $this->visit('2026-10-24 23:59:59');
        $this->visit('2026-10-25 00:00:00');
        $this->visit('2026-10-17 23:59:59');
        $report = $this->report();
        $this->assertSame(['arrivals' => 2, 'trials' => 1, 'first_paid' => 1], $report['totals']);
        $this->assertCount(1, $report['rows']);
        $this->assertSame(['campaign' => 'weekly_music', 'source' => 'youtube.com', 'medium' => '', 'arrivals' => 2, 'trials' => 1, 'first_paid' => 1], $report['rows'][0]);
        Http::assertNothingSent(); Mail::assertNothingOutgoing();
    }

    public function test_keeps_anonymous_arrivals_but_requires_consent_and_matching_membership_receipts_for_outcomes()
    {
        $anonymous = $this->visit('2026-10-20 12:00:00');
        $this->subscription($anonymous, 'unconsented', '2026-10-20 12:00:00');
        $this->payment('unconsented', '2026-10-20 12:00:00');
        $consented = $this->visit('2026-10-20 12:00:00', ['consented_at' => '2026-10-20 12:00:00']);
        $this->subscription($consented, 'correct', '2026-10-20 12:00:00');
        $this->payment('correct', '2026-10-19 12:00:00', ['stripe_customer_id' => 'cus_other']);
        $this->payment('correct', '2026-10-20 12:00:00', ['kind' => 'payment_intent']);
        $this->payment('correct', '2026-10-21 12:00:00');
        $this->payment('correct', '2026-10-22 12:00:00');
        $this->subscription($consented, 'zero');
        $this->payment('zero', '2026-10-21 12:00:00', ['amount' => 0]);
        foreach ([' ArthurVillar@Gmail.Com ', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $index => $email) {
            $user = Model::withoutEvents(function () use ($email) { return create(User::class, ['email' => $email]); });
            $visit = $this->visit('2026-10-20 12:00:00', ['user_id' => $user->id, 'consented_at' => '2026-10-20 12:00:00']);
            $this->subscription($visit, 'excluded'.$index, '2026-10-20 12:00:00');
            $this->payment('excluded'.$index, '2026-10-20 12:00:00');
        }
        $this->assertSame(['arrivals' => 2, 'trials' => 1, 'first_paid' => 1], $this->report()['totals']);
    }

    public function test_ranks_membership_outcomes_first_merges_placements_and_keeps_all_campaigns_in_totals()
    {
        foreach (['description', 'comment'] as $placement) {
            $visit = $this->visit('2026-10-20 12:00:00', ['utm_content' => $placement, 'utm_medium' => 'video', 'consented_at' => '2026-10-20 12:00:00', 'landing_url' => 'https://my.pianolit.com/pieces/'.($placement === 'comment' ? 2 : 1)]);
            $this->subscription($visit, $placement, '2026-10-20 12:00:00');
            $this->payment($placement, '2026-10-20 12:00:00');
        }
        $trial = $this->visit('2026-10-20 12:00:00', ['utm_campaign' => 'trial_campaign', 'consented_at' => '2026-10-20 12:00:00']);
        $this->subscription($trial, 'trial', '2026-10-20 12:00:00');
        for ($i = 0; $i < 4; $i++) $this->visit('2026-10-20 12:00:00', ['utm_campaign' => 'traffic_campaign']);
        $this->visit('2026-10-20 12:00:00', ['utm_source' => 'other.example', 'utm_campaign' => 'weekly_music']);
        $this->visit('2026-10-20 12:00:00', ['utm_medium' => 'email']);
        $report = $this->report();
        $this->assertSame(['arrivals' => 9, 'trials' => 3, 'first_paid' => 2], $report['totals']);
        $this->assertSame(['weekly_music', 'trial_campaign', 'traffic_campaign'], array_column($report['rows'], 'campaign'));
        $this->assertSame([2, 1, 4], array_column($report['rows'], 'arrivals'));
        $this->assertSame([2, 1, 0], array_column($report['rows'], 'trials'));
        $this->assertSame([2, 0, 0], array_column($report['rows'], 'first_paid'));
        $this->assertSame('video', $report['rows'][0]['medium']);
        $this->assertStringNotContainsString('description', json_encode($report));
    }

    public function test_handles_partial_utm_labels_and_empty_reports_without_inventing_sources()
    {
        $this->assertSame(['totals' => ['arrivals' => 0, 'trials' => 0, 'first_paid' => 0], 'rows' => []], $this->report());
        $this->visit('2026-10-20 12:00:00', ['utm_source' => '', 'utm_campaign' => 'campaign_only']);
        $this->visit('2026-10-20 12:00:00', ['utm_source' => '', 'utm_campaign' => '', 'utm_medium' => 'email']);
        $this->visit('2026-10-20 12:00:00', ['utm_campaign' => '']);
        $this->visit('2026-10-20 12:00:00', ['utm_source' => '', 'utm_campaign' => '']);
        $report = $this->report();
        $this->assertSame(3, $report['totals']['arrivals']);
        $this->assertCount(3, $report['rows']);
        $this->assertSame(['', '', 'campaign_only'], array_column($report['rows'], 'campaign'));
        $this->assertSame(['', 'youtube.com', ''], array_column($report['rows'], 'source'));
        $this->assertSame(['email', '', ''], array_column($report['rows'], 'medium'));
    }
}
