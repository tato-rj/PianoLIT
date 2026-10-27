<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use App\Services\CampaignConversions;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Crypt, DB, Http};
use Ramsey\Uuid\Uuid;

class GoogleAnalyticsConversions
{
    public const TRIAL_EVENT = 'membership_trial_started';
    public const PAYMENT_EVENT = 'purchase';

    public static function browserContext(array $input): ?array
    {
        $client = $input['client_id'] ?? null;
        if (!is_string($client) || !preg_match('/^\d{1,20}\.\d{1,20}$/D', $client)) return null;
        $session = $input['session_id'] ?? null;
        if (!is_string($session) && !is_int($session)) $session = null;
        if ($session !== null && (!preg_match('/^\d{1,12}$/D', (string) $session) || (int) $session > now('UTC')->timestamp + 300
            || (int) $session < now('UTC')->subDay()->timestamp)) $session = null;
        return ['client_id' => $client, 'session_id' => $session === null ? null : (string) $session, 'observed_at' => now('UTC')->timestamp];
    }

    public static function enqueueForSubscription(string $subscription, string $customer): void
    {
        if (!config('attribution.conversion_links_enabled')) return;
        $link = DB::table('campaign_subscriptions')->where('stripe_subscription_id', $subscription)->where('stripe_customer_id', $customer)->where('google_live', true)->first();
        if (!$link) return;
        $kinds = $link->trial_started_at ? ['trial'] : [];
        if (DB::table('stripe_payment_receipts')->where('kind', 'invoice')->where('stripe_subscription_id', $subscription)->where('stripe_customer_id', $customer)->where('amount', '>', 0)->exists()) $kinds[] = 'first_payment';
        foreach ($kinds as $kind) {
            DB::table('google_analytics_conversions')->insertOrIgnore([
                'id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'pianolit:ga:'.$link->id.':'.$kind),
                'campaign_subscription_id' => $link->id, 'kind' => $kind, 'state' => 'pending',
                'available_at' => now('UTC'), 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
            ]);
        }
    }

    public function configured(): bool
    {
        return app()->environment('production') && config('attribution.conversion_links_enabled')
            && config('services.google_analytics.conversions_enabled')
            && preg_match('/^G-[A-Z0-9]+$/D', (string) config('services.google_analytics.measurement_id')) && $this->secret() !== '';
    }

    private function secret(): string
    {
        $secret = config('services.google_analytics.api_secret');
        $path = config('services.google_analytics.api_secret_file');
        if (!$secret && is_string($path) && is_file($path) && is_readable($path) && filesize($path) <= 4096) $secret = file_get_contents($path);
        return is_string($secret) ? trim($secret) : '';
    }

    public function sendPending(int $limit = 100): int
    {
        if (!$this->configured()) return 0;
        // A process can die after posting but before saving the acknowledgement.
        // Purchase retries use GA's transaction deduplication. Trials have no
        // equivalent guarantee, so ambiguous deliveries require review.
        DB::table('google_analytics_conversions')->where('state', 'dispatching')->where('kind', 'trial')->where('claimed_until', '<', now('UTC'))
            ->update(['state' => 'uncertain', 'last_error' => 'interrupted_delivery', 'updated_at' => now('UTC')]);
        DB::table('google_analytics_conversions')->whereIn('state', ['processing', 'dispatching'])->where('claimed_until', '<', now('UTC'))
            ->update(['state' => 'pending', 'updated_at' => now('UTC')]);
        $ids = DB::table('google_analytics_conversions')->where('state', 'pending')->where('available_at', '<=', now('UTC'))->orderBy('available_at')->orderBy('id')->limit($limit)->pluck('id');
        $submitted = 0;
        foreach ($ids as $id) if ($this->send($id)) $submitted++;
        return $submitted;
    }

    private function send(string $id): bool
    {
        $row = DB::transaction(function () use ($id) {
            $row = DB::table('google_analytics_conversions')->where('id', $id)->lockForUpdate()->first();
            if (!$row || $row->state !== 'pending' || Carbon::parse($row->available_at, 'UTC')->gt(now('UTC'))) return null;
            DB::table('google_analytics_conversions')->where('id', $id)->update(['state' => 'processing', 'attempts' => $row->attempts + 1, 'claimed_until' => now('UTC')->addMinutes(5), 'updated_at' => now('UTC')]);
            $row->attempts++;
            return $row;
        });
        if (!$row) return false;
        $dispatched = false;
        try {
            $link = $this->eligibleLink($row);
            if (!$link) { $this->finish($row, 'skipped', 'consent_or_account_unavailable'); return false; }
            $payload = $row->payload ? json_decode(Crypt::decryptString($row->payload), true) : $this->payload($row, $link);
            if (!$payload) { $this->finish($row, 'skipped', 'no_eligible_event'); return false; }
            $at = (int) floor($payload['timestamp_micros'] / 1000000);
            if ($at < now('UTC')->subHours(72)->timestamp || $at > now('UTC')->timestamp + 300) { $this->finish($row, 'expired', 'outside_ga_delivery_window'); return false; }
            // Recheck after optional provider reads. Withdrawal/account removal
            // cancels queued work, including saved payloads from a failed attempt.
            if (!$this->eligibleLink($row)) { $this->finish($row, 'skipped', 'consent_or_account_unavailable'); return false; }
            if (!DB::table('google_analytics_conversions')->where('id', $row->id)->where('state', 'processing')->update([
                'state' => 'dispatching', 'payload' => Crypt::encryptString(json_encode($payload)), 'updated_at' => now('UTC'),
            ])) return false;
            $dispatched = true;
            try {
                $response = Http::asJson()->timeout(10)->withOptions(['allow_redirects' => false])->post(
                    'https://www.google-analytics.com/mp/collect?'.http_build_query([
                        'measurement_id' => config('services.google_analytics.measurement_id'), 'api_secret' => $this->secret(),
                    ]), $payload);
            } catch (\Throwable $exception) {
                // Exceptions can contain the API-secret URL. Keep only a safe code.
                if ($row->kind === 'trial') $this->finish($row, 'uncertain', 'transport_outcome_unknown');
                else $this->retry($row, 'transport_outcome_unknown');
                return false;
            }
            if ($response->successful()) { $this->finish($row, 'submitted', null); return true; }
            if ($response->status() === 429) $this->retry($row, 'http_429');
            elseif ($response->serverError() && $row->kind === 'first_payment') $this->retry($row, 'http_'.$response->status());
            elseif ($response->serverError()) $this->finish($row, 'uncertain', 'http_'.$response->status());
            else $this->finish($row, 'failed', 'http_'.$response->status());
        } catch (\Throwable $exception) {
            // Incomplete provider history/decryption/temporary SQL failures must
            // never reach billing responses or leak provider/customer data.
            if ($dispatched && $row->kind === 'trial') $this->finish($row, 'uncertain', 'transport_outcome_unknown');
            else $this->retry($row, 'verification_unavailable');
        }
        return false;
    }

