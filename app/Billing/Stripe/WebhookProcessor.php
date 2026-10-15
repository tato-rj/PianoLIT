<?php

namespace App\Billing\Stripe;

use App\Billing\{Customer, Membership, Payment};
use App\Billing\Sources\Stripe;
use App\Services\StripePaymentReceipts;
use App\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WebhookProcessor
{
    public const EVENTS = [
        'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted',
        'customer.subscription.paused', 'customer.subscription.resumed',
        'customer.updated', 'customer.source.created', 'customer.source.updated', 'customer.source.deleted',
        'payment_method.attached', 'payment_method.detached', 'payment_method.updated', 'payment_method.automatically_updated',
        'invoice.payment_succeeded', 'invoice.payment_failed', 'invoice.payment_action_required', 'invoice.finalization_failed',
        'charge.succeeded', 'charge.refunded', 'charge.dispute.created',
    ];

    public function receive(array $payload, string $account): void
    {
        if (!in_array($payload['type'], self::EVENTS, true)) return;
        abort_unless(is_string($payload['id'] ?? null) && preg_match('/^evt_[A-Za-z0-9_]+$/D', $payload['id'])
            && strlen($payload['id']) <= 255 && is_array($payload['data']['object'] ?? null)
            && is_string(data_get($payload, 'data.object.id')) && strlen($payload['data']['object']['id']) <= 255
            && preg_match('/^(cus|sub|card|src|pm|in|ch|dp)_[A-Za-z0-9]+$/D', $payload['data']['object']['id']), 400);
        // Persist only identifiers and minimal accounting fields, never the full
        // provider payload with contact, address, card or authentication data.
        $object = Arr::only($payload['data']['object'], ['id', 'object', 'customer', 'subscription', 'charge', 'amount', 'amount_refunded', 'amount_paid', 'currency', 'paid', 'status', 'status_transitions', 'parent']);
        foreach (['customer', 'subscription', 'charge'] as $key) if (isset($object[$key])) $object[$key] = SubscriptionState::id($object[$key]);
        $object['parent'] = ['subscription_details' => ['subscription' => SubscriptionState::id(data_get($object, 'parent.subscription_details.subscription'))]];
        $object['status_transitions'] = Arr::only($object['status_transitions'] ?? [], ['paid_at']);
        if (empty($object['customer'])) {
            $previous = SubscriptionState::id(data_get($payload, 'data.previous_attributes.customer'));
            if ($previous) $object['customer'] = $previous;
        }
        $stored = Arr::only($payload, ['id', 'type', 'created', 'livemode']);
        $stored['data'] = ['object' => $object];
        DB::table('stripe_webhook_events')->insertOrIgnore([
            'billing_account' => $account, 'event_id' => $payload['id'], 'type' => $payload['type'],
            'payload' => json_encode($stored), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $row = DB::table('stripe_webhook_events')->where('billing_account', $account)->where('event_id', $payload['id'])->first();
        $this->process($row->id);
    }

    public function process(int $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $event = DB::table('stripe_webhook_events')->where('id', $id)->lockForUpdate()->first();
                if (!$event || $event->processed_at) return;
                $payload = json_decode($event->payload, true);
                $this->handle($payload, $event->billing_account);
                DB::table('stripe_webhook_events')->where('id', $id)->update([
                    'processed_at' => now(), 'attempts' => $event->attempts + 1, 'last_error' => null, 'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            // Stripe retries on non-2xx responses. The inbox also survives retry
            // exhaustion and can be replayed by the reconciliation command.
            DB::table('stripe_webhook_events')->where('id', $id)->increment('attempts', 1, ['last_error' => get_class($exception), 'updated_at' => now()]);
            throw $exception;
        }
    }

    private function handle(array $payload, string $account): void
    {
        $type = $payload['type'];
        $object = $payload['data']['object'];
        $customer = $type === 'customer.updated' ? $object['id'] : ($object['customer'] ?? null);
        if (in_array($type, ['charge.succeeded', 'charge.refunded', 'charge.dispute.created'], true)) {
            $id = $type === 'charge.dispute.created' ? ($object['charge'] ?? null) : $object['id'];
            $charge = app(Gateway::class)->call($account, 'charges', 'retrieve', [], $id);
            $this->charge($charge, $account);
            return;
        }
        if ($type === 'invoice.payment_succeeded') {
            (new StripePaymentReceipts)->invoiceSucceeded($payload);
            \App\Activity\Actions::paidInvoice($payload);
        }
        if (!$customer) return;
        $sources = Stripe::where('stripe_id', $customer)->get()->filter(function ($source) use ($account) {
            return app(Accounts::class)->forSource($source) === $account;
        });
        if ($sources->count() > 1) throw new BillingException('Multiple memberships share this Stripe customer.');
        $source = $sources->first();
        $membership = $source ? Membership::where('source_type', Stripe::class)->where('source_id', $source->id)->first() : null;
        $userId = $membership ? $membership->user_id : DB::table('stripe_billing_profiles')->where('customer_id', $customer)->where('billing_account', $account)->value('user_id');
        $user = $userId ? User::find($userId) : null;
        if (!$user) return; // Unrelated Stripe customers and retained deleted accounts.
        app(Operations::class)->locked($user, function () use ($payload, $account, $user, $type, $object, $customer) {
            $billing = app(MembershipBilling::class);
            $source = $billing->source($user, false);
            if ($source && ($source->stripe_id !== $customer || app(Accounts::class)->forSource($source) !== $account)) return;
            if (!$source) {
                if (strpos($type, 'customer.subscription.') !== 0) return;
                $subscription = app(Gateway::class)->call($account, 'subscriptions', 'retrieve', ['expand' => ['latest_invoice.confirmation_secret']], $object['id']);
                $operationId = data_get($subscription, 'metadata.pianolit_operation');
                $operation = DB::table('stripe_billing_operations')->where('id', $operationId)->where('user_id', $user->id)
                    ->where('billing_account', $account)->where('customer_id', $object['customer'])->where('kind', 'purchase')->first();
                if (!$operation || ($operation->subscription_id && $operation->subscription_id !== $subscription['id'])) return;
                $source = $billing->save($user, $account, $subscription, $operation->plan);
                app(Operations::class)->update($operation->id, ['subscription_id' => $subscription['id'], 'state' => 'confirming']);
            } else {
                // Resolve a legacy customer once, then compare the actual
                // subscription identity before processing an event about it.
                if ($source->subscription_id && strpos($type, 'customer.subscription.') === 0 && $source->subscription_id !== $object['id']) return;
                $subscription = $billing->subscription($user);
                if (strpos($type, 'customer.subscription.') === 0 && $subscription['id'] !== $object['id']) return;
                $invoiceSubscription = $object['subscription'] ?? data_get($object, 'parent.subscription_details.subscription');
                if (strpos($type, 'invoice.') === 0 && $invoiceSubscription && $subscription['id'] !== $invoiceSubscription) return;
                $source = $billing->source($user);
            }
            // Fetching current provider state while holding the user's lock
            // prevents a delayed snapshot from undoing a newer update.
            $billing->refreshCard($source, $subscription);
            if (in_array($subscription['status'], ['canceled', 'incomplete_expired'], true)) {
                DB::table('stripe_billing_operations')->where('user_id', $user->id)->where('subscription_id', $subscription['id'])->whereNotNull('active_key')
                    ->update(['active_key' => null, 'state' => 'canceled', 'updated_at' => now()]);
            }
        });
    }

    private function charge(array $charge, string $account): void
    {
        $customer = SubscriptionState::id($charge['customer'] ?? null);
        $user = null;
        if ($customer) {
            $sources = Stripe::where('stripe_id', $customer)->get()->filter(function ($source) use ($account) { return app(Accounts::class)->forSource($source) === $account; });
            $source = $sources->count() === 1 ? $sources->first() : null;
            if ($source) $user = Membership::where('source_type', Stripe::class)->where('source_id', $source->id)->value('user_id');
            if (!$user) $user = DB::table('stripe_billing_profiles')->where('customer_id', $customer)->where('billing_account', $account)->value('user_id');
            if (!$user) {
                $shop = Customer::where('stripe_id', $customer)->first();
                if ($shop) $user = $shop->user_id;
            }
        }
        if (($charge['status'] ?? null) !== 'succeeded') return;
        $record = function () use ($charge, $user) {
            Payment::create(['user_id' => $user, 'charge_id' => $charge['id'], 'amount' => $charge['amount'],
                'refund' => $charge['amount_refunded'] ?? 0, 'currency' => $charge['currency']]);
        };
        (new StripePaymentReceipts)->oncePerCharge(['data' => ['object' => $charge]], $record);
        Payment::where('charge_id', $charge['id'])->update(['refund' => $charge['amount_refunded'] ?? 0, 'currency' => $charge['currency']]);
    }
}
