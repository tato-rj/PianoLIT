<?php

namespace App\Console\Commands;

use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use App\Billing\Stripe\{MembershipBilling, Operations, WebhookProcessor};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStripeMemberships extends Command
{
    protected $signature = 'stripe:reconcile {--limit=100 : Maximum pending events and memberships per run} {--audit : Report local inconsistencies without contacting Stripe or changing data}';
    protected $description = 'Replay pending signed events and reconcile local membership state using read-only Stripe requests';

    public function handle()
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if (!$limit || $limit < 1 || $limit > 1000) { $this->error('The limit must be between 1 and 1000.'); return 1; }
        if ($this->option('audit')) {
            $this->line('Duplicate membership owners: '.DB::table('memberships')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get()->count());
            $this->line('Duplicate charge identities: '.DB::table('payments')->select('charge_id')->groupBy('charge_id')->havingRaw('COUNT(*) > 1')->get()->count());
            $this->line('Stripe memberships awaiting subscription linkage: '.Stripe::whereNull('subscription_id')->count());
            $this->line('Pending webhook events: '.DB::table('stripe_webhook_events')->whereNull('processed_at')->count());
            $this->line('Unfinished checkouts older than 23 hours: '.DB::table('stripe_billing_operations')->whereNotNull('active_key')->where('created_at', '<', now()->subHours(23))->count());
            return 0;
        }
        $failures = 0;
        foreach (DB::table('stripe_webhook_events')->whereNull('processed_at')->orderBy('updated_at')->orderBy('id')->limit($limit)->pluck('id') as $id) {
            try { app(WebhookProcessor::class)->process($id); }
            catch (\Throwable $exception) { report($exception); $failures++; }
        }
        // Oldest synchronization first ensures successive bounded runs reach all
        // accounts. No provider mutation or email occurs in this command.
        $sources = Stripe::orderBy('synced_at')->orderBy('id')->limit($limit)->get();
        foreach ($sources as $source) {
            $membership = Membership::where('source_type', Stripe::class)->where('source_id', $source->id)->first();
            $user = $membership ? $membership->user : null;
            if (!$user) { $source->update(['synced_at' => now()]); continue; }
            try {
                app(Operations::class)->locked($user, function () use ($user) {
                    $billing = app(MembershipBilling::class);
                    $subscription = $billing->subscription($user);
                    $billing->refreshCard($billing->source($user), $subscription);
                });
            } catch (\Throwable $exception) {
                // Advance the rotation even when an account needs manual review.
                $source->update(['synced_at' => now()]); report($exception); $failures++;
            }
        }
        DB::table('stripe_webhook_events')->whereNotNull('processed_at')->where('processed_at', '<', now()->subDays(90))->where('payload', '!=', '{}')->update(['payload' => '{}']);
        $this->info('Stripe reconciliation finished; '.$failures.' item(s) need retry or review.');
        return $failures ? 1 : 0;
    }
}
