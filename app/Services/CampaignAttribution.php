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
    const ARRIVAL_SESSION = 'campaign.arrivals';
    const PENDING_SESSION = 'campaign.pending';
    const GOOGLE_SESSION = 'campaign.google';

    public function available(Request $request)
    {
        return (config('attribution.enabled') || config('attribution.conversion_links_enabled')) && $this->supportsRequest($request);
    }

    public function supportsRequest(Request $request)
    {
        return $request->hasSession()
            && in_array($request->getHost(), [config('app.short_url'), 'www.'.config('app.short_url'), 'my.'.config('app.short_url'), 'blog.'.config('app.short_url'), 'shop.'.config('app.short_url'), 'learn.'.config('app.short_url'), 'games.'.config('app.short_url'), 'metronome.'.config('app.short_url')], true)
            && ! auth('admin')->check() && ! $request->session()->get('impersonator')
            && ! \App\Activity\ExcludedAccounts::request($request);
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
        if (! $this->available($request) || ! $request->isMethod('GET')) return null;
        $pending = $request->session()->get($this->sessionKey($request, self::PENDING_SESSION));
        if ($pending && ($pending['captured_at'] ?? 0) < now()->subDays(config('attribution.touch_days'))->timestamp) $pending = null;
        $parameters = CampaignParameters::fromQuery($request->query()) ?: ($pending['parameters'] ?? null);
        if (! $parameters) return null;
        if ($request->attributes->has('campaign.touch')) return Crypt::encryptString(json_encode($request->attributes->get('campaign.touch')));
        $visit = $this->arrival($request);
        $touch = [
            'parameters' => $visit ? array_intersect_key((array) $visit, array_flip(CampaignParameters::FIELDS)) : ($pending['parameters'] ?? $parameters),
            'visit_id' => $visit ? $visit->id : ($pending['visit_id'] ?? (string) Str::uuid()), 'issued_at' => now()->timestamp,
            'captured_at' => $visit ? carbon($visit->created_at)->timestamp : ($pending['captured_at'] ?? now()->timestamp),
            'landing_url' => $visit ? $visit->landing_url : ($pending['landing_url'] ?? CampaignLandingPage::capture($request)),
            'context' => $this->context($request),
        ];
        $request->attributes->set('campaign.touch', $touch);
        return Crypt::encryptString(json_encode($touch));
    }

    public function recordArrival(Request $request, $response)
    {
        // Ordinary navigation must not add account-exclusion/database lookups.
        if (! $request->isMethod('GET') || ! CampaignParameters::fromQuery($request->query())) return;
        if (! $this->available($request) || $this->consent($request) === 'denied'
            || $request->ajax() || $request->expectsJson()
            || ($request->hasHeader('Accept') && strpos($request->header('Accept'), 'text/html') === false)
            || \App\Activity\TrafficFilter::ignores($request)
            || stripos($request->header('Sec-Purpose', $request->header('Purpose', '')), 'prefetch') !== false
            || ! $response->isSuccessful() || stripos($response->headers->get('Content-Type', ''), 'text/html') === false) return;
        if (! $this->landingToken($request) || $this->arrival($request)) return;
        $touch = $request->attributes->get('campaign.touch');
        if (!config('attribution.enabled')) {
            // Keep only the first tagged destination in this host's session.
            // GA counts the traffic; SQL starts only after attribution opt-in.
            $request->session()->put($this->sessionKey($request, self::PENDING_SESSION), $touch);
            return;
        }
        // An arrival is anonymous until the existing consent endpoint explicitly
        // upgrades it. No account link or long-lived campaign cookie is created.
        DB::table('campaign_visits')->insertOrIgnore($touch['parameters'] + [
            'id' => $touch['visit_id'], 'campaign' => CampaignParameters::key($touch['parameters']),
            'user_id' => null, 'consented_at' => null, 'created_at' => now(),
            'landing_url' => $touch['landing_url'],
            'expires_at' => now()->addDays(config('attribution.touch_days')),
        ]);
        $request->session()->put($this->arrivalSessionKey($request), $touch['visit_id']);
    }

    private function arrivalSessionKey(Request $request)
    {
        // Session domains can be shared; attribution must still stay host-local.
        return $this->sessionKey($request, self::ARRIVAL_SESSION);
    }

    private function sessionKey(Request $request, $key)
    {
        return $key.'.'.hash('sha256', $request->getHost());
    }

    private function arrival(Request $request, $requireFresh = true)
    {
        if ($visit = $this->visit($request, $requireFresh)) return $visit;
        $id = $request->session()->get($this->arrivalSessionKey($request));
        if (! is_string($id) || ! Str::isUuid($id)) return null;
        $query = DB::table('campaign_visits')->where('id', $id)->whereNull('user_id')->whereNull('consented_at');
        if ($requireFresh) $query->where('expires_at', '>', now());
        return $query->first();
    }

    public function campaignFromToken($token)
    {
        try { $data = json_decode(Crypt::decryptString($token), true); }
        catch (\Exception $exception) { return null; }
        if (! is_array($data) || ! is_array($data['parameters'] ?? null) || ! is_int($data['issued_at'] ?? null) || ! is_string($data['visit_id'] ?? null) || ! Str::isUuid($data['visit_id'])) return null;
        $parameters = CampaignParameters::fromQuery($data['parameters']);
        if (! $parameters || $parameters !== $data['parameters'] || ($data['context'] ?? null) !== $this->context(request())) return null;
        // Older open pages may still acknowledge a token without this field.
        if (($data['landing_url'] ?? '') !== '' && (! CampaignLandingPage::valid($data['landing_url'])
            || parse_url($data['landing_url'], PHP_URL_HOST) !== request()->getHost())) return null;
        $age = now()->timestamp - $data['issued_at'];
        return $age >= 0 && $age <= 600 ? $data : null;
    }

    private function context(Request $request)
    {
        return hash('sha256', $request->getHost().'|'.$request->session()->getId());
    }

    public function visit(Request $request, $requireFresh = true)
    {
        if (! $this->available($request) || $this->consent($request) !== 'granted') return null;
        $id = $request->cookie(self::VISIT_COOKIE);
        if (! is_string($id) || ! Str::isUuid($id)) return null;
        $visit = DB::table('campaign_visits')->where('id', $id)->first();
        if (! $visit || ! $visit->consented_at) return null;
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
            if ($arrival = $this->arrival($request, false)) DB::table('campaign_visits')->where('id', $arrival->id)->delete();
            if (auth('web')->check()) DB::table('campaign_visits')->where('user_id', auth('web')->id())->delete();
            $this->clearCookies($request);
            $request->session()->forget([$this->sessionKey($request, self::PENDING_SESSION), $this->sessionKey($request, self::GOOGLE_SESSION)]);
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
            DB::table('campaign_visits')->insertOrIgnore(array_merge($touch['parameters'], [
                'id' => $id, 'campaign' => CampaignParameters::key($touch['parameters']), 'user_id' => auth('web')->id(),
                'landing_url' => $touch['landing_url'] ?? '',
                'consented_at' => now(), 'created_at' => isset($touch['captured_at']) ? \Carbon\Carbon::createFromTimestamp($touch['captured_at'], config('app.timezone')) : now(),
                'expires_at' => now()->addDays(config('attribution.touch_days')),
            ]));
            // The GET may already have counted this exact anonymous arrival.
            // Opt-in adds permission/account linkage without a second arrival.
            DB::table('campaign_visits')->where('id', $id)->whereNull('user_id')->whereNull('consented_at')
                ->update(['consented_at' => now(), 'user_id' => auth('web')->id()]);
            $this->queueCookie($request, self::VISIT_COOKIE, $id);
            $request->cookies->set(self::VISIT_COOKIE, $id);
        }
        $this->bind($request);
    }

    public function captureGoogle(Request $request, array $context): void
    {
        if (!$this->available($request) || $this->consent($request) !== 'granted') return;
        $context = \App\Analytics\GoogleAnalyticsConversions::browserContext($context);
        if (!$context) return; // Missing/blocked Google tag is not an invented identity.
        $request->session()->put($this->sessionKey($request, self::GOOGLE_SESSION), $context);
        if ($visit = $this->visit($request)) {
            DB::table('campaign_visits')->where('id', $visit->id)->update(['google_context' => Crypt::encryptString(json_encode($context))]);
        }
    }

    public function checkout(Request $request, User $user, string $operationId): void
    {
        if (!$this->available($request) || $this->consent($request) !== 'granted' || auth('web')->id() !== $user->id) return;
        $operation = DB::table('stripe_billing_operations')->where('id', $operationId)->where('user_id', $user->id)->where('kind', 'purchase')->first();
        if (!$operation) return;
        if (!$this->visit($request) && ($token = $this->landingToken($request))) $this->choose($request, 'granted', $this->campaignFromToken($token));
        $visit = $this->visit($request);
        $google = $request->session()->get($this->sessionKey($request, self::GOOGLE_SESSION));
        if (!$visit && $google) {
            // Direct/organic conversions need a browser link too. Blank UTM
            // fields are not counted as campaign arrivals by the legacy reader.
            $id = (string) Str::uuid();
            DB::table('campaign_visits')->insert(array_fill_keys(CampaignParameters::FIELDS, '') + [
                'id' => $id, 'campaign' => CampaignParameters::key(array_fill_keys(CampaignParameters::FIELDS, '')),
                'user_id' => $user->id, 'consented_at' => now(), 'created_at' => now(), 'expires_at' => now()->addDays(config('attribution.touch_days')),
                'landing_url' => '', 'google_context' => Crypt::encryptString(json_encode($google)),
            ]);
            $this->queueCookie($request, self::VISIT_COOKIE, $id);
            $request->cookies->set(self::VISIT_COOKIE, $id);
            $visit = $this->visit($request);
        }
        if (!$visit || (int) $visit->user_id !== $user->id) return;
        DB::table('campaign_checkout_contexts')->insertOrIgnore(['operation_id' => $operationId, 'visit_id' => $visit->id, 'created_at' => now('UTC')]);
    }

    public function clearCookies(Request $request)
    {
        if ($request->hasSession()) $request->session()->forget([$this->arrivalSessionKey($request), $this->sessionKey($request, self::PENDING_SESSION), $this->sessionKey($request, self::GOOGLE_SESSION)]);
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
                'stripe_customer_id' => $customer->id, 'billing_account' => $user->membership && $user->membership->source instanceof \App\Billing\Sources\Stripe
                    ? app(\App\Billing\Stripe\Accounts::class)->forSource($user->membership->source) : ($user->isSwissCustomer() ? 'swiss' : 'us'),
                'plan' => (string) ($user->membership ? $user->membership->source->plan : ($subscription->plan->id ?? 'unknown')),
                'trial_started_at' => $subscription->status === 'trialing' ? \Carbon\Carbon::createFromTimestamp($subscription->trial_start ?? now()->timestamp) : null,
                'subscribed_at' => \Carbon\Carbon::createFromTimestamp($subscription->created ?? now()->timestamp),
            ]);
            (new CampaignConversions)->link($visit->id, $user->id, $user->membership && $user->membership->source instanceof \App\Billing\Sources\Stripe
                ? app(\App\Billing\Stripe\Accounts::class)->forSource($user->membership->source) : ($user->isSwissCustomer() ? 'swiss' : 'us'),
                json_decode(json_encode($subscription), true), $user->membership ? $user->membership->source->plan : null);
        });
    }
}
