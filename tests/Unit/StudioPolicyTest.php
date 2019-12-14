<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\{User, StudioPolicy};

class StudioPolicyTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$this->studio_policy = create(StudioPolicy::class, ['user_id' => $this->user->id]);
    }
    
	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->studio_policy->user);
	}
}
