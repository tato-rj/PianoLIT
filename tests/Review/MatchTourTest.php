<?php

namespace Tests\Review;

use App\{Piece, Tag};
use App\Resources\FindYourMatch\Quiz;
use App\Services\WebApp\MatchTour;
use Illuminate\Database\Eloquent\Model;

class MatchTourTest extends ReviewTestCase
{
    private $pieces;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $this->pieces = Model::withoutEvents(function () {
            $levels = collect(['elementary', 'beginner', 'intermediate', 'advanced'])->mapWithKeys(function ($name) {
                return [$name => create(Tag::class, ['name' => $name, 'type' => 'level'])];
            });
            $moods = collect(['calm', 'flashy', 'dreamy'])->map(function ($name) { return create(Tag::class, ['name' => $name, 'type' => 'mood']); });
            $period = create(Tag::class, ['name' => 'romantic', 'type' => 'period']);
            return collect(range(0, 11))->map(function ($i) use ($levels, $moods, $period) {
                $piece = create(Piece::class, ['name' => 'Tour piece '.$i, 'audio_path' => 'tour.mp3', 'score_path' => 'excerpt.pdf', 'score_url' => null, 'show_on_tour' => true, 'highlighted_at' => now()]);
                $piece->tags()->attach([$levels->values()[$i % 4]->id, $moods[$i % 3]->id, $period->id]);
                return $piece;
            });
        });
    }

    public function test_large_catalog_has_bounded_queries_and_preserves_choices()
    {
        $tour = new MatchTour;
        \DB::enableQueryLog();
        $before = $tour->data();
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        Model::withoutEvents(function () {
            for ($i = 0; $i < 600; $i++) {
                $source = $this->pieces[$i % 12];
                $copy = $source->replicate();
                $copy->save();
                $copy->tags()->attach($source->tags()->pluck('tags.id')->all());
            }
        });
        \DB::enableQueryLog();
        \DB::flushQueryLog();
        $data = $tour->data();
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();
        $this->assertTrue($data['ready']);
        $this->assertSame(612, $data['total']);
        $this->assertSame($before['pieces'], $data['pieces']);
        $this->assertSame($before['scores'], $data['scores']);
        $this->assertSame($queryCount, count($queries));
        $this->assertLessThanOrEqual(10, count($queries));
        foreach ($queries as $query) $this->assertStringNotContainsString('pieces_count', $query['query']);
    }

    public function test_contrast_and_editorial_order_remain_stable()
    {
        Model::withoutEvents(function () {
            $genre = create(Tag::class, ['name' => 'dance', 'type' => 'genre']);
            foreach ($this->pieces as $i => $piece) {
                $piece->update(['composer_id' => $this->pieces[intdiv($i, 3)]->composer_id, 'show_on_tour' => in_array($i, [2, 5, 9])]);
                if ($i % 2 === 0) $piece->tags()->attach($genre);
            }
        });
        $data = (new MatchTour)->data();
        // Existing choices for this fixture, including editorial boosts and ties.
        $expected = array_map(function ($i) { return $this->pieces[$i]->id; }, [2, 9, 1, 5, 0, 4, 3, 6, 7, 8]);
        $this->assertSame($expected, array_column($data['pieces'], 'id'));
    }

    private function answers()
    {
        $data = (new MatchTour)->data();
        $ids = array_column($data['pieces'], 'id');
        return ['preferredPiece' => $ids[0], 'reading' => [true, false], 'estimatedLevel' => 'elementary', 'winners' => [$ids[4], $ids[6], $ids[8]], 'intent' => 'personal'];
    }

    public function test_catalog_and_web_only_flow_are_deterministic_and_guest_safe()
    {
        $tour = new MatchTour;
        $data = $tour->data();
        $this->assertTrue($data['ready']);
        $this->assertSame(12, $data['total']);
        $this->assertSame($data, $tour->data());
        $this->assertCount(10, array_unique(array_column($data['pieces'], 'id')));
        $this->assertCount(3, array_unique(array_column($data['scores'], 'id')));
        $this->get(route('webapp.tour'))->assertOk()->assertSee('Let’s narrow down')
            ->assertSee('modal-fullscreen', false)->assertSee('Close Find your match')
            ->assertSee('id="menu"', false)
            ->assertDontSee('QUESTION')->assertDontSee('id="find-match-carousel"', false)
            ->assertDontSee('build/pdf.min.js', false)
            ->assertDontSee('cdn.plyr.io', false);
        $response = $this->getJson(route('webapp.tour'))->assertOk()->assertJsonPath('tour.ready', true);
        $this->assertSame($tour->data(), $response->json('tour'));
        $this->assertStringContainsString('data-count', $response->json('html'));
        $this->assertStringNotContainsString('<script', $response->json('html'));
    }

    public function test_direct_page_opens_a_shell_without_preparing_the_catalog()
    {
        $tour = \Mockery::mock(MatchTour::class);
        $tour->shouldNotReceive('data');
        $this->app->instance(MatchTour::class, $tour);
        $this->get(route('webapp.tour'))->assertOk()->assertSee('MatchTour.Launcher', false)
            ->assertSee('data-match-tour-open', false)->assertDontSee('data-count', false);
    }

    public function test_discover_launches_the_fullscreen_tour_without_the_old_carousel()
    {
        $html = view('webapp.discover.index', ['rows' => collect(), 'composers' => collect(), 'hasFullAccess' => true, 'errors' => new \Illuminate\Support\ViewErrorBag])->render();
        $this->assertStringContainsString('data-match-tour-open', $html);
        $this->assertStringContainsString('modal-fullscreen', $html);
        $this->assertStringContainsString('Close Find your match', $html);
        $this->assertStringNotContainsString('find-match-carousel', $html);
        $this->assertStringNotContainsString('resetTour()', $html);
    }

    public function test_all_reading_branches_and_intents_adapt_to_legacy_keywords()
    {
        $tour = new MatchTour;
        foreach ([[false, false, 'elementary'], [false, true, 'beginner'], [true, false, 'intermediate'], [true, true, 'advanced']] as [$first, $second, $level]) {
            $answers = $this->answers(); $answers['reading'] = [$first, $second];
            $keywords = $tour->keywords($answers);
            $this->assertSame($level, $keywords[4]);
            $this->assertSame(array_merge([$answers['preferredPiece']], $answers['winners']), array_slice($keywords, 0, 4));
        }
        foreach (['quick' => 'short', 'work' => 'long', 'personal' => 'calm', 'impressive' => 'flashy'] as $intent => $tag) {
            $answers = $this->answers(); $answers['intent'] = $intent;
            $this->assertContains($tag, $tour->keywords($answers));
        }
        Model::withoutEvents(function () { $this->pieces[0]->tags()->attach(create(Tag::class, ['name' => 'famous', 'type' => 'genre'])); });
        $answers = $this->answers(); $answers['intent'] = 'unfamiliar';
        $this->assertContains($this->pieces[0]->id, $tour->exclusions($answers));
    }

    public function test_result_calls_existing_engine_and_handles_audio_only_piece()
    {
        $answers = $this->answers();
        $quiz = \Mockery::mock(Quiz::class);
        $quiz->shouldReceive('getKeywords')->once()->with((new MatchTour)->keywords($answers))->andReturnSelf();
        $quiz->shouldReceive('exclude')->once()->with([])->andReturnSelf();
        $quiz->shouldReceive('search')->once()->andReturn($this->pieces[0]);
        $this->app->instance(Quiz::class, $quiz);
        $this->postJson(route('webapp.tour.result'), $answers)->assertOk()->assertSee('Your match')
            ->assertSee('id="match-result-heading"', false)->assertDontSee('data-bs-dismiss', false)
            ->assertSee('data-result-media', false)->assertSee('<audio', false)
            ->assertSee($this->pieces[0]->medium_name)->assertSee('Learn more about this piece')
            ->assertSee('More like this')->assertDontSee('id="match-modal"', false);
    }

    public function test_inline_result_shares_legacy_content_and_prefers_performance_video()
    {
        $piece = $this->pieces[0];
        Model::withoutEvents(function () use ($piece) {
            create(\App\Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Tutorial', 'video_url' => 'https://example.test/lesson.mp4']);
            create(\App\Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'video_url' => 'https://example.test/performance.mp4']);
        });
        $html = view('webapp.tour.result', ['piece' => $piece])->render();
        $this->assertStringContainsString('id="match-result-heading"', $html);
        $this->assertStringNotContainsString('data-bs-dismiss', $html);
        $this->assertStringNotContainsString('modal-dialog', $html);
        $this->assertStringContainsString('performance.mp4', $html);
        $this->assertStringNotContainsString('lesson.mp4', $html);
        $this->assertStringContainsString("What's this piece like?", $html);
        $legacy = view('funnels.find-your-match.results', ['piece' => $piece])->render();
        $this->assertStringContainsString('id="match-modal"', $legacy);
        $this->assertStringNotContainsString('data-result-media', $legacy);
        $this->assertStringContainsString('data-bs-dismiss="modal"', $legacy);
    }

    public function test_real_engine_can_recommend_with_the_new_answers()
    {
        $this->postJson(route('webapp.tour.result'), $this->answers())->assertOk()->assertSee('Your match');
    }

    public function test_malformed_answers_and_out_of_round_choices_are_rejected()
    {
        $this->withExceptionHandling();
        $answers = $this->answers();
        $answers['winners'][0] = $answers['preferredPiece'];
        $this->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
        $answers = $this->answers(); $answers['reading'] = [true];
        $this->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
        $answers = $this->answers(); $answers['reading'] = ['first' => true, 'second' => false];
        $this->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
        $answers = $this->answers(); $answers['intent'] = 'arbitrary';
        $this->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
    }
}
