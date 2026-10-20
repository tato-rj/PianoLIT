<?php

namespace App\Mail\Traits;

trait Trackable
{
	public function track($model, $list_id, $recipient = null)
	{
		if ($recipient && ! \App\Activity\ExcludedAccounts::email($recipient->email)) {
	        $this->withSwiftMessage(function ($message) use ($model, $list_id, $recipient) {
                $log = \Illuminate\Support\Facades\DB::transaction(function () use ($model, $message, $list_id, $recipient) {
                    return $model->emailLog()->create([
                        'message_id' => $message->getId(),
                        'list_id' => $list_id,
                        'recipient' => $recipient->email,
                    ]);
                });
                if ($log->exists && $list_id) {
                    $message->getHeaders()->addTextHeader('X-Mailgun-Tag', \App\Services\MailgunEvents::campaignTag($list_id));
                }
	        });
	    }
	}
}
