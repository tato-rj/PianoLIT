<?php

namespace Tests\Unit\Membership;

use Tests\AppTest;
use Tests\Traits\BillingResources;
use App\Billing\Sources\Apple;
use App\User;

class AppleTest extends AppTest
{
	use BillingResources;

	/** @test */
	public function it_cannot_have_two_apple_sources_at_the_same_time()
	{
		$this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

		$this->assertTrue($this->appleUser->membership()->exists());

        $this->postAppleMembership($this->appleUser);
	}

    /** @test */
    public function it_knows_how_to_subscribe_a_user()
    {
        $user = create(User::class);
     
        Apple::subscribe($user, requestWith(['receipt_data' => 'fake-data', 'password' => 'fake-pass']));

        $this->assertInstanceOf(Apple::class, $user->membership->source);
     
        $this->assertTrue($user->isAuthorized());
    }

	/** @test */
	public function it_knows_its_status()
	{
        $this->appleUser->membership->source->update(['renews_at' => now()->copy()->addDays(4)]);

        $this->assertTrue($this->appleUser->isOnTrial);

        $this->assertEquals('active', $this->appleUser->getStatus());

        $this->appleUser->membership->source->update(['renews_at' => now()->copy()->addDays(20)]);

        $this->assertFalse($this->appleUser->isOnTrial);

        $this->assertEquals('active', $this->appleUser->getStatus());

        $this->appleUser->membership->source->update(['renews_at' => now()->copy()->subDay()]);

        $this->assertFalse($this->appleUser->isOnTrial);
        
        $this->assertEquals('inactive', $this->appleUser->getStatus());
	}
}
