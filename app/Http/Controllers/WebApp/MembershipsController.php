<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MembershipsController extends Controller
{
    public function pricing()
    {
    	return view('webapp.pricing.index');
    }
}
