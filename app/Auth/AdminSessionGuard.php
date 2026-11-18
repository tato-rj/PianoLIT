<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Symfony\Component\HttpFoundation\Cookie;

class AdminSessionGuard extends SessionGuard
{
    public function getName()
    {
        // Keep existing signed-in sessions when replacing the original guard.
        return 'login_'.$this->name.'_'.sha1(SessionGuard::class);
    }

    public function getRecallerName()
    {
        return ($this->secureCookie() ? '__Host-' : '').'pianolit_admin_remember_v1';
    }

    protected function createRecaller($value)
    {
        return $this->rememberCookie($value, now()->addMinutes($this->getRememberDuration()));
    }

    protected function clearUserDataFromStorage()
    {
        $this->session->remove($this->getName());
        $this->getCookieJar()->queue($this->rememberCookie(null, 1));
    }

    private function rememberCookie($value, $expires)
    {
        return new Cookie($this->getRecallerName(), $value, $expires, '/', null, $this->secureCookie(), true, false, 'lax');
    }

    private function secureCookie()
    {
        return app()->environment('production') || config('session.secure') || $this->getRequest()->isSecure();
    }
}
