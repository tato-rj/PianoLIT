<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api};
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function update(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        if (! $user)
            return response()->json('Sorry, user not found.');

        if ($user->favorites()->find($request->piece_id)) {
            $user->favorites()->detach($request->piece_id);
            return response()->json('The piece has been removed from ' . possessive($user->first_name) . ' favorites');
        } else {
            $user->favorites()->attach($request->piece_id);
            return response()->json('The piece has been added to ' . possessive($user->first_name) . ' favorites');
        };
    }
}
