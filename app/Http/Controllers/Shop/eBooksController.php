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
    	$products = eBook::published()->get();

    	return view('shop.products.ebooks', compact('products'));
    }

    public function show(eBook $ebook)
    {
    	return view('shop.products.show.index', ['product' => $ebook]);    	
    }

    public function checkout(eBook $ebook)
    {
        return view('shop.checkout.index', ['product' => $ebook]);
    }
}
