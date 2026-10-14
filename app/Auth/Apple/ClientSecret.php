<?php

namespace App\Auth\Apple;

use UnexpectedValueException;

class ClientSecret
{
    public function generate()
    {
        $path = app(Configuration::class)->privateKeyPath();
        $key = $path ? @openssl_pkey_get_private(file_get_contents($path)) : false;
        $details = $key ? openssl_pkey_get_details($key) : null;
        if (!$details || $details['type'] !== OPENSSL_KEYTYPE_EC || $details['bits'] !== 256
            || ($details['ec']['curve_name'] ?? '') !== 'prime256v1') throw new UnexpectedValueException('Apple signing key is unavailable.');
        $now = time();
        $body = Jwt::encode(['alg' => 'ES256', 'kid' => config('services.apple_login.key_id')]).'.'.Jwt::encode([
            'iss' => config('services.apple_login.team_id'), 'iat' => $now, 'exp' => $now + 3600,
            'aud' => 'https://appleid.apple.com', 'sub' => config('services.apple_login.client_id'),
        ]);
        if (!openssl_sign($body, $signature, $key, OPENSSL_ALGO_SHA256)) throw new UnexpectedValueException('Apple signing key is unavailable.');
        // A fresh one-hour secret for each exchange needs no stored token or cron.
        return $body.'.'.Jwt::encode(Jwt::es256Signature($signature));
    }
}
