<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class FavoritesController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app');
    }

    public function show(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['User not found']);

        $favorites = $user->favorites;
        
        $favorites->each->isFavorited($request->user_id);

        return $favorites;
    }
}
