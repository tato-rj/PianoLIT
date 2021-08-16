<?php

use Illuminate\Database\Seeder;
use App\{Piece, Admin, Composer};

class PieceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $piece = Piece::create([
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
            "is_free" => true,
            "show_on_tour" => false,
            "is_attributed_to" => false,
            "highlighted_at" => now(),
            "composer_id" => Composer::first()->id,
            "composed_in" => 1892,
            "published_in" => null,
            "creator_id" => Admin::first()->id,
        ]);

        $piece->tutorials()->create([
            'type' => 'Performance',
            'category' => 'performance',
            'description' => 'Watch a video recording of this piece',
            'filename' => 'op61-01',
        ]);

        $piece->tutorials()->create([
            'type' => 'Synthesia',
            'category' => 'synthesia',
            'description' => 'Follow along with an animated video',
            'filename' => 'op61-synthesia',
        ]);

        $piece->tutorials()->create([
            'type' => 'Tutorial',
            'category' => 'tutorial',
            'description' => 'How to play the arpeggiated chords with a consistent and fluid sound',
            'filename' => 'op76-no1-03',
        ]);

        $piece->tutorials()->create([
            'type' => 'Tutorial',
            'category' => 'tutorial',
            'description' => 'Tips on how to practice "2 x 3" rhythms',
            'filename' => 'op76-no1-04',
        ]);

        $piece->tutorials()->create([
            'type' => 'Harmonic analysis',
            'category' => 'harmony',
            'description' => 'A full harmonic analysis one measure at a time',
            'filename' => 'op76-no1-05',
        ]);

        \DB::table('piece_tag')->insert(['tag_id' => 7, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 14, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 19, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 26, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 3, 'piece_id' => $piece->id]);
    }
}
