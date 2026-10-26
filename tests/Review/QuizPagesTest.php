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
        $response->assertDontSee('class="grid row', false)->assertSee('Join the newsletter');
        $this->get('http://learn.localhost/quizzes?topics=theory&level=beginner&page=2')->assertOk()->assertSee('Theory challenge 13');
        $this->get('http://learn.localhost/quizzes?topics=theory&level=')->assertOk()->assertSee('Advanced theory');
        $this->get('http://learn.localhost/quizzes?level=missing')->assertOk()->assertSee('No quizzes match these filters');
        $this->preview($response, 'directory');
        Mail::assertNothingOutgoing();
    }

    public function test_detail_has_accessible_questions_preserved_media_and_inline_results()
    {
        $quiz = $this->quiz('Discover the piano.', ['questions' => serialize([
            ['Q' => 'Listen to this phrase [storage/quiz/audio/phrase.mp3]', 'A' => ['Major[x]', 'Minor']],
            ['Q' => 'Name this note [storage/quiz/images/note.png]', 'A' => ['C', 'D[x]']],
        ])]);
        $response = $this->get('http://learn.localhost/quizzes/'.$quiz->slug)->assertOk()
            ->assertSee('Discover the piano')->assertSee('Start quiz')->assertSee('Your progress')->assertSee('Review my answers')
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
        $this->assertCount(1, $xpath->query('//section[@data-quiz-results and @hidden]'));
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
                ->assertOk()->assertSee('1 out of 2')->assertSee('data-quiz-score', false)->assertDontSee('giphy');
            $this->preview($response, 'feedback');
        }
        $this->assertSame(2, DB::table('quiz_results')->where('quiz_id', $quiz->id)->where('user_id', $user->id)->where('score', 1)->count());
        $this->assertSame(0, DB::table('quiz_results')->where('user_id', $other->id)->count());
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
