<?php

namespace App\Jobs;

use App\{EmailList, FreePick};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFreePickCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 5;
    public $backoff = 15;
    public $freePickId, $afterSubscriberId;

    public function __construct($freePickId, $afterSubscriberId = 0)
    {
        $this->freePickId = $freePickId;
        $this->afterSubscriberId = $afterSubscriberId;
    }

    public function handle()
    {
        $pick = FreePick::find($this->freePickId);
        if (!$pick || !$pick->send_email || !$pick->discover_refreshed_at) return;
        // Redis workers can pick up the job before the SQL release transaction commits.
        if (!$pick->email_queued_at) throw new \RuntimeException('The Freepick email release is not committed yet.');
        $list = EmailList::freepick();
        if (!$list) throw new \RuntimeException('The Free Pick email list is missing.');
        $subscribers = $list->subscribers()->where('subscriptions.id', '>', $this->afterSubscriberId)
            ->orderBy('subscriptions.id')->take(500)->get(['subscriptions.id']);
        foreach ($subscribers as $subscriber) {
            SendFreePickEmail::dispatch($pick->id, $subscriber->id)->onConnection('redis');
        }
        if ($subscribers->isNotEmpty()) {
            self::dispatch($pick->id, $subscribers->last()->id)->onConnection('redis');
        }
    }
}
