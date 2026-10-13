<?php

namespace App\Services\Apple;

use App\Billing\Sources\Apple;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

// Deliberately independent of AppleValidator and Apple::validate(), used by iOS.
class AdminReceiptVerifier
{
    public function verify(Apple $source)
    {
        $secret = config('apple-membership-monitor.shared_secret') ?: $source->password;
        if (! $source->latest_receipt || ! $secret) {
            return ['error_code' => 'missing_receipt'];
        }

        $payload = ['receipt-data' => $source->latest_receipt, 'password' => $secret, 'exclude-old-transactions' => false];
        $endpointEnvironment = 'Production';
        try {
            $response = $this->request('https://buy.itunes.apple.com/verifyReceipt', $payload);
            if (($response['status'] ?? null) === 21007) {
                $endpointEnvironment = 'Sandbox';
                $response = $this->request('https://sandbox.itunes.apple.com/verifyReceipt', $payload);
            }
        } catch (\Throwable $e) {
            // Never persist/log HTTP exception messages: they may include receipt data.
            return ['error_code' => 'connection_failed'];
        }

        if (! is_array($response) || ! isset($response['status']) || ! is_int($response['status'])) {
            return ['error_code' => 'invalid_response'];
        }
        if (! in_array($response['status'], [0, 21006], true)) {
            return ['error_code' => (string) $response['status']];
        }
        if (! is_array($response['receipt'] ?? null) || ($response['receipt']['bundle_id'] ?? null) !== config('apple-membership-monitor.bundle_id')) {
            return ['error_code' => 'bundle_mismatch'];
        }

        $transactions = $response['latest_receipt_info'] ?? $response['receipt']['in_app'] ?? [];
        if (isset($transactions['product_id'])) $transactions = [$transactions];
        // Match the original subscription chain, allowing a plan change in that chain.
        $saved = $source->latest_receipt_info;
        $originalId = is_object($saved) ? ($saved->original_transaction_id ?? null) : null;
        $transactions = collect(is_array($transactions) ? $transactions : [])->filter(function ($transaction) use ($source, $originalId) {
            if (! is_array($transaction) || ! is_string($transaction['product_id'] ?? null) || strlen($transaction['product_id']) > 255) return false;
            return $originalId ? ($transaction['original_transaction_id'] ?? null) === $originalId : $transaction['product_id'] === $source->plan;
        });

        $dated = $transactions->map(function ($transaction) {
            $transaction['_expires'] = $this->date($transaction, 'expires_date');
            return $transaction;
        });
        // An incomplete matching transaction is unknown, never proof of expiry.
        if ($dated->isEmpty() || $dated->contains(function ($t) { return ! $t['_expires']; })) {
            return ['error_code' => 'subscription_not_found'];
        }
        $latest = $dated->sortByDesc(function ($t) { return $t['_expires']->getTimestamp(); })->first();
        $renewal = collect(is_array($response['pending_renewal_info'] ?? null) ? $response['pending_renewal_info'] : [])->first(function ($item) use ($latest) {
            return is_array($item) && ($item['original_transaction_id'] ?? null) === ($latest['original_transaction_id'] ?? null)
                && ($item['product_id'] ?? null) === $latest['product_id'];
        });
        $expires = $latest['_expires'];
        $grace = $this->date($renewal ?: [], 'grace_period_expires_date');
        // Apple's renewal metadata can retain a grace date from a previous cycle.
        if ($grace && $grace->lte($expires)) $grace = null;
        $revoked = ! empty($latest['cancellation_date']) || ! empty($latest['cancellation_date_ms']);
        $trial = filter_var($latest['is_trial_period'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $autoRenews = isset($renewal['auto_renew_status']) ? (string) $renewal['auto_renew_status'] === '1' : null;
        $status = $revoked ? 'revoked' : ($expires->gt(now()) ? ($trial ? 'trial' : 'active') : ($grace && $grace->gt(now()) ? 'grace_period' : 'expired'));

        $environment = $response['environment'] ?? $endpointEnvironment;
        if (! in_array($environment, ['Production', 'Sandbox'], true)) return ['error_code' => 'invalid_response'];
        return [
            'status' => $status, 'environment' => $environment,
            'product_id' => $latest['product_id'], 'expires_at' => $expires,
            'grace_expires_at' => $grace, 'auto_renews' => $autoRenews,
        ];
    }

    private function request($url, $payload)
    {
        $response = Http::withOptions(['connect_timeout' => 5, 'allow_redirects' => false])->timeout(15)->post($url, $payload);
        return $response->successful() ? $response->json() : ['status' => null];
    }

    private function date(array $data, $key)
    {
        try {
            if (isset($data[$key.'_ms']) && is_numeric($data[$key.'_ms'])) {
                return Carbon::createFromTimestampUTC((int) floor($data[$key.'_ms'] / 1000))->setTimezone(config('app.timezone'));
            }
            return ! empty($data[$key]) ? Carbon::parse($data[$key])->setTimezone(config('app.timezone')) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
