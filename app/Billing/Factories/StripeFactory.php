<?php

namespace App\Billing\Factories;

use Stripe\{Stripe, Customer, Subscription, Invoice, Coupon, Charge};
use Stripe\Plan as StripePlan;
use App\Billing\Plan;
use App\Billing\Sources\Concerns\StripeJurisdiction;

class StripeFactory
{
  public $customer, $subscription, $newMember, $coupon, $saveCard, $token, $quickCheckout;

	public function __construct()
	{
    if (auth()->user()->isSwissCustomer()) {
      (new StripeJurisdiction)->swiss();
    } else {
      (new StripeJurisdiction)->us();
    }

    Stripe::setApiKey(config('services.stripe.secret'));
    Stripe::setApiVersion(config('services.stripe.version'));
	}

  public function transaction($token)
  {
    $this->token = $token;

    if (auth()->user()->customer()->exists()) {
      $this->customer = Customer::retrieve(auth()->user()->customer->stripe_id);

      if ($this->token && ! $this->saveCard)
        $this->quickCheckout = true;

      if ($this->saveCard)
        $this->updateCard($this->token);
    } else {
      $this->customer = Customer::create([
                            'description' => auth()->user()->full_name,
                            'email' => auth()->user()->email,
                            'source' => $this->token]);


      auth()->user()->customer()->create([
        'stripe_id' => $this->customer->id,
        'card_brand' => $this->saveCard ? $this->customer->sources->data[0]->brand : null,
        'card_last_four' => $this->saveCard ? $this->customer->sources->data[0]->last4 : null]);
    }

    return $this;
  }

  public function customer()
  {
    $this->newMember = ! auth()->user()->hasMembershipWith('App\Billing\Sources\Stripe');

    $this->customer = $this->newMember ? null : Customer::retrieve(auth()->user()->membership->source->stripe_id);

    return $this;
  }

  public function charge($item)
  {
    return Charge::create([
      'customer' => $this->quickCheckout ? null : $this->customer->id,
      'source' => $this->quickCheckout ? $this->token : null,
      'amount' => $this->calculatePrice($item),
      'currency' => 'usd',
    ]);
  }

  public function subscription()
  {
      $this->subscription = Subscription::retrieve($this->customer->subscriptions->data[0]->id);

      return $this;
  }

  public function calculatePrice($item)
  {
    $priceInCents = $item->finalPrice($inCents = true);

    if (! $this->coupon)
      return $priceInCents;

    return $priceInCents - $this->percentage($priceInCents, $this->coupon->percent_off);
  }

  public function percentage($num, $percent)
  {
    return (int)round(($num * $percent) / 100);
  }

  public function withCoupon($id = null)
  {
    if (! $id)
      return $this;

    if ($coupon = $this->getCoupon($id))
      $this->validateCoupon($coupon);

    return $this;
  }

  public function getCoupon($id)
  {
    return Coupon::retrieve($id);
  }

  public function validateCoupon(Coupon $coupon)
  {
    if ($coupon->valid)
      $this->coupon = $coupon;
  }

	public function subscribe(Plan $plan, $stripeToken)
	{
    if ($this->newMember) {
      return Customer::create([
          'description' => auth()->user()->full_name,
          'email' => auth()->user()->email,
          'source' => $stripeToken,
          'plan' => $plan->name,
          'trial_from_plan' => true,
          'coupon' => $this->coupon
      ]);
    }

    $this->updateCard($stripeToken);

    Subscription::create([
      'customer' => $this->customer->id,
      'items' => [['plan' => $plan->name]],
    ]);

    return Customer::retrieve($this->customer->id);
	}

    public function updatePlan($plan)
    {
      Subscription::update($this->subscription->id, [
        'cancel_at_period_end' => false,
        'proration_behavior' => 'create_prorations',
        'items' => [
          [
            'id' => $this->subscription->items->data[0]->id,
            'plan' => $plan,
          ],
        ],
      ]);
    }

    public function updateBillingStatus()
    {
      return Subscription::update($this->subscription->id, 
        ['pause_collection' => ! is_null($this->subscription->pause_collection) ? '' : ['behavior' => 'void']]);
    }

    public function cancel()
    {
      return Subscription::update($this->subscription->id, ['cancel_at_period_end' => true]);
    }

    public function resume()
    {
      return Subscription::update($this->subscription->id, ['cancel_at_period_end' => false]);
    }

    public function withCard($saveCard)
    {
      $this->saveCard = $saveCard;

      return $this;
    }

    public function getCardsFor($customerId)
    {
      $customer = Customer::retrieve($customerId);

       return $customer->sources->data;
    }

    public function deleteCard()
    {
      if ($this->customer->sources->total_count > 0) {
        Customer::deleteSource(
          $this->customer->id,
          $this->customer->sources->data[0]->id
        );
      }
    }

    public function createCard($stripeToken)
    {
      return Customer::createSource(
        $this->customer->id,
        ['source' => $stripeToken]
      );
    }

    public function updateCard($stripeToken)
    {
      $this->deleteCard();

      $card = $this->createCard($stripeToken);

      return auth()->user()->customer()->update([
        'card_brand' => $card->brand,
        'card_last_four' => $card->last4]);
    }

    public function upcomingInvoice()
    {
      try {
        return Invoice::upcoming(["customer" => $this->customer->id]);     
      } catch (\Exception $e) {
        return null;
      }
    }

    public function pastInvoices()
    {
      $past = Invoice::all(['customer' => $this->customer->id]);

      return $past->data;
    }

    public function getCharge($chargeId)
    {
      return Charge::retrieve($chargeId, []);
    }
}
