<?php

use Illuminate\Database\Seeder;
use App\Country;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Country::create(['name' => 'Italy', 'nationality' => 'Italian', 'flag_code' => 'it']);
        Country::create(['name' => 'Germany', 'nationality' => 'German', 'flag_code' => 'de']);
        Country::create(['name' => 'Russia', 'nationality' => 'Russian', 'flag_code' => 'ru']);
        Country::create(['name' => 'Poland', 'nationality' => 'Polish', 'flag_code' => 'pl']);
        Country::create(['name' => 'France', 'nationality' => 'French', 'flag_code' => 'fr']);
    }
}
