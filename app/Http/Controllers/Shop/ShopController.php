<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Billing\Factories\StripeFactory;

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
}
