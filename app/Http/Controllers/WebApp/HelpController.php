<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;

class HelpController extends Controller
{
    public function terms()
    {
    	return view('webapp.legal.terms');
    }

    public function privacy()
    {
    	return view('webapp.legal.privacy');
    }
}
