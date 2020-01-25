<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{EmailList, Subscription};

class TransferEmailLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:transfer-lists';

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
        $newsletter = EmailList::byName('newsletter');
        $birthdays = EmailList::byName('birthdays');
        $freepick = EmailList::byName('free pick');
        $tutorials = EmailList::byName('latest tutorials');

        foreach (Subscription::all() as $subscriber) {
            if ($subscriber->newsletter_list == 1) {
                $newsletter->add($subscriber);
                $freepick->add($subscriber);
                $tutorials->add($subscriber);
            }

            if ($subscriber->birthday_list == 1) {
                $birthdays->add($subscriber);
            }
        }

        $this->info('All emails were successfully transfered.');
    }
}
