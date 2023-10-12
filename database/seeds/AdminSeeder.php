<?php

use Illuminate\Database\Seeder;
use App\Admin;
use App\Billing\Plan;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        create(Admin::class, [
            'name' => 'Arthur Villar',
            'email' => 'arthurvillar@gmail.com',
            'role' => 'manager',
            'password' => \Hash::make('maiden')
        ]);

        create(Plan::class, [
            'name' => 'monthly',
            'description' => 'Try it out with the monthly plan. With this option, you can see if PianoLIT is for you and you can cancel anytime.',
            'price' => 999,
            'statement_descriptor' => 'PianoLIT membership',
            'interval' => 'month',
            'trial_period_days' => 7
        ]);

        create(Plan::class, [
            'name' => 'yearly',
            'description' => 'Save big with our most popular plan! This starts with a 7-day free trial so you can explore PianoLIT with zero risk.',
            'price' => 8999,
            'statement_descriptor' => 'PianoLIT membership',
            'interval' => 'year',
            'trial_period_days' => 7
        ]);
    }
}
