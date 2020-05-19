<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateStripePlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:create-plans';

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
     * @return mixed
     */
    public function handle()
    {
        $this->createPlans();
    }

    public function createPlans()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            foreach (\App\Billing\Plan::all() as $plan) {
                \Stripe\Plan::create([
                  'id' => $plan->name,
                  'amount' => $plan->price,
                  'currency' => 'usd',
                  'interval' => $plan->interval,
                  'trial_period_days' => $plan->trial_period_days,
                  'nickname' => $plan->long_name,
                  'product' => [
                        'name' => $plan->long_name,
                        'statement_descriptor' => $plan->statement_descriptor,
                    ],
                ]);
            }   
        } catch (\Exception $e) {
            return $this->info($e->getMessage());
        }

        return $this->info('New plans have been created on Stripe.');
    }
}
