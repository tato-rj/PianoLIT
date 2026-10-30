<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Quiz\{Quiz, Level, Topic};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Mail, Redis};
use Stevebauman\Location\Facades\Location;

class QuizPagesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function level($name = 'Beginner')
    {
        return Level::firstOrCreate(['slug' => str_slug($name)], ['name' => $name]);
    }

    private function quiz($title = 'How well do you know the piano?', array $attributes = [])
    {
        return Model::withoutEvents(function () use ($title, $attributes) {
            return create(Quiz::class, $attributes + [
                'title' => $title, 'slug' => str_slug($title), 'description' => 'A short musical challenge with something new to discover.',
                'level_id' => $this->level()->id, 'cover_path' => 'quiz/piano.jpg', 'published_at' => now(),
                'questions' => serialize([
                    ['Q' => 'Which instrument has keys?', 'A' => ['Piano[x]', 'Violin', 'Flute']],
                    ['Q' => 'Which note comes after C?', 'A' => ['B', 'D[x]', 'A']],
                ]),
            ]);
        });
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    private function preview($response, $name)
    {
        if ($directory = getenv('QUIZ_PREVIEW_DIR')) file_put_contents($directory.'/'.$name.'.html', $response->getContent());
    }

    public function test_directory_uses_shared_learn_components_and_filters_paginate_together()
    {
        $topic = Model::withoutEvents(function () { return create(Topic::class, ['name' => 'Theory', 'slug' => 'theory']); });
        for ($i = 1; $i <= 13; $i++) {
            $quiz = $this->quiz('Theory challenge '.$i, ['created_at' => now()->subMinutes($i)]);
            $quiz->topics()->attach($topic);
        }
        $this->quiz('Advanced theory', ['level_id' => $this->level('Advanced')->id])->topics()->attach($topic);
        $this->quiz('Composer challenge');
        $draft = $this->quiz('Draft challenge', ['published_at' => null]);
        $draftTopic = Model::withoutEvents(function () { return create(Topic::class, ['name' => 'Draft topic', 'slug' => 'draft-topic']); });
        $draft->topics()->attach($draftTopic);
        $response = $this->get('http://learn.localhost/quizzes?topics=theory&level=beginner')->assertOk()
            ->assertSee('Find your next challenge')->assertDontSee('Draft challenge')->assertDontSee('Advanced theory')->assertDontSee('Composer challenge')->assertDontSee('Draft topic');
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//li[@aria-current="page"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Quiz topics"]//a[@aria-current="page" and contains(.,"Theory")]'));
        $this->assertCount(1, $xpath->query('//form[@action="http://learn.localhost/quizzes"]//option[@value="beginner" and @selected]'));
        $this->assertCount(1, $xpath->query('//form//input[@name="topics[]" and @value="theory"]'));
        $this->assertCount(1, $xpath->query('//a[contains(@href,"page=2") and contains(@href,"topics=theory") and contains(@href,"level=beginner") and normalize-space(.)="2"]'));
        $response->assertDontSee('class="grid row', false);
        $this->get('http://learn.localhost/quizzes?topics=theory&level=beginner&page=2')->assertOk()->assertSee('Theory challenge 13');
        $this->get('http://learn.localhost/quizzes?topics=theory&level=')->assertOk()->assertSee('Advanced theory');
        $this->get('http://learn.localhost/quizzes?level=missing')->assertOk()->assertSee('No quizzes match these filters');
        $this->preview($response, 'directory');
        Mail::assertNothingOutgoing();
    }

    public function test_detail_has_accessible_questions_preserved_media_and_a_result_dialog()
    {
        $quiz = $this->quiz('Discover the piano.', ['questions' => serialize([
            ['Q' => 'Listen to this phrase [storage/quiz/audio/phrase.mp3]', 'A' => ['Major[x]', 'Minor']],
            ['Q' => 'Name this note [storage/quiz/images/note.png]', 'A' => ['C', 'D[x]']],
        ])]);
        $response = $this->get('http://learn.localhost/quizzes/'.$quiz->slug)->assertOk()
            ->assertSee('Discover the piano')->assertSee('Test your knowledge')->assertSee('Answer each question, then see your score.')->assertSee('Start quiz')->assertSee('Your progress')->assertSee('Review answers')
            ->assertSee('http://learn.localhost/quizzes/'.$quiz->slug.'/feedback', false)
            ->assertDontSee('game-results')->assertDontSee('pinterest.com')->assertDontSee('DISCLAIMER');
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//h1[normalize-space(.)="Discover the piano"]'));
        $this->assertCount(2, $xpath->query('//section[@data-quiz-question and @aria-labelledby]'));
        $this->assertCount(4, $xpath->query('//button[@data-quiz-answer and @aria-pressed="false"]'));
        $this->assertCount(2, $xpath->query('//button[@data-quiz-answer and @correct]'));
        $this->assertCount(1, $xpath->query('//audio[@controls and @preload="none"]//source[contains(@src,"/storage/quiz/audio/phrase.mp3")]'));
        $this->assertCount(1, $xpath->query('//img[contains(@src,"/storage/quiz/images/note.png") and @alt]'));
        $this->assertCount(1, $xpath->query('//div[@id="quiz-content" and @hidden]'));
        $this->assertCount(1, $xpath->query('//button[@data-quiz-start and @aria-describedby="quiz-start-description"]'));
        $this->assertCount(1, $xpath->query('//section[@data-quiz-results and @hidden]'));
        $this->assertCount(1, $xpath->query('//div[@id="quiz-result-modal" and @data-quiz-modal and @aria-labelledby="quiz-result-modal-title"]//section[@data-quiz-results]'));
        $this->assertCount(1, $xpath->query('//div[@data-quiz-reveal and @aria-hidden="true" and @hidden]'));
        $this->assertCount(1, $xpath->query('//div[@id="quiz-result-modal"]//button[@data-bs-dismiss="modal" and @aria-label="Close"]'));
        $this->assertCount(1, $xpath->query('//button[@data-quiz-show-result and @hidden]'));
        $this->assertCount(1, $xpath->query('//script[contains(@src,"/js/views/quizzes.js?id=")]'));
        $this->assertStringNotContainsString('@if', $response->getContent());
        $this->preview($response, 'detail');
    }

    public function test_current_and_legacy_feedback_keep_the_score_and_account_ownership()
    {
        $quiz = $this->quiz();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $other = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        foreach (['http://learn.localhost', 'http://localhost'] as $origin) {
            $response = $this->get($origin.'/quizzes/'.$quiz->slug.'/feedback?answers[]=0&answers[]=0&user_id='.$other->id)
                ->assertOk()->assertSee('1 out of 2')->assertSee('data-quiz-score', false)->assertSee('https://media.giphy.com/media/', false)->assertDontSee('data-quiz-gif-toggle', false)->assertSee('Share my score');
            $this->preview($response, 'feedback');
        }
        $this->assertSame(2, DB::table('quiz_results')->where('quiz_id', $quiz->id)->where('user_id', $user->id)->where('score', 1)->count());
        $this->assertSame(0, DB::table('quiz_results')->where('user_id', $other->id)->count());
    }

    public function test_endings_restore_the_original_gifs_for_each_score_band_with_short_copy()
    {
        $quiz = $this->quiz('Can you outsmart a piano?');
        $this->preview($this->get('http://learn.localhost/quizzes/'.$quiz->slug)->assertOk(), 'playable');
        foreach ([0 => 'oops', 25 => 'oops', 50 => 'good', 75 => 'great', 100 => 'perfect'] as $percentage => $tier) {
            $original = $quiz->getFeedback($percentage, 100);
            $response = $this->responseForView('games.quizzes.feedback', ['feedback' => [
                'score' => $percentage / 25, 'questions_count' => 4, 'percentage' => $percentage, 'gif' => $original['gif'],
            ]]);
            $xpath = $this->xpath($response);
            $this->assertCount(1, $xpath->query('//div[@data-quiz-tier="'.$tier.'"]'));
            $this->assertCount(1, $xpath->query('//img[@data-quiz-gif and @data-animated-src="'.$original['gif'].'" and @referrerpolicy="no-referrer"]'));
            $this->assertCount(1, $xpath->query('//img[contains(@src,"/giphy_s.gif")]'));
            $this->assertCount(0, $xpath->query('//button[@data-quiz-gif-toggle]'));
            $this->assertLessThan(12, str_word_count($xpath->query('//h2')->item(0)->textContent));
            $this->preview($response, 'feedback-'.$tier);
        }
    }

    private function responseForView($view, $data)
    {
        return new \Illuminate\Http\Response(view($view, $data)->render());
    }

    public function test_finale_compares_against_earlier_plays_of_this_quiz_before_saving_the_current_score()
    {
        $quiz = $this->quiz('Can you outsmart a piano?');
        foreach ([0, 1, 2] as $score) $quiz->results()->create(['score' => $score]);
        $other = $this->quiz('Another quiz');
        $other->results()->create(['score' => 2]);
        foreach ([2 => ['above', [0, 1]], 1 => ['tied', [0, 0]], 0 => ['below', [1, 0]]] as $score => [$standing, $answers]) {
            $response = $this->get('http://learn.localhost/quizzes/'.$quiz->slug.'/feedback?'.http_build_query(['answers' => $answers, 'quiz_average' => 0, 'plays' => 999999]));
            $response->assertOk()->assertSee('earlier plays')->assertDontSee('999,999');
            $xpath = $this->xpath($response);
            $this->assertCount(1, $xpath->query('//*[@data-quiz-comparison and @data-standing="'.$standing.'"]'));
            $this->assertCount(1, $xpath->query('//*[@role="progressbar" and @aria-labelledby="quiz-average-score-label" and @aria-valuenow="1" and @aria-valuemax="2"]'));
            $this->assertCount(1, $xpath->query('//*[@role="progressbar" and @aria-labelledby="quiz-your-score-label" and @aria-valuenow="'.$score.'"]'));
            $this->assertCount(1, $xpath->query('//*[contains(@class,"quiz-play-count")]//strong[text()="3"]'));
            $this->assertSame(4, $quiz->results()->count(), 'Only the existing completion write is performed.');
            $this->preview($response, 'comparison-'.[0 => 'oops', 1 => 'good', 2 => 'perfect'][$score]);
            $quiz->results()->latest('id')->first()->delete(); // Isolated test fixture only; keep the same benchmark for each state.
        }
    }

    public function test_comparison_distinguishes_no_history_zero_average_and_incompatible_old_scores()
    {
        $quiz = $this->quiz();
        $service = app(\App\Services\QuizResultComparison::class);
        $empty = $service->beforeCurrentPlay($quiz, 0, 2);
        $this->assertSame('first', $empty['standing']);
        $this->assertNull($empty['average']);
        $this->assertSame(0, $empty['plays']);
        $first = $this->get('http://learn.localhost/quizzes/'.$quiz->slug.'/feedback?answers[]=1&answers[]=0')->assertOk()->assertSee('First on stage!')->assertSee('No earlier plays yet.');
        $this->assertCount(1, $this->xpath($first)->query('//*[@role="progressbar"]'), 'An unavailable average must not look like a zero score.');
        $zero = $service->beforeCurrentPlay($quiz, 0, 2);
        $this->assertSame('tied', $zero['standing']);
        $this->assertSame('0', $zero['average_label']);
        $this->assertSame(0.0, $zero['average_percent']);
        foreach ([2, 2] as $score) $quiz->results()->create(['score' => $score]);
        $decimal = $service->beforeCurrentPlay($quiz, 2, 2);
        $this->assertSame('1.3', $decimal['average_label']);
        $this->assertSame(65.0, $decimal['average_percent']);
        $quiz->results()->create(['score' => 8]);
        $changed = $service->beforeCurrentPlay($quiz, 2, 2);
        $this->assertSame('unavailable', $changed['standing']);
        $this->assertNull($changed['average']);
        $this->assertSame(4, $changed['plays']);
        $this->assertSame(4, $quiz->results()->count(), 'Comparison reads never create or change results.');
        Mail::assertNothingOutgoing();
    }

    public function test_shared_scores_have_signed_public_pages_and_large_downloadable_social_cards()
    {
        $quiz = $this->quiz('Can you outsmart a piano?');
        $sharing = app(\App\Services\QuizResultShare::class);
        foreach ([0 => 'oops', 1 => 'good', 2 => 'perfect'] as $score => $tier) {
            $answers = $score === 2 ? [0, 1] : ($score === 1 ? [0, 0] : [1, 0]);
            $response = $this->get('http://learn.localhost/quizzes/'.$quiz->slug.'/feedback?'.http_build_query(['answers' => $answers]));
            $response->assertOk()->assertSee('Share my score')->assertSee('GIF via GIPHY')->assertDontSee('Pause GIF');
            $share = $sharing->data($quiz, $score, 2);
            $xpath = $this->xpath($response);
            $this->assertSame($share['url'], $xpath->query('//*[@data-quiz-share-panel]')->item(0)->getAttribute('data-share-url'));
            $this->assertCount(6, $xpath->query('//a[starts-with(@aria-label,"Share via ")]'));
            $this->assertCount(1, $xpath->query('//input[@readonly and @value="'.$share['url'].'"]'));
            foreach ($share['destinations'] as $destination) {
                parse_str(parse_url($destination['url'], PHP_URL_QUERY), $params);
                $this->assertStringContainsString($share['url'], implode(' ', $params));
            }
            $before = DB::table('quiz_results')->count();
            $page = $this->get($share['url'])->assertOk()->assertSee('Take the quiz');
            $meta = $this->xpath($page);
            $this->assertSame($share['url'], $meta->query('//meta[@property="og:url"]')->item(0)->getAttribute('content'));
            $this->assertSame($share['image'], $meta->query('//meta[@property="og:image"]')->item(0)->getAttribute('content'));
            $this->assertCount(1, $meta->query('//meta[@name="twitter:card" and @content="summary_large_image"]'));
            $image = $this->get($share['image'])->assertOk()->assertHeader('Content-Type', 'image/png');
            $size = getimagesizefromstring($image->getContent());
            $this->assertSame([1200, 630], [$size[0], $size[1]]);
            $this->get($share['download'])->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="pianolit-quiz-score.png"');
            $this->assertSame($before, DB::table('quiz_results')->count(), 'Share previews never record quiz plays.');
            $this->preview($response, 'share-feedback-'.$tier);
            $this->preview($page, 'shared-'.$tier);
            if ($directory = getenv('QUIZ_PREVIEW_DIR')) file_put_contents($directory.'/score-'.$score.'.png', $image->getContent());
        }
        Mail::assertNothingOutgoing();
    }

    public function test_shared_scores_reject_tampering_drafts_and_invalid_bounds_without_changing_legacy_feedback()
    {
        $this->withExceptionHandling();
        $quiz = $this->quiz();
        $sharing = app(\App\Services\QuizResultShare::class);
        $share = $sharing->data($quiz, 1, 2);
        $tracked = $this->get($share['url'].'&fbclid=example&utm_source=facebook&igsh=example')->assertOk();
        $this->assertSame($share['url'], $this->xpath($tracked)->query('//meta[@property="og:url"]')->item(0)->getAttribute('content'));
        $this->get(str_replace('/1/2', '/2/2', $share['url']))->assertForbidden();
        $this->get(str_replace('/1/2', '/2/2', $share['url']).'&fbclid=example')->assertForbidden();
        $this->get(strtok($share['image'], '?'))->assertForbidden();
        $this->get($share['image'].'&download=1')->assertForbidden();
        foreach ([[3, 2], [0, 0], [1, 1000]] as [$score, $total]) {
            $this->get(\URL::signedRoute('quizzes.share', ['quiz' => $quiz->slug, 'score' => $score, 'total' => $total]))->assertNotFound();
        }
        $quiz->forceFill(['published_at' => null])->saveQuietly();
        $this->get($share['url'])->assertNotFound();
        $this->get($share['image'])->assertNotFound();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get('http://learn.localhost/quizzes/'.$quiz->slug.'/feedback?answers[]=0&answers[]=1')
            ->assertOk()->assertSee('2 out of 2')->assertDontSee('data-quiz-share-panel', false);
    }

    public function test_drafts_stay_private_and_empty_quizzes_and_directories_remain_usable()
    {
        $this->get('http://learn.localhost/quizzes')->assertOk()->assertSee('New quizzes are on their way');
        $draft = $this->quiz('Unpublished quiz', ['published_at' => null]);
        $this->withExceptionHandling()->get('http://learn.localhost/quizzes/'.$draft->slug)->assertNotFound();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get('http://learn.localhost/quizzes/'.$draft->slug)->assertOk()->assertSee('Admin preview');
        $empty = $this->quiz('Coming soon', ['questions' => serialize([])]);
        $response = $this->get('http://learn.localhost/quizzes/'.$empty->slug)->assertOk()->assertSee('This quiz is being prepared');
        $this->assertCount(0, $this->xpath($response)->query('//button[@data-quiz-start]'));
    }

    public function test_recommendations_only_show_other_published_quizzes_in_shared_cards()
    {
        $quiz = $this->quiz('Main challenge');
        $this->quiz('Another discovery');
        $this->quiz('Hidden discovery', ['published_at' => null]);
        $response = $this->get('http://learn.localhost/quizzes/'.$quiz->slug)->assertOk()->assertSee('Another discovery')->assertDontSee('Hidden discovery');
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(0, $xpath->query('//a[contains(@class,"resource-card") and @href="http://learn.localhost/quizzes/'.$quiz->slug.'"]'));
    }
}
