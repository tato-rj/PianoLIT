<?php

namespace App\Services\Newsletter;

use App\{EmailList, Subscription};
use Illuminate\Support\Facades\DB;

class Subscribers
{
    public function subscribe($email)
    {
        $email = mb_strtolower(trim($email));
        return DB::transaction(function () use ($email) {
            $list = EmailList::firstOrCreate(['name' => EmailList::NEWSLETTER], [
                'description' => 'Monthly news from across PianoLIT: features, games, tools, stories and more. Sent only when there is something new.',
            ]);
            // Serialize newsletter opt-ins, preserving every other list's saved preferences.
            EmailList::whereKey($list->id)->lockForUpdate()->first();
            $subscriber = Subscription::whereRaw('LOWER(TRIM(email)) = ?', [$email])->orderBy('id')->first();
            $subscriber = $subscriber ?: Subscription::createOrActivate((object) [
                'email' => $email,
                'origin_url' => request()->getSchemeAndHttpHost(),
                'form_id' => 'newsletter-subscription-form',
            ], false, false);
            $subscriber->lists()->syncWithoutDetaching([$list->id]);
            return $subscriber;
        }, 3);
    }
}
