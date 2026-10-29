<?php

namespace App\Console\Commands;

use App\Services\Shop\PrintfulWebhooks;
use Illuminate\Console\Command;

class ConfigurePrintfulWebhooks extends Command
{
    protected $signature = 'printful:webhooks {--configure : Register shipment events and privately save their signing keys}';
    protected $description = 'Inspect Printful shipment webhooks, or explicitly configure them in production';

    public function handle(PrintfulWebhooks $webhooks)
    {
        if (!app()->environment('production')) { $this->warn('Local/test Printful connections remain disabled.'); return 1; }
        try {
            if ($this->option('configure')) $webhooks->configure();
            $configuration = $webhooks->configuration();
            $types = array_column($configuration['events'] ?? [], 'type');
            $keys = $webhooks->keys();
            $this->info('Shipment events configured: '.(!array_diff(PrintfulWebhooks::EVENTS, $types) ? 'yes' : 'no'));
            $this->info('Matching private signing keys installed: '.($keys && hash_equals($keys['public_key'], $configuration['public_key'] ?? '') ? 'yes' : 'no'));
            return 0;
        } catch (\Throwable $exception) { $this->error('Printful webhook setup could not be verified. Check store permissions, HTTPS and the private key file.'); return 1; }
    }
}
