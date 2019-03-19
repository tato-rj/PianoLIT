<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\HasMembership;

class User extends Authenticatable
{
    use Notifiable, HasMembership;

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function membership()
    {
    	return $this->hasOne(Membership::class);
    }
}
