<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Billing\Sources\Stripe;
use App\Billing\{Membership, Plan, Payment};

class RefreshStripe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:refresh';

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
        if (! local())
            return $this->info('You can only do this on local environment.');

        if ($this->confirm('This will delete all Stripe records in our database. Are you sure?')) {
            $this->flushRecords();
            $this->flushPayments();
            $this->createPlans();
            $this->createCoupon();
        }

    }

    public function flushRecords()
    {
        Stripe::truncate();
        
        Membership::where('source_type', Stripe::class)->delete();

        return $this->info('All Stripe memberships have been deleted.');
    }

    public function flushPayments()
    {
        Payment::truncate();

        return $this->info('All payment records have been deleted.');
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

    public function createCoupon()
    {
        try {
            \Stripe\Coupon::create([
                'id' => 'TEST-COUPON',
                'name' => '50% OFF',
                'percent_off' => 50,
                'duration' => 'repeating',
                'duration_in_months' => 3,
            ]);   
        } catch (\Exception $e) {
            return $this->info($e->getMessage());            
        }

        return $this->info('A test coupon has been created on Stripe.');
    }
}
