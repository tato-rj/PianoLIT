<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;

/** Laravel's usual guard, with a distinct, explicitly parent-scoped recaller. */
class SharedSessionGuard extends SessionGuard
{
    public function getName()
    {
        return 'login_'.$this->name.'_'.sha1(SessionGuard::class);
    }

    public function getRecallerName()
    {
        return SharedWebSessions::REMEMBER_COOKIE;
    }

    protected function createRecaller($value)
    {
        return $this->getCookieJar()->make($this->getRecallerName(), $value, $this->getRememberDuration(),
            '/', app(SharedWebSessions::class)->domain(), config('session.secure'), true, false, 'lax');
    }

    protected function clearUserDataFromStorage()
    {
        $this->session->remove($this->getName());
        // Always expire the shared cookie, including when the session expired.
        $this->getCookieJar()->queue($this->getCookieJar()->forget($this->getRecallerName(),
            '/', app(SharedWebSessions::class)->domain()));
    }
}
