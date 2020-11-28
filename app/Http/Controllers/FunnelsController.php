<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FunnelsController extends Controller
{
    public function findYourMatch()
    {
    	return view('funnels.find-your-match.index');
    } 
}
