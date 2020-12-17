<?php

namespace Tests\Feature;

use Tests\AppTest;

class ReviewsTest extends AppTest
{
    /** @test */
    public function unauthorized_users_cannot_review_products()
    {
        //
    }

    /** @test */
    public function authorized_users_can_review_products()
    {
        //
    }

    /** @test */
    public function a_user_cannot_review_the_same_product_twice()
    {
        //
    }

    /** @test */
    public function an_ebook_can_have_many_reviews()
    {
        $this->assertFalse($this->ebook->reviews()->exists());

        $this->ebook->reviews()->create(['rating' => 5]);

        $this->assertCount(1, $this->ebook->reviews);

        $this->ebook->reviews()->create(['rating' => 5]);

        $this->assertCount(2, $this->ebook->fresh()->reviews);
    }
}
