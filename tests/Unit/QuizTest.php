<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Quiz;

class QuizTest extends AppTest
{
	/** @test */
	public function it_has_many_questions_and_answers()
	{
		$quiz = create(Quiz::class);

		$this->assertCount(1, $quiz->questions);
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
}
