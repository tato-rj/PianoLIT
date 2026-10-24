<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'api/*',
        'register',
        '/subscriptions/*/unsubscribe/*',
        'crashcourses/cancel'
    ];

    protected function addCookieToResponse($request, $response)
    {
        if (!app(\App\Auth\SharedWebSessions::class)->active($request)) return parent::addCookieToResponse($request, $response);
        $domain = config('session.domain');
        try {
            config(['session.domain' => null]);
            return parent::addCookieToResponse($request, $response);
        } finally {
            config(['session.domain' => $domain]);
        }
    }
}
