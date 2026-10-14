<?php

namespace App\Http\Controllers\Auth;

use App\{User, Admin};
use App\Auth\Apple\{Configuration, Provider};
use App\Auth\Google\{Accounts, Challenges};
use App\Http\Controllers\Controller;
use App\Notifications\NewUser;
use App\Notifications\User\ConfirmGoogleAccount;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Notification, URL};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppleController extends Controller
{
    public function redirect(Request $request, Configuration $config, Provider $provider)
    {
        if (!$config->available($request)) return redirect('/login')->with('error', 'Apple sign-in is currently unavailable. Please use Google or email and password.');
        if (auth('web')->check()) return redirect('/auth/google/accounts');
        return $this->start($request, $config, $provider, 'login');
    }

    public function connect(Request $request, Configuration $config, Provider $provider, Accounts $accounts)
    {
        if (!$config->available($request)) return back()->with('error', 'Apple sign-in is currently unavailable.');
        if (!$accounts->recent($request, $request->user('web'))) return $this->confirm($request, 'connect:apple');
        return $this->start($request, $config, $provider, 'connect');
    }

    public function reauthenticate(Request $request, Configuration $config, Provider $provider, Accounts $accounts)
    {
        if (!$config->available($request) || !$accounts->linkFor($request->user('web'), 'apple')) return back()->with('error', 'Please confirm your account using your password or email.');
        return $this->start($request, $config, $provider, 'verify');
    }

    public function confirm(Request $request, $action)
    {
        $request->session()->put('google_auth.action', $action);
        return redirect('/auth/apple/confirm');
    }

    private function start(Request $request, Configuration $config, Provider $provider, $kind)
    {
        $request->session()->forget(['apple_auth.challenge', 'apple_auth.choice']);
        $request->session()->put('apple_auth.context', ['kind' => $kind, 'user_id' => auth('web')->id(), 'host' => $request->getHost(),
            'destination' => $config->destination($request->session()->pull('url.intended', '/'), $request)]);
        return $provider->redirect($request, $config->callback($request), bin2hex(random_bytes(32)));
    }

    public function callback(Request $request, Configuration $config, Provider $provider)
    {
        try {
            if (!$config->available($request)) throw new \UnexpectedValueException;
            $id = $provider->receive($request);
            return response()->view('auth.apple.return', ['target' => '/auth/apple/complete/'.$id, 'failed' => false]);
        } catch (\Throwable $e) {
            // No session, identity mutation, state consumption or secret logging.
            try { app(Accounts::class)->audit(null, 'authentication_failed', 'apple'); } catch (\Throwable $ignored) {}
            return response()->view('auth.apple.return', ['target' => '/login', 'failed' => true], 400);
        }
    }

    public function complete(Request $request, $attempt, Configuration $config, Provider $provider, Accounts $accounts, Challenges $challenges)
    {
        $context = $request->session()->get('apple_auth.context');
        try {
            if (!$config->available($request) || !is_array($context) || $context['host'] !== $request->getHost()
                || $context['user_id'] !== auth('web')->id()) throw new \UnexpectedValueException;
            [$flow, $response] = $provider->consume($request, $attempt);
            $request->session()->forget('apple_auth.context');
            if ($response['error']) {
                if (in_array($response['error'], ['access_denied', 'user_cancelled_authorize'], true)) return $this->failure($context, 'Apple sign-in was cancelled. You can try again or use another sign-in method.');
                $accounts->audit($context['user_id'], 'authentication_failed', 'apple');
                return $this->failure($context, 'Apple could not complete sign-in. Please try again or use another sign-in method.');
            }
            $identity = $provider->identity($response['code'], $config->callback($request), $flow['nonce']);
            if ($context['kind'] === 'verify') {
                $user = $request->user('web'); $link = $accounts->linkFor($user, 'apple');
                if (!$link || !hash_equals($link->provider_user_id, $identity['sub'])) throw new \UnexpectedValueException;
                $accounts->verified($request, $user, 'apple'); $accounts->audit($user->id, 'verified', 'apple');
                return app(GoogleSecurityController::class)->next($request);
            }
            if ($context['kind'] === 'connect') {
                DB::transaction(function () use ($request, $accounts, $identity) {
                    $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
                    if (!$accounts->recent($request, $user)) throw new \UnexpectedValueException;
                    $accounts->link($user, $identity, null, 'apple');
                }, 3);
                return redirect('/auth/google/accounts')->with('status', 'Apple connected. Your PianoLIT email, password and Google connection are unchanged.');
            }
            $user = DB::transaction(function () use ($identity, $accounts) {
                    $linked = DB::table('user_auth_providers')->where('provider', 'apple')->where('provider_user_id', $identity['sub'])->lockForUpdate()->first();
                    if (!$linked) return null;
                    $user = User::whereKey($linked->user_id)->lockForUpdate()->firstOrFail();
                    $accounts->link($user, $identity, null, 'apple'); return $user;
                }, 3);
            if ($user) {
                return app(GoogleHandoffController::class)->login($request, $user, $context['destination'], 'apple');
            }
            $identity += $this->name($response['user']);
            if ($identity['email'] && ($user = $this->emailAccount($identity['email']))) {
                $challenges->create($request, $user, 'link', $identity, $context['destination'], 'apple');
                return redirect('/auth/apple/link');
            }
            // A relay address is not evidence of a different PianoLIT person.
            // Offer linking before making any account or firing signup events.
            $request->session()->put('apple_auth.choice', ['id' => (string) Str::uuid(), 'identity' => $identity,
                'host' => $request->getHost(), 'destination' => $context['destination'], 'expires' => time() + 900]);
            return redirect('/auth/apple/choose');
        } catch (\Throwable $e) {
            try { $accounts->audit($context['user_id'] ?? null, 'authentication_failed', 'apple'); } catch (\Throwable $ignored) {}
            return $this->failure($context, $e instanceof ValidationException ? collect($e->errors())->flatten()->first() : 'We could not complete Apple sign-in. Please start again in the same browser.');
        }
    }

    public function choose(Request $request)
    {
        return view('auth.apple.choose', ['choice' => $this->choice($request)]);
    }

    public function existing(Request $request, Challenges $challenges)
    {
        $request->validate(['choice_id' => 'required|uuid', 'email' => 'required|string|email|max:255']);
        $choice = $this->choice($request, true);
        $user = $this->emailAccount(strtolower($request->email));
        if (!$user) throw ValidationException::withMessages(['email' => 'We could not start confirmation for that email. Check your PianoLIT email or choose to create an account.']);
        $challenges->create($request, $user, 'link', $choice['identity'], $choice['destination'], 'apple');
        $request->session()->forget('apple_auth.choice');
        return redirect('/auth/apple/link');
    }

    public function register(Request $request, Accounts $accounts, Challenges $challenges)
    {
        $request->validate(['choice_id' => 'required|uuid']);
        $choice = $this->choice($request, true);
        if (!$choice['identity']['email']) throw ValidationException::withMessages(['email' => 'Verify your email before creating an account.']);
        return $this->registerVerified($request, $choice, $choice['identity']['email'], $accounts, $challenges);
    }

    public function requestEmail(Request $request, Challenges $challenges)
    {
        $request->validate(['choice_id' => 'required|uuid', 'email' => 'required|string|email|max:255']);
        $choice = $this->choice($request, true);
        if ($choice['identity']['email']) throw ValidationException::withMessages(['apple' => 'Apple has already verified your email. Please continue with that address.']);
        $email = strtolower($request->email);
        if ($user = $this->emailAccount($email)) {
            $challenges->create($request, $user, 'link', $choice['identity'], $choice['destination'], 'apple');
            $request->session()->forget('apple_auth.choice'); return redirect('/auth/apple/link');
        }
        if (!Cache::add('apple-signup.email-rate.'.hash('sha256', $email), true, 60)) throw ValidationException::withMessages(['email' => 'Please wait a minute before requesting another email.']);
        $token = bin2hex(random_bytes(32));
        $choice['email_proof'] = ['email' => $email, 'hash' => hash('sha256', $token), 'id' => (string) Str::uuid()];
        $url = URL::temporarySignedRoute('apple.signup.verify', carbon($choice['expires']), ['attempt' => $choice['email_proof']['id'], 'token' => $token]);
        try { Notification::route('mail', $email)->notify(new ConfirmGoogleAccount($url, 'signup', 'apple')); }
        catch (\Throwable $e) { throw ValidationException::withMessages(['email' => 'We could not send the email. Please try again shortly.']); }
        $request->session()->put('apple_auth.choice', $choice);
        return back()->with('status', 'Check your inbox and open the verification link in this same browser.');
    }

    public function signupEmail(Request $request, $attempt)
    {
        $this->emailProof($request, $attempt);
        return view('auth.apple.signup-email');
    }

    public function finishEmail(Request $request, $attempt, Accounts $accounts, Challenges $challenges)
    {
        $choice = $this->emailProof($request, $attempt);
        return $this->registerVerified($request, $choice, $choice['email_proof']['email'], $accounts, $challenges);
    }

    private function registerVerified(Request $request, array $choice, $email, Accounts $accounts, Challenges $challenges)
    {
        if (!Cache::add('apple-signup.used.'.$choice['id'], true, 900)) throw ValidationException::withMessages(['apple' => 'This signup attempt has already been used. Start again.']);
        [$user, $created, $needsLink] = $this->resolve($choice['identity'], $email, $request->getHost() === app(Configuration::class)->hosts()[1] ? 'webapp' : 'web', $accounts);
        $request->session()->forget('apple_auth.choice');
        if ($needsLink) { $challenges->create($request, $user, 'link', $choice['identity'], $choice['destination'], 'apple'); return redirect('/auth/apple/link'); }
        if ($created) { event(new Registered($user)); Admin::notifyAll(new NewUser($user)); }
        return app(GoogleHandoffController::class)->login($request, $user, $choice['destination'], 'apple');
    }

    private function resolve(array $identity, $email, $origin, Accounts $accounts)
    {
        $resolve = function () use ($identity, $email, $origin, $accounts) {
            $linked = DB::table('user_auth_providers')->where('provider', 'apple')->where('provider_user_id', $identity['sub'])->lockForUpdate()->first();
            if ($linked) { $user = User::whereKey($linked->user_id)->lockForUpdate()->firstOrFail(); $accounts->link($user, $identity, null, 'apple'); return [$user, false, false]; }
            $matches = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->get();
            if ($matches->count() > 1) throw new \UnexpectedValueException('Ambiguous account.');
            if ($matches->count()) return [$matches->first(), false, true];
            return [$accounts->create($identity, $origin, 'apple', $email), true, false];
        };
        try { return DB::transaction($resolve, 3); }
        catch (QueryException $e) {
            if (!in_array((string) $e->getCode(), ['23000', '23505'], true)) throw $e;
            return DB::transaction($resolve, 3);
        }
    }

    private function emailAccount($email)
    {
        $matches = User::whereRaw('LOWER(email) = ?', [$email])->limit(2)->get();
        if ($matches->count() > 1) throw ValidationException::withMessages(['apple' => 'We could not safely identify this account. Please contact PianoLIT support.']);
        return $matches->first();
    }

    private function choice(Request $request, $submitted = false)
    {
        $choice = $request->session()->get('apple_auth.choice');
        if (!is_array($choice) || auth('web')->check() || $choice['host'] !== $request->getHost() || $choice['expires'] <= time()
            || ($submitted && $request->input('choice_id') !== $choice['id'])) throw ValidationException::withMessages(['apple' => 'This signup attempt has expired. Start Apple sign-in again.']);
        return $choice;
    }

    private function emailProof(Request $request, $attempt)
    {
        $choice = $this->choice($request); $proof = $choice['email_proof'] ?? [];
        $token = $request->query('token');
        if (($proof['id'] ?? null) !== $attempt || !is_string($token) || strlen($token) !== 64 || !hash_equals($proof['hash'] ?? '', hash('sha256', $token))) throw ValidationException::withMessages(['apple' => 'This email verification is invalid or expired.']);
        return $choice;
    }

    private function name($json)
    {
        $user = is_string($json) ? json_decode($json, true, 16) : null; $name = is_array($user) && is_array($user['name'] ?? null) ? $user['name'] : [];
        $clean = function ($value) { return mb_substr(trim(strip_tags(is_string($value) ? $value : '')), 0, 255); };
        return ['first_name' => $clean($name['firstName'] ?? null) ?: 'PianoLIT', 'last_name' => $clean($name['lastName'] ?? null)];
    }

    private function failure($context, $message)
    {
        return redirect(is_array($context) && $context['kind'] !== 'login' ? '/auth/google/accounts' : '/login')->with('error', $message);
    }
}
