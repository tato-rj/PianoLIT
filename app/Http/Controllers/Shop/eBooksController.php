<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\{eBook, eBookTopic};
use App\Filters\ProductFilters;
use Illuminate\Http\Request;
use App\Billing\Factories\StripeFactory;

class eBooksController extends Controller
{
    public function index(ProductFilters $filters)
    {
    	$products = eBook::published()->latest()->filter($filters)->paginate(12);
        $topics = eBookTopic::has('ebooks')->get();

    	return view('shop.products.ebooks', compact(['products', 'topics']));
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
