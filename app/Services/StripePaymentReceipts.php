<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StripePaymentReceipts
{
    public function invoiceSucceeded(array $payload)
    {
        if (! config('attribution.enabled') || ($payload['type'] ?? null) !== 'invoice.payment_succeeded') return;
        $invoice = $payload['data']['object'] ?? [];
        $subscription = $invoice['subscription'] ?? ($invoice['parent']['subscription_details']['subscription'] ?? null);
        if (is_array($subscription)) $subscription = $subscription['id'] ?? null;
        $customer = $invoice['customer'] ?? null;
        if (is_array($customer)) $customer = $customer['id'] ?? null;
        $paidAt = $invoice['status_transitions']['paid_at'] ?? ($payload['created'] ?? null);
        if (! $this->id($invoice['id'] ?? null, 'in_') || ! $this->id($subscription, 'sub_') || ! $this->id($customer, 'cus_')
            || ($invoice['paid'] ?? false) !== true || ! is_int($invoice['amount_paid'] ?? null) || $invoice['amount_paid'] <= 0
            || ! is_string($invoice['currency'] ?? null) || ! preg_match('/^[a-z]{3}$/D', $invoice['currency'])
            || ! is_int($paidAt) || $paidAt <= 0) return;
        // This financial receipt is not a campaign event. Unknown/unconsented
        // subscribers never appear in the report. Keeping it briefly allows a
        // signed payment received before the checkout response to reconcile.
        DB::table('stripe_payment_receipts')->insertOrIgnore([
            'kind' => 'invoice', 'stripe_object_id' => $invoice['id'],
            'stripe_customer_id' => $customer, 'stripe_subscription_id' => $subscription,
            'amount' => $invoice['amount_paid'], 'currency' => $invoice['currency'],
            'paid_at' => \Carbon\Carbon::createFromTimestamp($paidAt), 'created_at' => now(),
        ]);
    }

    public function oncePerCharge(array $payload, callable $record)
    {
        if (! config('attribution.enabled')) return $record();
        $id = $payload['data']['object']['id'] ?? null;
        abort_unless($this->id($id, 'ch_'), 400);
        // Uniqueness serializes concurrent retries; rollback includes the marker
        // if the existing payment handler fails. Never rewrite historic payments.
        return DB::transaction(function () use ($id, $record) {
            $inserted = DB::table('stripe_payment_receipts')->insertOrIgnore([
                'kind' => 'charge', 'stripe_object_id' => $id, 'created_at' => now(),
            ]);
            if ($inserted && ! DB::table('payments')->where('charge_id', $id)->exists()) return $record();
        });
    }

    private function id($value, $prefix)
    {
        return is_string($value) && strlen($value) <= 255 && preg_match('/^'.preg_quote($prefix, '/').'[A-Za-z0-9]+$/D', $value);
    }
}
