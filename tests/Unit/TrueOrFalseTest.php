<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Games\TrueOrFalse\TrueOrFalse;

class TrueOrFalseTest extends AppTest
{
	/** @test */
	public function it_calculates_non_zero_results()
	{
		$score = 5;
		$count = 10;
		$results = (new TrueOrFalse)->evaluate($score, $count);

		$this->assertEquals($results['percentage'], 50);
	}
	
	/** @test */
	public function it_calculates_null_results()
	{
		$score = 0;
		$count = 10;
		$results = (new TrueOrFalse)->evaluate($score, $count);

		$this->assertEquals($results['percentage'], 0);
	}
}
