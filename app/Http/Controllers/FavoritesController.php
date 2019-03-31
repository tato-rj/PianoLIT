<?php

namespace App\Http\Controllers;

use App\{User, Piece};
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function show(Request $request)
    {
    	
    }

    public function update(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['Sorry, user not found.']);

        if ($user->favorites()->find($request->piece_id)) {
            $user->favorites()->detach($request->piece_id);
        } else {
            $user->favorites()->attach($request->piece_id);
        };

        return response()->json(['Add to favorites!']);
    }
}
