<?php

namespace App\Auth\Apple;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Crypt, Http};
use Illuminate\Support\Str;
use UnexpectedValueException;

class Provider
{
    public function redirect(Request $request, $callback, $nonce)
    {
        $state = bin2hex(random_bytes(32)); $ttl = config('services.apple_login.transaction_seconds');
        $hash = hash('sha256', $state);
        $request->session()->put('apple_auth.oauth', ['state_hash' => $hash, 'nonce' => $nonce,
            'expires' => time() + $ttl, 'session_hash' => $this->sessionHash($request)]);
        Cache::put('apple-login.pending.'.$hash, ['host' => $request->getHost(), 'expires' => time() + $ttl], $ttl);
        return redirect()->away('https://appleid.apple.com/auth/authorize?'.http_build_query([
            'client_id' => config('services.apple_login.client_id'), 'redirect_uri' => $callback,
            'response_type' => 'code', 'response_mode' => 'form_post', 'scope' => 'name email',
            'state' => $state, 'nonce' => $nonce,
        ], '', '&', PHP_QUERY_RFC3986));
    }

    // This POST deliberately has no session middleware. It cannot sign in,
    // link accounts or replace the browser's existing SameSite session cookie.
    public function receive(Request $request)
    {
        $state = $request->input('state');
        if (!is_string($state) || !preg_match('/^[a-f0-9]{64}$/D', $state)) $this->invalid();
        $hash = hash('sha256', $state); $pending = Cache::get('apple-login.pending.'.$hash);
        if (!is_array($pending) || $pending['host'] !== $request->getHost() || $pending['expires'] <= time()) $this->invalid();
        // Some user agents omit Origin. State plus the original session is
        // mandatory regardless; a supplied foreign Origin is never accepted.
        $origin = $request->header('Origin');
        if ($origin !== null && $origin !== 'https://appleid.apple.com') $this->invalid();
        $values = ['host' => $request->getHost(), 'state_hash' => $hash];
        foreach (['code' => 4096, 'error' => 200, 'user' => 16000] as $field => $max) {
            $value = $request->input($field);
            if ($value !== null && (!is_string($value) || strlen($value) > $max)) $this->invalid();
            $values[$field] = $value;
        }
        if (!$values['error'] && !$values['code']) $this->invalid();
        $ttl = max(1, $pending['expires'] - time());
        if (!Cache::add('apple-login.posted.'.$hash, true, $ttl)) $this->invalid();
        $id = (string) Str::uuid();
        Cache::put('apple-login.return.'.$id, Crypt::encryptString(json_encode($values, JSON_THROW_ON_ERROR)), $ttl);
        return $id;
    }

    public function consume(Request $request, $attempt)
    {
        if (!is_string($attempt) || !Str::isUuid($attempt)) $this->invalid();
        $encrypted = Cache::get('apple-login.return.'.$attempt);
        $flow = $request->session()->get('apple_auth.oauth');
        if (!is_string($encrypted) || !is_array($flow)) $this->invalid();
        $response = json_decode(Crypt::decryptString($encrypted), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($response) || $response['host'] !== $request->getHost() || $flow['expires'] <= time()
            || !hash_equals($flow['session_hash'], $this->sessionHash($request))
            || !hash_equals($flow['state_hash'], $response['state_hash'])) $this->invalid();
        if (!Cache::add('apple-login.used-state.'.$flow['state_hash'], true, config('services.apple_login.transaction_seconds'))) $this->invalid();
        Cache::forget('apple-login.return.'.$attempt);
        Cache::forget('apple-login.pending.'.$flow['state_hash']);
        $request->session()->forget('apple_auth.oauth');
        return [$flow, $response];
    }

    public function identity($code, $callback, $nonce)
    {
        if (!is_string($code) || $code === '' || strlen($code) > 4096) $this->invalid();
        $response = Http::asForm()->timeout(10)->withOptions(['connect_timeout' => 5, 'allow_redirects' => false])->post('https://appleid.apple.com/auth/token', [
            'grant_type' => 'authorization_code', 'client_id' => config('services.apple_login.client_id'),
            'client_secret' => app(ClientSecret::class)->generate(), 'redirect_uri' => $callback, 'code' => $code,
        ]);
        if (!$response->successful()) $this->invalid();
        return app(IdTokenVerifier::class)->verify($response->json('id_token'), $nonce);
    }

    private function sessionHash(Request $request)
    {
        return hash_hmac('sha256', $request->session()->getId(), config('app.key'));
    }

    private function invalid() { throw new UnexpectedValueException('Expired Apple authentication attempt.'); }
}
