<?php

namespace App\Services\Admin;

use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Support\Facades\DB;

class UserRemovalPolicy
{
    public function reason($userId, $lock = false)
    {
        $memberships = DB::table('memberships')->where('user_id', $userId)->orderBy('id');
        if ($lock) $memberships->lockForUpdate();
        $memberships = $memberships->get(['source_type', 'source_id']);
        foreach ($memberships->groupBy('source_type') as $type => $records) {
            if (!in_array($type, [Stripe::class, Apple::class], true)) return 'Review the unrecognized membership before removing this account.';
            $query = $type::whereIn('id', $records->pluck('source_id')->unique())->orderBy('id');
            if ($lock) $query->lockForUpdate();
            $sources = $query->get($type === Stripe::class ? ['id', 'status', 'ended_at'] : ['id', 'renews_at']);
            if ($sources->count() !== $records->pluck('source_id')->unique()->count()) return 'The saved membership is incomplete. Review billing before removing this account.';
            foreach ($sources as $source) {
                if ($source instanceof Stripe) {
                    // Expired access or a pause does not prove that external billing ended.
                    if (!$source->ended_at && !in_array($source->status, ['canceled', 'incomplete_expired'], true)) {
                        return 'End the Stripe subscription before deleting or purging this account. Trials, paused subscriptions and scheduled cancellations remain blocked until billing has ended.';
                    }
                } else {
                    $checkQuery = DB::table('apple_membership_checks')->where('apple_membership_id', $source->id);
                    if ($lock) $checkQuery->lockForUpdate();
                    $check = $checkQuery->first(['status', 'expires_at', 'grace_expires_at']);
                    if (($source->renews_at && now()->lte($source->renews_at)) || ($check && collect([$check->expires_at, $check->grace_expires_at])->filter()->contains(function ($date) { return now()->lte(carbon($date)); }))) {
                        return 'The App Store subscription is still active. Wait until its paid or trial access has ended before deleting or purging this account.';
                    }
                    if (!$source->renews_at && (!$check || !in_array($check->status, ['expired', 'revoked'], true))) return 'The App Store subscription end date is unknown. Review billing before removing this account.';
                }
            }
        }
        return null;
    }
}
