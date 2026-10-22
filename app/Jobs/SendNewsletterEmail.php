<?php

namespace App\Jobs;

use App\Newsletter\Digest;
use App\{EmailList, Subscription};
use App\Mail\Newsletter\MonthlyDigestEmail;
use App\Services\Newsletter\MonthlyDigest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\DB;

class SendNewsletterEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = 30;
    public $deliveryId;

    public function __construct($deliveryId)
    {
        $this->deliveryId = $deliveryId;
    }

    public function handle(MonthlyDigest $digests)
    {
        DB::transaction(function () use ($digests) {
            $delivery = DB::table('newsletter_deliveries')->where('id', $this->deliveryId)->lockForUpdate()->first();
            if (!$delivery || $delivery->sent_at || $delivery->skipped_at) return;
            $digest = Digest::find($delivery->digest_id);
            $list = $digest ? EmailList::where('name', EmailList::NEWSLETTER)->find($digest->email_list_id) : null;
            $subscriber = Subscription::find($delivery->subscription_id);
            $items = $digest ? $digests->items($digest->month, $digest->content_keys) : collect();
            if (!$list || !$subscriber || !$list->subscribers()->where('subscriptions.id', $subscriber->id)->exists() || $items->isEmpty()) {
                DB::table('newsletter_deliveries')->where('id', $delivery->id)->update(['skipped_at' => now()]);
                return;
            }
            // Send once under this recipient's lock, without another mailable queue hop.
            (new MonthlyDigestEmail($digest->month, $items, $list, $subscriber, $digest->campaignId()))
                ->to($subscriber->email)->send(app('mailer'));
            DB::table('newsletter_deliveries')->where('id', $delivery->id)->update(['sent_at' => now()]);
        });
    }
}
