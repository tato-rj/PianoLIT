<?php

namespace App\Billing\Stripe;

class SubscriptionState
{
    public static function array($value): array
    {
        if (is_array($value)) return $value;
        if (is_object($value) && method_exists($value, 'toArray')) return $value->toArray();
        return json_decode(json_encode($value), true) ?: [];
    }

    public static function id($value): ?string
    {
        return is_string($value) ? $value : (is_array($value) ? ($value['id'] ?? null) : null);
    }

    public static function periodEnd(array $subscription): ?int
    {
        return $subscription['current_period_end'] ?? data_get($subscription, 'items.data.0.current_period_end') ?? $subscription['trial_end'] ?? null;
    }

    public static function attributes(array $subscription): array
    {
        $ended = $subscription['ended_at'] ?? null;
        $ending = !empty($subscription['cancel_at_period_end']) || !empty($subscription['cancel_at']);
        $period = self::periodEnd($subscription);
        $end = $subscription['cancel_at'] ?? ($ending ? $period : null);
        $price = data_get($subscription, 'items.data.0.price.id') ?? data_get($subscription, 'plan.id');
        return [
            'status' => $subscription['status'],
            'plan' => $price,
            'renews_at' => !$ended && !$ending && $period ? carbon($period) : null,
            'ended_at' => $ended ? carbon($ended) : null,
            'canceled_at' => !empty($subscription['canceled_at']) ? carbon($subscription['canceled_at']) : null,
            'membership_ends_at' => !$ended && $end ? carbon($end) : null,
        ];
    }
}
