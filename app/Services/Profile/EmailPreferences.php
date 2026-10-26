<?php

namespace App\Services\Profile;

use App\{EmailList, Subscription, User};
use Illuminate\Support\Facades\DB;

class EmailPreferences
{
    public function lists()
    {
        return EmailList::whereRaw('LOWER(TRIM(name)) <> ?', ['test'])->orderBy('id')->get();
    }

    public function subscribers(User $user)
    {
        return Subscription::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($user->email))])->orderBy('id');
    }

    public function save($userId, EmailList $list, $enabled)
    {
        abort_if(mb_strtolower(trim($list->name)) === 'test', 404);
        return DB::transaction(function () use ($userId, $list, $enabled) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $subscribers = $this->subscribers($user)->lockForUpdate()->get();
            $subscriber = $subscribers->first();
            if ($enabled && !$subscriber) {
                // Explicitly opt into this list only, including PianoLIT News.
                $subscriber = Subscription::create(['email' => $user->email, 'origin_url' => \App\Support\ProfileUrl::get(), 'form_id' => 'profile-email-preferences']);
            }
            if ($enabled) $subscriber->lists()->syncWithoutDetaching([$list->id]);
            else foreach ($subscribers as $record) $list->remove($record);
            return (bool) $enabled;
        }, 3);
    }

    public function leaveAll($userId)
    {
        DB::transaction(function () use ($userId) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            foreach ($this->subscribers($user)->lockForUpdate()->get() as $subscriber) {
                foreach ($subscriber->lists()->orderBy('email_lists.id')->get() as $list) $list->remove($subscriber);
            }
        }, 3);
    }
}
