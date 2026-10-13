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
        return view('auth.google.accounts', ['google' => $accounts->linkFor($request->user('web')), 'hasPassword' => $accounts->hasPassword($request->user('web')), 'available' => $config->available($request)]);
    }

    public function link(Request $request, Challenges $challenges, Accounts $accounts)
    {
        $challenge = $challenges->current($request);
        abort_unless($challenge->purpose === 'link', 403);
        return view('auth.google.confirm', ['challenge' => $challenge, 'linking' => true, 'hasPassword' => $accounts->hasPassword(User::findOrFail($challenge->user_id))]);
    }

    public function confirm(Request $request, Challenges $challenges, Accounts $accounts)
    {
        $user = $request->user('web');
        $challenge = $challenges->create($request, $user, 'reauth');
        return view('auth.google.confirm', ['challenge' => $challenge, 'linking' => false, 'hasPassword' => $accounts->hasPassword($user), 'google' => $accounts->linkFor($user)]);
    }

    public function passwordProof(Request $request, Challenges $challenges, Accounts $accounts)
    {
        $request->validate(['password' => 'required|string|max:1024']);
        $row = $challenges->current($request);
        $this->limit($row->user_id, 'password-proof', 6);
        return $this->complete($request, $challenges, $accounts, false);
    }

    public function sendEmail(Request $request, Challenges $challenges)
    {
        $row = $challenges->current($request);
        $this->limit($row->user_id, 'email-proof', 6, 3600);
        $challenges->send($request);
        return back()->with('status', 'Check your inbox. Open the confirmation link in this same browser.');
    }

    public function email(Request $request, $challenge, Challenges $challenges)
    {
        $row = $challenges->emailProof($request, $challenge);
        return view('auth.google.email-confirm', ['challenge' => $row]);
    }

    public function verifyEmail(Request $request, $challenge, Challenges $challenges, Accounts $accounts)
    {
        $challenges->emailProof($request, $challenge);
        return $this->complete($request, $challenges, $accounts, true);
    }

    private function complete(Request $request, Challenges $challenges, Accounts $accounts, $email)
    {
        $row = DB::transaction(function () use ($request, $challenges, $accounts, $email) {
            $row = $email ? $challenges->emailProof($request, $request->route('challenge'), true) : $challenges->current($request, null, true);
            $user = User::whereKey($row->user_id)->lockForUpdate()->firstOrFail();
            if ($user->email !== $row->account_email) $challenges->expired();
            if (!$email && (!$accounts->hasPassword($user) || !Hash::check($request->password, $user->password))) {
                throw ValidationException::withMessages(['password' => 'That password does not match your PianoLIT account.']);
            }
            if ($row->purpose === 'link') $accounts->link($user, ['sub' => $row->provider_user_id, 'email' => $row->provider_email]);
            $challenges->consume($row);
            return $row;
        }, 3);
        $request->session()->forget('google_auth.challenge');
        $user = User::findOrFail($row->user_id);
        if ($row->purpose === 'link') return app(GoogleHandoffController::class)->login($request, $user, $row->destination);
        $request->session()->regenerate();
        $accounts->verified($request, $user);
        return $this->next($request);
    }

    public function next(Request $request)
    {
        $action = $request->session()->pull('google_auth.action');
        if ($action === 'connect') return app(GoogleController::class)->connect($request, app(Configuration::class), app(\App\Auth\Google\Provider::class), app(Accounts::class));
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
        DB::transaction(function () use ($request, $accounts) {
            $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            if (!$accounts->recent($request, $user)) throw ValidationException::withMessages(['google' => 'Please confirm your identity again before setting a password.']);
            if ($accounts->hasPassword($user)) throw ValidationException::withMessages(['password' => 'You already have a password. Use the existing password reset option to change it.']);
            $user->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
            DB::table('user_auth_providers')->where('user_id', $user->id)->update(['unusable_password_hash' => null, 'updated_at' => now()]);
            $accounts->audit($user->id, 'password_set');
            event(new PasswordReset($user));
        }, 3);
        $request->session()->regenerate();
        $accounts->verified($request, $request->user('web')->fresh());
        return redirect('/auth/google/accounts')->with('status', 'Password set. You can now use your email and password in the PianoLIT iOS app.');
    }

    public function disconnect(Request $request, Accounts $accounts)
    {
        if (!$accounts->recent($request, $request->user('web'))) return app(GoogleController::class)->confirm($request, 'disconnect');
        DB::transaction(function () use ($request, $accounts) {
            $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            if (!$accounts->recent($request, $user)) throw ValidationException::withMessages(['google' => 'Please confirm your identity again before disconnecting Google.']);
            if (!$accounts->hasPassword($user)) throw ValidationException::withMessages(['google' => 'Set a password before disconnecting Google so you can still sign in.']);
            if (DB::table('user_auth_providers')->where('user_id', $user->id)->where('provider', 'google')->delete()) $accounts->audit($user->id, 'disconnected');
        }, 3);
        return back()->with('status', 'Google disconnected. Your PianoLIT account and data are unchanged.');
    }

    private function limit($userId, $purpose, $max, $seconds = 60)
    {
        $key = 'google-account.'.$purpose.'.'.$userId;
        if (RateLimiter::tooManyAttempts($key, $max)) throw ValidationException::withMessages(['google' => 'Too many attempts. Please try again later.']);
        RateLimiter::hit($key, $seconds);
    }
}
