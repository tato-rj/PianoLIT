<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class CleanRedisLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:clean-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all logs where the user id does no longer exist.';

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
        $prefix = config('database.redis.prefix');

        if (testing() || $this->confirm('This will delete all records from any user who is no longer in our database. Are you sure?')) {
            $records = \Redis::keys($prefix . 'user:*');

            foreach ($records as $record) {
                $array = explode(':', $record);
                
                foreach ($array as $value) {
                    if (is_numeric($value)) {
                        if (! User::find($value)) {
                            \Redis::del($prefix . 'user:' . $value . ':app');
                            \Redis::del($prefix . 'user:' . $value . ':web');
                            \Redis::del($prefix . 'user:' . $value . ':webapp');
                        }

                        break;
                    }
                }
            }

            return $this->info('All logs have been cleaned.');
        }
    }
}
