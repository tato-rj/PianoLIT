<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, Tutorial, User};
use App\Services\WebApp\{PieceRecommendations, SimilarPieces};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SimilarPiecesTest extends ReviewTestCase
{
    private $tags, $composer, $matcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $this->matcher = new SimilarPieces;
        Model::withoutEvents(function () {
            $this->composer = create(Composer::class);
            // Deliberately interleave levels with unrelated IDs, as a real
            // catalogue can after adding/replacing tags over time.
            $this->tags = collect([
                'advanced' => ['advanced', 'level'], 'romantic' => ['romantic', 'period'],
                'gloomy' => ['gloomy', 'mood'], 'beginner' => ['beginner', 'level'],
                'slow' => ['slow', 'mood'], 'baroque' => ['baroque', 'period'],
                'melancholic' => ['melancholic', 'mood'], 'elementary' => ['elementary', 'level'],
                'character' => ['character piece', 'genre'], 'sad' => ['sad', 'mood'],
                'early' => ['early beginner', 'sublevel'], 'abrsm1' => ['ABRSM 1', 'ranking'],
                'rcm1' => ['RCM 1', 'ranking'], 'suzuki1' => ['Suzuki Book 1', 'ranking'],
                'suzuki2' => ['Suzuki Book 2', 'ranking'], 'halloween' => ['halloween', 'season'],
                'serious' => ['serious', 'mood'], 'short' => ['short', 'length'],
                'late' => ['late beginner', 'level'], 'staccato' => ['staccato', 'technique'],
                'intermediate' => ['intermediate', 'level'], 'impressionist' => ['impressionist', 'period'],
            ])->map(function ($tag) { return create(Tag::class, ['name' => $tag[0], 'type' => $tag[1]]); });
        });
    }

    private function piece(string $name, array $tags): Piece
    {
        return Model::withoutEvents(function () use ($name, $tags) {
            $piece = create(Piece::class, ['name' => $name, 'composer_id' => $this->composer->id]);
            $piece->tags()->attach($this->tags->only($tags)->pluck('id'));
            return $piece;
        });
    }

    public function test_screenshot_traits_match_both_directions_across_periods_and_rank_ahead_of_weak_matches()
    {
        // Live pages: /pieces/1178 (Levitin) and /pieces/875 (Bonis),
        // plus the genre/mood/grading/season tags in the user's screenshot.
        $left = $this->piece('A Sad Song by Yuri Levitin', ['beginner', 'early', 'impressionist', 'character', 'gloomy', 'melancholic', 'sad', 'slow', 'abrsm1', 'rcm1', 'suzuki2', 'halloween']);
        for ($i = 0; $i < 20; $i++) $this->piece('Earlier weak match '.$i, ['beginner', 'early', 'impressionist', 'gloomy']);
        $right = $this->piece('The Little Beggar by Mélanie Bonis', ['elementary', 'romantic', 'character', 'gloomy', 'melancholic', 'sad', 'serious', 'abrsm1', 'rcm1', 'suzuki1']);

        $this->assertNotContains($right->id, $left->similar()->pluck('id'), 'The old period cutoff misses this strong match');
        $this->assertSame($right->id, $this->matcher->forPiece($left, 16)->first()->id);
        $this->assertSame($left->id, $this->matcher->forPiece($right, 16)->first()->id);
        $this->assertCount(21, $this->matcher->forPiece($left), 'View all retains matches beyond the card limit');
        $rows = (new PieceRecommendations)->rows($left)->keyBy('title');
        $this->assertSame($right->id, $rows['More like this']['pieces']->first()->id);
        $this->assertCount(16, $rows['More like this']['pieces']);
        $feed = (new \App\Api\Api)->for('webapp')->order(9)->similar("Like today's free pick", $left);
        $this->assertSame($right->id, $feed['content']->first()->id);
        $this->assertSame(route('webapp.pieces.similar', $left), $feed['url']);
    }

    public function test_difficulty_uses_names_accepts_split_tags_and_keeps_distant_repertoire_out()
    {
        $source = $this->piece('Elementary source', ['elementary', 'gloomy', 'romantic']);
        $neighbor = $this->piece('Beginner neighbor', ['beginner', 'gloomy', 'romantic']);
        $split = Model::withoutEvents(function () {
            $tag = create(Tag::class, ['name' => 'early beginner', 'type' => 'level']);
            $piece = $this->piece('Split early beginner', ['gloomy', 'romantic']);
            $piece->tags()->attach($tag);
            return $piece;
        });
        $distant = $this->piece('Advanced decoy', ['advanced', 'gloomy', 'romantic', 'character']);
        $this->assertNotContains($neighbor->id, $source->similar()->pluck('id'), 'Tag IDs are not a difficulty scale');
        $this->assertSame([$split->id, $neighbor->id], $this->matcher->forPiece($source)->pluck('id')->all());
        $this->assertNotContains($distant->id, $this->matcher->forPiece($source)->pluck('id'));
        $this->assertContains($source->id, $this->matcher->forPiece($neighbor)->pluck('id'));
        $this->assertContains($source->id, $this->matcher->forPiece($split)->pluck('id'));
    }

    public function test_sublevel_precedes_base_level_and_equivalent_tag_representations_rank_together()
    {
        $source = $this->piece('Early source', ['beginner', 'early', 'gloomy']);
        $same = $this->piece('Early match', ['beginner', 'early', 'gloomy']);
        $late = $this->piece('Late match', ['late', 'gloomy']);
        $base = $this->piece('Base match', ['beginner', 'gloomy']);
        $this->assertSame([$same->id, $base->id, $late->id], $this->matcher->forPiece($source)->pluck('id')->all());
        $this->assertContains($source->id, $this->matcher->forPiece($same)->pluck('id'));
    }

    public function test_genre_and_technique_can_supply_missing_mood_but_grades_period_and_season_alone_cannot()
    {
        $source = $this->piece('No moods', ['beginner', 'character', 'staccato', 'romantic', 'abrsm1', 'halloween']);
        $genre = $this->piece('Shared genre', ['beginner', 'character']);
        $technique = $this->piece('Shared technique', ['beginner', 'staccato']);
        $unrelated = $this->piece('Shared context only', ['beginner', 'romantic', 'abrsm1', 'halloween']);
        $this->assertEqualsCanonicalizing([$genre->id, $technique->id], $this->matcher->forPiece($source)->pluck('id')->all());
        $this->assertNotContains($unrelated->id, $this->matcher->forPiece($source)->pluck('id'));
        $this->assertCount(0, $this->matcher->forPiece($unrelated));
    }

    public function test_missing_period_is_allowed_and_missing_difficulty_requires_shared_grading_evidence()
    {
        $source = $this->piece('Graded source', ['beginner', 'gloomy', 'abrsm1']);
        $noPeriod = $this->piece('No period', ['beginner', 'gloomy']);
        $graded = $this->piece('No level but shared grade', ['gloomy', 'abrsm1']);
        $unknown = $this->piece('No difficulty evidence', ['gloomy']);
        $conflicting = $this->piece('Distant level despite same grade', ['advanced', 'gloomy', 'abrsm1']);
        $this->assertEqualsCanonicalizing([$noPeriod->id, $graded->id], $this->matcher->forPiece($source)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$source->id, $conflicting->id], $this->matcher->forPiece($graded)->pluck('id')->all());
        $this->assertCount(0, $this->matcher->forPiece($unknown));
        $this->assertNotContains($conflicting->id, $this->matcher->forPiece($source)->pluck('id'));
        $empty = $this->piece('Empty metadata', []);
        $this->assertCount(0, $this->matcher->forPiece($empty));
    }

    public function test_normalized_overlap_prefers_a_focused_match_over_an_entry_with_many_extra_tags()
    {
        $source = $this->piece('Source', ['beginner', 'gloomy', 'sad']);
        $broad = $this->piece('Overtagged', ['beginner', 'gloomy', 'sad', 'serious', 'melancholic', 'slow', 'character', 'staccato', 'halloween']);
        $focused = $this->piece('Focused', ['beginner', 'gloomy', 'sad']);
        $tie = $this->piece('Equal focused match', ['beginner', 'gloomy', 'sad']);
        $expected = [$focused->id, $tie->id, $broad->id];
        $this->assertSame($expected, $this->matcher->forPiece($source)->pluck('id')->all());
        $this->assertSame($expected, $this->matcher->forPiece($source)->pluck('id')->all());
        $this->assertSame([$focused->id], $this->matcher->forPiece($source, 1)->pluck('id')->all());
        $this->assertCount(0, $this->matcher->forPiece($source, 0));
    }

    public function test_fresh_requests_reflect_tag_changes_without_a_stored_pair_list()
    {
        $source = $this->piece('Source', ['beginner', 'gloomy', 'romantic']);
        $other = $this->piece('Changing match', ['beginner', 'gloomy', 'baroque']);
        $this->assertSame([$other->id], $this->matcher->forPiece($source->fresh())->pluck('id')->all());
        $other->tags()->detach($this->tags['gloomy']);
        $this->assertCount(0, $this->matcher->forPiece($source->fresh()));
        $other->tags()->attach($this->tags['gloomy']);
        $this->assertSame([$other->id], $this->matcher->forPiece($source->fresh())->pluck('id')->all());
    }

    public function test_candidates_are_reciprocal_unique_and_never_include_the_source()
    {
        $pieces = collect([
            $this->piece('Elementary graded', ['elementary', 'gloomy', 'abrsm1']),
            $this->piece('Beginner graded', ['beginner', 'gloomy', 'abrsm1', 'romantic']),
            $this->piece('Late beginner', ['late', 'gloomy', 'sad']),
            $this->piece('Intermediate', ['intermediate', 'gloomy', 'baroque']),
            $this->piece('Advanced', ['advanced', 'gloomy', 'sad']),
            $this->piece('Unleveled graded', ['gloomy', 'abrsm1']),
            $this->piece('No evidence', ['gloomy']),
        ]);
        $matches = $pieces->mapWithKeys(function ($piece) {
            return [$piece->id => $this->matcher->forPiece($piece)->pluck('id')->all()];
        });
        foreach ($matches as $id => $ids) {
            $this->assertNotContains($id, $ids);
            $this->assertSame(array_values(array_unique($ids)), $ids);
            foreach ($ids as $other) $this->assertContains($id, $matches[$other]);
        }
    }

    public function test_full_web_list_retains_ranked_links_video_badges_and_source_options_for_guests_and_accounts()
    {
        $source = $this->piece('Viewed source', ['beginner', 'gloomy', 'sad', 'romantic']);
        $weak = $this->piece('Weaker same period', ['beginner', 'gloomy', 'romantic']);
        $best = $this->piece('Strong match without period', ['beginner', 'gloomy', 'sad']);
        Model::withoutEvents(function () use ($best) { create(Tutorial::class, ['piece_id' => $best->id]); });
        $user = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($user, 'web');
            $response = $this->get(route('webapp.pieces.similar', $source))->assertOk();
            $this->assertSame([$best->id, $weak->id], $response->viewData('similar')->pluck('id')->all());
            $response->assertSee('title="Video"', false);
            $this->assertStringContainsString('href="'.route('webapp.pieces.show', $best).'"', $response->getContent());
            $html = $response->getContent();
            $panel = substr($html, strpos($html, 'id="options-panel"'));
            $this->assertStringContainsString('href="'.route('webapp.pieces.similar', $source).'"', $panel);
            $this->assertStringNotContainsString('href="'.route('webapp.pieces.similar', $weak).'"', $panel);
        }
    }

    public function test_query_count_is_constant_and_only_the_limited_cards_are_hydrated_on_a_larger_catalogue()
    {
        $source = $this->piece('Source', ['beginner', 'gloomy', 'romantic']);
        $source->load('tags');
        $counts = [];
        $report = [];
        $hydrated = [];
        // retrieved events measure actual models, not just the returned slice.
        Piece::retrieved(function ($piece) use (&$hydrated) { $hydrated[] = $piece->id; });
        $created = 0;
        foreach ([12, 612] as $size) {
            $rows = [];
            $pivots = [];
            for (; $created < $size; $created++) {
                $id = 1000 + $created;
                $rows[] = ['id' => $id, 'name' => 'Candidate '.$created, 'key' => 'C major', 'composer_id' => $this->composer->id, 'created_at' => now(), 'updated_at' => now()];
                foreach ($this->tags->only(['beginner', 'gloomy', 'romantic'])->pluck('id') as $tagId) {
                    $pivots[] = ['piece_id' => $id, 'tag_id' => $tagId];
                }
            }
            foreach (array_chunk($rows, 100) as $chunk) DB::table('pieces')->insert($chunk);
            foreach (array_chunk($pivots, 100) as $chunk) DB::table('piece_tag')->insert($chunk);
            $hydrated = [];
            DB::enableQueryLog();
            DB::flushQueryLog();
            $started = hrtime(true);
            $matches = $this->matcher->forPiece($source, 16);
            $elapsed = (hrtime(true) - $started) / 1000000;
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            $counts[] = count($queries);
            $this->assertCount(min(16, $size), $matches);
            $this->assertSame($matches->pluck('id')->all(), $hydrated);
            $this->assertStringContainsString('limit 16', strtolower($queries[0]['query']));
            $this->assertStringNotContainsString('favorites_count', $queries[0]['query']);
            $this->assertStringNotContainsString('tags_count', $queries[0]['query']);
            $this->assertArrayNotHasKey('shared_weight', $matches->first()->getAttributes());
            $this->assertArrayHasKey('views_count', $matches->first()->getAttributes());
            $this->assertArrayHasKey('tutorials_count', $matches->first()->getAttributes());
            if (getenv('SIMILAR_PIECES_REPORT')) {
                $report[$size]['ranked'] = ['queries' => count($queries), 'piece_models' => count($hydrated), 'elapsed_ms' => $elapsed];
                $hydrated = [];
                DB::enableQueryLog();
                DB::flushQueryLog();
                $started = hrtime(true);
                $source->similar()->where('id', '!=', $source->id)->take(16);
                $report[$size]['legacy'] = ['queries' => count(DB::getQueryLog()), 'piece_models' => count($hydrated), 'elapsed_ms' => (hrtime(true) - $started) / 1000000];
                DB::disableQueryLog();
            }
        }
        $this->assertSame([3, 3], $counts);
        if (getenv('SIMILAR_PIECES_REPORT')) file_put_contents(getenv('SIMILAR_PIECES_REPORT'), json_encode($report, JSON_PRETTY_PRINT));
    }

    public function test_legacy_mobile_matcher_keeps_its_period_filter_and_payload()
    {
        $source = $this->piece('Mobile source', ['beginner', 'gloomy', 'romantic']);
        $samePeriod = $this->piece('Legacy match', ['beginner', 'gloomy', 'romantic']);
        $crossPeriod = $this->piece('Web match', ['beginner', 'gloomy', 'baroque']);
        $expected = $source->similar()->pluck('id')->all();
        $request = \Illuminate\Http\Request::create('/api/pieces/'.$source->id.'/similar', 'GET');
        $mobile = (new \App\Http\Controllers\Api\PiecesController)->similar($request, $source->id);
        $this->assertSame($expected, $mobile->pluck('id')->all());
        $this->assertContains($samePeriod->id, $expected);
        $this->assertNotContains($crossPeriod->id, $expected);
        $this->assertContains($crossPeriod->id, $this->matcher->forPiece($source)->pluck('id'));
        foreach ($mobile as $piece) {
            $this->assertArrayNotHasKey('shared_weight', $piece->getAttributes());
            $this->assertArrayNotHasKey('webapp_has_video', $piece->getAttributes());
            $this->assertTrue($piece->hasAppended('media'));
            $this->assertTrue($piece->relationLoaded('composer'));
        }
    }
}
