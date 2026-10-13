<?php

namespace App\Auth\Google;

use Illuminate\Support\Facades\{Cache, Http};
use UnexpectedValueException;

class IdTokenVerifier
{
    public function verify($token, $nonce)
    {
        if (!is_string($token) || strlen($token) > 16000 || !is_string($nonce) || $nonce === '') $this->invalid();
        $parts = explode('.', $token);
        if (count($parts) !== 3) $this->invalid();
        $header = json_decode($this->decode($parts[0]), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'RS256' || !is_string($header['kid'] ?? null) || strlen($header['kid']) > 200) $this->invalid();
        $certs = $this->certificates();
        if (!isset($certs[$header['kid']])) {
            Cache::forget('google-login.certificates');
            $certs = $this->certificates();
        }
        $pem = $certs[$header['kid']] ?? null;
        if (!is_string($pem) || !($key = openssl_pkey_get_public($pem))) $this->invalid();
        $details = openssl_pkey_get_details($key);
        if (!$details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048
            || openssl_verify($parts[0].'.'.$parts[1], $this->decode($parts[2]), $key, OPENSSL_ALGO_SHA256) !== 1) $this->invalid();
        $claims = json_decode($this->decode($parts[1]), true, 16, JSON_THROW_ON_ERROR);
        $now = time();
        if (!is_array($claims) || !in_array($claims['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true)
            || ($claims['aud'] ?? null) !== config('services.google_login.client_id')
            || (isset($claims['azp']) && $claims['azp'] !== config('services.google_login.client_id'))
            || !is_int($claims['exp'] ?? null) || $claims['exp'] <= $now
            || !is_int($claims['iat'] ?? null) || $claims['iat'] > $now
            || (isset($claims['nbf']) && (!is_int($claims['nbf']) || $claims['nbf'] > $now))
            || !is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])
            || ($claims['email_verified'] ?? null) !== true
            || !is_string($claims['sub'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,255}$/D', $claims['sub'])
            || !is_string($claims['email'] ?? null) || strlen($claims['email']) > 255
            || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) $this->invalid();
        return ['sub' => $claims['sub'], 'email' => strtolower($claims['email']),
            'first_name' => $this->name($claims['given_name'] ?? null) ?: 'PianoLIT',
            'last_name' => $this->name($claims['family_name'] ?? null)];
    }

    private function name($value)
    {
        return mb_substr(trim(strip_tags(is_string($value) ? $value : '')), 0, 255);
    }

    private function certificates()
    {
        $certs = Cache::remember('google-login.certificates', 1800, function () {
            return Http::timeout(10)->withOptions(['connect_timeout' => 5, 'allow_redirects' => false])->get('https://www.googleapis.com/oauth2/v1/certs')->throw()->json();
        });
        if (!is_array($certs)) $this->invalid();
        return $certs;
    }

    private function decode($part)
    {
        if (!is_string($part) || !preg_match('/^[A-Za-z0-9_-]+$/D', $part)) $this->invalid();
        $decoded = base64_decode(strtr($part, '-_', '+/').str_repeat('=', (4 - strlen($part) % 4) % 4), true);
        if ($decoded === false) $this->invalid();
        return $decoded;
    }

    private function invalid()
    {
        throw new UnexpectedValueException('Invalid Google identity.');
    }
}
