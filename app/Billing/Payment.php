<?php

namespace App\Billing;

use App\{PianoLit, User};

class Payment extends PianoLit
{
    protected $hidden = ['currency'];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
