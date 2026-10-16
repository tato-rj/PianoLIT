<?php

namespace App\Mail\Admin;

use Illuminate\Mail\Mailable;
use Illuminate\Support\HtmlString;

class EmailPreview extends Mailable
{
    public function __construct($subject, $html, $text)
    {
        // Send already-rendered sample content synchronously. No real models or
        // queued transactional mailables should be restored by a preview send.
        $this->subject('[Preview] '.$subject)->html($html)->text(new HtmlString($text));
    }

    public function build()
    {
        return $this;
    }
}
