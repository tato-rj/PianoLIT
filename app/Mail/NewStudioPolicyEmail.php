<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\{User, StudioPolicy};

class NewStudioPolicyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $policy;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(StudioPolicy $policy)
    {
        $this->policy = $policy;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Studio Policy is ready!')->markdown('emails.studio-policy');
    }
}
