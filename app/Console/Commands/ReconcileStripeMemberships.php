<?php

namespace App\Console\Commands;

use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use App\Billing\Stripe\{Accounts, BillingException, MembershipBilling, Operations, SubscriptionResolutionException, WebhookProcessor};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStripeMemberships extends Command
{
    protected $signature = 'stripe:reconcile {--limit=100 : Maximum pending events and memberships per run} {--audit : Report local inconsistencies without contacting Stripe or changing data} {--source= : Reconcile only this Stripe membership source, without processing webhook events}';
    protected $description = 'Replay pending signed events and reconcile local membership state using read-only Stripe requests';

    public function handle()
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if (!$limit || $limit < 1 || $limit > 1000) { $this->error('The limit must be between 1 and 1000.'); return 1; }
        $sourceId = null;
        if ($this->input->hasParameterOption('--source')) {
            $sourceId = filter_var($this->option('source'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($sourceId === false) { $this->error('The source must be a positive Stripe membership source ID.'); return 1; }
            if ($this->option('audit')) { $this->error('Use --audit without --source for the read-only database audit.'); return 1; }
        }
        if ($this->option('audit')) {
            $this->line('Duplicate membership owners: '.DB::table('memberships')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get()->count());
            $this->line('Duplicate charge identities: '.DB::table('payments')->select('charge_id')->groupBy('charge_id')->havingRaw('COUNT(*) > 1')->get()->count());
            $this->line('Stripe memberships awaiting subscription linkage: '.Stripe::whereNull('subscription_id')->count());
            $this->line('Pending webhook events: '.DB::table('stripe_webhook_events')->whereNull('processed_at')->count());
            $this->line('Unfinished checkouts older than 23 hours: '.DB::table('stripe_billing_operations')->whereNotNull('active_key')->where('created_at', '<', now()->subHours(23))->count());
            $unlinked = DB::table('stripe_memberships as s')->leftJoin('memberships as m', function ($join) {
                $join->on('m.source_id', '=', 's.id')->where('m.source_type', '=', Stripe::class);
            })->leftJoin('users as u', 'u.id', '=', 'm.user_id')->whereNull('s.subscription_id')
                ->orderBy('s.id')->orderBy('m.id')->limit($limit)
                ->get(['s.id', 's.stripe_id', 's.billing_account', 's.status', 'm.user_id', 'u.id as existing_user_id']);
            if ($unlinked->isNotEmpty()) {
                $this->line('Unlinked Stripe sources (local state only; showing up to '.$limit.' associations):');
                $this->table(['Source', 'Resolved account', 'Customer', 'Local status', 'Owner'], $unlinked->map(function ($row) {
                    $source = new Stripe(['stripe_id' => $row->stripe_id, 'billing_account' => $row->billing_account]);
                    $owner = $row->existing_user_id ?: ($row->user_id ? 'missing account '.$row->user_id : 'no association');
                    return [$row->id, app(Accounts::class)->forSource($source), $row->stripe_id, $row->status, $owner];
                })->all());
            }
            return 0;
        }
        $failures = 0;
        $pending = $sourceId === null
            ? DB::table('stripe_webhook_events')->whereNull('processed_at')->orderBy('updated_at')->orderBy('id')->limit($limit)->pluck('id')
            : [];
        foreach ($pending as $id) {
            try { app(WebhookProcessor::class)->process($id); }
            catch (\Throwable $exception) { report($exception); $failures++; }
        }
        // Oldest synchronization first ensures successive bounded runs reach all
        // accounts. No provider mutation or email occurs in this command.
        $sources = Stripe::when($sourceId !== null, function ($query) use ($sourceId) { $query->whereKey($sourceId); })
            ->orderBy('synced_at')->orderBy('id')->limit($limit)->get();
        if ($sourceId !== null && $sources->isEmpty()) { $this->error('Stripe membership source '.$sourceId.' does not exist.'); return 1; }
        foreach ($sources as $source) {
            $membership = Membership::where('source_type', Stripe::class)->where('source_id', $source->id)->first();
            $user = $membership ? $membership->user : null;
            if (!$user) {
                if ($sourceId !== null) { $this->error('The selected source has no existing account owner; its records were left unchanged.'); $failures++; }
                else $source->update(['synced_at' => now()]);
                continue;
            }
            try {
                $identity = app(Operations::class)->locked($user, function () use ($user, $sourceId) {
                    $billing = app(MembershipBilling::class);
                    if ($sourceId !== null) {
                        $owners = DB::table('memberships')->where('source_type', Stripe::class)->where('source_id', $sourceId)
                            ->lockForUpdate()->pluck('user_id');
                        if ($owners->count() !== 1 || (int) $owners[0] !== (int) $user->id
                            || (int) $billing->source($user)->id !== $sourceId) {
                            throw new BillingException('The selected source has ambiguous or changed account ownership.');
                        }
                    }
                    $subscription = $billing->subscription($user);
                    $current = $billing->source($user);
                    $billing->refreshCard($current, $subscription);
                    return [$current->billing_account, $subscription['id']];
                });
                if ($sourceId !== null) $this->info('Reconciled source '.$sourceId.' using '.$identity[0].' / '.$identity[1].'.');
            } catch (\Throwable $exception) {
                // Advance the rotation even when an account needs manual review.
                // A targeted repair has no rotation to advance on failure.
                if ($sourceId === null) $source->update(['synced_at' => now()]);
                if ($exception instanceof SubscriptionResolutionException) {
                    if ($sourceId === null && $exception->isEndedHistory()) {
                        $this->line('Historical membership left unlinked: '.$exception->diagnostic().'.');
                        continue;
                    }
                    $this->error('Stripe membership needs review: '.$exception->diagnostic().'.');
                }
                report($exception); $failures++;
            }
        }
        if ($sourceId === null) {
            DB::table('stripe_webhook_events')->whereNotNull('processed_at')->where('processed_at', '<', now()->subDays(90))->where('payload', '!=', '{}')->update(['payload' => '{}']);
        }
        $this->info('Stripe reconciliation finished; '.$failures.' item(s) need retry or review.');
        return $failures ? 1 : 0;
    }
}
