<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Piece, Timeline};

class PiecesController extends Controller
{
    public function show(Piece $piece)
    {
    	$timeline = Timeline::for($piece->id, 4);;

    	return view('webapp.piece.index', compact(['piece', 'timeline']));
    }
}
