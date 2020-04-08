<?php

namespace App\Http\Controllers\Auth\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            if (\Hash::check($request->password, $user->password))
                return response()->json($user);

            $error = ['Sorry, the password is not valid.'];

        } else {
            $error = ['This email is not registered.'];
        }

        $response = new \stdClass;
        $response->error = $error;

        return response()->json($response);
    }
}
