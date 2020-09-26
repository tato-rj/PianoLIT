<?php

namespace App\Http\Controllers;

use App\{User, Piece, Api, Favorite, FavoriteFolder};
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function update(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $piece = Piece::findOrFail($request->piece_id);
        $folder = FavoriteFolder::find($request->folder_id);

        $status = Favorite::toggle($user, $piece, $folder);

        $folders = $user->favoriteFolders()->lastUpdated()->get();

        return response()->json([
        	'status' => $status,
            'html' => [
                'list' => view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render(),
                'flex' => null
            ]
        ]);
    }
}
