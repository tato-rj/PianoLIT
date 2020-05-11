<?php

namespace App\Billing\Factories;

use Stripe\{Stripe, Customer, Subscription, Invoice};
use Stripe\Plan as StripePlan;
use App\Billing\Plan;

class StripeFactory
{
  public $customer, $subscription, $newMember;

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

  public function get()
  {
    return $this;
  }

	public function subscribe(Plan $plan, $stripeToken)
	{
    if ($this->newMember) {
      return Customer::create([
          'description' => auth()->user()->full_name,
          'email' => auth()->user()->email,
          'source' => $stripeToken,
          'plan' => $plan->name,
          'trial_from_plan' => true
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

    public function updateCollection()
    {
      return Subscription::update($this->subscription->id, 
        ['pause_collection' => ! is_null($this->subscription->pause_collection) ? '' : ['behavior' => 'void']]);
    }

    public function cancel()
    {
      return Subscription::update($this->subscription->id, ['cancel_at_period_end' => true]);
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
      $upcoming = Invoice::upcoming(["customer" => $this->customer->id]);

      return $upcoming;
    }

    public function pastInvoices()
    {
      $past = Invoice::all(['customer' => $this->customer->id]);

      return $past->data;
    }
}
