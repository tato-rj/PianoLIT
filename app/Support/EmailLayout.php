<?php

namespace App\Support;

use App\{EmailList, Subscription};

class EmailLayout
{
    public const WIDTH = 600;
    public const DESKTOP_INSET = 48;
    public const MOBILE_INSET = 24;
    public const MOBILE_BREAKPOINT = 620;

    public static function websiteUrl(): string
    {
        $website = rtrim(config('app.url'), '/');
        if (!preg_match('#^https?://#i', $website)) {
            $website = ltrim($website, '/');
            $host = strtolower((string) parse_url('//'.$website, PHP_URL_HOST));
            $website = (substr($host, -5) === '.test' ? request()->getScheme() : 'https').'://'.$website;
        }
        return $website;
    }

    public static function footer(array $data = []): array
    {
        $website = self::websiteUrl();
        $list = $data['list'] ?? null;
        $subscription = $data['subscription'] ?? null;
        $subscribed = $list instanceof EmailList && $subscription instanceof Subscription;

        return [
            'websiteUrl' => $website,
            'footerReason' => $data['footerReason'] ?? ($list instanceof EmailList
                ? 'You’re receiving this email because you are subscribed to '.$list->name.'.'
                : 'A message from PianoLIT'),
            'unsubscribeUrl' => $data['unsubscribeUrl'] ?? ($subscribed
                ? $website.route('subscriptions.unsubscribe', [$subscription, $list], false)
                : $website.route('users.profile', [], false).'?tab=email-preferences'),
            'unsubscribeLabel' => $data['unsubscribeLabel'] ?? ($subscribed ? 'Unsubscribe' : 'Unsubscribe / email preferences'),
        ];
    }
}
