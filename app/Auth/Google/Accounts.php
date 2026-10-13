<?php

namespace App\Auth\Google;

use App\User;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Illuminate\Validation\ValidationException;

class Accounts
{
    public function linkFor(User $user)
    {
        return Schema::hasTable('user_auth_providers') ? DB::table('user_auth_providers')->where('user_id', $user->id)->where('provider', 'google')->first() : null;
    }

    public function hasPassword(User $user)
    {
        $link = $this->linkFor($user);
        return (bool) $user->password && (!$link || !$link->unusable_password_hash || !hash_equals($link->unusable_password_hash, $user->password));
    }

    public function recent($request, User $user)
    {
        $proof = $request->session()->get('google_auth.recent', []);
        return ($proof['user_id'] ?? null) === $user->id
            && ($proof['at'] ?? 0) >= time() - config('services.google_login.recent_auth_minutes') * 60
            && ($proof['password_stamp'] ?? '') === hash('sha256', $user->password);
    }

    public function verified($request, User $user)
    {
        $request->session()->put('google_auth.recent', ['user_id' => $user->id, 'at' => time(), 'password_stamp' => hash('sha256', $user->password)]);
    }

    public function link(User $user, array $identity, $unusableHash = null)
    {
        $existing = DB::table('user_auth_providers')->where('provider', 'google')->where('provider_user_id', $identity['sub'])->lockForUpdate()->first();
        $current = $this->linkFor($user);
        if (($existing && (int) $existing->user_id !== $user->id) || ($current && $current->provider_user_id !== $identity['sub'])) {
            throw ValidationException::withMessages(['google' => 'This Google account is already connected. Please use a different account.']);
        }
        if (!$existing) {
            DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => $identity['sub'], 'provider_email' => $identity['email'], 'unusable_password_hash' => $unusableHash, 'created_at' => now(), 'updated_at' => now()]);
            $this->audit($user->id, 'connected');
        } else {
            DB::table('user_auth_providers')->where('id', $existing->id)->update(['provider_email' => $identity['email'], 'updated_at' => now()]);
        }
    }

    public function create(array $identity, $origin)
    {
        $hash = Hash::make(bin2hex(random_bytes(32)));
        $user = User::create(['first_name' => $identity['first_name'], 'last_name' => $identity['last_name'], 'email' => $identity['email'], 'email_verified_at' => now(), 'password' => $hash, 'locale' => 'unknown', 'origin' => $origin]);
        $this->link($user, $identity, $hash);
        return $user;
    }

    public function audit($userId, $action)
    {
        DB::table('auth_provider_audits')->insert(['user_id' => $userId, 'action' => $action, 'provider' => 'google', 'created_at' => now()]);
    }
}
