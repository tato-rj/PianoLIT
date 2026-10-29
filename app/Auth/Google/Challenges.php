<?php

namespace App\Auth\Google;

use App\User;
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema, URL};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Challenges
{
    public function create(Request $request, User $user, $purpose, array $identity = [], $destination = null, $provider = 'google')
    {
        (new Accounts)->provider($provider);
        $secret = bin2hex(random_bytes(32));
        $id = (string) Str::uuid();
        $values = [
            'id' => $id, 'user_id' => $user->id, 'purpose' => $purpose, 'host' => $request->getHost(),
            'browser_hash' => hash('sha256', $secret), 'account_email' => $user->email,
            'provider_user_id' => $identity['sub'] ?? null, 'provider_email' => $identity['email'] ?? null,
            'destination' => $destination, 'expires_at' => now()->addMinutes(config('services.google_login.challenge_minutes')),
            'created_at' => now(), 'updated_at' => now(),
        ];
        if (Schema::hasColumn('auth_link_challenges', 'provider')) $values['provider'] = $provider;
        elseif ($provider !== 'google') $this->expired($provider);
        if ($provider === 'google' && Schema::hasColumn('auth_link_challenges', 'profile_image_url')) {
            $values['profile_image_url'] = \App\Services\Navigation\AccountAvatar::googleUrl($identity['picture'] ?? null);
        }
        DB::table('auth_link_challenges')->insert($values);
        $request->session()->put($provider.'_auth.challenge', ['id' => $id, 'secret' => $secret]);
        return $this->current($request, null, false, $provider);
    }

    public function current(Request $request, $id = null, $lock = false, $provider = 'google')
    {
        (new Accounts)->provider($provider);
        $pending = $request->session()->get($provider.'_auth.challenge', []);
        $query = DB::table('auth_link_challenges')->where('id', $pending['id'] ?? '');
        if ($lock) $query->lockForUpdate();
        $row = $query->first();
        if (!$row || ($id !== null && $row->id !== $id) || $row->host !== $request->getHost()
            || ($row->provider ?? 'google') !== $provider
            || $row->consumed_at || carbon($row->expires_at)->isPast()
            || !hash_equals($row->browser_hash, hash('sha256', $pending['secret'] ?? ''))) $this->expired($provider);
        $user = User::find($row->user_id);
        if (!$user || $user->email !== $row->account_email || (auth('web')->check() && auth('web')->id() !== $user->id)
            || ($row->purpose === 'reauth' && auth('web')->id() !== $user->id)) $this->expired($provider);
        return $row;
    }

    public function send(Request $request, $provider = 'google')
    {
        $result = DB::transaction(function () use ($request, $provider) {
            $row = $this->current($request, $request->input('challenge_id'), true, $provider);
            if ($row->email_sent_at && carbon($row->email_sent_at)->gt(now()->subMinute())) return null;
            $token = bin2hex(random_bytes(32));
            DB::table('auth_link_challenges')->where('id', $row->id)->update(['email_token_hash' => hash('sha256', $token), 'email_sent_at' => now(), 'updated_at' => now()]);
            return [$row, $token];
        });
        if (!$result) return;
        [$row, $token] = $result;
        $url = URL::temporarySignedRoute($provider.'.verify', carbon($row->expires_at), ['challenge' => $row->id, 'token' => $token]);
        try {
            User::findOrFail($row->user_id)->notify(new ConfirmGoogleAccount($url, $row->purpose, $provider));
        } catch (\Throwable $e) {
            DB::table('auth_link_challenges')->where('id', $row->id)->where('email_token_hash', hash('sha256', $token))->update(['email_token_hash' => null, 'email_sent_at' => null]);
            throw ValidationException::withMessages([$provider => 'We could not send the email. Please try again shortly.']);
        }
    }

    public function emailProof(Request $request, $id, $lock = false, $provider = 'google')
    {
        $row = $this->current($request, $id, $lock, $provider);
        $token = $request->query('token');
        if (!is_string($token) || strlen($token) !== 64 || !$row->email_token_hash || !hash_equals($row->email_token_hash, hash('sha256', $token))) $this->expired($provider);
        return $row;
    }

    public function consume($row)
    {
        $changed = DB::table('auth_link_challenges')->where('id', $row->id)->whereNull('consumed_at')->where('expires_at', '>', now())
            ->update(['consumed_at' => now(), 'email_token_hash' => null, 'updated_at' => now()]);
        if (!$changed) $this->expired();
    }

    public function expired($provider = 'google')
    {
        throw ValidationException::withMessages([$provider => 'This verification attempt has expired or was already used. Start again in the same browser.']);
    }
}
