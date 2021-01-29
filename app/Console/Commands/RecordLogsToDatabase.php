<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{User, Log};
use App\Log\LogFactory;

class RecordLogsToDatabase extends Command
{
    protected $signature = 'logs:record-to-database';
    protected $description = 'Create or update user logs to the database for quicker analysis.';
    protected $factory, $count;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->count = 0;
        $this->factory = new LogFactory;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        User::chunk(500, function($users) {
            foreach($users as $user) {
                if (! $user->logs_count)
                    continue;

                $this->updateRecords($user);
            }
        });

        return $this->info('The logs of '.$this->count.' out of '. User::count() .' users have been recorded in the database.');
    }

    public function updateRecords($user)
    {
        $record = Log::firstOrCreate(['user_id' => $user->id]);

        $record->update([
            'total' => $user->logs_count,
            'webapp' => $this->factory->count($user->id, 'webapp'),
            'app' => $this->factory->count($user->id, 'app'),
            'web' => $this->factory->count($user->id, 'web')
        ]);

        $this->count += 1;
    }
}
