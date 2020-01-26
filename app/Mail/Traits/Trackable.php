<?php

namespace App\Mail\Traits;

trait Trackable
{
	public function track($model, $list_id, $recipient = null)
	{
		if ($recipient) {
	        $this->withSwiftMessage(function ($message) use ($model, $list_id, $recipient) {
	            $model->emailLog()->create([
	                'message_id' => $message->getId(),
	                'list_id' => $list_id,
	                'recipient' => $recipient->email
	            ]);
	        });
	    }
	}
}
