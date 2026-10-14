<?php

namespace App\Auth\Apple;

use UnexpectedValueException;

class Jwt
{
    public static function encode($value)
    {
        return rtrim(strtr(base64_encode(is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value), '+/', '-_'), '=');
    }

    public static function decode($value)
    {
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]+$/D', $value) || strlen($value) % 4 === 1) throw new UnexpectedValueException('Invalid token encoding.');
        $result = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($result === false) throw new UnexpectedValueException('Invalid token encoding.');
        return $result;
    }

    private static function der($tag, $bytes)
    {
        $length = strlen($bytes);
        if ($length < 128) $prefix = chr($length);
        else {
            $prefix = ''; $remaining = $length;
            while ($remaining) { $prefix = chr($remaining & 255).$prefix; $remaining >>= 8; }
            $prefix = chr(128 | strlen($prefix)).$prefix;
        }
        return chr($tag).$prefix.$bytes;
    }

    public static function rsaPem(array $jwk)
    {
        $integers = '';
        foreach (['n', 'e'] as $part) {
            $value = ltrim(self::decode($jwk[$part] ?? null), "\0");
            if ($value === '' || strlen($value) > ($part === 'n' ? 1024 : 8)) throw new UnexpectedValueException('Invalid public key.');
            if (ord($value[0]) & 128) $value = "\0".$value;
            $integers .= self::der(2, $value);
        }
        $rsa = self::der(48, $integers);
        $algorithm = hex2bin('300d06092a864886f70d0101010500');
        $key = self::der(48, $algorithm.self::der(3, "\0".$rsa));
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($key), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    public static function es256Signature($der)
    {
        if (strlen($der) < 8 || ord($der[0]) !== 48 || ord($der[1]) !== strlen($der) - 2) throw new UnexpectedValueException('Invalid client signature.');
        $offset = 2; $result = '';
        for ($i = 0; $i < 2; $i++) {
            if ($offset + 2 > strlen($der) || ord($der[$offset++]) !== 2) throw new UnexpectedValueException('Invalid client signature.');
            $length = ord($der[$offset++]);
            if ($length < 1 || $offset + $length > strlen($der)) throw new UnexpectedValueException('Invalid client signature.');
            $value = ltrim(substr($der, $offset, $length), "\0"); $offset += $length;
            if (strlen($value) > 32) throw new UnexpectedValueException('Invalid client signature.');
            $result .= str_pad($value, 32, "\0", STR_PAD_LEFT);
        }
        if ($offset !== strlen($der)) throw new UnexpectedValueException('Invalid client signature.');
        return $result;
    }
}
