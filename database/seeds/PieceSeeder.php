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
        $this->arabesque();
        $this->adagio();
        $this->rondo();
        $this->randomPiece(10);
    }

    public function randomPiece($count)
    {
        for ($i=0; $i < $count; $i++) { 
            $piece = create(Piece::class, [
                'composer_id' => Composer::inRandomOrder()->first()->id,
                'creator_id' => Admin::first()->id
            ]);

            \DB::table('piece_tag')->insert(['tag_id' => 8, 'piece_id' => $piece->id]);
            \DB::table('piece_tag')->insert(['tag_id' => 14, 'piece_id' => $piece->id]);
            \DB::table('piece_tag')->insert(['tag_id' => 19, 'piece_id' => $piece->id]);
            \DB::table('piece_tag')->insert(['tag_id' => 26, 'piece_id' => $piece->id]);
            \DB::table('piece_tag')->insert(['tag_id' => 5, 'piece_id' => $piece->id]);
        }
    }

    public function arabesque()
    {
        $piece = Piece::create([
            "name" => "Arabesque No.1",
            "nickname" => null,
            "catalogue_name" => null,
            "catalogue_number" => null,
            "collection_name" => null,
            "collection_number" => null,
            "movement_number" => null,
            "key" => "C# minor",
            "description" => "This adagio is a hidden gem by Weber that packs a lot of expression in just a few measures of music. The dotted rhythm creates a solemn and grand character and can be played very explicitly. The dramatic character comes from the sudden dynamic and harmonic contrasts.",
            "curiosity" => null,
            "audio_path" => 'app/audio/NYOLOjThMRjkqDIC073EJAKkLeyA98Dvl9ZY8HFj.mp3',
            "audio_path_rh" => null,
            "audio_path_lh" => null,
            "itunes" => null,
            "videos" => null,
            "score_url" => null,
            "score_path" => "app/score/pianolit-adagio-patetico-in-c-minor-1083.pdf",
            "score_editor" => "William Scharfenberg (1819-1895)",
            "score_publisher" => "New York: G. Schirmer, 1894",
            "score_copyright" => "Public Domain",
            "cover_path" => "piece/cover_image/pianolit-adagio-patetico-in-c-minor-1941.jpg",
            "is_free" => false,
            "show_on_tour" => true,
            "is_attributed_to" => false,
            "highlighted_at" => now()->subWeek(),
            "composer_id" => Composer::first()->id,
            "composed_in" => 1892,
            "published_in" => null,
            "creator_id" => Admin::first()->id,
            'created_at' => now()->subMonth()
        ]);

        $piece->tutorials()->create([
            'type' => 'Performance',
            'category' => 'performance',
            'description' => 'Watch a video recording of this piece',
            'filename' => 'adagiopatetico-01',
        ]);

        $piece->tutorials()->create([
            'type' => 'Synthesia',
            'category' => 'synthesia',
            'description' => 'Follow along with an animated video',
            'filename' => 'adagiopatetico-synthesia',
        ]);

        \DB::table('piece_tag')->insert(['tag_id' => 8, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 14, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 19, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 26, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 5, 'piece_id' => $piece->id]);
    }

    public function adagio()
    {
        $piece = Piece::create([
            "name" => "Adagio Patetico",
            "nickname" => null,
            "catalogue_name" => "Op.",
            "catalogue_number" => "61",
            "collection_name" => null,
            "collection_number" => null,
            "movement_number" => null,
            "key" => "G minor",
            "description" => "This Arabesque is an excellent early-advanced level choice for pianists who are starting to explore advanced repertoire. It has a bold character, folk-like rhythms, and explores many different colors and moods.",
            "curiosity" => null,
            "audio_path" => 'app/audio/D8OOk5etrnyHtaseaOR2GYRAoq2SkFgVNdKSOFXk.mp3',
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

    public function rondo()
    {
        $piece = Piece::create([
            "name" => "Rondo",
            "nickname" => null,
            "catalogue_name" => "K",
            "catalogue_number" => "485",
            "collection_name" => null,
            "collection_number" => null,
            "movement_number" => null,
            "key" => "D major",
            "description" => "Rondos usually repeat the main theme in between episodes, but this piece does everything but that. In fact, Mozart rarely \"played by the book\" when it comes to musical forms and conventions. \r\n\r\nIn this delightful rondo, Mozart seems to be merging a sonata-form structure with the rondo, where the main theme constantly changes keys, always adapting and changing as the music develops.",
            "curiosity" => null,
            "audio_path" => 'app/audio/5FOoRVZpE3ReDfpfR0d3IkY6pRW467vZ7WboHk6c.mp3',
            "audio_path_rh" => null,
            "audio_path_lh" => null,
            "itunes" => null,
            "videos" => null,
            "score_url" => null,
            "score_path" => "app/score/pianolit-rondo-in-d-major-k-485-5692.pdf",
            "score_editor" => "William Scharfenberg (1819-1895)",
            "score_publisher" => "New York: G. Schirmer, 1894",
            "score_copyright" => "Public Domain",
            "cover_path" => "piece/cover_image/pianolit-rondo-k-485-4510.jpg",
            "is_free" => false,
            "show_on_tour" => false,
            "is_attributed_to" => false,
            "highlighted_at" => now()->subWeeks(2),
            "composer_id" => Composer::first()->id,
            "composed_in" => 1892,
            "published_in" => null,
            "creator_id" => Admin::first()->id,
            "created_at" => now()->subMonths(3),
        ]);

        $piece->tutorials()->create([
            'type' => 'Performance',
            'category' => 'performance',
            'description' => 'Watch a video recording of this piece',
            'filename' => 'k485-01',
        ]);

        $piece->tutorials()->create([
            'type' => 'Synthesia',
            'category' => 'synthesia',
            'description' => 'Follow along with an animated video',
            'filename' => 'k485-synthesia',
        ]);

        \DB::table('piece_tag')->insert(['tag_id' => 6, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 13, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 19, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 26, 'piece_id' => $piece->id]);
        \DB::table('piece_tag')->insert(['tag_id' => 11, 'piece_id' => $piece->id]);
    }
}
