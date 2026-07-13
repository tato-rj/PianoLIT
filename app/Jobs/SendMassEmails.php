<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\Emails\EmailListSent;
use App\EmailList;

class SendMassEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    public $listId;
    public $campaignId;
    public $subject;
    public $afterSubscriberId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($listId, $campaignId, $subject = null, $afterSubscriberId = 0)
    {
        $this->listId = $listId;
        $this->campaignId = $campaignId;
        $this->subject = $subject;
        $this->afterSubscriberId = $afterSubscriberId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $list = EmailList::find($this->listId);

        if (! $list) {
            return;
        }

        $subscribers = $list->subscribers()
            ->where('subscriptions.id', '>', $this->afterSubscriberId)
            ->orderBy('subscriptions.id')
            ->take(500)
            ->get(['subscriptions.id']);

        if ($subscribers->isEmpty()) {
            event(new EmailListSent($list));
            return;
        }

        foreach ($subscribers as $subscriber) {
            SendEmail::dispatch($this->listId, $this->campaignId, $subscriber->id, $this->subject)
                ->onConnection('redis');
        }

        self::dispatch($this->listId, $this->campaignId, $this->subject, $subscribers->last()->id)
            ->onConnection('redis');
    }
}
