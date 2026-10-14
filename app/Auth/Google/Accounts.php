<?php

namespace App\Auth\Google;

use App\User;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Illuminate\Validation\ValidationException;

class Accounts
{
    public function linkFor(User $user, $provider = 'google')
    {
        $this->provider($provider);
        return Schema::hasTable('user_auth_providers') ? DB::table('user_auth_providers')->where('user_id', $user->id)->where('provider', $provider)->first() : null;
    }

    public function hasPassword(User $user)
    {
        if (!$user->password) return false;
        if (!Schema::hasTable('user_auth_providers')) return true;
        foreach (DB::table('user_auth_providers')->where('user_id', $user->id)->whereNotNull('unusable_password_hash')->get() as $link) {
            if (hash_equals($link->unusable_password_hash, $user->password)) return false;
        }
        return true;
    }

    public function recent($request, User $user)
    {
        $proof = $request->session()->get('google_auth.recent', []);
        return ($proof['user_id'] ?? null) === $user->id
            && ($proof['at'] ?? 0) >= time() - config('services.google_login.recent_auth_minutes') * 60
            && ($proof['password_stamp'] ?? '') === hash('sha256', $user->password);
    }

    public function verified($request, User $user, $provider = 'google')
    {
        $this->provider($provider);
        $request->session()->put('google_auth.recent', ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password), 'provider' => $provider]);
    }

    public function link(User $user, array $identity, $unusableHash = null, $provider = 'google')
    {
        $label = $this->provider($provider);
        $existing = DB::table('user_auth_providers')->where('provider', $provider)->where('provider_user_id', $identity['sub'])->lockForUpdate()->first();
        $current = $this->linkFor($user, $provider);
        if (($existing && (int) $existing->user_id !== $user->id) || ($current && $current->provider_user_id !== $identity['sub'])) {
            throw ValidationException::withMessages([$provider => 'This '.$label.' account is already connected. Please use a different account.']);
        }
        if ($unusableHash === null && !$this->hasPassword($user)) $unusableHash = $user->password;
        if (!$existing) {
            DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => $provider, 'provider_user_id' => $identity['sub'], 'provider_email' => $identity['email'] ?? null, 'unusable_password_hash' => $unusableHash, 'created_at' => now(), 'updated_at' => now()]);
            $this->audit($user->id, 'connected', $provider);
        } else {
            $values = ['updated_at' => now()];
            if (!empty($identity['email'])) $values['provider_email'] = $identity['email'];
            if ($unusableHash !== null) $values['unusable_password_hash'] = $unusableHash;
            DB::table('user_auth_providers')->where('id', $existing->id)->update($values);
        }
    }

    public function create(array $identity, $origin, $provider = 'google', $verifiedEmail = null)
    {
        $this->provider($provider);
        $email = $identity['email'] ?? $verifiedEmail;
        if (!is_string($email) || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \UnexpectedValueException('A verified registration email is required.');
        $hash = Hash::make(bin2hex(random_bytes(32)));
        $user = User::create(['first_name' => $identity['first_name'] ?? 'PianoLIT', 'last_name' => $identity['last_name'] ?? '', 'email' => strtolower($email), 'email_verified_at' => now(), 'password' => $hash, 'locale' => 'unknown', 'origin' => $origin]);
        $this->link($user, $identity, $hash, $provider);
        return $user;
    }

    public function audit($userId, $action, $provider = 'google')
    {
        $this->provider($provider);
        DB::table('auth_provider_audits')->insert(['user_id' => $userId, 'action' => $action, 'provider' => $provider, 'created_at' => now()]);
    }

    public function otherMethod(User $user, $provider)
    {
        $this->provider($provider);
        if ($this->hasPassword($user)) return true;
        foreach (DB::table('user_auth_providers')->where('user_id', $user->id)->whereIn('provider', ['google', 'apple'])->where('provider', '!=', $provider)->get() as $link) {
            $config = $link->provider === 'apple' ? app(\App\Auth\Apple\Configuration::class) : app(Configuration::class);
            if ($config->available()) return true;
        }
        return false;
    }

    public function provider($provider)
    {
        if (!in_array($provider, ['google', 'apple'], true)) throw new \UnexpectedValueException('Unsupported authentication provider.');
        return $provider === 'apple' ? 'Apple' : 'Google';
    }
}
