<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ComposersController extends Controller
{
    public function index()
    {
    	return Composer::atLeast(1)->get()->sortBy('last_name');
    }
}
