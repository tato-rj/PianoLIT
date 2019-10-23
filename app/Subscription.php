<?php

namespace App;

use App\Mail\Newsletter\Welcome;
use App\Notifications\Subscriptions\NewSubscriber;

class Subscription extends PianoLit
{    
    protected $lists = ['newsletter_list', 'birthday_list'];
	protected $casts = ['newsletter_list' => 'boolean', 'birthday_list' => 'boolean'];
    protected $appends = ['report_name'];
    protected $report_by = 'email';

    public function scopeAdmin($query)
    {
        return $query->where('email', 'arthurvillar@gmail.com')->first();
    }

    public function scopeLists($query, $list = null)
    {
        if (is_null($list))
            return $this->lists;

        $this->validateList($list);

        return [$list];
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

    public function reactivate($list)
    {
        $this->validateList($list);

    	$this->update([$list => true]);

        return $this;
    }

    public function reactivateAll()
    {
        foreach ($this->lists as $list) {
            $this->reactivate($list);
        }

        return $this;
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

    public function scopeCreateOrActivate($query, $form)
    {
    	$record = $query->byEmail($form->email);

    	if ($record->exists())
    		return $record->first()->reactivateAll();

        $subscriber = $this->create([
            'email' => $form->email,
            'origin_url' => $form->origin_url
        ]);

        \Mail::to($form->email)->send(new Welcome($subscriber));
        
        Admin::notifyAll(new NewSubscriber($subscriber));

        return $subscriber;
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
