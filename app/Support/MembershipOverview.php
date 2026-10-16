<?php

namespace App\Support;

use App\Billing\Sources\Stripe;

/** A web-only summary of saved billing state; never fetch or change provider data. */
class MembershipOverview
{
    public static function forSource(Stripe $source): array
    {
        $end = $source->membership_ends_at;
        $ended = $source->isEnded() || in_array($source->status, ['canceled', 'incomplete_expired'], true) || ($end && $end->isPast());
        if ($ended) {
            return ['status' => 'Ended', 'tone' => 'grey', 'dateLabel' => 'Membership ended', 'date' => $source->ended_at ?: $end,
                'note' => 'No upcoming membership payments. Your invoices are still available.', 'ended' => true];
        }
        if (in_array($source->status, ['past_due', 'unpaid', 'incomplete'], true)) {
            return ['status' => 'Payment needed', 'tone' => 'orange', 'dateLabel' => 'Payment status', 'date' => null, 'dateText' => 'Action required',
                'note' => 'Review your payment method in Billing, then complete the open invoice in Invoices.', 'ended' => false];
        }
        // A scheduled end takes precedence over the saved renewal date.
        if ($end || $source->isCanceled()) {
            return ['status' => 'Ending soon', 'tone' => 'orange', 'dateLabel' => 'Access until', 'date' => $end,
                'note' => 'Your membership will not renew. You keep access until the end of your membership.', 'ended' => false];
        }
        if ($source->isPaused()) {
            return ['status' => 'Billing paused', 'tone' => 'grey', 'dateLabel' => 'Next payment', 'date' => null, 'dateText' => 'Not scheduled',
                'note' => 'Payments are paused. Manage your billing to review or resume your membership.', 'ended' => false];
        }
        if (in_array($source->status, ['active', 'trialing'], true) && $source->renews_at && $source->renews_at->isFuture()) {
            $trial = $source->isOnTrial();
            return ['status' => $trial ? 'Free trial' : 'Active', 'tone' => $trial ? 'blue' : 'green',
                'dateLabel' => $trial ? 'First payment' : 'Next renewal', 'date' => $source->renews_at,
                'note' => $trial ? 'Your plan starts billing after your trial. You can cancel before then in Billing.' : 'Your membership renews automatically. You can manage or cancel it in Billing.', 'ended' => false];
        }
        return ['status' => 'Review billing', 'tone' => 'grey', 'dateLabel' => 'Renewal details', 'date' => null,
            'note' => 'Check Billing and Invoices for your latest membership details.', 'ended' => false];
    }
}
