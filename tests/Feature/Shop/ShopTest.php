<?php

namespace Tests\Feature\Shop;

use Tests\AppTest;
use Tests\Traits\InteractsWithStripe;
use App\Billing\Sandbox\StripeSandbox;
use App\User;

class ShopTest extends AppTest
{
	use InteractsWithStripe;

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

        $this->postStripePurchase($this->ebook->purchaseRoute(), $coupon = false, $card = true);

        $this->assertTrue($this->user->customer->hasCard());

        $this->delete(route('shop.payment-method.remove'));

        $this->assertFalse($this->user->customer->fresh()->hasCard());
    }

    /** @test */
    public function a_card_is_automatically_updated_if_the_customer_uses_a_different_card()
    {
        $this->signIn($this->user);

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
}
