<?php

namespace App;

use App\Mail\Newsletter\Welcome;
use App\Notifications\Subscriptions\NewSubscriber;

class Subscription extends PianoLit
{    
    protected $appends = ['report_name'];
    protected $report_by = 'email';

    public function getRouteKeyName()
    {
        return 'email';
    }

    public function scopeAdmin($query)
    {
        return $query->where('email', 'arthurvillar@gmail.com')->first();
    }

    public function scopeLists($query)
    {
        return $this->belongsToMany(EmailList::class);
    }

    public function join(EmailList $list)
    {
        return $this->lists()->attach($list);
    }

    public function joinAll()
    {
        foreach (EmailList::all() as $list) {
            $this->lists()->attach($list);
        }
    }

    public function leave(EmailList $list)
    {
        return $this->lists()->detach($list);
    }

    public function leaveAll(EmailList $list)
    {
        foreach (EmailList::all() as $list) {
            $this->lists()->detach($list);
        }
    }

    public function in(EmailList $list)
    {
        return $this->lists()->byName($list->name)->exists();
    }

    public function getStatusFor($list)
    {
        return EmailList::byName($list)->has($this->email);
    }

    public function scopeByEmail($query, $email)
    {
    	return $query->where('email', $email);
    }

    public function scopeCreateOrActivate($query, $form, $notifyUser = true)
    {
    	$record = $query->byEmail($form->email);

    	if ($record->exists())
    		return $record->first()->joinAll();

        $subscriber = $this->create([
            'email' => strtolower($form->email),
            'origin_url' => $form->origin_url ?? route('register')
        ]);

        $subscriber->joinAll();

        if ($notifyUser)
            \Mail::to($form->email)->send(new Welcome($subscriber));
        
        Admin::notifyAll(new NewSubscriber($subscriber));

        return $subscriber;
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withBlade([
            'action' => view('admin.pages.subscriptions.actions')
        ])->checkable()->make();
    }
}
