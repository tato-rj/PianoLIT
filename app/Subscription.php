<?php

namespace App;

use App\Mail\Newsletter\Welcome;

class Subscription extends PianoLit
{
	protected $casts = ['is_active' => 'boolean', 'daily_timeline' => 'boolean'];

    public function getRouteKeyName()
    {
        return 'email';
    }

    public function getStatusAttribute()
    {
        return $this->is_active ? 'subscribed' : 'unsubscribed';
    }

    public function reactivate()
    {
    	$this->update(['is_active' => true]);
    }

    public function deactivate()
    {
    	$this->update(['is_active' => false]);
    }

    public function updateStatus()
    {
        $this->update(['is_active' => ! $this->is_active]);
    }

    public function scopeByEmail($query, $email)
    {
    	return $query->where('email', $email);
    }

    public function scopeActive($query)
    {
    	return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeCreateOrActivate($query, $email)
    {
    	$record = $query->byEmail($email);

    	if ($record->exists())
    		return $record->first()->reactivate();

        \Mail::to($email)->send(new Welcome);

    	return $this->create(['email' => $email]);
    }

    public function scopeTimeline($query)
    {
        return $query->where('daily_timeline', true);
    }
}
