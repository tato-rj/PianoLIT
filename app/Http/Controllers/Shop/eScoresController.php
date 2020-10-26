<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\{eScore, eScoreTopic};
use App\Filters\ProductFilters;
use Illuminate\Http\Request;
use App\Billing\Factories\StripeFactory;

class eScoresController extends Controller
{
    public function index(ProductFilters $filters)
    {
    	$products = eScore::published()->filter($filters)->paginate(12);
        $topics = eScoreTopic::has('escores')->get();

    	return view('shop.products.escores', compact(['products', 'topics']));
    }

    public function show(eScore $escore)
    {
    	return view('shop.products.show.index', ['product' => $escore]);    	
    }

    public function checkout(eScore $escore)
    {
        return view('shop.checkout.index', ['product' => $escore]);
    }

    public function success(eScore $escore)
    {
        if (! auth()->user()->purchasesOf($escore)->exists())
            return redirect($escore->checkoutRoute());

        return view('shop.success.index', ['product' => $escore]);        
    }
}
