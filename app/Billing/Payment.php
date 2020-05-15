<?php

namespace App\Billing;

use App\{PianoLit, User};

class Payment extends PianoLit
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
