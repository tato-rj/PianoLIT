<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Auth\Google\{Accounts, Challenges, Configuration};
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleSecurityController extends Controller
{
    public function accounts(Request $request, Accounts $accounts, Configuration $config)
    {
        $user = $request->user('web');
        return view('auth.google.accounts', ['google' => $accounts->linkFor($user), 'apple' => $accounts->linkFor($user, 'apple'),
            'hasPassword' => $accounts->hasPassword($user), 'available' => $config->available($request),
            'appleAvailable' => app(\App\Auth\Apple\Configuration::class)->available($request),
            'canDisconnectGoogle' => $accounts->otherMethod($user, 'google'), 'canDisconnectApple' => $accounts->otherMethod($user, 'apple')]);
    }

    public function link(Request $request, Challenges $challenges, Accounts $accounts)
    {
        $provider = $this->provider($request);
        $challenge = $challenges->current($request, null, false, $provider);
        abort_unless($challenge->purpose === 'link', 403);
        return view('auth.google.confirm', ['challenge' => $challenge, 'provider' => $provider, 'linking' => true, 'hasPassword' => $accounts->hasPassword(User::findOrFail($challenge->user_id))]);
    }

    public function confirm(Request $request, Challenges $challenges, Accounts $accounts)
    {
        $user = $request->user('web');
        $provider = $this->provider($request);
        if ($provider === 'google' && !$accounts->linkFor($user) && $accounts->linkFor($user, 'apple')) $provider = 'apple';
        $challenge = $challenges->create($request, $user, 'reauth', [], null, $provider);
        return view('auth.google.confirm', ['challenge' => $challenge, 'provider' => $provider, 'linking' => false, 'hasPassword' => $accounts->hasPassword($user), 'google' => $accounts->linkFor($user), 'apple' => $accounts->linkFor($user, 'apple')]);
    }

    public function passwordProof(Request $request, Challenges $challenges, Accounts $accounts)
    {
        $request->validate(['password' => 'required|string|max:1024', 'challenge_id' => 'required|uuid'], [
            'challenge_id.required' => 'Please start account confirmation again.',
            'challenge_id.uuid' => 'Please start account confirmation again.',
        ]);
        $row = $challenges->current($request, $request->input('challenge_id'), false, $this->provider($request));
        $this->limit($row->user_id, 'password-proof', 6);
        return $this->complete($request, $challenges, $accounts, false);
    }

    public function sendEmail(Request $request, Challenges $challenges)
    {
        $request->validate(['challenge_id' => 'required|uuid'], [
            'challenge_id.required' => 'Please start account confirmation again.',
            'challenge_id.uuid' => 'Please start account confirmation again.',
        ]);
        $provider = $this->provider($request);
        $row = $challenges->current($request, $request->input('challenge_id'), false, $provider);
        $this->limit($row->user_id, 'email-proof', 6, 3600);
        $challenges->send($request, $provider);
        return back()->with('status', 'Check your inbox. Open the confirmation link in this same browser.');
    }

    public function email(Request $request, $challenge, Challenges $challenges)
    {
        $provider = $this->provider($request);
        $row = $challenges->emailProof($request, $challenge, false, $provider);
        return view('auth.google.email-confirm', ['challenge' => $row, 'provider' => $provider]);
    }

    public function verifyEmail(Request $request, $challenge, Challenges $challenges, Accounts $accounts)
    {
        $challenges->emailProof($request, $challenge, false, $this->provider($request));
        return $this->complete($request, $challenges, $accounts, true);
    }

    private function complete(Request $request, Challenges $challenges, Accounts $accounts, $email)
    {
        $provider = $this->provider($request);
        $row = DB::transaction(function () use ($request, $challenges, $accounts, $email, $provider) {
            $row = $email ? $challenges->emailProof($request, $request->route('challenge'), true, $provider) : $challenges->current($request, $request->input('challenge_id'), true, $provider);
            $user = User::whereKey($row->user_id)->lockForUpdate()->firstOrFail();
            if ($user->email !== $row->account_email) $challenges->expired($provider);
            if (!$email && (!$accounts->hasPassword($user) || !Hash::check($request->password, $user->password))) {
                throw ValidationException::withMessages(['password' => 'That password does not match your PianoLIT account.']);
            }
            if ($row->purpose === 'link') $accounts->link($user, ['sub' => $row->provider_user_id, 'email' => $row->provider_email], null, $provider);
            $challenges->consume($row);
            $accounts->audit($user->id, 'verified', $provider);
            return $row;
        }, 3);
        $request->session()->forget($provider.'_auth.challenge');
        $user = User::findOrFail($row->user_id);
        if ($row->purpose === 'link') return app(GoogleHandoffController::class)->login($request, $user, $row->destination, $provider);
        $request->session()->regenerate();
        $accounts->verified($request, $user, $provider);
        return $this->next($request);
    }

    public function next(Request $request)
    {
        $action = $request->session()->pull('google_auth.action');
        if ($action === 'connect') return app(GoogleController::class)->connect($request, app(Configuration::class), app(\App\Auth\Google\Provider::class), app(Accounts::class));
        if ($action === 'connect:apple') return app(AppleController::class)->connect($request, app(\App\Auth\Apple\Configuration::class), app(\App\Auth\Apple\Provider::class), app(Accounts::class));
        if ($action === 'password') return redirect('/auth/google/password');
        if ($action === 'profile') return redirect($request->getHost() === 'my.'.config('app.short_url') ? '/profile' : '/my-profile')
            ->with('status', 'Account confirmed. Please enter your changes again and save them.');
        return redirect('/auth/google/accounts')->with('status', 'Account confirmed. You can now update your connected accounts.');
    }

    public function passwordForm(Request $request, Accounts $accounts)
    {
        if (!$accounts->recent($request, $request->user('web'))) return app(GoogleController::class)->confirm($request, 'password');
        if ($accounts->hasPassword($request->user('web'))) return redirect('/auth/google/accounts')->with('status', 'You already have a PianoLIT password.');
        return view('auth.google.password');
    }

    public function setPassword(Request $request, Accounts $accounts)
    {
        if (!$accounts->recent($request, $request->user('web'))) return app(GoogleController::class)->confirm($request, 'password');
        $request->validate(['password' => 'required|string|min:8|max:1024|confirmed']);
        $proofProvider = $request->session()->get('google_auth.recent.provider', 'google');
        DB::transaction(function () use ($request, $accounts, $proofProvider) {
            $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            if (!$accounts->recent($request, $user)) throw ValidationException::withMessages(['google' => 'Please confirm your identity again before setting a password.']);
            if ($accounts->hasPassword($user)) throw ValidationException::withMessages(['password' => 'You already have a password. Use the existing password reset option to change it.']);
            $user->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
            DB::table('user_auth_providers')->where('user_id', $user->id)->update(['unusable_password_hash' => null, 'updated_at' => now()]);
            $accounts->audit($user->id, 'password_set', $proofProvider);
            event(new PasswordReset($user));
        }, 3);
        $request->session()->regenerate();
        $accounts->verified($request, $request->user('web')->fresh(), $proofProvider);
        return redirect('/auth/google/accounts')->with('status', 'Password set. You can now use your email and password in the PianoLIT iOS app.');
    }

    public function disconnect(Request $request, Accounts $accounts)
    {
        $provider = $this->provider($request);
        $label = $accounts->provider($provider);
        if (!$accounts->recent($request, $request->user('web'))) {
            if ($provider === 'apple') return app(AppleController::class)->confirm($request, 'disconnect');
            return app(GoogleController::class)->confirm($request, 'disconnect');
        }
        DB::transaction(function () use ($request, $accounts, $provider, $label) {
            $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            if (!$accounts->recent($request, $user)) throw ValidationException::withMessages([$provider => 'Please confirm your identity again before disconnecting '.$label.'.']);
            if (!$accounts->otherMethod($user, $provider)) throw ValidationException::withMessages([$provider => 'Set a password before disconnecting '.$label.' so you can still sign in.']);
            if (!$accounts->hasPassword($user)) DB::table('user_auth_providers')->where('user_id', $user->id)->where('provider', '!=', $provider)->update(['unusable_password_hash' => $user->password]);
            if (DB::table('user_auth_providers')->where('user_id', $user->id)->where('provider', $provider)->delete()) $accounts->audit($user->id, 'disconnected', $provider);
        }, 3);
        return back()->with('status', $label.' disconnected. Your PianoLIT account and data are unchanged.');
    }

    private function limit($userId, $purpose, $max, $seconds = 60)
    {
        $key = 'google-account.'.$purpose.'.'.$userId;
        if (RateLimiter::tooManyAttempts($key, $max)) throw ValidationException::withMessages(['google' => 'Too many attempts. Please try again later.']);
        RateLimiter::hit($key, $seconds);
    }

    private function provider(Request $request)
    {
        return $request->routeIs('apple.*') ? 'apple' : 'google';
    }
}
