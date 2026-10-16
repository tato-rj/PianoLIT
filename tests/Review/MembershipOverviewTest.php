<?php

namespace Tests\Review;

use App\Billing\Sources\Stripe;
use App\Support\MembershipOverview;

class MembershipOverviewTest extends ReviewTestCase
{
    /** @dataProvider billingStates */
    public function test_summary_describes_saved_state_without_claiming_a_stale_renewal(array $attributes, string $status, string $label, ?string $date, bool $ended)
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 12:00:00'));
        $source = new Stripe(array_merge(['status' => 'active', 'renews_at' => '2027-10-16 12:00:00'], $attributes));
        $summary = MembershipOverview::forSource($source);
        $this->assertSame($status, $summary['status']);
        $this->assertSame($label, $summary['dateLabel']);
        $this->assertSame($date, $summary['date'] ? $summary['date']->toDateString() : null);
        $this->assertSame($ended, $summary['ended']);
        $this->assertArrayNotHasKey('price', $summary, 'The catalog price is not an existing member’s verified recurring charge.');
    }

    public function billingStates(): array
    {
        return [
            'active' => [[], 'Active', 'Next renewal', '2027-10-16', false],
            'trial' => [['status' => 'trialing'], 'Free trial', 'First payment', '2027-10-16', false],
            'scheduled cancellation with stale renewal' => [['canceled_at' => '2026-10-15', 'membership_ends_at' => '2026-11-01', 'renews_at' => '2026-10-01'], 'Ending soon', 'Access until', '2026-11-01', false],
            'expired grace period with future renewal' => [['membership_ends_at' => '2026-10-01'], 'Ended', 'Membership ended', '2026-10-01', true],
            'paused' => [['paused_at' => '2026-10-15'], 'Billing paused', 'Next payment', null, false],
            'past due' => [['status' => 'past_due'], 'Payment needed', 'Payment status', null, false],
            'incomplete' => [['status' => 'incomplete'], 'Payment needed', 'Payment status', null, false],
            'unpaid' => [['status' => 'unpaid'], 'Payment needed', 'Payment status', null, false],
            'ended with stale active status' => [['ended_at' => '2026-10-15'], 'Ended', 'Membership ended', '2026-10-15', true],
            'canceled' => [['status' => 'canceled'], 'Ended', 'Membership ended', null, true],
            'stale active period' => [['renews_at' => '2026-10-01'], 'Review billing', 'Renewal details', null, false],
            'no renewal date' => [['renews_at' => null], 'Review billing', 'Renewal details', null, false],
        ];
    }
}
