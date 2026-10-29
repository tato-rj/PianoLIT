<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\{eBook, eBookTopic};
use Illuminate\Http\Request;
use App\Billing\Factories\StripeFactory;
use App\Services\Shop\{ProductPage, Storefront, MerchandiseBag};

class eBooksController extends Controller
{
    public function index(Request $request, Storefront $catalog, MerchandiseBag $bag)
    {
        return app(MerchandiseController::class)->index($request, $catalog, $bag, 'ebooks');
    }

    public function show(Request $request, eBook $ebook, ProductPage $page)
    {
        return view('shop.storefront.show', $page->data($ebook, $request));
    }

    public function checkout(eBook $ebook, Request $request)
    {
        return app(CheckoutController::class)->digital($request, $ebook, 'ebooks');
    }
}
