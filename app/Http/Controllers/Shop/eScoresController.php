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
        $response = response()->view('shop.storefront.show', $page->data($escore, $request));

        return $escore->isPublished() ? $response : $response->header('X-Robots-Tag', 'noindex');
    }

    public function topic(Request $request, eScoreTopic $topic)
    {
        return redirect()->route('escores.index', ['topics' => [$topic->slug]] + $request->except(['topics', 'page']), 301);
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
