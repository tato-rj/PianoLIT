<?php

namespace App\Http\Controllers;

use App\Piece;
use Illuminate\Http\Request;

class PiecesController extends Controller
{
    public function show(Piece $piece)
    {
        return view('pieces.show', compact('piece'));
    }
}
