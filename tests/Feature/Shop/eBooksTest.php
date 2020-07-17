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
    public function only_authorized_users_can_purchase_an_ebook()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');
        
        $this->postStripePurchase(route('ebooks.purchase', $this->ebook));         
    }

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

    /** @test */
    public function a_user_receives_an_email_confirming_the_purchase_of_an_ebook_that_also_contains_the_url_to_download_the_product()
    {
        \Mail::fake();

        $this->signIn($this->user);

        $this->postStripePurchase(route('ebooks.purchase', $this->ebook));

        \Mail::assertQueued(ConfirmPurchase::class, 2);
    }
}
