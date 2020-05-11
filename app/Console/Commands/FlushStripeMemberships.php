<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Billing\Sources\Stripe;
use App\Billing\Membership;

class FlushStripeMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'local:flush-stripe';

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

        if ($this->confirm('This will delete all Stripe membership records. Are you sure?')) {
            Stripe::truncate();
            Membership::where('source_type', Stripe::class)->delete();
        }

        return $this->info('All Stripe memberships have been deleted.');
    }
}
