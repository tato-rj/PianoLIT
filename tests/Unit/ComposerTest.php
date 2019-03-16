<?php

namespace Tests\Unit;

use App\{Composer, Admin};
use Tests\AppTest;

class ComposerTest extends AppTest
{
	/** @test */
	public function it_knows_who_added_it_to_the_database()
	{
		$composer = create(Composer::class);

		$this->assertInstanceOf(Admin::class, $composer->creator);
	}
}
