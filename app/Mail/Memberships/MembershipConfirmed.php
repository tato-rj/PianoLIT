<?php

namespace App\Mail\Memberships;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Mail\Traits\EditorialLayout;

class MembershipConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, EditorialLayout;

    public $firstName, $plan, $trial, $renewalDate;
    public $theme = 'welcome';
    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(string $firstName, string $plan, bool $trial, ?string $renewalDate)
    {
        // Snapshot only customer-facing details; no provider objects or card data in the queue.
        $this->firstName = $firstName;
        $this->plan = ucfirst($plan).' Premium';
        $this->trial = $trial;
        $this->renewalDate = $renewalDate;
    }

    public function build()
    {
        return $this->subject('You’re all set! Welcome to PianoLIT Premium')->markdown('emails.memberships.confirmed', [
            'websiteUrl' => $this->websiteUrl(),
        ]);
    }
}
