<?php

use Illuminate\Database\Seeder;
use App\Piece;

class PieceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Piece::create([
            "name" => "Arabesque No.1",
            "nickname" => null,
            "catalogue_name" => "Op.",
            "catalogue_number" => "61",
            "collection_name" => null,
            "collection_number" => null,
            "movement_number" => null,
            "key" => "G minor",
            "description" => "This Arabesque is an excellent early-advanced level choice for pianists who are starting to explore advanced repertoire. It has a bold character, folk-like rhythms, and explores many different colors and moods.",
            "curiosity" => null,
            "audio_path" => null,
            "audio_path_rh" => null,
            "audio_path_lh" => null,
            "itunes" => null,
            "videos" => null,
            "score_url" => null,
            "score_path" => "app/score/pianolit-arabesque-no1-in-g-minor-op61-4876.pdf",
            "score_editor" => "William Scharfenberg (1819-1895)",
            "score_publisher" => "New York: G. Schirmer, 1894",
            "score_copyright" => "Public Domain",
            "cover_path" => "piece/cover_image/pianolit-arabesque-no1-op61-0619.jpg",
            "is_free" => false,
            "show_on_tour" => false,
            "is_attributed_to" => false,
            "highlighted_at" => null,
            "composer_id" => 1,
            "composed_in" => 1892,
            "published_in" => null,
            "creator_id" => 1,
        ]);

        \DB::table('piece_tag')->insert(['tag_id' => 7, 'piece_id' => 1]);
        \DB::table('piece_tag')->insert(['tag_id' => 14, 'piece_id' => 1]);
        \DB::table('piece_tag')->insert(['tag_id' => 19, 'piece_id' => 1]);
        \DB::table('piece_tag')->insert(['tag_id' => 26, 'piece_id' => 1]);
        \DB::table('piece_tag')->insert(['tag_id' => 3, 'piece_id' => 1]);
    }
}
