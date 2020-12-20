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

		$this->assertTrue($this->ebook->reviews()->ratings() == 4.3);
	}

	/** @test */
	public function it_knows_how_to_find_reviews_by_rating()
	{
		create(Review::class, ['rating' => 5, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);
		create(Review::class, ['rating' => 5, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);
		create(Review::class, ['rating' => 2, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);
		 
		$this->assertCount(2, $this->ebook->reviews()->byRating(5)->get());
	}

	/** @test */
	public function a_user_knows_if_it_has_a_review_for_a_given_product()
	{
		$this->assertNull($this->user->reviewFor($this->ebook));

		create(Review::class, ['user_id' => $this->user->id, 'rating' => 5, 'reviewable_type' => get_class($this->ebook), 'reviewable_id' => $this->ebook->id]);

		$this->assertNotNull($this->user->reviewFor($this->ebook));
	}
}
