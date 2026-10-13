<?php

namespace App\Auth\Google;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Http};
use UnexpectedValueException;

class Provider
{
    public function redirect(Request $request, $callback, $nonce)
    {
        $state = bin2hex(random_bytes(32));
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $request->session()->put('google_auth.oauth', ['state' => $state, 'verifier' => $verifier, 'expires' => time() + 600]);
        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_login.client_id'), 'redirect_uri' => $callback,
            'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state,
            'nonce' => $nonce, 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256', 'prompt' => 'select_account', 'max_age' => 0,
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function consume(Request $request)
    {
        $flow = $request->session()->pull('google_auth.oauth');
        $state = $request->input('state');
        if (!is_array($flow) || !is_string($state) || !hash_equals($flow['state'], $state) || $flow['expires'] <= time()
            || !Cache::add('google-login.used-state.'.hash('sha256', $state), true, 600)) throw new UnexpectedValueException('Expired authentication attempt.');
        return $flow;
    }

    public function identity(Request $request, $callback, $nonce, array $flow)
    {
        $code = $request->input('code');
        if (!is_string($code) || $code === '' || strlen($code) > 4096) throw new UnexpectedValueException('Invalid authorization code.');
        $response = Http::asForm()->timeout(10)->withOptions(['connect_timeout' => 5, 'allow_redirects' => false])->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'authorization_code', 'client_id' => config('services.google_login.client_id'),
            'client_secret' => config('services.google_login.client_secret'), 'redirect_uri' => $callback,
            'code' => $code, 'code_verifier' => $flow['verifier'],
        ])->throw()->json();
        return app(IdTokenVerifier::class)->verify($response['id_token'] ?? null, $nonce);
    }
}
