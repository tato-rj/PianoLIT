<?php

namespace App\Http\Controllers;

use App\Piece;
use Illuminate\Http\Request;

class PieceViewsController extends Controller
{
    public function store(Request $request)
    {
    	$user = User::findOrFail($request->user_id);

    	if (! in_array($user->email, ['arthurvillar@gmail.com', 'mark@twain.com']));
	        Piece::findOrFail($request->piece_id)->views()->create(['user_id' => $request->user_id]);
     
        return response(200);
    }
}
