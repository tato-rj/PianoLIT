<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\User;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'impersonate']);
        $this->redirectTo = url()->previous();
    }

    public function guard()
    {
        return \Auth::guard('web');
    }

    public function impersonate(User $user)
    {
        if (! auth()->guard('admin')->check())
            abort(403, 'You are not authorized to do this.');

        session(['impersonator' => true]);

        auth()->login($user);

        return redirect(route('home'));
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request), $remember = true
        );
    }
}
