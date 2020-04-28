<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Piece;

class PiecesController extends Controller
{
    public function show(Piece $piece)
    {
    	return view('webapp.piece.index', compact('piece'));
    }
}
