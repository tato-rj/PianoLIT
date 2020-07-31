<?php

namespace Tests\Feature\Shop;

use Tests\AppTest;
use Tests\Traits\InteractsWithStripe;
use App\Shop\eScore;
use App\Mail\Shop\ConfirmPurchase;
use App\Notifications\NewPurchaseCompleted;
use App\User;

class eScoresTest extends AppTest
{
	use InteractsWithStripe;

    /** @test */
    public function only_authorized_users_can_purchase_an_escore()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');
        
        $this->postStripePurchase($this->escore->purchaseRoute());         
    }

    /** @test */
    public function an_escore_purchase_hits_stripe_api_and_charges_the_customer()
    {
    	$this->signIn($this->user);

        $this->postStripePurchase($this->escore->purchaseRoute());
		
		$purchase = auth()->user()->purchases()->latest()->first();

		$this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function a_member_can_make_purchases()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();
         
        $this->postStripePurchase($this->escore->purchaseRoute());
        
        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertTrue($user->hasMembershipWith('App\Billing\Sources\Stripe'));
        
        $this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function a_customer_can_become_a_member()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripePurchase($this->escore->purchaseRoute());
        
        $this->postStripeMembership();
        
        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertTrue($user->hasMembershipWith('App\Billing\Sources\Stripe'));
        
        $this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function admins_are_notified_when_a_purchase_has_been_made()
    {
        \Notification::fake();

        $this->signIn($this->user);

        $this->postStripePurchase($this->escore->purchaseRoute());

        \Notification::assertSentTo($this->admin, NewPurchaseCompleted::class);
    }

    /** @test */
    public function a_user_receives_an_email_confirming_the_purchase_of_an_escore_that_also_contains_the_url_to_download_the_product()
    {
        \Mail::fake();

        $this->signIn($this->user);

        $this->postStripePurchase($this->escore->purchaseRoute());

        \Mail::assertSent(ConfirmPurchase::class);
    }

    /** @test */
    public function a_user_does_not_receive_a_confirmation_email_if_the_product_is_free()
    {
        \Mail::fake();

        $escore = create(eScore::class, ['price' => 0]);

        $this->signIn($this->user);

        $this->postStripePurchase($escore->purchaseRoute());

        \Mail::assertNotSent(ConfirmPurchase::class);
    }
}
