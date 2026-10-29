<?php

namespace App\Services\Navigation;

use App\User;
use Illuminate\Support\Facades\{DB, Schema};

/** Web presentation metadata only; never changes User attributes or mobile JSON. */
class AccountAvatar
{
    public static function googleUrl($value): ?string
    {
        if (!is_string($value) || strlen($value) > 2048 || !filter_var($value, FILTER_VALIDATE_URL)) return null;
        $url = parse_url($value);
        $host = strtolower($url['host'] ?? '');
        if (($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass'])
            || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)
            || !($host === 'googleusercontent.com' || str_ends_with($host, '.googleusercontent.com'))) return null;
        return $value;
    }

    public function forAccount($account): ?string
    {
        if (!$account instanceof User || !$account->exists) return null;
        $key = 'account-avatar.'.$account->id;
        if (request()->attributes->has($key)) return request()->attributes->get($key);
        $image = Schema::hasColumn('user_auth_providers', 'profile_image_url')
            ? self::googleUrl(DB::table('user_auth_providers')->where('user_id', $account->id)->where('provider', 'google')->value('profile_image_url'))
            : null;
        request()->attributes->set($key, $image);
        return $image;
    }
}
