<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Resources\FindYourMatch\Quiz;

class FunnelsController extends Controller
{
    public function match()
    {
    	return view('funnels.find-your-match.index');
    }

    public function matchResults(Request $request)
    {
   		$piece = (new Quiz)->getKeywords($request->input)->search();

    	return view('funnels.find-your-match.results', compact('piece'))->render();
    }
}
