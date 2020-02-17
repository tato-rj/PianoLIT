<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UpdateUsersGender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pianolit:update-gender';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will call the Genderdize api and update the gender of all users.';

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
        if ($this->confirm('This will call the api ' . User::count() . ' times and update all gender records. Are you sure?')) {
            foreach (User::all() as $user) {
                $user->getGender();
            }
            return $this->info('All genders have been updated');
        }
    }
}
