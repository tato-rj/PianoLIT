<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Log\Loggers\DailyLog;

class RefreshDailyLogsCount extends Command
{
    protected $signature = 'redis:refresh-daily-logs';
    protected $description = 'Delete and refresh all daily logs.';
    protected $redisPrefix, $keys, $logger;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->redisPrefix = config('database.redis.prefix');
        $this->logs = \Redis::keys($this->redisPrefix . 'user:*');
        $this->logger = new DailyLog;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (testing() || $this->confirm('This will delete and refresh all daily logs. Individual user logs will not be affected. Do you wish to continue?')) {
            
            $this->flushAll();

            foreach ($this->logs as $log) {           
                $this->save($log);       
            }

            $this->info('The daily logs have been refreshed.');
        }
    }

    public function save($log)
    {
        foreach ($this->getRecords($log) as $timestamp => $record) {
            $this->logger->getDate($timestamp)->increment($log);
        }
    }

    public function getRecords($log)
    {
        return array_map('json_decode', \Redis::hgetall($log));
    }

    public function flushAll()
    {
        exec('redis-cli --scan --pattern ' . $this->redisPrefix . $this->logger->namespace() . '* | xargs redis-cli unlink');
    }
}
