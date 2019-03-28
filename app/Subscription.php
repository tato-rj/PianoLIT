<?php

namespace App;

class Subscription extends PianoLit
{
	protected $casts = ['is_active' => 'boolean'];

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

    	return $this->create(['email' => $email]);
    }
}
