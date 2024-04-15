<?php

namespace App;

use App\Events\Emails\Unsubscribed;

class EmailList extends PianoLit
{
	protected $dates = ['last_sent_at'];
    protected $casts = ['previewable' => 'boolean'];
	protected $withCount = ['subscribers'];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($list) {
            $list->subscribers()->detach();
        });
    }

    public function emailLog()
    {
        return $this->morphOne(EmailLog::class, 'sender');
    }

	public function send()
	{
        $list_id = $this->listId();

        // ->where('id', '>', 5522)
        
        $this->subscribers()->where('id', '>', 5522)->chunk(500, function($subscribers) use ($list_id) {
            foreach ($subscribers as $subscriber) {
                \Mail::to($subscriber->email)->queue($this->mailable($list_id, $subscriber));
            }
        });

        $this->update(['last_sent_at' => now()]);
	}

	public function mailable($list_id, $subscription = null)
	{
		$model = '\App\Mail\\' . str_replace(' ', '', $this->name) . 'Email';

		if (class_exists($model))
			return new $model($list_id, $subscription);

		abort(404, "The mail class {$model} does not exist");
	}

    public function subscribers()
    {
    	return $this->belongsToMany(Subscription::class);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', ucwords($name))->first();
    }

    public function scopeNewsletter($query)
    {
        return $query->where('name', 'Newsletter')->first();
    }

    public function scopeFreepick($query)
    {
        return $query->where('name', 'Free Pick')->first();
    }

    public function scopeBirthdays($query)
    {
        return $query->where('name', 'Birthdays')->first();
    }

    public function scopeTutorials($query)
    {
        return $query->where('name', 'Latest Tutorials')->first();
    }

    public function scopeTest($query)
    {
        return $query->where('name', 'Test')->first();
    }

    public function listId()
    {
        return  str_slug($this->name) . '.' . now()->timestamp;
    }

    public function scopeDatatable($query, EmailList $list)
    {
        return datatable(Subscription::query())->withDate()->withBlade([
            'status' => view('admin.pages.subscriptions.toggles.status', compact('list')),
            'action' => view('admin.pages.subscriptions.actions')
        ])->checkable()->make();
    }

    public function has($email)
    {
        return $this->subscribers()->byEmail($email)->exists();
    }

    public function add(Subscription $subscription)
    {
        if (! $this->has($subscription->email))
            return $this->subscribers()->attach($subscription);        
    }

    public function remove(Subscription $subscription)
    {
        if ($this->has($subscription->email)) {
            $this->subscribers()->detach($subscription);

            event(new Unsubscribed($this, $subscription));
        }
    }

    public function toggle(Subscription $subscription)
    {
        return $this->has($subscription->email) ? 
                $this->remove($subscription) : 
                $this->add($subscription);
    }

    public function getActionsViewAttribute()
    {
        return 'admin.pages.subscriptions.lists.actions.' . str_slug($this->name);
    }
}
