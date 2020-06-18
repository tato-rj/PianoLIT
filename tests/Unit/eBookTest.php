<?php

namespace Tests\Unit;

use App\Shop\{eBook, eBookTopic};
use App\Merchandise\Purchase;
use Tests\AppTest;

class eBookTest extends AppTest
{
	/** @test */
	public function it_has_many_topics()
	{
		$this->assertInstanceOf(eBookTopic::class, $this->ebook->topics->first());
	}
}
