<?php

namespace Tests\Feature\Shop;

use Tests\AppTest;
use Tests\Traits\{InteractsWithStripe, BillingResources, ManageTime};
use App\Billing\Sandbox\StripeSandbox;
use App\Shop\eScore;
use App\User;

class ShopTest extends AppTest
{
	use InteractsWithStripe, BillingResources, ManageTime;

    /** @test */
    public function customers_cards_are_not_saved_unless_they_choose_to()
    {
        $userWithCard = create(User::class);
        $userWithoutCard = create(User::class);

        $this->signIn($userWithoutCard);

        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = false);
        
        $this->assertFalse($userWithoutCard->customer->hasCard());

        $this->signIn($userWithCard);

        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = true);
        
        $this->assertTrue($userWithCard->customer->hasCard());
    }

    /** @test */
    public function customers_can_remove_their_card_on_file()
    {
        $this->signIn($this->user);

        $this->postStripePurchase(create(eScore::class)->purchaseRoute());

        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = true);

        $this->assertTrue($this->user->customer->hasCard());

        $this->delete(route('shop.payment-method.remove'));

        $this->assertFalse($this->user->customer->fresh()->hasCard());
    }

    /** @test */
    public function a_card_is_automatically_updated_if_the_customer_uses_a_different_card()
    {
        $this->signIn($this->user);

        $this->postStripePurchase(create(eScore::class)->purchaseRoute());

        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = true);

        $this->assertTrue($this->user->customer->hasCard());

        $this->assertHasCard($this->user->customer->stripe_id, '4242');

        $this->assertEquals($this->user->customer->card_last_four, '4242');

        $this->post($this->escore->purchaseRoute(),
            [
                'stripeToken' => (new StripeSandbox)->token('5555555555554444'),
                'coupon' => null,
                'save_card' => true
            ]
        );

        $this->assertHasCard($this->user->customer->stripe_id, '4444');

        $this->assertEquals($this->user->customer->fresh()->card_last_four, '4444');
    }

    /** @test */
    public function a_card_is_not_automatically_updated_if_the_customer_uses_a_different_card_but_chooses_not_to_save_it()
    {
        $this->signIn($this->user);

        $this->postStripePurchase(create(eScore::class)->purchaseRoute());
        
        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = true);

        $this->assertTrue($this->user->customer->hasCard());

        $this->assertHasCard($this->user->customer->stripe_id, '4242');

        $this->assertEquals($this->user->customer->card_last_four, '4242');

        $this->post($this->escore->purchaseRoute(),
            [
                'stripeToken' => (new StripeSandbox)->token('5555555555554444'),
                'coupon' => null,
                'save_card' => false
            ]
        );

        $this->assertHasCard($this->user->customer->stripe_id, '4242');

        $this->assertEquals($this->user->customer->fresh()->card_last_four, '4242');
    }

    /** @test */
    public function a_customer_with_a_saved_card_can_charge_another_card_without_saving_it()
    {
        $this->signIn($this->user);

        $this->postStripePurchase(create(eScore::class)->purchaseRoute());

        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = true);

        $this->post($this->escore->purchaseRoute(),
            [
                'stripeToken' => (new StripeSandbox)->token('5555555555554444'),
                'coupon' => null,
                'save_card' => false
            ]
        );

        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertCardWasCharged($purchase->charge_id, '4444');
        
        $this->assertEquals($this->user->customer->fresh()->card_last_four, '4242');
    }

    /** @test */
    public function webapp_members_can_download_one_product_for_free_per_month()
    {
        $this->signIn($this->stripeUser);

        $this->assertTrue($this->stripeUser->membership->source->isPaying());

        $this->assertFalse($this->ebook->isFree());

        $this->postStripePurchase($this->ebook->purchaseRoute());

        $this->assertUserWasNotCharged(auth()->user()->purchases()->latest()->first());

        $this->assertCount(1, auth()->user()->fresh()->loyaltyDiscounts);

        $this->assertEquals(0, $this->stripeUser->purchases->first()->cost);

        $this->assertFalse($this->escore->isFree());

        $this->postStripePurchase($this->escore->purchaseRoute());

        $this->assertChargeSucceeded(auth()->user()->purchases()->latest()->first()->charge_id);

        $this->assertCount(1, auth()->user()->fresh()->loyaltyDiscounts);

        $this->setDate(now()->addMonth());

        $this->postStripePurchase(create(eScore::class)->purchaseRoute());

        $this->assertUserWasNotCharged(auth()->user()->purchases()->latest()->first());

        $this->assertCount(2, auth()->user()->fresh()->loyaltyDiscounts);

        $this->resetDate();
    }

    /** @test */
    public function ios_members_can_download_one_product_for_free_per_month()
    {
        $this->signIn($this->appleUser);

        $this->assertTrue($this->appleUser->membership->source->isPaying());

        $this->assertFalse($this->ebook->isFree());

        $this->postStripePurchase($this->ebook->purchaseRoute());

        $this->assertUserWasNotCharged(auth()->user()->purchases()->latest()->first());

        $this->assertCount(1, auth()->user()->fresh()->loyaltyDiscounts);

        $this->assertEquals(0, $this->appleUser->purchases->first()->cost);

        $this->assertFalse($this->escore->isFree());

        $this->postStripePurchase($this->escore->purchaseRoute());

        $this->assertChargeSucceeded(auth()->user()->purchases()->latest()->first()->charge_id);

        $this->assertCount(1, auth()->user()->fresh()->loyaltyDiscounts);

        $this->setDate(now()->addMonth());

        auth()->user()->membership->source()->update(['renews_at' => now()->addMonth()]);

        $this->assertTrue($this->appleUser->membership->fresh()->source->isPaying());

        $this->postStripePurchase(create(eScore::class)->purchaseRoute());

        $this->assertUserWasNotCharged(auth()->user()->purchases()->latest()->first());

        $this->assertCount(2, auth()->user()->fresh()->loyaltyDiscounts);

        $this->resetDate();
    }

    /** @test */
    public function visitors_know_about_the_discount_for_members_on_the_product_page()
    {
        $this->assertFalse(auth()->check());

        $this->get($this->ebook->showRoute())
             ->assertSee('Become a member');
    }

    /** @test */
    public function non_member_users_know_about_the_discount_for_members_on_the_product_page()
    {
        $this->signIn(create(User::class));

        $this->assertTrue(auth()->check());

        $this->assertFalse(auth()->user()->membership()->exists());

        $this->assertFalse(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->ebook->showRoute())
             ->assertSee('Become a member');
    }

    /** @test */
    public function users_on_trial_know_when_their_free_download_will_be_available()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        $this->assertTrue(auth()->user()->membership->source->isOnTrial());

        $this->assertFalse(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->ebook->showRoute())
             ->assertSee(auth()->user()->membership->source->renews_at->diffForHumans());
    }

    /** @test */
    public function users_who_cancel_their_trial_know_about_the_discount_for_members_on_the_product_page()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        auth()->user()->membership->source->renews_at = null;
        auth()->user()->membership->source->canceled_at = now();
        auth()->user()->membership->source->membership_ends_at = now()->addWeek();
        auth()->user()->membership->source->save();

        $this->assertTrue(auth()->user()->membership->source->isOnTrial());

        $this->assertTrue(auth()->user()->membership->source->isCanceled());

        $this->assertFalse(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->ebook->showRoute())
             ->assertSee('Become a member');
    }

    /** @test */
    public function users_whose_membership_expired_know_about_the_discount_for_members_on_the_product_page()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        auth()->user()->membership->source->renews_at = null;
        auth()->user()->membership->source->canceled_at = now();
        auth()->user()->membership->source->ended_at = now();
        auth()->user()->membership->source->status = 'canceled';
        auth()->user()->membership->source->save();

        $this->assertFalse(auth()->user()->fresh()->membership->source->isOnTrial());

        $this->assertTrue(auth()->user()->fresh()->membership->source->isEnded());

        $this->assertFalse(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->ebook->showRoute())
             ->assertSee('Become a member');
    }

    /** @test */
    public function paying_members_can_redeem_one_free_download_and_after_they_see_how_many_days_left_for_the_next_one()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        auth()->user()->membership->source->renews_at = now()->addMonth();
        auth()->user()->membership->source->status = 'active';
        auth()->user()->membership->source->save();

        $this->assertFalse(auth()->user()->fresh()->membership->source->isOnTrial());

        $this->assertTrue(auth()->user()->fresh()->membership->source->isPaying());

        $this->assertTrue(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->ebook->showRoute())
             ->assertSee('Hooray');

        $this->postStripePurchase($this->ebook->purchaseRoute());

        $this->assertUserWasNotCharged(auth()->user()->purchases()->latest()->first());

        $this->assertFalse(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->escore->showRoute())
             ->assertSee(auth()->user()->loyaltyDiscounts()->latest()->first()->availableIn());
    }

    /** @test */
    public function members_on_grace_period_can_still_redeem_a_free_download_if_eligible()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        auth()->user()->membership->source->renews_at = null;
        auth()->user()->membership->source->status = 'active';
        auth()->user()->membership->source->canceled_at = now();
        auth()->user()->membership->source->membership_ends_at = now()->addWeek();
        auth()->user()->membership->source->save();

        $this->assertFalse(auth()->user()->fresh()->membership->source->isOnTrial());

        $this->assertTrue(auth()->user()->fresh()->membership->source->isPaying());

        $this->assertTrue(auth()->user()->isEligibleForFreeMonthlyProduct());

        $this->get($this->ebook->showRoute())
             ->assertSee('Hooray');

        $this->postStripePurchase($this->ebook->purchaseRoute());

        $this->assertUserWasNotCharged(auth()->user()->purchases()->latest()->first());

        $this->assertFalse(auth()->user()->isEligibleForFreeMonthlyProduct());
        
        $this->get($this->escore->showRoute())
             ->assertSee(auth()->user()->loyaltyDiscounts()->latest()->first()->availableIn());
    }
}
