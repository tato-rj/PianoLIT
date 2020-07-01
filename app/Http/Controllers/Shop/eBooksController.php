<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\eBook;
use Illuminate\Http\Request;
use App\Billing\Factories\StripeFactory;

class eBooksController extends Controller
{
    public function index()
    {
    	$ebooks = eBook::published()->get();

    	return view('shop.ebooks.index', compact('ebooks'));
    }

    public function show(eBook $ebook)
    {
    	return view('shop.ebooks.show', compact('ebook'));    	
    }

    public function checkout(eBook $ebook)
    {
        return view('shop.checkout.index', compact('ebook'));
    }

    public function purchase(Request $request, eBook $ebook)
    {
        $chargeId = null;

    	if (! $ebook->isFree()) {
	        try {
	            $chargeId = (new StripeFactory)->transaction($request->stripeToken)->withCoupon(strtoupper($request->coupon))->charge($ebook)->id;
	        } catch (\Exception $e) {
	            return back()->with('error', $e->getMessage());
	        }
	    }

        auth()->user()->purchase($ebook, $chargeId);

        return redirect('');
    }
}
