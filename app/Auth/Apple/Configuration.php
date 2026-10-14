<?php

namespace App\Auth\Apple;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class Configuration extends \App\Auth\Google\Configuration
{
    public function callback(Request $request)
    {
        $key = $request->getHost() === $this->hosts()[1] ? 'webapp_redirect_uri' : 'redirect_uri';
        $expected = $this->base($request->getHost()).'/auth/apple/callback';
        $url = config('services.apple_login.'.$key) ?: $expected;
        return $url === $expected ? $url : null;
    }

    public function available(Request $request = null)
    {
        $request = $request ?: request();
        return in_array($request->getHost(), $this->hosts(), true)
            && is_string(config('services.apple_login.client_id')) && preg_match('/^[A-Za-z0-9.-]{1,255}$/D', config('services.apple_login.client_id'))
            && preg_match('/^[A-Z0-9]{10}$/D', (string) config('services.apple_login.team_id'))
            && preg_match('/^[A-Z0-9]{10}$/D', (string) config('services.apple_login.key_id'))
            && $this->privateKeyPath() && $this->callback($request) && $this->ready();
    }

    public function privateKeyPath()
    {
        $path = config('services.apple_login.private_key_path');
        if (!is_string($path) || !($real = realpath($path)) || !is_file($real) || !is_readable($real)) return null;
        foreach ([public_path(), storage_path('app/public'), config('filesystems.disks.public.root')] as $directory) {
            $public = is_string($directory) ? realpath($directory) : false;
            if ($public && strpos($real, $public.DIRECTORY_SEPARATOR) === 0) return null;
        }
        return $real;
    }

    public function ready()
    {
        try { return parent::ready() && Schema::hasColumn('auth_link_challenges', 'provider'); }
        catch (\Throwable $e) { return false; }
    }
}
