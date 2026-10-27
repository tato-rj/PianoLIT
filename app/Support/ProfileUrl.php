<?php

namespace App\Support;

class ProfileUrl
{
    public static function base()
    {
        $website = rtrim(config('app.url'), '/');
        return preg_match('#^https?://#i', $website) ? $website : request()->getScheme().'://'.ltrim($website, '/');
    }

    public static function get($fragment = '')
    {
        return self::base().'/profile'.($fragment ? '#'.$fragment : '');
    }

    public static function downloads()
    {
        return self::get().'/downloads';
    }
}
