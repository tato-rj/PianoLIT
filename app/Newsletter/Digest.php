<?php

namespace App\Newsletter;

use App\PianoLit;

class Digest extends PianoLit
{
    protected $table = 'newsletter_digests';
    protected $casts = ['content_keys' => 'array'];
    protected $dates = ['queued_at', 'fanned_out_at'];

    public function campaignId()
    {
        // Keep the existing email-report name.timestamp contract.
        return 'pianolit-news.'.$this->created_at->timestamp;
    }
}
