<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Billing\Sources\Concerns\StripeJurisdiction;
use Stripe\Stripe;

class CreateStripeWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new StripeJurisdiction)->us();

        Stripe::setApiKey(config('services.stripe.secret'));
        Stripe::setApiVersion(config('services.stripe.version'));

        \Stripe\WebhookEndpoint::create([
            'url' => 'https://pianolit.com/webhooks/stripe',
            'api_version' => '2020-03-02',
            'enabled_events' => [
                'charge.succeeded',
                'customer.source.created',
                'customer.source.deleted',
                'customer.subscription.deleted',
                'customer.subscription.updated'
            ],
        ]);

        return $this->info('The webhook was created on Stripe.');
    }
}
