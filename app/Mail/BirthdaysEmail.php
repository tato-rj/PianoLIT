<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\{EmailList, Timeline, Composer};
use App\Mail\Traits\Trackable;

class BirthdaysEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable;

    public $composer, $history, $subscription, $list, $list_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($list_id, $subscription = null)
    {
        $this->composer = Composer::famous()->bornToday()->inRandomOrder()->first();
     
        if (! $this->composer)
            abort(413, 'No composer was born today.');
     
        $this->history = Timeline::aroundYear($this->composer->born_in, 5)->get();
        $this->subscription = $subscription;
        $this->list = EmailList::birthdays();
        $this->list_id = $list_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->list_id, $this->subscription);

        return $this->subject($this->composer->last_name . '\'s birthday today!')->markdown('emails.timeline.daily');
    }
}