    private function eligibleLink($row)
    {
        $link = DB::table('campaign_subscriptions as s')->join('campaign_visits as v', 'v.id', '=', 's.visit_id')
            ->join('users as u', 'u.id', '=', 'v.user_id')->where('s.id', $row->campaign_subscription_id)->where('s.google_live', true)
            ->whereNotNull('v.consented_at')->select('s.*', 'v.user_id', 'v.consented_at', 'v.google_context', 'v.landing_url', 'v.utm_source', 'v.utm_medium', 'v.utm_campaign', 'v.utm_content')->first();
        return $link && !ExcludedAccounts::id($link->user_id) ? $link : null;
    }

    private function payload($row, $link): ?array
    {
        if (!$link->google_context) throw new \RuntimeException('browser_context_unavailable');
        $context = json_decode(Crypt::decryptString($link->google_context), true);
        if (!is_array($context) || !preg_match('/^\d{1,20}\.\d{1,20}$/D', $context['client_id'] ?? '') || !is_int($context['observed_at'] ?? null)) return null;
        $params = ['membership_conversion' => $row->kind, 'event_id' => hash_hmac('sha256', $row->id, config('app.key'))];
        foreach (['utm_campaign' => 'membership_campaign', 'utm_source' => 'membership_source', 'utm_medium' => 'membership_medium', 'utm_content' => 'membership_placement'] as $field => $name) {
            if ($link->$field !== '') $params[$name] = $link->$field;
        }
        if ($link->landing_url && \App\Services\CampaignLandingPage::valid($link->landing_url)) $params['membership_landing_host'] = parse_url($link->landing_url, PHP_URL_HOST);
        if ($row->kind === 'trial') {
            if (!$link->trial_started_at) return null;
            $at = carbon($link->trial_started_at)->timestamp;
            $name = self::TRIAL_EVENT;
        } else {
            $first = app(CampaignConversions::class)->firstPayment($link);
            if (!$first) throw new \RuntimeException('paid_invoice_unavailable');
            $at = $first['at'];
            // These are the currencies used by PianoLIT web memberships. Fail
            // closed for an unknown exponent rather than misstate an amount.
            if (!in_array($first['currency'], ['usd', 'chf', 'eur', 'gbp'], true)) throw new \RuntimeException('unsupported_currency');
            $value = $first['amount'] / 100;
            $params += ['currency' => strtoupper($first['currency']), 'value' => $value,
                'transaction_id' => 'membership_'.hash_hmac('sha256', $link->billing_account.'|'.$link->stripe_subscription_id, config('app.key')),
                'items' => [['item_id' => 'pianolit_web_membership', 'item_name' => 'PianoLIT membership', 'item_category' => 'Web membership', 'price' => $value, 'quantity' => 1]]];
            $name = self::PAYMENT_EVENT;
        }
        if ($at < carbon($link->consented_at)->timestamp || $at < carbon($link->subscribed_at)->timestamp || $at < now('UTC')->subHours(72)->timestamp) return null;
        // A delayed post-trial payment is not backdated into a weeks-old session.
        if (!empty($context['session_id']) && (int) $context['session_id'] >= now('UTC')->subDay()->timestamp
            && $at >= (int) $context['session_id']
            && $at >= $context['observed_at'] - 5 && $at <= $context['observed_at'] + 600) $params['session_id'] = $context['session_id'];
        return ['client_id' => $context['client_id'], 'timestamp_micros' => $at * 1000000,
            'consent' => ['ad_user_data' => 'DENIED', 'ad_personalization' => 'DENIED'],
            'events' => [['name' => $name, 'params' => $params]]];
    }

    private function retry($row, string $code): void
    {
        if ($row->attempts >= 12) { $this->finish($row, 'failed', $code); return; }
        DB::table('google_analytics_conversions')->where('id', $row->id)->update(['state' => 'pending', 'last_error' => $code,
            'available_at' => now('UTC')->addSeconds(min(3600, 60 * (2 ** min(6, $row->attempts - 1)))), 'claimed_until' => null, 'updated_at' => now('UTC')]);
    }

    private function finish($row, string $state, ?string $code): void
    {
        DB::table('google_analytics_conversions')->where('id', $row->id)->update(['state' => $state, 'last_error' => $code,
            'sent_at' => $state === 'submitted' ? now('UTC') : null, 'claimed_until' => null, 'updated_at' => now('UTC')]);
    }
}
