<?php

namespace App\Http\Middleware;

use Closure;

class ValidateRegistrationForm
{
    protected $request;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->request = $request;

        if ($this->origin('web') && $this->hasIssues(['missingRecaptcha', 'isBot']))
            return redirect(route('home'))->with('error', $this->errorMessage());

        return $next($request);
    }

    public function origin($origin)
    {
        return $this->request->origin == $origin;
    }

    public function hasIssues($issues)
    {
        foreach($issues as $issue) {
            if (method_exists($this, $issue) && $this->$issue())
                return true;
        }

        return false;
    }

    public function errorMessage()
    {
        return 'Sorry, we couldn\'t complete your registration. If this problem persists, please let us know at contact@pianolit.com';
    }

    public function missingRecaptcha()
    {
        return ! $this->request->has('g-recaptcha-response') || ! $this->request->get('g-recaptcha-response');
    }

    public function isBot()
    {
        $isJamesSmith = strtolower($this->request->first_name) == 'james' && strtolower($this->request->last_name) == 'smith';

        return $isJamesSmith;
    }
}
