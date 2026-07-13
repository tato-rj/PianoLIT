<?php

namespace App\Jobs;

use App\EmailList;
use App\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    public $listId;
    public $campaignId;
    public $subscriberId;
    public $subject;

    public function __construct($listId, $campaignId, $subscriberId, $subject = null)
    {
        $this->listId = $listId;
        $this->campaignId = $campaignId;
        $this->subscriberId = $subscriberId;
        $this->subject = $subject;
    }

    public function handle()
    {
        $list = EmailList::find($this->listId);
        $subscriber = Subscription::find($this->subscriberId);

        if (! $list || ! $subscriber || ! $list->subscribers()->where('subscriptions.id', $subscriber->id)->exists()) {
            return;
        }

        $mailable = $list->mailable($this->campaignId, $subscriber);

        if ($this->subject && property_exists($mailable, 'subject')) {
            $mailable->subject = $this->subject;
        }

        $mailable->onConnection('redis');
        Mail::to($subscriber->email)->queue($mailable);
    }
}
