<?php

use Illuminate\Database\Seeder;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            "first_name" => "Arthur",
            "last_name" => "Villar",
            "email" => "arthurvillar@gmail.com",
            'password' => bcrypt('maiden'),
            "origin" => "web",
            "locale" => "unknown",
            "super_user" => false,
        ]);
    }
}
