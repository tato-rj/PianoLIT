<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cookie, Crypt, DB};
use Illuminate\Support\Str;
use App\User;
use Symfony\Component\HttpFoundation\Cookie as BrowserCookie;

class CampaignAttribution
{
    const VISIT_COOKIE = 'pianolit_campaign_visit';
    const CONSENT_COOKIE = 'pianolit_campaign_consent';

    public function available(Request $request)
    {
        return config('attribution.enabled')
            && $request->getHost() === 'my.'.config('app.short_url')
            && ! auth('admin')->check() && ! $request->session()->get('impersonator');
    }

    public function consent(Request $request)
    {
        $value = $request->cookie(self::CONSENT_COOKIE);
        foreach (['granted', 'denied'] as $choice) {
            if ($value === config('attribution.consent_version').':'.$choice) return $choice;
        }
        return null;
    }

    public function landingToken(Request $request)
    {
        if (! $this->available($request) || ! $request->isMethod('GET') || ! $request->routeIs('webapp.pieces.show')) return null;
        $piece = $request->route('piece');
        $pieceId = is_object($piece) ? $piece->getKey() : $piece;
        foreach (config('attribution.campaigns', []) as $key => $campaign) {
            if ((string) $pieceId !== (string) $campaign['piece_id']) continue;
            foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'] as $field) {
                if ($request->query($field) !== $campaign[$field]) continue 2;
            }
            // A signed/encrypted, short-lived acknowledgement of the rendered page.
            return Crypt::encryptString(json_encode(['campaign' => $key, 'visit_id' => (string) Str::uuid(), 'issued_at' => now()->timestamp]));
        }
        return null;
    }

    public function campaignFromToken($token)
    {
        try { $data = json_decode(Crypt::decryptString($token), true); }
        catch (\Exception $exception) { return null; }
        if (! is_array($data) || ! is_string($data['campaign'] ?? null) || ! is_int($data['issued_at'] ?? null) || ! is_string($data['visit_id'] ?? null) || ! Str::isUuid($data['visit_id'])) return null;
        $age = now()->timestamp - $data['issued_at'];
        return $age >= 0 && $age <= 600 && array_key_exists($data['campaign'], config('attribution.campaigns', [])) ? $data : null;
    }

    public function visit(Request $request, $requireFresh = true)
    {
        if (! $this->available($request) || $this->consent($request) !== 'granted') return null;
        $id = $request->cookie(self::VISIT_COOKIE);
        if (! is_string($id) || ! Str::isUuid($id)) return null;
        $visit = DB::table('campaign_visits')->where('id', $id)->first();
        if (! $visit) return null;
        $userId = auth('web')->id();
        if ($visit->user_id !== null && (int) $visit->user_id !== (int) $userId) return null;
        if ($requireFresh && \Carbon\Carbon::parse($visit->expires_at)->lte(now())) return null;
        return $visit;
    }

    public function bind(Request $request)
    {
        if (! $this->available($request) || ! auth('web')->check()) return;
        $visit = $this->visit($request, false);
        if (! $visit) {
            if ($request->cookie(self::VISIT_COOKIE)) $this->clearCookies($request);
            return;
        }
        if (\Carbon\Carbon::parse($visit->expires_at)->lte(now())) return;
        // Conditional claiming is atomic; another account cannot take this touch.
        DB::table('campaign_visits')->where('id', $visit->id)->whereNull('user_id')->update(['user_id' => auth('web')->id()]);
    }

    public function choose(Request $request, $choice, $touch = null)
    {
        if ($choice === 'denied') {
            if ($visit = $this->visit($request, false)) DB::table('campaign_visits')->where('id', $visit->id)->delete();
            if (auth('web')->check()) DB::table('campaign_visits')->where('user_id', auth('web')->id())->delete();
            $this->clearCookies($request);
        }
        $value = config('attribution.consent_version').':'.$choice;
        $this->queueCookie($request, self::CONSENT_COOKIE, $value);
        $request->cookies->set(self::CONSENT_COOKIE, $value);
        if ($choice !== 'granted' || ! $touch) return;
        // One first eligible touch per 30-day cookie; refreshes/direct pages do not
        // create extra arrivals or replace the acquisition campaign.
        $visit = $this->visit($request);
        if (! $visit) {
            $id = $touch['visit_id'];
            DB::table('campaign_visits')->insertOrIgnore([
                'id' => $id, 'campaign' => $touch['campaign'], 'user_id' => auth('web')->id(),
                'consented_at' => now(), 'created_at' => now(),
                'expires_at' => now()->addDays(config('attribution.touch_days')),
            ]);
            $this->queueCookie($request, self::VISIT_COOKIE, $id);
            $request->cookies->set(self::VISIT_COOKIE, $id);
        }
        $this->bind($request);
    }

    public function clearCookies(Request $request)
    {
        foreach ([self::VISIT_COOKIE, self::CONSENT_COOKIE] as $name) {
            Cookie::queue(new BrowserCookie($name, '', now()->subDay(), '/', null, $request->isSecure(), true, false, 'lax'));
            $request->cookies->remove($name);
        }
    }

    private function queueCookie(Request $request, $name, $value)
    {
        // EncryptCookies encrypts these; host-only, HttpOnly, SameSite=Lax.
        Cookie::queue(new BrowserCookie($name, $value, now()->addDays(config('attribution.touch_days')), '/', null, $request->isSecure(), true, false, 'lax'));
    }

    public function subscribed(Request $request, User $user, $customer, $subscription)
    {
        if (! $this->available($request) || auth('web')->id() !== $user->id || ! $subscription) return;
        $this->bind($request);
        $visit = $this->visit($request);
        if (! $visit || (int) $visit->user_id !== (int) $user->id) return;
        if (! in_array($subscription->status ?? null, ['trialing', 'active', 'incomplete'], true)) return;
        if (! is_string($subscription->id ?? null) || ! is_string($customer->id ?? null)) return;
        $subscriptionCustomer = $subscription->customer ?? $customer->id;
        if ($subscriptionCustomer !== $customer->id) return;
        DB::transaction(function () use ($visit, $user, $customer, $subscription) {
            $owned = DB::table('campaign_visits')->where('id', $visit->id)->where('user_id', $user->id)->where('expires_at', '>', now())->lockForUpdate()->first();
            if (! $owned) return;
            DB::table('campaign_subscriptions')->insertOrIgnore([
                'visit_id' => $visit->id, 'stripe_subscription_id' => $subscription->id,
                'stripe_customer_id' => $customer->id, 'billing_account' => $user->isSwissCustomer() ? 'swiss' : 'us',
                'plan' => (string) ($subscription->plan->id ?? 'unknown'),
                'trial_started_at' => $subscription->status === 'trialing' ? \Carbon\Carbon::createFromTimestamp($subscription->trial_start ?? now()->timestamp) : null,
                'subscribed_at' => \Carbon\Carbon::createFromTimestamp($subscription->created ?? now()->timestamp),
            ]);
        });
    }
}
