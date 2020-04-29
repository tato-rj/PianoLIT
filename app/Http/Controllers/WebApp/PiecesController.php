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

    public function about(Piece $piece)
    {
    	$siblings = $piece->siblings()->each->isFavorited(auth()->user()->id);

    	return view('webapp.piece.options.about', compact(['piece', 'siblings']));
    }

    public function composer(Piece $piece)
    {
    	return view('webapp.piece.options.composer', compact('piece'));
    }

    public function similar(Piece $piece)
    {
    	$similar = $piece->similar()->each->isFavorited(auth()->user()->id);

    	return view('webapp.piece.options.similar', compact(['piece', 'similar']));
    }
}
