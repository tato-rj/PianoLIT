<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api};
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api;        
    }

    public function show(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['User not found']);

        $favorites = $user->favorites;
        
        $favorites->each(function($piece) use ($user) {
            $this->api->setCustomAttributes($piece, $user->id);
        });

        return $favorites;
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
