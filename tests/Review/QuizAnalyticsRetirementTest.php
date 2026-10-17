<?php

namespace Tests\Review;

use App\Admin;
use App\Quiz\Quiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class QuizAnalyticsRetirementTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
    }

    protected function quiz(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) {
            return create(Quiz::class, array_merge(['title' => 'Published music quiz', 'views' => 987654321, 'published_at' => now(),
                'questions' => serialize([['Q' => 'Which instrument has keys?', 'A' => ['Piano[x]', 'Violin']]])], $attributes));
        });
    }

    public function test_retired_quiz_analytics_is_unavailable_and_quiz_management_remains()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $this->withExceptionHandling()->get('http://admin.localhost/analytics/quizzes')->assertNotFound();
        $this->assertNull(app('router')->getRoutes()->getByName('admin.analytics.quizzes'));
        $response = $this->get(route('admin.analytics.composers'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(6, $xpath->query('//ul[@id="analytics"]//a')->length);
        $this->assertSame(1, $xpath->query('//ul[@id="analytics"]//a[@href="'.route('admin.analytics.campaigns').'"]')->length);
        $this->assertSame(0, $xpath->query('//ul[@id="analytics"]//a[contains(@href, "/analytics/quizzes")]')->length);
        $this->assertSame(1, $xpath->query('//ul[@id="quizzes"]//a[@href="'.route('admin.quizzes.index').'"]')->length);
        $this->get(route('admin.quizzes.edit', $this->quiz()->slug))->assertOk()->assertSee('Published music quiz');
    }

    public function test_quiz_page_reads_no_longer_increment_or_display_views()
    {
        $quiz = $this->quiz();
        $this->assertTrue(traffic()->isRealVisitor());
        for ($visit = 0; $visit < 2; $visit++) {
            $this->get(route('quizzes.show', $quiz->slug))->assertOk()->assertSee('Published music quiz')
                ->assertSee('Which instrument has keys?')->assertSee('start-quiz')->assertDontSee('987654321');
            $this->assertSame(987654321, (int) DB::table('quizzes')->where('id', $quiz->id)->value('views'));
        }
        $this->get(route('quizzes.index'))->assertOk()->assertSee('Published music quiz');
    }

    public function test_quiz_feedback_still_scores_and_records_completion_without_incrementing_views()
    {
        $quiz = $this->quiz();
        $this->get(route('quizzes.feedback', ['quiz' => $quiz->slug, 'answers' => [0]]))->assertOk()->assertSee('You scored')->assertSee('1 out of 1');
        $this->assertSame(1, DB::table('quiz_results')->where('quiz_id', $quiz->id)->where('score', 1)->count());
        $this->assertSame(987654321, (int) DB::table('quizzes')->where('id', $quiz->id)->value('views'));
    }

    public function test_draft_quiz_retains_admin_preview_and_guest_restriction()
    {
        $quiz = $this->quiz(['published_at' => null]);
        $this->withExceptionHandling()->get(route('quizzes.show', $quiz->slug))->assertNotFound();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('quizzes.show', $quiz->slug))->assertOk()->assertSee('not published')->assertDontSee('987654321');
        $this->assertSame(987654321, (int) DB::table('quizzes')->where('id', $quiz->id)->value('views'));
    }
}
