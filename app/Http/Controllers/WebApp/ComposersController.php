<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Composer;

class ComposersController extends Controller
{
    public function index()
    {
    	$composers = Composer::all()->sortBy('last_name');

    	return view('webapp.composers.index', compact('composers'));
    }
}
