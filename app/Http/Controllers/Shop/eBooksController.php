<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Shop\eBook;
use Illuminate\Http\Request;

class eBooksController extends Controller
{
    public function index()
    {
    	return view('shop.ebooks.index');
    }
}
