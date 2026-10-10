<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExploreArtworkQueriesTest extends ReviewTestCase
{
    /** @dataProvider artworkCases */
    public function test_artwork_queries_do_not_grow_with_matching_pieces($artwork)
    {
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $periodImages = glob(public_path('images/backgrounds/periods/baroque/*.jpg'));
        $moodCount = count($periodImages) + 2; // Exhaust real period artwork before the last moods.
        [$composer, $tags] = Model::withoutEvents(function () use ($artwork, $moodCount) {
            $tags = collect([create(Tag::class, ['type' => 'level', 'name' => 'elementary'])->id]);
            if ($artwork === 'period') $tags->push(create(Tag::class, ['type' => 'period', 'name' => 'baroque'])->id);
            for ($i = 0; $i < $moodCount; $i++) $tags->push(create(Tag::class, ['type' => 'mood', 'name' => 'Artwork mood '.$i])->id);
            return [create(Composer::class), $tags];
        });

        $queryCounts = [];
        foreach ([20, 80] as $pieceCount) {
            Model::withoutEvents(function () use ($composer, $tags, $pieceCount, $artwork) {
                for ($i = Piece::count(); $i < $pieceCount; $i++) {
                    $piece = create(Piece::class, ['composer_id' => $composer->id,
                        'cover_path' => $artwork === 'cover' ? 'pieces/artwork-'.$i.'.jpg' : null]);
                    $piece->tags()->attach($tags);
                }
            });

            DB::flushQueryLog();
            DB::enableQueryLog();
            try {
                $response = $this->get(route('webapp.explore'))->assertOk();
                $queryCounts[] = count(DB::getQueryLog());
            } finally {
                DB::disableQueryLog();
            }
            $choices = $response->viewData('choices');
            $images = $choices->pluck('image')->filter();
            $this->assertCount($moodCount, $choices);
            $this->assertSame($images->count(), $images->unique()->count());
            $this->assertCount($artwork === 'cover' ? $moodCount : ($artwork === 'period' ? count($periodImages) : 1), $images);
            foreach ($response->viewData('moods') as $key => $mood) $this->assertSame($mood['image'], $choices[$key]['image']);
        }

        $this->assertSame($queryCounts[0], $queryCounts[1], 'Exhausted artwork must not fetch tags separately for each candidate piece.');
    }

    public function artworkCases()
    {
        return [['cover'], ['period'], ['default']];
    }
}
