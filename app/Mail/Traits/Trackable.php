<?php

namespace App\Mail\Traits;

trait Trackable
{
	public function track($model, $recipient = null)
	{
		if ($recipient) {
	        $this->withSwiftMessage(function ($message) use ($model, $recipient) {
	            $model->emailLog()->create([
	                'message_id' => $message->getId(),
	                'recipient' => $recipient->email
	            ]);
	        });
	    }
	}
}
