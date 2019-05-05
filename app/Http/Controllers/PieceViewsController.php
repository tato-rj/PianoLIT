<?php

namespace App\Http\Controllers;

use App\Piece;
use Illuminate\Http\Request;

class PieceViewsController extends Controller
{
    public function store(Request $request)
    {
    	if ($request->user_id != 196 && $request->user_id != 244);
	        Piece::findOrFail($request->piece_id)->views()->create(['user_id' => $request->user_id]);
     
        return response(200);
    }
}
