<?php

namespace App\Console\Commands;

use App\Billing\Stripe\{Gateway, WebhookProcessor};
use Illuminate\Console\Command;

class CreateStripeWebhook extends Command
{
    protected $signature = 'stripe:webhook {--account=us : us or swiss} {--create : Explicitly create a new endpoint}';
    protected $description = 'Show required Stripe webhook events, or explicitly create an endpoint for one billing account';

    public function handle()
    {
        $account = $this->option('account');
        if (!in_array($account, ['us', 'swiss'], true)) { $this->error('Choose --account=us or --account=swiss.'); return 1; }
        if (!$this->option('create')) {
            $this->line('Configure the '.$account.' Stripe endpoint in the Dashboard with API version '.config('services.stripe.membership_version').':');
            foreach (WebhookProcessor::EVENTS as $event) $this->line($event);
            $this->line('Use --create only when a new endpoint is needed. Existing endpoints are not modified.');
            return 0;
        }
        $endpoint = app(Gateway::class)->call($account, 'webhookEndpoints', 'create', [
            'url' => rtrim(config('app.url'), '/').'/webhooks/stripe',
            'api_version' => config('services.stripe.membership_version'), 'enabled_events' => WebhookProcessor::EVENTS,
        ]);
        $this->info('Created '.$endpoint['id'].' for '.$account.'. Set its account-specific signing secret from the Stripe Dashboard.');
        return 0;
    }
}
