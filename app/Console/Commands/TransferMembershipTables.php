<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TransferMembershipTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:transfer-memberships';

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
        $records = \App\Payments\Sources\Apple::all();

        foreach ($records as $record) {
            \App\Payments\Membership::create([
                'user_id' => $record->user_id,
                'source_id' => $record->id,
                'source_type' => get_class($record)
            ]);
        }

        $this->info('All memberships were successfully transfered.');
    }
}
