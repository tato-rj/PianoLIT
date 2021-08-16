<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AdminSeeder::class,
            UserSeeder::class,
            CountrySeeder::class,
            TagSeeder::class,
            ComposerSeeder::class,
            PieceSeeder::class,
            PlaylistSeeder::class
        ]);
    }
}
