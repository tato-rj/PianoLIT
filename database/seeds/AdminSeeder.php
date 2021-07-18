<?php

use Illuminate\Database\Seeder;
use App\Admin;

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
    }
}
