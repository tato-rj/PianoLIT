<?php

namespace App;

use App\Mail\Newsletter\Welcome;

class Subscription extends PianoLit
{
    protected $lists = ['newsletter_list', 'birthday_list'];
	protected $casts = ['newsletter_list' => 'boolean', 'birthday_list' => 'boolean'];

    public function scopeLists()
    {
        return $this->lists;
    }

    public function getRouteKeyName()
    {
        return 'email';
    }

    public function getStatusFor($list, $boolean = false)
    {
        $this->validateList($list);

        $results = $boolean ? [true, false] : ['subscribed', 'unsubscribed'];

        return $this->$list ? $results[0] : $results[1];
    }

    public function reactivate()
    {
        $this->validateList($list);

    	$this->update([$list => true]);
    }

    public function reactivateAll()
    {
        foreach ($this->lists as $list) {
            $this->reactivate($list);
        }
    }

    public function deactivate($list)
    {
        $this->validateList($list);

    	$this->update([$list => false]);
    }

    public function toggleStatusFor($list)
    {
        $this->validateList($list);

        $this->update([$list => ! $this->$list]);
    }

    public function scopeByEmail($query, $email)
    {
    	return $query->where('email', $email);
    }

    public function scopeCreateOrActivate($query, $email)
    {
    	$record = $query->byEmail($email);

    	if ($record->exists())
    		return $record->first()->reactivateAll();

        \Mail::to($email)->send(new Welcome);

    	return $this->create(['email' => $email]);
    }

    public function scopeActiveList($query, $list)
    {
        $this->validateList($list);

        return $query->where($list, true);
    }

    public function scopeInactiveList($query, $list)
    {
        $this->validateList($list);

        return $query->where($list, false);
    }

    public function validateList($list)
    {
        if (! \Schema::hasColumn($this->getTable(), $list))
            abort(403, 'The list ' . $list . ' does not exist.');
    }
}
