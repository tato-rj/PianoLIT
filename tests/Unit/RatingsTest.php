<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\{Rating, User};

class RatingsTest extends AppTest
{
    /** @test */
    public function it_belongs_to_a_user()
    {
        $rating = create(Rating::class, ['user_id' => $this->user]);

        $this->assertInstanceOf(User::class, $rating->user);
    }
}
