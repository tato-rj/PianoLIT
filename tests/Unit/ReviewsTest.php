<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Review;

class ReviewsTest extends AppTest
{
	/** @test */
	public function it_knows_its_average_rating()
	{
		create(Review::class, ['rating' => 5]);

		$a = 3;
		$b = 5;
		$c = 5;

		create(Review::class, ['rating' => $a, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);
		create(Review::class, ['rating' => $b, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);
		create(Review::class, ['rating' => $c, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);

		$rating = $this->ebook->reviews()->ratings();

		$this->assertTrue($rating > 4.0 && $rating < 5.0);
	}
}
