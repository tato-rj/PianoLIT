<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\V2\{Response, Tokens};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request, Tokens $tokens)
    {
        $credentials = $request->validate(['email' => 'required|string|email|max:255', 'password' => 'required|string|max:4096']);
        $provider = Auth::createUserProvider('users');
        $user = $provider->retrieveByCredentials($credentials);
        if (!$user || !$user->password || !$provider->validateCredentials($user, $credentials)) {
            return Response::error('invalid_credentials', 'The email or password is incorrect.', 401);
        }
        return Response::data(array_merge($tokens->issue($user), ['user' => [
            'id' => (string) $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
        ]]));
    }

    public function logout(Request $request, Tokens $tokens)
    {
        $tokens->revoke($request->bearerToken());
        return response()->noContent();
    }
}
