<?php

namespace Tests\Feature\Membership;

use Tests\AppTest;
use Tests\Traits\{BillingResources, InteractsWithStripe};
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};
use App\User;

class MembersTest extends AppTest
{
    use BillingResources, InteractsWithStripe;

    /** @test */
    public function it_cannot_have_two_sources_at_the_same_time()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->assertTrue($this->stripeUser->membership()->exists());

        $this->postAppleMembership($this->stripeUser);
    }

    /** @test */
    public function a_stripe_member_cannot_add_a_new_source_even_if_the_other_one_is_expired()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->assertFalse(Membership::hasSourceFor(Apple::class, $this->stripeUser));

        $this->stripeUser->membership->source->update(['status' => 'canceled', 'canceled_at' => now()]);

        $this->postAppleMembership($this->stripeUser);

        $this->assertFalse(Membership::hasSourceFor(Apple::class, $this->stripeUser));
    }

    /** @test */
    public function when_opening_a_piece_it_is_redirected_to_their_membership_settings_page_if_paused()
    {
        $this->withExceptionHandling();

        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership(auth()->user());

        auth()->user()->membership->source->update(['status' => 'paused', 'paused_at' => now()]);

        $this->get(route('webapp.pieces.show', $this->piece))->assertRedirect(route('webapp.membership.edit'));
    }

    /** @test */
    public function when_opening_a_piece_it_is_redirected_to_the_pricing_page_if_canceled()
    {
        $this->withExceptionHandling();

        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership(auth()->user());

        auth()->user()->membership->source->update(['status' => 'canceled', 'canceled_at' => now()]);

        $this->get(route('webapp.pieces.show', $this->piece))->assertRedirect(route('webapp.membership.pricing'));
    }
}
