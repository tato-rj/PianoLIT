<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class SendConfirmationEmailRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pianolit:unconfirmed-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly reminders to unconfirmed users to confirm their emails.';

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
        foreach (User::unconfirmed()->get() as $user) {
            $user->sendEmailVerificationNotification();
        }

        return $this->info('The confirmation emails have been sent.');
    }
}
