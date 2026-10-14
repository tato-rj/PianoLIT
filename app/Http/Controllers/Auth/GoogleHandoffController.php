<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Auth\Google\{Accounts, Configuration};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoogleHandoffController extends Controller
{
    public function login(Request $request, User $user, $destination, $provider = 'google')
    {
        auth('web')->login($user, true);
        $request->session()->regenerate();
        app(Accounts::class)->verified($request, $user, $provider);
        $config = app(Configuration::class);
        $destination = $config->destination($destination, $request);
        $host = parse_url($destination, PHP_URL_HOST);
        if ($host === $request->getHost()) return redirect($destination);
        $id = (string) Str::uuid();
        DB::table('auth_browser_handoffs')->insert([
            'id' => $id, 'user_id' => $user->id, 'source_host' => $request->getHost(), 'target_host' => $host,
            'source_session_hash' => $this->sessionHash($request), 'destination' => $destination,
            'expires_at' => now()->addSeconds(config('services.google_login.handoff_seconds')), 'created_at' => now(), 'updated_at' => now(),
        ]);
        // This random ID is only a lookup, never a credential. The authentication
        // secret is issued later into an HTML POST body and bound to both sessions.
        return redirect($config->base($host).'/auth/google/handoff/'.$id);
    }

    public function begin(Request $request, $handoff)
    {
        $row = DB::transaction(function () use ($request, $handoff) {
            $row = $this->row($handoff);
            abort_unless($row->target_host === $request->getHost(), 403);
            abort_if(auth('web')->check() && auth('web')->id() !== (int) $row->user_id, 403);
            $hash = $this->sessionHash($request);
            abort_if($row->target_session_hash && !hash_equals($row->target_session_hash, $hash), 403);
            DB::table('auth_browser_handoffs')->where('id', $row->id)->update(['target_session_hash' => $hash, 'updated_at' => now()]);
            return $row;
        });
        return redirect(app(Configuration::class)->base($row->source_host).'/auth/google/handoff/'.$row->id.'/approve');
    }

    public function approve(Request $request, $handoff)
    {
        $token = bin2hex(random_bytes(32));
        $row = DB::transaction(function () use ($request, $handoff, $token) {
            $row = $this->row($handoff);
            abort_unless($row->source_host === $request->getHost() && auth('web')->id() === (int) $row->user_id
                && hash_equals($row->source_session_hash, $this->sessionHash($request)) && $row->target_session_hash && !$row->token_hash, 403);
            DB::table('auth_browser_handoffs')->where('id', $row->id)->update(['token_hash' => hash('sha256', $token), 'updated_at' => now()]);
            return $row;
        });
        return view('auth.google.handoff', ['target' => app(Configuration::class)->base($row->target_host).'/auth/google/handoff/'.$row->id, 'token' => $token]);
    }

    public function finish(Request $request, $handoff)
    {
        $token = $request->input('handoff_token');
        $row = DB::transaction(function () use ($request, $handoff, $token) {
            $row = $this->row($handoff);
            abort_unless($row->target_host === $request->getHost()
                && $request->header('Origin') === app(Configuration::class)->base($row->source_host)
                && $row->target_session_hash && hash_equals($row->target_session_hash, $this->sessionHash($request))
                && is_string($token) && strlen($token) === 64 && $row->token_hash && hash_equals($row->token_hash, hash('sha256', $token)), 403);
            abort_if(auth('web')->check() && auth('web')->id() !== (int) $row->user_id, 403);
            $changed = DB::table('auth_browser_handoffs')->where('id', $row->id)->whereNull('consumed_at')->update(['consumed_at' => now(), 'token_hash' => null, 'updated_at' => now()]);
            abort_unless($changed, 403);
            return $row;
        });
        auth('web')->login(User::findOrFail($row->user_id), true);
        $request->session()->regenerate();
        // Handoff conveys login, not authority for changing authentication methods.
        $request->session()->forget('google_auth.recent');
        return redirect(app(Configuration::class)->destination($row->destination, $request));
    }

    private function row($id)
    {
        $row = DB::table('auth_browser_handoffs')->where('id', $id)->lockForUpdate()->first();
        abort_unless($row && !$row->consumed_at && carbon($row->expires_at)->isFuture(), 403);
        return $row;
    }

    private function sessionHash(Request $request)
    {
        return hash_hmac('sha256', $request->session()->getId(), config('app.key'));
    }
}
