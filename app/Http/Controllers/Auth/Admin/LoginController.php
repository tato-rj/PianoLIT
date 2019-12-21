<?php

namespace App\Http\Controllers\Auth\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/admin';

	public function showLoginForm()
	{
		return view('admin.auth.login');
	}

	public function login(Request $request)
	{
		$validator = \Validator::make($request->all(), [
				'email' => 'required|email',
				'password' => 'required'
			]);

        if ($validator->fails()) {
            return back()
	            ->withInput($request->only('email', 'remember'))
	            ->withErrors($validator);
        }

		if (\Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $rememeber = true))
			return redirect()->intended(route('admin.home'));

		return $this->sendFailedLoginResponse($request);
	}

    /**
     * Get the failed login response instance.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->to(route('admin.login.show'))
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => \Lang::get('auth.failed'),
            ]);
    }

    public function guard()
    {
    	return \Auth::guard('admin');
    }

    protected function loggedOut(Request $request)
    {
        return redirect(route('admin.home'));
    }
}
