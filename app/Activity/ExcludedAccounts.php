<?php

namespace App\Activity;

use App\{Admin, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cookie, DB};
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as BrowserCookie;

class ExcludedAccounts
{
    const COOKIE = 'pianolit_analytics_optout';
    const SESSION = 'analytics.excluded';
    const REQUIRED_EMAILS = ['arthurvillar@gmail.com', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'];

    public static function emails()
    {
        // Explicit account opt-outs survive an older cached config list. Config
        // may add exclusions, but must not re-enable these accounts' tracking.
        $emails = array_merge(self::REQUIRED_EMAILS, (array) config('activity.excluded_emails', []));
        return array_values(array_unique(array_filter(array_map(function ($email) {
            return strtolower(trim((string) $email));
        }, $emails))));
    }

    public static function user($user)
    {
        return ($user instanceof User || $user instanceof Admin) && self::email($user->email);
    }

    public static function email($email)
    {
        return in_array(strtolower(trim((string) $email)), self::emails(), true);
    }

    public static function ids()
    {
        return DB::table('users')->whereIn(DB::raw('LOWER(TRIM(email))'), self::emails())->select('id');
    }

    public static function id($id)
    {
        if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9]\d*$/D', (string) $id) || (float) $id > 4294967295) return false;
        $user = auth('web')->user();
        if ($user instanceof User && (string) $user->id === (string) $id) return self::user($user);
        return self::ids()->where('id', $id)->exists();
    }

    public static function request(Request $request)
    {
        $user = auth('web')->user();
        if (self::user($user) || self::user(auth('admin')->user())) return true;
        if ($user instanceof User) return false;
        return $request->cookie(self::COOKIE) === '1'
            || ($request->hasSession() && $request->session()->get(self::SESSION) === true);
    }

    public static function synchronize(Request $request, $user = null)
    {
        $user = $user ?: auth('web')->user();
        if (!self::user($user) && self::user(auth('admin')->user())) $user = auth('admin')->user();
        if (self::user($user)) {
            // A successful guard login identifies the current browser's old
            // anonymous context. Never infer ownership from submitted IDs/IPs.
            $cleanupFailed = false;
            $visitor = $request->hasSession() ? $request->session()->get('activity.visitor') : null;
            if ($visitor || $request->cookie('pianolit_campaign_visit')) {
                try { (new ExcludedHistory)->anonymous($visitor, $request->cookie('pianolit_campaign_visit')); }
                // Keep the anonymous context for a later retry without sending
                // its identity/payload to an external error tracker.
                catch (\Throwable $exception) { $cleanupFailed = true; }
            }
            if ($request->hasSession()) {
                $request->session()->forget(['activity.visit', PendingVisits::KEY, 'webapp.location_attempt']);
                if (!$cleanupFailed) $request->session()->forget('activity.visitor');
                $request->session()->put(self::SESSION, true);
            }
            foreach (['activity.page', 'activity.visitor', 'webapp.location'] as $name) $request->attributes->remove($name);
            if ($request->cookie(self::COOKIE) !== '1') {
                Cookie::queue(new BrowserCookie(self::COOKIE, '1', now()->addYear(), '/', self::domain($request), $request->isSecure(), true, false, 'lax'));
                $request->cookies->set(self::COOKIE, '1');
            }
            if (!$cleanupFailed && ($request->cookie('pianolit_campaign_visit') || $request->cookie('pianolit_campaign_consent'))) (new \App\Services\CampaignAttribution)->clearCookies($request);
        } elseif ($user instanceof User) {
            // A different signed-in account on a shared browser resumes normal
            // measurement. This privacy flag never grants account/media access.
            if ($request->hasSession()) $request->session()->forget(self::SESSION);
            if ($request->cookie(self::COOKIE)) {
                Cookie::queue(new BrowserCookie(self::COOKIE, '', now()->subDay(), '/', self::domain($request), $request->isSecure(), true, false, 'lax'));
                $request->cookies->remove(self::COOKIE);
            }
        }
    }

    public static function users($query, $email = 'users.email')
    {
        $column = DB::connection()->getQueryGrammar()->wrap($email);
        return $query->whereNotIn(DB::raw('LOWER(TRIM('.$column.'))'), self::emails());
    }

    private static function domain(Request $request)
    {
        $site = ltrim((string) config('app.short_url'), '.');
        $host = $request->getHost();
        // Share only this privacy flag across the website and my.*. Preview/IP
        // hosts use a host-only cookie. It contains no account or visit identity.
        return strpos($site, '.') !== false && !filter_var($site, FILTER_VALIDATE_IP)
            && ($host === $site || Str::endsWith($host, '.'.$site)) ? '.'.$site : null;
    }

    public static function owned($query, $column = 'user_id')
    {
        return $query->where(function ($query) use ($column) { $query->whereNull($column)->orWhereNotIn($column, self::ids()); });
    }

    public static function visits($query, $alias = 'v')
    {
        self::owned($query, $alias.'.user_id');
        return $query->whereNotExists(function ($links) use ($alias) {
            $links->from('activity_visit_links as excluded_links')->selectRaw('1')->whereColumn('excluded_links.visit_id', $alias.'.id')
                ->whereNull($alias.'.user_id')->whereIn('excluded_links.user_id', self::ids());
        });
    }

    public static function visitIds()
    {
        return DB::table('activity_visits as excluded_visits')->where(function ($query) {
            $query->whereIn('excluded_visits.user_id', self::ids())->orWhere(function ($anonymous) {
                $anonymous->whereNull('excluded_visits.user_id')->whereIn('excluded_visits.id', DB::table('activity_visit_links')->whereIn('user_id', self::ids())->select('visit_id'));
            });
        })->select('excluded_visits.id');
    }

    public static function events($query, $alias = null)
    {
        $prefix = $alias ? $alias.'.' : '';
        self::owned($query, $prefix.'user_id');
        return $query->where(function ($query) use ($prefix) { $query->whereNull($prefix.'visit_id')->orWhereNotIn($prefix.'visit_id', self::visitIds()); });
    }
}
