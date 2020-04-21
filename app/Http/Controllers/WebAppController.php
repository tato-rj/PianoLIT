<?php

namespace App\Http\Controllers;

use App\Api\Api;
use Illuminate\Http\Request;

class WebAppController extends Controller
{
    public function discover(Api $api)
    {
    	$rows = $api->discover()->toArray();

    	return view('webapp.discover.index', compact('rows'));
    }
}
