<?php

namespace Tests\Unit;

use App\User;
use Tests\AppTest;
use App\Payments\Sources\Apple;
use App\Services\Apple\Sandbox\Membership as AppleFakeMembership;

class MembershipTest extends AppTest
{
	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->membership->user);
	}

	/** @test */
	public function it_has_a_source()
	{
		$this->assertInstanceOf(Apple::class, $this->membership->source);
	}

	/** @test */
	public function it_cannot_have_two_sources_at_the_same_time()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$this->assertTrue($this->user->membership()->exists());

        $this->postMembership($this->user, new AppleFakeMembership);
	}

	/** @test */
	public function an_apple_member_know_its_status()
	{
		$user = create(User::class);

        $this->postMembership($user, new AppleFakeMembership);

        $user->membership->source->update(['renews_at' => now()->copy()->addDays(4)]);

        $this->assertTrue($user->isOnTrial);
        $this->assertEquals('active', $user->getStatus());

        $user->membership->source->update(['renews_at' => now()->copy()->addDays(20)]);

        $this->assertFalse($user->isOnTrial);
        $this->assertEquals('active', $user->getStatus());

        $user->membership->source->update(['renews_at' => now()->copy()->subDay()]);

        $this->assertFalse($user->isOnTrial);
        $this->assertEquals('inactive', $user->getStatus());
	}
}
