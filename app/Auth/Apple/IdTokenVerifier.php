<?php

namespace App\Auth\Apple;

use Illuminate\Support\Facades\{Cache, Http};
use UnexpectedValueException;

class IdTokenVerifier
{
    public function verify($token, $nonce)
    {
        if (!is_string($token) || strlen($token) > 16000 || !is_string($nonce) || $nonce === '') $this->invalid();
        $parts = explode('.', $token);
        if (count($parts) !== 3) $this->invalid();
        $header = json_decode(Jwt::decode($parts[0]), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'RS256' || isset($header['crit'])
            || !is_string($header['kid'] ?? null) || strlen($header['kid']) > 200) $this->invalid();
        $keys = $this->keys();
        if (!isset($keys[$header['kid']])) { Cache::forget('apple-login.keys'); $keys = $this->keys(); }
        $jwk = $keys[$header['kid']] ?? [];
        if (($jwk['kty'] ?? null) !== 'RSA' || ($jwk['alg'] ?? null) !== 'RS256' || ($jwk['use'] ?? null) !== 'sig') $this->invalid();
        $key = openssl_pkey_get_public(Jwt::rsaPem($jwk));
        $details = $key ? openssl_pkey_get_details($key) : null;
        if (!$details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048
            || openssl_verify($parts[0].'.'.$parts[1], Jwt::decode($parts[2]), $key, OPENSSL_ALGO_SHA256) !== 1) $this->invalid();
        $claims = json_decode(Jwt::decode($parts[1]), true, 16, JSON_THROW_ON_ERROR); $now = time();
        if (!is_array($claims) || ($claims['iss'] ?? null) !== 'https://appleid.apple.com'
            || ($claims['aud'] ?? null) !== config('services.apple_login.client_id')
            || !is_int($claims['exp'] ?? null) || $claims['exp'] <= $now
            || !is_int($claims['iat'] ?? null) || $claims['iat'] > $now || $claims['iat'] >= $claims['exp']
            || (isset($claims['nbf']) && (!is_int($claims['nbf']) || $claims['nbf'] > $now))
            || !is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])
            || !is_string($claims['sub'] ?? null) || !preg_match('/^[A-Za-z0-9_.-]{1,255}$/D', $claims['sub'])) $this->invalid();
        $email = $claims['email'] ?? null;
        if ($email !== null && (!is_string($email) || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($claims['email_verified'] ?? null, [true, 'true'], true))) $this->invalid();
        return ['sub' => $claims['sub'], 'email' => $email === null ? null : strtolower($email)];
    }

    private function keys()
    {
        return Cache::remember('apple-login.keys', 1800, function () {
            $response = Http::timeout(10)->withOptions(['connect_timeout' => 5, 'allow_redirects' => false])->get('https://appleid.apple.com/auth/keys');
            if (!$response->successful()) $this->invalid();
            $values = $response->json('keys');
            if (!is_array($values) || count($values) > 20) $this->invalid();
            $keys = [];
            foreach ($values as $key) {
                if (!is_array($key) || !is_string($key['kid'] ?? null) || strlen($key['kid']) > 200) $this->invalid();
                $keys[$key['kid']] = $key;
            }
            return $keys;
        });
    }

    private function invalid() { throw new UnexpectedValueException('Invalid Apple identity.'); }
}
