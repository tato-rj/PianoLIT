<?php

namespace App\Jobs;

use App\Newsletter\Digest;
use App\EmailList;
use App\Events\Emails\EmailListSent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\DB;

class SendNewsletterCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 5;
    public $backoff = 30;
    public $digestId, $afterDeliveryId;

    public function __construct($digestId, $afterDeliveryId = 0)
    {
        $this->digestId = $digestId;
        $this->afterDeliveryId = $afterDeliveryId;
    }

    public function handle()
    {
        $digest = Digest::find($this->digestId);
        // A Redis worker may start before the SQL scheduling transaction commits.
        if (!$digest || !$digest->queued_at) throw new \RuntimeException('The newsletter has not committed yet.');
        if ($digest->fanned_out_at) return;
        $ids = DB::table('newsletter_deliveries')->where('digest_id', $digest->id)
            ->where('id', '>', $this->afterDeliveryId)->orderBy('id')->limit(500)->pluck('id');
        foreach ($ids as $id) SendNewsletterEmail::dispatch($id)->onConnection('redis');
        if ($ids->isNotEmpty()) {
            self::dispatch($digest->id, $ids->last())->onConnection('redis');
            return;
        }
        DB::transaction(function () use ($digest) {
            $locked = Digest::whereKey($digest->id)->lockForUpdate()->first();
            if (!$locked || $locked->fanned_out_at) return;
            $locked->update(['fanned_out_at' => now()]);
            $list = EmailList::find($locked->email_list_id);
            if ($list) event(new EmailListSent($list));
        });
    }
}
