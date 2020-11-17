<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Merchandise\Purchase;
use App\Billing\Factories\StripeFactory;
use App\Shop\Contract\Merchandise;
use App\Http\Requests\PurchaseForm;

class ShopController extends Controller
{
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

    public function removeCard()
    {
        if (! auth()->user()->customer()->exists())
            return back()->with('error', 'We don\'t have a card from you on file');

        auth()->user()->customer()->update(['card_last_four' => null, 'card_brand' => null]);

        return back()->with('status', 'Your card has been removed');
    }

    public function download(Request $request, Purchase $purchase)
    {
        try {
            return $purchase->download(decrypt($request->path));   
        } catch (\Exception $e) {
            return back()->with('error', 'Your file could not be downloaded at this time. If this problem persists, please let us know at contact@pianolit.com');
        }
    }

    public function purchase(PurchaseForm $form, $model, $reference)
    {
        $chargeId = null;

        if (! $form->product->isFree()) {
            try {
                $chargeId = (new StripeFactory)->withCard($form->save_card)
                                               ->withCoupon(strtoupper($form->coupon))
                                               ->transaction($form->stripeToken)
                                               ->charge($form->product)->id;
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        
        $purchase = auth()->user()->purchase($form->product, $chargeId);

        return redirect(route('shop.success', ['purchase' => $purchase, 'type' => $form->product->isFree() ? 'free' : 'purchase']));
    }

    public function success(Purchase $purchase)
    {
        if (! auth()->user()->purchasesOf($purchase->item)->exists())
            return redirect($purchase->item->checkoutRoute());

        return view('shop.success.index', compact('purchase'));
    }
}
