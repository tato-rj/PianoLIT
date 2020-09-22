<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api};
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->check() ? auth()->user() : User::findOrFail($request->user_id);

        $piece = Piece::findOrFail($request->piece_id);

        $user->favorites()->toggle($piece);

        return response()->json($user->favorites->contains($piece) ? 'Remove from favorites' : 'Add to favorites');

        // if ($user->favorites()->find($request->piece_id)) {
        //     $user->favorites()->detach($request->piece_id);
        //     return response()->json('The piece has been removed');
        // } else {
        //     $user->favorites()->attach($request->piece_id);
        //     return response()->json('The piece has been added');
        // };
    }
}
