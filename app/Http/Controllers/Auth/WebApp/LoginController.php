<?php

namespace App\Http\Controllers\Auth\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function logout(Request $request)
    {
        \Auth::guard('web')->logout();

        $request->session()->invalidate();

        return redirect(route('webapp.discover'));
    }
}
