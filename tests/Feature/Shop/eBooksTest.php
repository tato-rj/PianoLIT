<?php

namespace Tests\Feature\Shop;

use Tests\AppTest;
use Tests\Traits\InteractsWithStripe;
use App\Shop\eBook;
use App\User;

class eBooksTest extends AppTest
{
	use InteractsWithStripe;

    /** @test */
    public function an_ebook_purchase_hits_stripe_api_and_charges_the_customer()
    {
    	$this->signIn($this->user);

        $this->postStripePurchase(route('ebooks.purchase', $this->ebook));
		
		$purchase = auth()->user()->purchases()->latest()->first();

		$this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function a_membership_customer_does_not_conflict_with_a_regular_customer()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();
         
        $this->postStripePurchase(route('ebooks.purchase', $this->ebook));
        
        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertTrue($user->hasMembershipWith('App\Billing\Sources\Stripe'));
        
        $this->assertChargeSucceeded($purchase->charge_id);
    }
}
