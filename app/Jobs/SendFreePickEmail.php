<?php

namespace App\Jobs;

use App\{EmailList, FreePick, Subscription};
use App\Mail\FreePickEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendFreePickEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = 30;
    public $freePickId, $subscriberId;

    public function __construct($freePickId, $subscriberId)
    {
        $this->freePickId = $freePickId;
        $this->subscriberId = $subscriberId;
    }

    public function handle()
    {
        $pick = FreePick::with('piece')->find($this->freePickId);
        if (!$pick || !$pick->piece || !$pick->send_email || !$pick->email_queued_at || !$pick->discover_refreshed_at) return;
        $subscriber = Subscription::find($this->subscriberId);
        if (!$subscriber) return;
        DB::transaction(function () use ($pick, $subscriber) {
            DB::table('free_pick_email_deliveries')->insertOrIgnore([
                'free_pick_id' => $pick->id, 'subscription_id' => $this->subscriberId,
            ]);
            $delivery = DB::table('free_pick_email_deliveries')->where('free_pick_id', $pick->id)
                ->where('subscription_id', $this->subscriberId)->lockForUpdate()->first();
            if (!$delivery || $delivery->sent_at) return;
            $list = EmailList::freepick();
            if (!$list || !$list->subscribers()->where('subscriptions.id', $subscriber->id)->exists()) return;
            // Send inside this recipient's lock, bypassing an additional mailable queue
            // hop. Pin the scheduled piece even when a later Freepick is now current.
            (new FreePickEmail($pick->campaignId(), $subscriber, $pick->piece))->to($subscriber->email)->send(app('mailer'));
            DB::table('free_pick_email_deliveries')->where('id', $delivery->id)->update(['sent_at' => now('UTC')]);
        });
    }
}
