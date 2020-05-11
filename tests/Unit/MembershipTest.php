<?php

namespace Tests\Unit;

use App\User;
use Tests\AppTest;
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};
use App\Services\Apple\Sandbox\Membership as AppleFakeMembership;
use App\Services\Stripe\StripeSandbox;
use Illuminate\Http\Request;

class MembershipTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
		
		$stripe = create(Stripe::class);
		$this->stripeMembership = create(Membership::class, ['source_id' => $stripe->id, 'source_type' => get_class($stripe)]);
		$this->stripeUser = $this->stripeMembership->user;
    }

	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->membership->user);
	}

	/** @test */
	public function it_has_a_source()
	{
		$this->assertInstanceOf(Apple::class, $this->membership->source);

		$this->assertInstanceOf(Stripe::class, $this->stripeMembership->source);
	}

	/** @test */
	public function it_cannot_have_two_apple_sources_at_the_same_time()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$this->assertTrue($this->user->membership()->exists());

        $this->postMembership($this->user, new AppleFakeMembership);
	}

	/** @test */
	public function it_cannot_have_two_active_sources_at_the_same_time()
	{
		$this->expectException('Illuminate\Auth\Access\AuthorizationException');

		$this->assertTrue($this->stripeUser->membership()->exists());

        $this->postMembership($this->stripeUser, new AppleFakeMembership);
	}

	/** @test */
	public function it_can_add_a_new_source_only_if_the_other_one_is_expired()
	{
		$this->assertFalse(Membership::hasSourceFor(Apple::class, $this->stripeUser));

        $this->stripeUser->membership->source->update(['status' => 'canceled']);

        $this->postMembership($this->stripeUser, new AppleFakeMembership);

		$this->assertTrue(Membership::hasSourceFor(Apple::class, $this->stripeUser));
	}

	/** @test */
	public function it_knows_if_it_has_a_source_for_a_specific_user()
	{
		$this->assertTrue(Membership::hasSourceFor(Apple::class, $this->user));
	}

	/** @test */
	public function a_user_can_subscribe_from_any_source()
	{
		$appleUser = create(User::class);
		Apple::subscribe($appleUser, new Request(['receipt_data' => 'fake-data', 'password' => 'fake-pass']));

		$stripeUser = create(User::class);
		Stripe::subscribe($stripeUser, (new StripeSandbox)->getCustomer());

		$this->assertInstanceOf(Apple::class, $appleUser->membership->source);
		$this->assertTrue($appleUser->isAuthorized());

		$this->assertInstanceOf(Stripe::class, $stripeUser->membership->source);
		$this->assertTrue($stripeUser->isAuthorized());
	}

	/** @test */
	public function an_apple_member_knows_its_status()
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
