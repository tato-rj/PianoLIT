<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\{eScore, eScoreTopic};
use Illuminate\Http\Request;
use App\Billing\Factories\StripeFactory;
use App\Services\Shop\{ProductPage, Storefront, MerchandiseBag};

class eScoresController extends Controller
{
    public function index(Request $request, Storefront $catalog, MerchandiseBag $bag)
    {
        return app(MerchandiseController::class)->index($request, $catalog, $bag, 'escores');
    }

    public function show(Request $request, eScore $escore, ProductPage $page)
    {
        return view('shop.storefront.show', $page->data($escore, $request));
    }

    public function checkout(eScore $escore, Request $request)
    {
        return app(CheckoutController::class)->digital($request, $escore, 'escores');
    }

    public function success(eScore $escore)
    {
        if (! auth()->user()->purchasesOf($escore)->exists())
            return redirect($escore->checkoutRoute());

        return view('shop.success.index', ['product' => $escore]);        
    }
}
