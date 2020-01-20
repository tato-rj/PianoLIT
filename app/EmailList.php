<?php

namespace App;

class EmailList extends PianoLit
{
	protected $dates = ['last_sent_at'];
	protected $withCount = ['subscribers'];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($list) {
            $list->subscribers()->detach();
        });
    }

	public function send()
	{
		foreach ($this->subscribers as $subscriber) {
			\Mail::to($subscriber->email)->send($this->mailable());
		}
	}

	public function mailable()
	{
		$model = '\App\Mail\\' . str_replace(' ', '', $this->name) . 'Email';
		
		if (class_exists($model))
			return new $model;

		abort(404, "The mail class {$model} does not exist");
	}

    public function subscribers()
    {
    	return $this->belongsToMany(Subscription::class);
    }

    public function scopeDatatable($query, EmailList $list)
    {
        return datatable(Subscription::query())->withDate()->withBlade([
            'status' => view('admin.pages.subscriptions.toggles.status', compact('list')),
            'action' => view('admin.pages.subscriptions.actions')
        ])->checkable()->make();
    }
}
