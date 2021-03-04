<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Resources\FindYourMatch\Quiz;

class FunnelsController extends Controller
{
    public function match()
    {
        $composers = (new Quiz)->showComposers();
        $tags = (new Quiz)->showTags();
        $pieces = (new Quiz)->showPieces();

    	return view('funnels.find-your-match.index', compact(['composers', 'tags', 'pieces']));
    }

    public function matchResults(Request $request)
    {
   		$piece = (new Quiz)->getKeywords($request->input)->search();
return $piece;
    	return view('funnels.find-your-match.results', compact('piece'))->render();
    }
}
