<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetGenders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:getgenders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the gender for all current users';

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
        $this->info(\Pixelpeter\Genderize\Facades\Genderize::name('John')->get()->result[0]->gender);
        // foreach (\App\User::all() as $user) {
        //     $user->update(['gender' => gender($user->first_name)]);
        //     $this->info($user->first_name . ' is ' . $user->gender . '!');
        // }

    }
}
