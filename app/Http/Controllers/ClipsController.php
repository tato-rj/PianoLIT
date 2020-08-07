<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Clip;

class ClipsController extends Controller
{
    public function show(Clip $clip)
    {
    	return view('media.clip', compact('clip'));
    }
}
