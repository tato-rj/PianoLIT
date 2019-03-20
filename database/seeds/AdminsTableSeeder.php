<?php

use App\Admin;
use Illuminate\Database\Seeder;

class AdminsTableSeeder extends Seeder
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
