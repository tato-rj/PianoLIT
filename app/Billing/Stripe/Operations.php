<?php

namespace App\Billing\Stripe;

use App\User;
use Illuminate\Support\Facades\DB;

class Operations
{
    public function begin(User $user, string $id, string $kind, string $account, ?string $plan = null, ?string $coupon = null)
    {
        return DB::transaction(function () use ($user, $id, $kind, $account, $plan, $coupon) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $row = DB::table('stripe_billing_operations')->where('id', $id)->first();
            if ($row) {
                if ((int) $row->user_id !== (int) $user->id || $row->kind !== $kind || $row->billing_account !== $account) throw new BillingException('This billing request does not match your checkout. Please reload the page.');
                if ($row->plan !== $plan || $row->coupon !== $coupon) {
                    if ($kind !== 'purchase' || $row->subscription_id || $row->state === 'complete') throw new BillingException('This checkout already has a subscription. Open My Membership to finish it.');
                    $this->update($id, ['plan' => $plan, 'coupon' => $coupon]);
                    return $this->owned($user, $id, $kind);
                }
                return $row;
            }
            $active = $kind === 'purchase' ? 'purchase:'.$user->id : null;
            if ($active && DB::table('stripe_billing_operations')->where('active_key', $active)->exists()) {
                $existing = DB::table('stripe_billing_operations')->where('active_key', $active)->first();
                if ($existing->plan === $plan && $existing->coupon === $coupon && $existing->billing_account === $account) return $existing;
                throw new BillingException('A checkout is already in progress. Open My Membership to finish or cancel it before starting another.');
            }
            DB::table('stripe_billing_operations')->insert([
                'id' => $id, 'user_id' => $user->id, 'kind' => $kind, 'active_key' => $active,
                'billing_account' => $account, 'plan' => $plan, 'coupon' => $coupon,
                'state' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $this->owned($user, $id, $kind);
        });
    }

    public function owned(User $user, string $id, string $kind)
    {
        $row = DB::table('stripe_billing_operations')->where('id', $id)->where('user_id', $user->id)->where('kind', $kind)->first();
        if (!$row) throw new BillingException('This billing request has expired. Please reload the page.');
        return $row;
    }

    public function update(string $id, array $attributes): void
    {
        DB::table('stripe_billing_operations')->where('id', $id)->update($attributes + ['updated_at' => now()]);
    }

    public function complete(string $id): void
    {
        $this->update($id, ['state' => 'complete', 'active_key' => null, 'completed_at' => now()]);
    }

    public function locked(User $user, callable $work)
    {
        // All web mutations and webhook refreshes serialize on the same account.
        // Provider requests use durable idempotency keys if local writes roll back.
        return DB::transaction(function () use ($user, $work) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            return $work();
        });
    }
}
