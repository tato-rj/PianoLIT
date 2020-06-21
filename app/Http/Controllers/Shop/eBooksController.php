<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\eBook;
use Illuminate\Http\Request;

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
}
