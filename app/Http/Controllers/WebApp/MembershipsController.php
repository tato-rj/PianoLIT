<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Events\Memberships\NewTrial;
use App\Billing\{Membership, Plan};
use App\Billing\Sources\Stripe as StripeMembership;
use App\Billing\Sources\Apple;
use App\Http\Requests\StripeMembershipForm;
use App\Billing\Factories\StripeFactory;

class MembershipsController extends Controller
{
    public function pricing()
    {
        $plans = Plan::all()->reverse();

    	return view('webapp.membership.pricing.index', compact('plans'));
    }

    public function edit()
    {
        if (! auth()->user()->membership()->exists() || auth()->user()->membership->source->isEnded())
            return redirect(route('webapp.membership.pricing'));

        if (auth()->user()->hasMembershipWith(Apple::class))
            return view('webapp.membership.edit.apple');

        $plans = Plan::all()->reverse();
        
        return view('webapp.membership.edit.index', compact('plans'));
    }

    public function checkout(Plan $plan)
    {
        return view('webapp.membership.checkout.index', compact('plan'));
    }

    public function purchase(Request $request, Plan $plan, StripeMembershipForm $form)
    {
        try {
            $customer = (new StripeFactory)->customer()->withCoupon(strtoupper($form->coupon))->subscribe($plan, $form->stripeToken);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        
        StripeMembership::subscribe(auth()->user(), $customer);

        event(new NewTrial(auth()->user()));

        return redirect(route('webapp.membership.success'))->with('valid-success', true);
    }

    public function validateCoupon(Request $request)
    {
        try {
            $coupon = (new StripeFactory)->getCoupon(strtoupper($request->coupon));
        } catch (\Exception $e) {
            return response()->json(['isValid' => false, 'message' => 'Sorry, this coupon was not found.']);
        }

        $response = $coupon->valid ? 
                    ['isValid' => true, 'message' => 'The coupon is valid, you\'re good to go! You\'ll get a discount of ' . $coupon->name . '.'] : 
                    ['isValid' => false, 'message' => 'Sorry, this coupon is no longer valid.'];

        return response()->json($response);
    }

    public function success()
    {
        if (session()->has('valid-success'))
            return view('webapp.membership.checkout.success');

        return redirect(route('webapp.discover'));
    }

    public function updatePlan(Request $request)
    {
        try {
            (new StripeFactory)->customer()->subscription()->updatePlan($request->plan);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        auth()->user()->membership->source->update(['plan' => $request->plan]);

        return back()->with('status', 'Your plan has been successfully updated');
    }


    public function updateCard(Request $request)
    {
        try {
            $card = (new StripeFactory)->customer()->updateCard($request->stripeToken);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        auth()->user()->membership->source->update(['card_brand' => $card->brand, 'card_last_four' => $card->last4]);

        return back()->with('status', 'Your card has been successfully updated');
    }

    public function deleteCard(Request $request)
    {
        try {
            (new StripeFactory)->customer()->deleteCard();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        auth()->user()->membership->source->update(['card_brand' => null, 'card_last_four' => null]);

        return back()->with('status', 'Your card has been successfully deleted');
    }   

    public function updateBillingStatus()
    {
        try {
            $response = (new StripeFactory)->customer()->subscription()->updateBillingStatus();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        auth()->user()->membership->source->updateBillingStatus($response->pause_collection);

        $status = auth()->user()->membership->source->paused_at ? 'stopped' : 'resumed';

        return back()->with('status', 'Your membership has been successfully ' . $status);
    }

    public function cancel()
    {
        try {
            $payload = (new StripeFactory)->customer()->subscription()->cancel();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        auth()->user()->membership->source->cancelAtPeriodEnd($payload);

        return back()->with('status', 'Your membership has been successfully canceled');
    }

    public function resume()
    {
        try {
            $payload = (new StripeFactory)->customer()->subscription()->resume();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        auth()->user()->membership->source->updateStatus($payload);

        return back()->with('status', 'Your membership has been successfully resumed');
    }
}
