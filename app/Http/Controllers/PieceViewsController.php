<?php

namespace App\Http\Controllers;

use App\Piece;
use Illuminate\Http\Request;

class PieceViewsController extends Controller
{
    public function store(Request $request)
    {
    	if (! in_array($request->user_id, [196, 244]));
	        Piece::findOrFail($request->piece_id)->views()->create(['user_id' => $request->user_id]);
     
        return response(200);
    }
}
