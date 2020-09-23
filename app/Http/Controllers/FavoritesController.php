<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api, Favorite, FavoriteFolder};
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function update(Request $request)
    {
        $status = Favorite::toggle(
        	User::findOrFail($request->user_id), 
        	Piece::findOrFail($request->piece_id), 
	        FavoriteFolder::find($request->folder_id)
	    );

        return response()->json(['status' => $status, 'comment' => 'The favorite has been updated']);
    }
}
