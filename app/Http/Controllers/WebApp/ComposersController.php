<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Composer;

class ComposersController extends Controller
{
    public function index()
    {
    	$composers = Composer::atLeast(1)->get()->sortBy('last_name');

    	return view('webapp.composers.index', compact('composers'));
    }

    public function show(Composer $composer)
    {
        return view('webapp.composers.show', compact('composer'));
    }
}
