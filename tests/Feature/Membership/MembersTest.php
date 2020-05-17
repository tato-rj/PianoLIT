<?php

namespace Tests\Feature\Membership;

use Tests\AppTest;
use Tests\Traits\BillingResources;
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};

class MembersTest extends AppTest
{
    use BillingResources;

    /** @test */
    public function it_cannot_have_two_sources_at_the_same_time()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

        $this->assertTrue($this->stripeUser->membership()->exists());

        $this->postAppleMembership($this->stripeUser);
    }

    /** @test */
    public function it_cannot_add_a_new_source_even_if_the_other_one_is_expired()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\HttpException');

        $this->assertFalse(Membership::hasSourceFor(Apple::class, $this->stripeUser));

        $this->stripeUser->membership->source->update(['status' => 'canceled']);

        $this->postAppleMembership($this->stripeUser);

        $this->assertFalse(Membership::hasSourceFor(Apple::class, $this->stripeUser));
    }
}
