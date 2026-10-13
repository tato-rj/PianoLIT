<?php

namespace App\Http\Controllers\Auth;

use App\{User, Admin};
use App\Auth\Google\{Accounts, Challenges, Configuration, Provider};
use App\Http\Controllers\Controller;
use App\Notifications\NewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoogleController extends Controller
{
    public function redirect(Request $request, Configuration $config, Provider $provider)
    {
        if (!$config->available($request)) return redirect('/login')->with('error', 'Google sign-in is currently unavailable. Please use email and password.');
        if (auth('web')->check()) return redirect('/auth/google/accounts');
        return $this->start($request, $config, $provider, 'login');
    }

    public function connect(Request $request, Configuration $config, Provider $provider, Accounts $accounts)
    {
        if (!$config->available($request)) return back()->with('error', 'Google sign-in is currently unavailable.');
        if (!$accounts->recent($request, $request->user('web'))) return $this->confirm($request, 'connect');
        return $this->start($request, $config, $provider, 'connect');
    }

    public function reauthenticate(Request $request, Configuration $config, Provider $provider, Accounts $accounts)
    {
        if (!$config->available($request) || !$accounts->linkFor($request->user('web'))) return back()->with('error', 'Please confirm your account using your password or email.');
        return $this->start($request, $config, $provider, 'verify');
    }

    private function start(Request $request, Configuration $config, Provider $provider, $kind)
    {
        $nonce = bin2hex(random_bytes(32));
        $destination = $config->destination($request->session()->pull('url.intended', '/'), $request);
        $request->session()->forget('google_auth.challenge');
        $request->session()->put('google_auth.context', ['kind' => $kind, 'user_id' => auth('web')->id(), 'host' => $request->getHost(), 'nonce' => $nonce, 'destination' => $destination]);
        return $provider->redirect($request, $config->callback($request), $nonce);
    }

    public function callback(Request $request, Configuration $config, Provider $provider, Accounts $accounts, Challenges $challenges)
    {
        $context = $request->session()->pull('google_auth.context');
        try {
            if (!$config->available($request) || !is_array($context) || $context['host'] !== $request->getHost()
                || $context['user_id'] !== auth('web')->id()) throw new \UnexpectedValueException;
            $flow = $provider->consume($request);
            if ($request->has('error')) return $this->failure($context, 'Google sign-in was cancelled. You can try again or use email and password.');
            $identity = $provider->identity($request, $config->callback($request), $context['nonce'], $flow);
            if ($context['kind'] === 'verify') {
                $user = $request->user('web');
                $link = $accounts->linkFor($user);
                if (!$link || !hash_equals($link->provider_user_id, $identity['sub'])) throw new \UnexpectedValueException;
                $accounts->verified($request, $user);
                return app(GoogleSecurityController::class)->next($request);
            }
            if ($context['kind'] === 'connect') {
                $user = $request->user('web');
                if (!$accounts->recent($request, $user)) throw new \UnexpectedValueException;
                DB::transaction(function () use ($request, $user, $identity, $accounts) {
                    $fresh = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                    if (!$accounts->recent($request, $fresh)) throw new \UnexpectedValueException;
                    $accounts->link($fresh, $identity);
                }, 3);
                return redirect('/auth/google/accounts')->with('status', 'Google connected. Your PianoLIT email and password are unchanged.');
            }
            [$user, $created, $needsLink] = $this->resolve($identity, $request->getHost() === $config->hosts()[1] ? 'webapp' : 'web', $accounts);
            if ($needsLink) {
                $challenges->create($request, $user, 'link', $identity, $context['destination']);
                return redirect('/auth/google/link');
            }
            if ($created) {
                event(new Registered($user));
                Admin::notifyAll(new NewUser($user));
            }
            return app(GoogleHandoffController::class)->login($request, $user, $context['destination']);
        } catch (\Throwable $e) {
            // Provider exception bodies may contain codes/tokens. Never log them.
            if (is_array($context)) {
                try { $accounts->audit($context['user_id'], 'authentication_failed'); } catch (\Throwable $ignored) {}
            }
            return $this->failure($context, $e instanceof ValidationException ? collect($e->errors())->flatten()->first() : 'We could not complete Google sign-in. Please try again.');
        } finally {
            $request->session()->forget('google_auth.oauth');
        }
    }

    private function resolve(array $identity, $origin, Accounts $accounts)
    {
        $resolve = function () use ($identity, $origin, $accounts) {
            $link = DB::table('user_auth_providers')->where('provider', 'google')->where('provider_user_id', $identity['sub'])->lockForUpdate()->first();
            if ($link) {
                $user = User::whereKey($link->user_id)->lockForUpdate()->firstOrFail();
                $accounts->link($user, $identity);
                return [$user, false, false];
            }
            $matches = User::whereRaw('LOWER(email) = ?', [$identity['email']])->lockForUpdate()->get();
            if ($matches->count() > 1) throw new \UnexpectedValueException('Ambiguous account.');
            if ($matches->count()) return [$matches->first(), false, true];
            return [$accounts->create($identity, $origin), true, false];
        };
        try { return DB::transaction($resolve, 3); }
        catch (QueryException $e) {
            if (!in_array((string) $e->getCode(), ['23000', '23505'], true)) throw $e;
            // Another callback may have created the account/subject first.
            return DB::transaction($resolve, 3);
        }
    }

    private function failure($context, $message)
    {
        return redirect(is_array($context) && $context['kind'] !== 'login' ? '/auth/google/accounts' : '/login')->with('error', $message);
    }

    public function confirm(Request $request, $action)
    {
        $request->session()->put('google_auth.action', $action);
        return redirect('/auth/google/confirm');
    }
}
