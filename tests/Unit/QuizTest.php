<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Admin;
use App\Quiz\{Quiz, QuizResult, Topic, Level};

class QuizTest extends AppTest
{
	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->quiz->creator);
	}

	/** @test */
	public function it_has_many_topics()
	{
		$this->assertInstanceOf(Topic::class, $this->quiz->topics->first());
	}

	/** @test */
	public function it_has_a_level()
	{
		$this->assertInstanceOf(Level::class, $this->quiz->level);
	}

	/** @test */
	public function it_has_many_questions_and_answers()
	{
		$quiz = create(Quiz::class);

		$this->assertCount(10, $quiz->questions);
	}

	/** @test */
	public function it_has_many_results()
	{
		$quiz = create(Quiz::class);
		$quiz->results()->create(['score' => 8]);

		$this->assertInstanceOf(QuizResult::class, $quiz->results->first());
	}

	/** @test */
	public function it_knows_if_a_question_has_audio()
	{
		$questions = [
			['Q' => 'Here is a question with audio [audio]', 'A' => ['Answer 1', 'Answer 2[x]']]
		];

		$quiz = create(Quiz::class, ['questions' => serialize($questions)]);

		$this->assertNotNull($quiz->questions()[0]['audio']);
		$this->assertFalse(strhas($quiz->questions()[0]['Q'], '['));
	}

	/** @test */
	public function it_fetches_the_correct_answer_for_a_given_question()
	{
		$quiz = create(Quiz::class);

		$this->assertEquals(1, $quiz->getAnswer(0));
	}

	/** @test */
	public function it_knows_how_to_evaluate_a_test()
	{
		$questions = [
			['Q' => 'Here is a question?', 'A' => ['Answer 1', 'Answer 2[x]']],
			['Q' => 'Here is another question?', 'A' => ['Answer 1[x]', 'Answer 2']]
		];

		$answers = [1,1];

		$quiz = create(Quiz::class, ['questions' => serialize($questions)]);

		$this->assertEquals([true, 1], $quiz->evaluate($answers)['results']);
		$this->assertEquals(1, $quiz->evaluate($answers)['score']);
	}

	/** @test */
	public function it_knows_how_to_compare_an_individual_result_with_all_other_results()
	{
		$quiz = create(Quiz::class);

		$quiz->results()->createMany([
			['score' => 6],
			['score' => 2]
		]);

		$this->assertEquals(40, $quiz->average);
	}
}
