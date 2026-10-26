<?php

namespace App\Http\Controllers\Api\V2;

use App\Api\V2\{Input, Responses};
use App\Auth\ApiV2\Sessions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, Sessions $sessions, Responses $responses)
    {
        $data = Input::validate($request, ['email' => 'required|string|email|max:254', 'password' => 'required|string|max:1024']);
        return $responses->success($request, $sessions->login($data['email'], $data['password']));
    }

    public function me(Request $request, Sessions $sessions, Responses $responses)
    {
        Input::validate($request);
        return $responses->success($request, ['account' => $sessions->account($request->user('api_v2'))]);
    }

    public function logout(Request $request, Sessions $sessions, Responses $responses)
    {
        Input::validate($request);
        $sessions->logout($request);
        return $responses->success($request, ['logged_out' => true]);
    }
}
