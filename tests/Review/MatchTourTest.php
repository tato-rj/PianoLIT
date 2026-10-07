<?php

namespace Tests\Review;

use App\{Piece, Tag, Tutorial};
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
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Tutorial', 'video_url' => 'https://example.test/tour.mp4']);
                return $piece;
            });
        });
    }

    public function test_large_catalog_has_bounded_queries_and_unique_choices()
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
        $this->assertCount(10, array_unique(array_column($data['pieces'], 'id')));
        $this->assertSame($before['scores'], $data['scores']);
        $this->assertSame($queryCount, count($queries));
        $this->assertLessThanOrEqual(12, count($queries));
        foreach ($queries as $query) $this->assertStringNotContainsString('pieces_count', $query['query']);
    }

    public function test_opening_choices_are_randomized_across_the_entire_free_pick_pool()
    {
        Model::withoutEvents(function () {
            $genre = create(Tag::class, ['name' => 'dance', 'type' => 'genre']);
            foreach ($this->pieces as $i => $piece) {
                $piece->update(['composer_id' => $this->pieces[intdiv($i, 3)]->composer_id, 'show_on_tour' => in_array($i, [2, 5, 9])]);
                if ($i % 2 === 0) $piece->tags()->attach($genre);
            }
        });
        $openings = []; $seen = [];
        $tour = new MatchTour;
        for ($i = 0; $i < 12; $i++) {
            $data = $tour->data();
            $ids = array_column($data['pieces'], 'id');
            $this->assertCount(10, array_unique($ids));
            $this->assertSame($ids, $tour->drawIds($data['draw']));
            $opening = array_slice($ids, 0, 4); sort($opening);
            $openings[] = implode(',', $opening); $seen = array_merge($seen, $opening);
        }
        $this->assertGreaterThan(1, count(array_unique($openings)), 'Randomization changes the pieces, not only their order');
        $this->assertGreaterThan(4, count(array_unique($seen)));
    }

    public function test_all_questionnaire_examples_come_from_historical_free_picks()
    {
        Model::withoutEvents(function () {
            foreach ($this->pieces as $piece) {
                $piece->update(['highlighted_at' => now()->subYear(), 'is_free' => false, 'show_on_tour' => false]);
            }
            foreach ($this->pieces->take(4) as $source) {
                $decoy = $source->replicate();
                // Even editorially preferred/currently free entries need free-pick history.
                $decoy->fill(['highlighted_at' => null, 'is_free' => true, 'show_on_tour' => true])->save();
                $decoy->tags()->attach($source->tags()->pluck('tags.id')->all());
            }
        });
        $tour = new MatchTour;
        $data = $tour->data();
        $this->assertTrue($data['ready']);
        $this->assertSame(16, $data['total']);
        $this->assertCount(10, $data['pieces']);
        $eligible = $this->pieces->pluck('id')->all();
        foreach ($data['pieces'] as $card) $this->assertContains($card['id'], $eligible);
        $this->assertSame(array_slice($eligible, 0, 4), array_column($data['scores'], 'id'));
        $response = $this->getJson(route('webapp.tour'))->assertOk();
        $this->assertSame(array_column($response->json('tour.pieces'), 'id'), $tour->drawIds($response->json('tour.draw')));
    }

    public function test_free_pick_examples_require_only_the_media_used_by_their_screen()
    {
        Model::withoutEvents(function () {
            $this->pieces[0]->update(['audio_path' => null]);
            $this->pieces[11]->update(['audio_path' => null]);
            $this->pieces[1]->update(['score_path' => null]);
        });
        $data = (new MatchTour)->data();
        $this->assertTrue($data['ready']);
        $this->assertSame($this->pieces[0]->id, $data['scores']['easy']['id']);
        $this->assertNotContains($this->pieces[0]->id, array_column($data['pieces'], 'id'));
        $this->assertContains($this->pieces[1]->id, array_column($data['pieces'], 'id'));
        $this->assertSame($this->pieces[5]->id, $data['scores']['beginner']['id']);
    }

    public function test_questionnaire_does_not_fill_missing_free_picks_with_other_pieces()
    {
        Piece::query()->update(['highlighted_at' => null]);
        $data = (new MatchTour)->data();
        $this->assertFalse($data['ready']);
        $this->assertSame([], $data['pieces']);
        $this->assertSame(['easy' => null, 'beginner' => null, 'middle' => null, 'hard' => null], $data['scores']);
        $this->assertSame(12, $data['total']);
    }

    private function answers($data = null)
    {
        $data = $data ?? (new MatchTour)->data();
        $ids = array_column($data['pieces'], 'id');
        return ['draw' => $data['draw'], 'preferredPiece' => $ids[0], 'reading' => [true, false], 'estimatedLevel' => 'elementary', 'winners' => [$ids[4], $ids[6], $ids[8]], 'intent' => 'personal'];
    }

    public function test_catalog_and_web_only_flow_are_guest_safe_with_verified_draws()
    {
        $tour = new MatchTour;
        $data = $tour->data();
        $this->assertTrue($data['ready']);
        $this->assertSame(12, $data['total']);
        $this->assertSame(array_column($data['pieces'], 'id'), $tour->drawIds($data['draw']));
        $this->assertCount(10, array_unique(array_column($data['pieces'], 'id')));
        $this->assertCount(4, array_unique(array_column($data['scores'], 'id')));
        $this->assertCount(9, $data['moods']);
        $this->assertSame(60, $data['previewSeconds']);
        $this->assertSame(10, config('webapp.media_preview_seconds'));
        $this->get(route('webapp.tour'))->assertOk()->assertSee('data-progress-step', false)
            ->assertSee('modal-fullscreen', false)->assertSee('Close Find your match')
            ->assertSee('id="menu"', false)
            ->assertDontSee('QUESTION')->assertDontSee('id="find-match-carousel"', false)
            ->assertDontSee('build/pdf.min.js', false)
            ->assertDontSee('cdn.plyr.io', false);
        $response = $this->getJson(route('webapp.tour'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('tour.ready', true);
        $this->assertSame(array_column($response->json('tour.pieces'), 'id'), $tour->drawIds($response->json('tour.draw')));
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

    public function test_results_use_each_displayed_draw_even_after_another_tour_opens()
    {
        $first = $this->getJson(route('webapp.tour'))->assertOk()->json('tour');
        $second = $this->getJson(route('webapp.tour'))->assertOk()->json('tour');
        $this->getJson(route('webapp.tour'))->assertOk();
        $tour = \Mockery::mock(MatchTour::class)->makePartial();
        $tour->shouldNotReceive('data');
        $this->app->instance(MatchTour::class, $tour);
        $this->postJson(route('webapp.tour.result'), $this->answers($first))->assertOk();
        $this->postJson(route('webapp.tour.result'), $this->answers($second))->assertOk();
    }

    public function test_missing_tampered_and_expired_draws_are_rejected()
    {
        $this->withExceptionHandling();
        $answers = $this->answers();
        $missing = $answers; unset($missing['draw']);
        $this->postJson(route('webapp.tour.result'), $missing)->assertStatus(422);
        $tampered = $answers; $tampered['draw'] .= '-tampered';
        $this->postJson(route('webapp.tour.result'), $tampered)->assertStatus(422);
        $this->travel(121)->minutes();
        $this->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
        $this->travelBack();
    }

    public function test_a_draw_with_a_removed_free_pick_cannot_be_submitted()
    {
        $this->withExceptionHandling();
        $answers = $this->answers();
        Piece::where('id', $answers['preferredPiece'])->update(['highlighted_at' => null]);
        $this->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
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

    public function test_result_opts_into_media_eligibility_in_the_existing_engine()
    {
        $answers = $this->answers();
        $quiz = \Mockery::mock(Quiz::class);
        $quiz->shouldReceive('getKeywords')->once()->with((new MatchTour)->keywords($answers))->andReturnSelf();
        $quiz->shouldReceive('exclude')->once()->with([])->andReturnSelf();
        $quiz->shouldReceive('search')->once()->with(true, true)->andReturn($this->pieces[0]);
        $quiz->shouldReceive('matchContext')->with($this->pieces[0])->andReturn(['fallback' => false, 'level' => 'intermediate', 'sharedMoods' => ['calm'], 'matchedTags' => ['calm']]);
        $this->app->instance(Quiz::class, $quiz);
        $this->postJson(route('webapp.tour.result'), $answers)->assertOk()->assertSee('We found your perfect match!')
            ->assertSee('id="match-result-heading"', false)->assertDontSee('data-bs-dismiss', false)
            ->assertSee('data-result-data', false)->assertDontSee('<video', false)
            ->assertSee($this->pieces[0]->medium_name)->assertSee('View piece')
            ->assertSee('You might also like')->assertSee('Explore more pieces')
            ->assertDontSee('data-submit="favorite"', false)->assertDontSee('id="match-modal"', false);
    }

    public function test_inline_result_uses_existing_media_and_preserves_legacy_modal()
    {
        $piece = $this->pieces[0];
        Model::withoutEvents(function () use ($piece) {
            create(\App\Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Tutorial', 'video_url' => 'https://example.test/lesson.mp4']);
            create(\App\Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'video_url' => '   ']);
            create(\App\Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'video_url' => 'https://example.test/performance.mp4']);
        });
        $html = view('webapp.tour.result', ['piece' => $piece])->render();
        $this->assertStringContainsString('id="match-result-heading"', $html);
        $this->assertStringNotContainsString('data-bs-dismiss', $html);
        $this->assertStringNotContainsString('modal-dialog', $html);
        $this->assertStringContainsString('performance.mp4', $html);
        $this->assertStringNotContainsString('lesson.mp4', $html);
        $this->assertStringContainsString('data-result-card', $html);
        $legacy = view('funnels.find-your-match.results', ['piece' => $piece])->render();
        $this->assertStringContainsString('id="match-modal"', $legacy);
        $this->assertStringNotContainsString('data-result-media', $legacy);
        $this->assertStringContainsString('data-bs-dismiss="modal"', $legacy);
        $this->assertStringContainsString("What's this piece like?", $legacy);
    }

    public function test_real_engine_can_recommend_with_the_new_answers()
    {
        $this->postJson(route('webapp.tour.result'), $this->answers())->assertOk()->assertSee('We found your perfect match!')->assertSee('Why this piece?');
    }

    public function test_explanation_uses_matching_traits_without_inventing_skipped_or_unmatched_answers()
    {
        $tour = new MatchTour;
        $answers = $this->answers();
        $answers['mood'] = 'calm'; $answers['reading'] = [false, false];
        $this->copyWithMedia();
        $quiz = (new Quiz)->getKeywords([$this->pieces[0]->id, 'elementary', 'calm']);
        $piece = $quiz->search(true);
        \DB::enableQueryLog(); \DB::flushQueryLog();
        $context = $quiz->matchContext($piece);
        $this->assertCount(0, \DB::getQueryLog(), 'Explanation reuses the engine’s loaded choice tags');
        \DB::disableQueryLog();
        $this->assertFalse($context['fallback']);
        $this->assertSame(['calm'], $context['sharedMoods']);
        $this->assertSame(['calm'], $context['matchedTags']);
        $explanation = $tour->explanation($answers, $context);
        $this->assertStringContainsString('calm character', $explanation);
        $this->assertStringContainsString('calm & peaceful', $explanation);
        $this->assertStringContainsString('elementary repertoire', $explanation);

        $answers['mood'] = 'romantic'; $answers['reading'] = [null, null];
        $explanation = $tour->explanation($answers, $context);
        $this->assertStringNotContainsString('romantic', $explanation);
        $this->assertStringNotContainsString('sight-reading', $explanation);
        $this->assertStringNotContainsString('repertoire', $explanation);

        $answers['mood'] = null; $answers['intent'] = 'quick';
        $this->assertStringNotContainsString('shorter', $tour->explanation($answers, $context));
        $context['matchedTags'][] = 'short';
        $this->assertStringContainsString('shorter length', $tour->explanation($answers, $context));
    }

    public function test_fallback_explanation_does_not_claim_personalized_traits_or_difficulty()
    {
        // Make the legacy fallback deterministically disagree with the requested traits/level.
        Tutorial::query()->where('piece_id', '!=', $this->pieces[1]->id)->delete();
        $quiz = (new Quiz)->getKeywords(['elementary', 'calm']);
        $piece = $quiz->search(true);
        $context = $quiz->matchContext($piece);
        $this->assertTrue($context['fallback']);
        $explanation = (new MatchTour)->explanation($this->answers(), $context);
        $this->assertStringContainsString('Here is the best match we could find', $explanation);
        $this->assertStringNotContainsString('couldn’t find', $explanation);
        $this->assertStringNotContainsString('free pick', $explanation);
        $this->assertStringNotContainsString('sight-reading', $explanation);
        $this->assertStringNotContainsString('character', $explanation);
        $this->copyWithMedia();
        $quiz->getKeywords([$this->pieces[0]->id, 'elementary'])->search(true);
        $this->assertFalse($quiz->matchContext($this->pieces[0])['fallback']);
    }

    public function test_moods_and_skips_use_existing_tags_and_preferred_piece_level()
    {
        $tour = new MatchTour;
        foreach (MatchTour::MOODS as $mood => $definition) {
            $answers = $this->answers();
            $answers['mood'] = $mood; $answers['intent'] = null;
            $answers['reading'] = [null, null]; $answers['winners'] = [null, null, null];
            $piece = Piece::with('tags')->find($answers['preferredPiece']);
            $expected = array_merge([$piece->id, $piece->level->name], $definition['tags']);
            $this->assertSame($expected, $tour->keywords($answers));
            $this->postJson(route('webapp.tour.result'), $answers)->assertOk();
        }
        $this->assertSame('beginner', $tour->level([false, null]));
        $this->assertSame('intermediate', $tour->level([true, null]));
        $answers['mood'] = 'arbitrary';
        $this->withExceptionHandling()->postJson(route('webapp.tour.result'), $answers)->assertStatus(422);
    }

    public function test_signed_in_result_has_no_favorite_or_confetti_and_excludes_match_from_recommendations()
    {
        Model::withoutEvents(function () {
            $incomplete = create(Piece::class);
            $incomplete->tags()->attach($this->pieces[0]->tags()->where('type', 'mood')->pluck('tags.id')->all());
        });
        $user = Model::withoutEvents(function () { return create(\App\User::class); });
        $this->actingAs($user, 'web');
        $answers = $this->answers(); $answers['mood'] = 'open';
        $quiz = \Mockery::mock(Quiz::class);
        $quiz->shouldReceive('getKeywords')->andReturnSelf();
        $quiz->shouldReceive('exclude')->andReturnSelf();
        $quiz->shouldReceive('search')->with(true, true)->andReturn($this->pieces[0]);
        $quiz->shouldReceive('matchContext')->with($this->pieces[0])->andReturn(['fallback' => false, 'level' => 'intermediate', 'sharedMoods' => ['calm'], 'matchedTags' => ['calm']]);
        $this->app->instance(Quiz::class, $quiz);
        $response = $this->postJson(route('webapp.tour.result'), $answers)->assertOk()
            ->assertDontSee('data-submit="favorite"', false)->assertDontSee('match-confetti', false);
        preg_match('/<script type="application\/json" data-result-data>(.*?)<\/script>/s', $response->getContent(), $matches);
        $result = json_decode($matches[1], true);
        $this->assertLessThanOrEqual(4, count($result['recommendations']));
        $this->assertNotContains($result['piece']['id'], array_column($result['recommendations'], 'id'));
        $this->assertSame('https://example.test/tour.mp4', $result['piece']['video']);
        $this->assertStringEndsWith('/tour.mp3', $result['piece']['audio']);
    }

    private function copyWithMedia(array $attributes = [], $video = 'https://example.test/copy.mp4', Piece $source = null)
    {
        return Model::withoutEvents(function () use ($attributes, $video, $source) {
            $source = $source ?? $this->pieces[0];
            $piece = $source->replicate();
            $piece->fill($attributes)->save();
            $piece->tags()->attach($source->tags()->pluck('tags.id')->all());
            create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'video_url' => $video]);
            return $piece;
        });
    }

    public function test_media_eligibility_is_applied_before_the_top_five_matches_are_ranked()
    {
        $invalid = collect();
        foreach ([null, '', '   '] as $video) $invalid->push($this->copyWithMedia([], $video));
        foreach ([null, '', '   '] as $score) $invalid->push($this->copyWithMedia(['score_path' => $score]));
        $invalid->push($this->copyWithMedia(['score_url' => 'https://example.test/buy-score']));
        $dreamy = Tag::name('dreamy')->first();
        foreach ($invalid as $piece) $piece->tags()->attach($dreamy);
        $valid = $this->copyWithMedia();
        $keywords = [$this->pieces[0]->id, 'elementary', 'dreamy'];
        $excluded = $this->pieces->pluck('id')->all();

        $match = (new Quiz)->getKeywords($keywords)->exclude($excluded)->search(true);
        $this->assertSame($valid->id, $match->id);
        // Legacy callers still use their original unrestricted ranking by default.
        $legacy = (new Quiz)->getKeywords($keywords)->exclude($excluded)->search();
        $this->assertContains($legacy->id, $invalid->pluck('id')->all());
    }

    public function test_perfect_match_and_all_more_options_have_video_and_available_score()
    {
        // Invalid entries precede valid ones, so filtering after take(4) would lose options.
        $this->copyWithMedia([], null);
        $this->copyWithMedia([], '');
        $this->copyWithMedia([], '   ');
        $this->copyWithMedia(['score_path' => null]);
        $this->copyWithMedia(['score_path' => '']);
        $this->copyWithMedia(['score_path' => '   ']);
        $this->copyWithMedia(['score_path' => null, 'score_url' => 'https://example.test/buy-score']);
        $this->copyWithMedia(['score_url' => 'https://example.test/buy-score']);
        $eligible = $this->pieces->pluck('id')->all();
        for ($i = 0; $i < 6; $i++) $eligible[] = $this->copyWithMedia()->id;
        // Keep the randomized examples on the shared elementary/calm branch for this assertion.
        Piece::whereIn('id', $this->pieces->slice(1)->pluck('id'))->update(['audio_path' => null]);
        $answers = $this->answers();
        $answers['reading'] = [null, null];
        $answers['winners'] = [null, null, null];
        $answers['mood'] = 'open';
        $response = $this->postJson(route('webapp.tour.result'), $answers)->assertOk();
        preg_match('/<script type="application\/json" data-result-data>(.*?)<\/script>/s', $response->getContent(), $matches);
        $result = json_decode($matches[1], true);
        $this->assertContains($result['piece']['id'], $eligible);
        $this->assertNotEmpty($result['piece']['video']);
        $this->assertCount(4, $result['recommendations']);
        foreach ($result['recommendations'] as $card) {
            $this->assertContains($card['id'], $eligible);
            $this->assertNotSame($result['piece']['id'], $card['id']);
        }
    }

    public function test_free_pick_fallback_also_requires_both_video_and_score()
    {
        Tutorial::query()->delete();
        Model::withoutEvents(function () {
            create(Tutorial::class, ['piece_id' => $this->pieces[0]->id, 'video_url' => '   ']);
            $this->pieces[1]->update(['score_path' => null]);
            create(Tutorial::class, ['piece_id' => $this->pieces[1]->id, 'video_url' => 'https://example.test/video-only.mp4']);
            create(Tutorial::class, ['piece_id' => $this->pieces[2]->id, 'video_url' => 'https://example.test/full.mp4']);
            $this->pieces[3]->update(['score_url' => 'https://example.test/buy-score']);
            create(Tutorial::class, ['piece_id' => $this->pieces[3]->id, 'video_url' => 'https://example.test/purchase.mp4']);
        });
        $this->assertSame($this->pieces[2]->id, (new Quiz)->getKeywords(['elementary'])->search(true)->id);
        $this->assertSame($this->pieces[2]->id, (new Quiz)->getKeywords(['elementary'])->search(true, true)->id);
    }

    private function partialCandidate($level, array $moods = [], array $attributes = [], $video = 'https://example.test/partial.mp4')
    {
        $piece = $this->copyWithMedia(array_merge(['audio_path' => null], $attributes), $video);
        Model::withoutEvents(function () use ($piece, $level, $moods) {
            $tags = collect(array_filter(array_merge([$level], $moods)))->map(function ($name) use ($level) {
                return Tag::firstOrCreate(['name' => $name, 'type' => $name === $level ? 'level' : 'mood'])->id;
            });
            $piece->tags()->sync($tags->all());
        });
        return $piece;
    }

    public function test_partial_matching_accepts_split_difficulty_without_musical_overlap()
    {
        Tutorial::query()->delete();
        $best = $this->partialCandidate('late intermediate');
        $this->partialCandidate('advanced', ['calm', 'dreamy']);
        $quiz = (new Quiz)->getKeywords([$this->pieces[0]->id, 'intermediate', 'dreamy']);
        $piece = $quiz->search(true, true);
        $this->assertSame($best->id, $piece->id);
        \DB::enableQueryLog(); \DB::flushQueryLog();
        $context = $quiz->matchContext($piece);
        $this->assertCount(0, \DB::getQueryLog(), 'Partial explanation reuses eager-loaded tags');
        \DB::disableQueryLog();
        $this->assertTrue($context['fallback']);
        $this->assertTrue($context['levelMatched']);
        $this->assertSame([], $context['sharedMoods']);
        $answers = $this->answers(); $answers['reading'] = [true, false]; $answers['mood'] = 'romantic';
        $explanation = (new MatchTour)->explanation($answers, $context);
        $this->assertStringContainsString('Here is the best match we could find', $explanation);
        $this->assertStringContainsString('intermediate repertoire', $explanation);
        $this->assertStringNotContainsString('romantic', $explanation);
        $this->assertStringNotContainsString('character', $explanation);
    }

    public function test_web_result_can_match_only_the_selected_mood_without_claiming_the_requested_level()
    {
        Tutorial::query()->delete();
        $best = $this->partialCandidate('advanced', ['happy']);
        $this->partialCandidate('elementary', ['mysterious']);
        $answers = $this->answers();
        $answers['reading'] = [false, true]; $answers['winners'] = [null, null, null]; $answers['mood'] = 'joyful';
        $response = $this->postJson(route('webapp.tour.result'), $answers)->assertOk();
        preg_match('/<script type="application\/json" data-result-data>(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertSame($best->id, json_decode($matches[1], true)['piece']['id']);
        $response->assertSee('Here is the best match we could find')->assertSee('fits your mood: joyful')
            ->assertDontSee('guided us toward beginner repertoire')->assertDontSee('past free pick')->assertDontSee('couldn’t find');
    }

    public function test_partial_matching_can_use_only_the_character_of_a_listening_choice()
    {
        Tutorial::query()->delete();
        $best = $this->partialCandidate('beginner', ['calm']);
        $this->partialCandidate('intermediate', ['mysterious']);
        $quiz = (new Quiz)->getKeywords([$this->pieces[0]->id, 'advanced']);
        $piece = $quiz->search(true, true);
        $this->assertSame($best->id, $piece->id);
        $context = $quiz->matchContext($piece);
        $this->assertFalse($context['levelMatched']);
        $this->assertSame(['calm'], $context['sharedMoods']);
        $answers = $this->answers(); $answers['reading'] = [true, true]; $answers['mood'] = 'open';
        $explanation = (new MatchTour)->explanation($answers, $context);
        $this->assertStringContainsString('calm character', $explanation);
        $this->assertStringNotContainsString('advanced repertoire', $explanation);
    }

    public function test_web_partial_match_can_use_a_mood_when_the_candidate_has_no_level_tag()
    {
        Tutorial::query()->delete();
        $best = $this->partialCandidate(null, ['calm']);
        $quiz = (new Quiz)->getKeywords([$this->pieces[0]->id, 'advanced']);
        $piece = $quiz->search(true, true);
        $this->assertSame($best->id, $piece->id);
        $context = $quiz->matchContext($piece);
        $this->assertSame(['calm'], $context['sharedMoods']);
        $this->assertFalse($context['levelMatched']);
        $this->assertNull($context['pieceLevel']);
    }

    public function test_partial_match_ranking_selects_the_strongest_fit_across_the_eligible_pool()
    {
        Tutorial::query()->delete();
        for ($i = 0; $i < 6; $i++) $this->partialCandidate('elementary', ['calm']);
        $best = $this->partialCandidate('elementary', ['dreamy']);
        $quiz = (new Quiz)->getKeywords(['elementary', 'dreamy']);
        for ($i = 0; $i < 5; $i++) $this->assertSame($best->id, $quiz->search(true, true)->id);
    }

    public function test_partial_match_preserves_history_media_and_exclusion_requirements()
    {
        Tutorial::query()->delete();
        $best = $this->partialCandidate('elementary');
        $this->partialCandidate('elementary', ['dreamy'], ['highlighted_at' => null]);
        $this->partialCandidate('elementary', ['dreamy'], [], '   ');
        $this->partialCandidate('elementary', ['dreamy'], ['score_path' => null]);
        $this->partialCandidate('elementary', ['dreamy'], ['score_url' => 'https://example.test/buy-score']);
        $excluded = $this->partialCandidate('elementary', ['dreamy']);
        $quiz = (new Quiz)->getKeywords(['elementary', 'dreamy'])->exclude([$excluded->id]);
        $this->assertSame($best->id, $quiz->search(true, true)->id);
        $quiz->exclude([$best->id, $excluded->id]);
        $this->assertNull($quiz->search(true, true));
        $this->assertNotNull($quiz->search(true), 'Legacy fallback keeps its original eligibility/exclusion behavior');
    }

    public function test_when_no_exact_trait_matches_the_closest_available_level_is_explained_truthfully()
    {
        Tutorial::query()->delete();
        $this->partialCandidate('elementary', ['calm']);
        $best = $this->partialCandidate('early intermediate', ['calm']);
        $quiz = (new Quiz)->getKeywords(['advanced', 'happy']);
        $piece = $quiz->search(true, true);
        $this->assertSame($best->id, $piece->id);
        $context = $quiz->matchContext($piece);
        $this->assertTrue($context['nearestLevel']);
        $this->assertFalse($context['levelMatched']);
        $answers = $this->answers(); $answers['reading'] = [true, true]; $answers['mood'] = 'joyful';
        $explanation = (new MatchTour)->explanation($answers, $context);
        $this->assertStringContainsString('early intermediate difficulty is the closest available', $explanation);
        $this->assertStringNotContainsString('fits your mood', $explanation);
        $this->assertStringNotContainsString('advanced repertoire', $explanation);
        $this->partialCandidate('elementary', ['calm']);
        $quiz->getKeywords([$this->pieces[0]->id, 'elementary'])->search(true, true);
        $this->assertFalse($quiz->matchContext($this->pieces[0])['nearestLevel'], 'A reused engine clears partial-match state');
    }

    public function test_web_match_does_not_invent_a_fit_for_an_untagged_candidate()
    {
        Tutorial::query()->delete();
        $this->partialCandidate(null);
        $quiz = (new Quiz)->getKeywords(['advanced', 'happy']);
        $this->assertNull($quiz->search(true, true));
        $this->assertNotNull($quiz->search(true), 'Default mobile/public fallback remains unchanged');
    }

    public function test_historical_free_pick_filter_runs_before_the_top_five_ranking()
    {
        $nonPicks = collect();
        for ($i = 0; $i < 6; $i++) {
            // Currently free/editorially preferred flags cannot replace free-pick history.
            $piece = $this->copyWithMedia(['highlighted_at' => null, 'is_free' => true, 'show_on_tour' => true]);
            $piece->tags()->attach(Tag::name('dreamy')->first());
            $nonPicks->push($piece);
        }
        $pastPick = $this->copyWithMedia(['highlighted_at' => now()->subYear(), 'is_free' => false, 'show_on_tour' => false]);
        $keywords = [$this->pieces[0]->id, 'elementary', 'dreamy'];
        $excluded = $this->pieces->pluck('id')->all();
        $quiz = (new Quiz)->getKeywords($keywords)->exclude($excluded);
        $match = $quiz->search(true, true);
        $this->assertSame($pastPick->id, $match->id);
        $this->assertFalse($quiz->matchContext($match)['fallback'], 'The eligible past pick is ranked, not rescued by fallback');
        $this->assertContains((new Quiz)->getKeywords($keywords)->exclude($excluded)->search(true)->id, $nonPicks->pluck('id')->all());
    }

    public function test_perfect_match_uses_past_free_picks_but_more_options_include_other_pieces()
    {
        $answers = $this->answers();
        $answers['reading'] = [null, null]; $answers['winners'] = [null, null, null]; $answers['mood'] = 'open';
        $source = $this->pieces->firstWhere('id', $answers['preferredPiece']);
        $pastPick = $this->copyWithMedia(['highlighted_at' => now()->subYear(), 'is_free' => false], 'https://example.test/past-pick.mp4', $source);
        $nonPicks = collect();
        for ($i = 0; $i < 5; $i++) $nonPicks->push($this->copyWithMedia(['highlighted_at' => null], 'https://example.test/other.mp4', $source));
        $invalid = $this->copyWithMedia(['highlighted_at' => null, 'score_path' => null], 'https://example.test/no-score.mp4', $source);

        $response = $this->postJson(route('webapp.tour.result'), $answers)->assertOk();
        preg_match('/<script type="application\/json" data-result-data>(.*?)<\/script>/s', $response->getContent(), $matches);
        $result = json_decode($matches[1], true);
        $this->assertContains($result['piece']['id'], $this->pieces->pluck('id')->push($pastPick->id)->all());
        $this->assertNotEmpty($result['piece']['video']);
        $ids = collect($result['recommendations'])->pluck('id');
        $this->assertCount(4, $ids);
        $this->assertNotEmpty($ids->intersect($nonPicks->pluck('id'))->all());
        $this->assertNotContains($invalid->id, $ids);
        $this->assertNotContains($result['piece']['id'], $ids);
    }

    public function test_no_eligible_past_pick_does_not_substitute_an_unhighlighted_perfect_match()
    {
        $answers = $this->answers();
        $answers['reading'] = [null, null]; $answers['winners'] = [null, null, null]; $answers['mood'] = 'open';
        $source = $this->pieces->firstWhere('id', $answers['preferredPiece']);
        Tutorial::query()->delete();
        $nonPick = $this->copyWithMedia(['highlighted_at' => null], 'https://example.test/other.mp4', $source);
        $this->assertSame($nonPick->id, (new Quiz)->getKeywords((new MatchTour)->keywords($answers))->search(true)->id);
        $this->withExceptionHandling()->postJson(route('webapp.tour.result'), $answers)->assertStatus(503);
    }

    public function test_no_eligible_match_returns_unavailable_without_changing_legacy_fallback()
    {
        Tutorial::query()->delete();
        $this->assertNull((new Quiz)->getKeywords(['elementary'])->search(true));
        $this->assertContains((new Quiz)->getKeywords(['elementary'])->search()->id, $this->pieces->pluck('id')->all());
        $this->assertTrue((new MatchTour)->data()['ready']);
        $this->withExceptionHandling()->postJson(route('webapp.tour.result'), $this->answers())->assertStatus(503);
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
