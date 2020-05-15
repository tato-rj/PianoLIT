<?php

namespace App\Billing\Factories;

use Stripe\{Stripe, Customer, Subscription, Invoice, Coupon};
use Stripe\Plan as StripePlan;
use App\Billing\Plan;

class StripeFactory
{
  public $customer, $subscription, $newMember, $coupon;

	public function __construct()
	{
    Stripe::setApiKey(config('services.stripe.secret'));
	}

  public function customer()
  {
    $this->newMember = ! auth()->user()->hasMembershipWith('App\Billing\Sources\Stripe');

    $this->customer = $this->newMember ? null : Customer::retrieve(auth()->user()->membership->source->stripe_id);

    return $this;
  }

  public function subscription()
  {
      $this->subscription = Subscription::retrieve($this->customer->subscriptions->data[0]->id);

      return $this;
  }

  public function withCoupon($coupon = null)
  {
    $this->coupon = $coupon;

    return $this;
  }

  public function getCoupon($id)
  {
    return Coupon::retrieve($id);
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

      return $this->createCard($stripeToken);
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
}
