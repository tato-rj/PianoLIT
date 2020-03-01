<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Composer, EmailList};
use App\Mail\Timeline\OnThisDay;
use App\Events\Emails\EmailListSent;

class SendTimelineEmail extends Command
{
    protected $composer;
    protected $signature = 'pianolit:timeline-email';
    protected $description = 'Send daily emails with birthdays along with historical events that happened around that time.';

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
        if (Composer::famous()->bornToday()->exists()) {
            $list = EmailList::birthdays();
            $list->send();
            event(new EmailListSent($list));
        }

        return $this->info('The birthday email has been processed for today.');
    }
}
