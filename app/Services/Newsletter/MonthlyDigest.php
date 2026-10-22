<?php

namespace App\Services\Newsletter;

use App\Newsletter\Digest;
use App\EmailList;
use App\Jobs\SendNewsletterCampaign;
use App\Mail\Newsletter\MonthlyDigestEmail;
use Illuminate\Support\Facades\DB;

class MonthlyDigest
{
    public function month()
    {
        return now('America/New_York')->startOfMonth()->subMonth()->format('Y-m');
    }

    public function items($month, array $keys = null)
    {
        return app(PublishedContent::class)->forMonth($month, $keys);
    }

    public function preview(EmailList $list, $subscription = null)
    {
        return new MonthlyDigestEmail($this->month(), $this->items($this->month()), $list, $subscription, 'preview');
    }

    public function queuePreviousMonth()
    {
        $month = $this->month();
        $items = $this->items($month);
        $list = EmailList::where('name', EmailList::NEWSLETTER)->first();
        // An empty month never creates a campaign or queues any mail.
        if ($items->isEmpty() || !$list || !$list->subscribers()->exists()) return null;

        return DB::transaction(function () use ($month, $items, $list) {
            DB::table('newsletter_digests')->insertOrIgnore([
                'month' => $month, 'email_list_id' => $list->id,
                'content_keys' => json_encode($items->pluck('key')->all()), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $digest = Digest::where('month', $month)->lockForUpdate()->firstOrFail();
            if ($digest->queued_at) return null;

            // Freeze the audience once; later signups wait for the next digest.
            $audience = DB::table('email_list_subscription as memberships')
                ->join('subscriptions', 'subscriptions.id', '=', 'memberships.subscription_id')
                ->where('memberships.email_list_id', $list->id)
                ->selectRaw('MIN(subscriptions.id) as id')->groupByRaw('LOWER(TRIM(subscriptions.email))');
            DB::query()->fromSub($audience, 'audience')->chunkById(500, function ($subscribers) use ($digest) {
                DB::table('newsletter_deliveries')->insertOrIgnore($subscribers->map(function ($subscriber) use ($digest) {
                    return ['digest_id' => $digest->id, 'subscription_id' => $subscriber->id, 'created_at' => now()];
                })->all());
            });

            SendNewsletterCampaign::dispatch($digest->id)->onConnection('redis');
            $digest->update(['queued_at' => now()]);
            $list->update(['last_sent_at' => now()]);

            return $digest->campaignId();
        }, 3);
    }

    public function pruneDeliveries()
    {
        // Recipient guards follow the same three-calendar-month email-history policy.
        // Retain the non-recipient month marker so old digests cannot be resent.
        $expired = Digest::where('created_at', '<', now()->subMonthsNoOverflow(3))->select('id');
        do {
            $ids = DB::table('newsletter_deliveries')->whereIn('digest_id', $expired)->orderBy('id')->limit(500)->pluck('id');
            if ($ids->isNotEmpty()) DB::table('newsletter_deliveries')->whereIn('id', $ids)->delete();
        } while ($ids->isNotEmpty());
    }
}
