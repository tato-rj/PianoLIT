<?php

namespace App;

class EmailLog extends PianoLit
{
    protected $dates = [
        'delivered_at',
        'failed_at',
    ];

    public function sender()
    {
        return $this->morphTo();
    }
}
