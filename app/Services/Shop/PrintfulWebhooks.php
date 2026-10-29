<?php

namespace App\Services\Shop;

use Illuminate\Http\Request;

class PrintfulWebhooks extends Printful
{
    const EVENTS = ['shipment_sent', 'shipment_delivered'];

    public function keys(): ?array
    {
        $path = config('shop-checkout.printful_webhook_file');
        if (!is_string($path) || !is_readable($path) || filesize($path) > 8192) return null;
        $keys = json_decode(file_get_contents($path), true);
        return is_array($keys) && !empty($keys['public_key']) && is_string($keys['secret_key'] ?? null)
            && preg_match('/^[a-f0-9]{64,128}$/iD', $keys['secret_key']) && strlen($keys['secret_key']) % 2 === 0
            && (string) ($keys['store_id'] ?? '') === $this->storeId() ? $keys : null;
    }

    public function verify(Request $request): void
    {
        abort_unless(app()->environment('production') && $this->enabled(), 503);
        $keys = $this->keys();
        abort_unless($keys, 503);
        $signature = $request->header('x-pf-webhook-signature', '');
        abort_unless(strlen($request->getContent()) <= 262144 && preg_match('/^[a-f0-9]{64}$/iD', $signature)
            && hash_equals($keys['public_key'], $request->header('x-pf-webhook-public-key', ''))
            && hash_equals(hash_hmac('sha256', $request->getContent(), hex2bin($keys['secret_key'])), strtolower($signature)), 400);
    }

    public function configuration(): ?array
    {
        $this->production();
        $response = $this->request('GET', 'v2/webhooks', [], 15, true);
        return $response['result'] ?? null;
    }

    public function configure(): void
    {
        $this->production();
        $website = rtrim(config('app.url'), '/');
        if (!preg_match('#^https?://#i', $website)) $website = 'https://'.ltrim($website, '/');
        $url = $website.'/webhooks/printful';
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') throw new CheckoutException('Printful webhooks require a valid public HTTPS URL.');
        $existing = $this->configuration();
        if ($existing) {
            $types = array_column($existing['events'] ?? [], 'type');
            if (($existing['default_url'] ?? null) !== $url || array_diff($types, self::EVENTS)
                || collect($existing['events'] ?? [])->contains(fn ($event) => !empty($event['url']) && $event['url'] !== $url)) {
                throw new CheckoutException('A different webhook configuration already exists. Review it before replacing any settings.');
            }
            if (!array_diff(self::EVENTS, $types) && $this->keys() && hash_equals($this->keys()['public_key'], $existing['public_key'] ?? '')) return;
        }
        // Establish that we can save the one-time secret before rotating provider keys.
        $path = config('shop-checkout.printful_webhook_file');
        if (!is_string($path) || !is_dir(dirname($path)) || !is_writable(dirname($path))) throw new CheckoutException('Prepare a writable private webhook-key directory first.');
        $temporary = tempnam(dirname($path), '.printful-webhook-');
        if (!$temporary) throw new CheckoutException('Private webhook keys could not be staged.');
        chmod($temporary, 0600);
        try {
            $result = $this->request('POST', 'v2/webhooks', ['default_url' => $url, 'events' => array_map(fn ($type) => ['type' => $type], self::EVENTS)])['result'];
            if (empty($result['public_key']) || !preg_match('/^[a-f0-9]{64,128}$/iD', $result['secret_key'] ?? '')) throw new CheckoutException('Printful did not return signing keys.');
            if (file_put_contents($temporary, json_encode(['public_key' => $result['public_key'], 'secret_key' => $result['secret_key'], 'store_id' => $this->storeId()], JSON_THROW_ON_ERROR), LOCK_EX) === false
                || !rename($temporary, $path)) throw new CheckoutException('Printful signing keys could not be installed. Review the configuration before retrying.');
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    private function production(): void
    {
        if (!app()->environment('production') || !$this->enabled()) throw new CheckoutException('Live Printful webhook configuration is disabled in this environment.');
    }
}
