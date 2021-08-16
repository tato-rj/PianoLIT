<?php

use Illuminate\Database\Seeder;
use App\{Playlist, Admin};

class PlaylistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Playlist::create([
            'creator_id' => Admin::first()->id,
            'name' => 'Melancholic mood',
            'subtitle' => 'Explore pieces immersed in longing and sorrow',
            "cover_path" => "app/playlists/Vdns3IMevqaQX5ITqV0ZhcmYgi0522ElQJX7f76W.jpg",
            'group' => null,
            'description' => 'This playlist explores sad and sorrowful pieces for piano. Ideal for the pianist who is in solitude, looking forward, or back, to better times.',
            'featured' => null,
            'order' => 0
        ]);
    }
}
