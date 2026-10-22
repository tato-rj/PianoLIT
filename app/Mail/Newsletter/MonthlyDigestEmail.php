<?php

namespace App\Mail\Newsletter;

use App\Mail\Traits\Trackable;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class MonthlyDigestEmail extends Mailable
{
    use Queueable, Trackable;

    public $month, $items, $list, $subscription, $list_id;

    public function __construct($month, $items, $list, $subscription = null, $campaignId = 'preview')
    {
        $this->month = $month;
        $this->items = $items;
        $this->list = $list;
        $this->subscription = $subscription;
        $this->list_id = $campaignId;
    }

    public function build()
    {
        $this->track($this->list, $this->list_id, $this->subscription);
        $monthLabel = CarbonImmutable::parse($this->month.'-01')->format('F Y');
        $unsubscribeUrl = $this->subscription
            ? rtrim(route('home'), '/').route('subscriptions.unsubscribe', [$this->subscription, $this->list], false)
            : null;

        return $this->subject(trim(view('emails.newsletter.monthly-subject', compact('monthLabel'))->render()))
            ->markdown('emails.newsletter.monthly-digest', compact('monthLabel', 'unsubscribeUrl'));
    }

    public function tags()
    {
        return ['email lists', 'newsletter'];
    }
}
