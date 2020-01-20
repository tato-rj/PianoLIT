<?php

namespace App;

class EmailList extends PianoLit
{
	protected $dates = ['last_sent_at'];

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
}
