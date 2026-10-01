<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use App\Services\WebApp\PieceRecommendations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PieceRecommendationsTest extends ReviewTestCase
{
    protected $piece, $tags, $composer;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->composer = create(Composer::class, ['name' => 'Clara Schumann']);
            $this->tags = collect([
                'level' => ['elementary', 'level'], 'period' => ['baroque', 'period'],
                'length' => ['short', 'length'], 'happy' => ['happy', 'mood'],
                'dreamy' => ['dreamy', 'mood'], 'sublevel' => ['early beginner', 'sublevel'],
                'otherLevel' => ['advanced', 'level'], 'otherPeriod' => ['romantic', 'period'],
                'decoy' => ['happy', 'technique'],
            ])->map(function ($tag) { return create(Tag::class, ['name' => $tag[0], 'type' => $tag[1]]); });
            $this->piece = $this->makePiece('Current piece', ['happy', 'dreamy', 'sublevel']);
        });
    }

    protected function makePiece($name, array $tags, $level = 'level', $period = 'period')
    {
        return Model::withoutEvents(function () use ($name, $tags, $level, $period) {
            $piece = create(Piece::class, ['name' => $name, 'composer_id' => $this->composer->id, 'description' => 'An expressive melody with a gentle accompaniment.']);
            $piece->tags()->attach($this->tags->only(array_merge([$level, $period, 'length'], $tags))->pluck('id'));
            return $piece;
        });
    }

    public function test_rows_match_the_selected_mood_level_and_period_and_exclude_the_current_piece()
    {
        $happy = $this->makePiece('Happy melody', ['happy'], 'otherLevel', 'otherPeriod');
        $dreamy = $this->makePiece('Dreamy nocturne', ['dreamy'], 'otherLevel', 'otherPeriod');
        $difficulty = $this->makePiece('Elementary waltz', ['decoy'], 'level', 'otherPeriod');
        $period = $this->makePiece('Baroque dance', [], 'otherLevel');
        $more = $this->makePiece('Another happy piece', ['happy']);

        $rows = (new PieceRecommendations)->rows($this->piece)->keyBy('title');
        $this->assertSame(['More like this', 'Similar mood', 'Similar difficulty', 'Same period'], $rows->keys()->all());
        $this->assertSame(['yellow', 'orange', 'red', 'darkpink'], $rows->pluck('color')->all());
        $this->assertSame([$more->id], $rows['More like this']['pieces']->pluck('id')->all());
        $mood = $rows['Similar mood'];
        $this->assertContains($mood['term'], ['happy', 'dreamy']);
        $expectedMood = $mood['term'] === 'happy' ? [$happy->id, $more->id] : [$dreamy->id];
        $this->assertEqualsCanonicalizing($expectedMood, $mood['pieces']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$difficulty->id, $more->id], $rows['Similar difficulty']['pieces']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$period->id, $more->id], $rows['Same period']['pieces']->pluck('id')->all());
        $this->assertSame('elementary', $rows['Similar difficulty']['term']);
        $this->assertSame('baroque', $rows['Same period']['term']);
        foreach ($rows as $row) {
            $this->assertNotContains($this->piece->id, $row['pieces']->pluck('id'));
            foreach ($row['pieces'] as $card) {
                $this->assertArrayHasKey('webapp_has_performances', $card->getAttributes());
                $this->assertArrayHasKey('webapp_has_synthesia', $card->getAttributes());
            }
        }
    }

    public function test_galleries_are_bounded_and_queries_do_not_grow_with_card_count()
    {
        $counts = [];
        $created = 0;
        foreach ([2, 20] as $size) {
            for (; $created < $size; $created++) $this->makePiece('Gallery piece '.$created, ['happy', 'dreamy']);
            DB::enableQueryLog();
            DB::flushQueryLog();
            $rows = (new PieceRecommendations)->rows($this->piece->fresh());
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
            foreach ($rows as $row) $this->assertCount(min($size, 16), $row['pieces']);
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(16, $counts[1]);
    }

    public function test_guest_and_signed_in_pages_render_ordered_gradients_and_working_tag_search_links()
    {
        foreach (['Minuet in G', 'Little prelude', 'Morning song', 'Evening dance'] as $name) {
            $this->makePiece($name, ['happy', 'dreamy']);
        }
        $user = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($user, 'web');
            $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
            $rows = $response->viewData('recommendationRows');
            $html = $response->getContent();
            $previous = -1;
            foreach ($rows as $row) {
                $position = strpos($html, '<h5 class="m-0">'.$row['title'].'</h5>');
                $this->assertGreaterThan($previous, $position);
                $previous = $position;
                $this->assertStringContainsString('linear-gradient(to right, '.implode(', ', gradient($row['color'])).')', $html);
                $response->assertSee('href="'.e($row['url']).'"', false);
                if (! isset($row['term'])) continue;
                $this->get($row['url'])->assertOk()->assertSee('name="search" value="'.$row['term'].'"', false);
                $results = $this->getJson($row['url'])->assertOk();
                $this->assertSame($signedIn ? 5 : 3, substr_count($results->getContent(), 'data-sort-name='));
            }
        }
    }

    public function test_missing_mood_and_empty_matches_do_not_render_empty_galleries()
    {
        $this->piece->tags()->detach($this->tags->only(['happy', 'dreamy'])->pluck('id'));
        $this->assertCount(0, (new PieceRecommendations)->rows($this->piece->fresh()));
        $match = $this->makePiece('Same difficulty only', [], 'level', 'otherPeriod');
        $rows = (new PieceRecommendations)->rows($this->piece->fresh());
        $this->assertSame(['Similar difficulty'], $rows->pluck('title')->all());
        $this->assertSame([$match->id], $rows->first()['pieces']->pluck('id')->all());
    }
}
