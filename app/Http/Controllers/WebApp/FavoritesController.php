<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{Piece, Favorite, FavoriteFolder};

class FavoritesController extends Controller
{
    public function update(Request $request, Piece $piece)
    {
        $status = Favorite::toggle(
        	auth()->user(), 
        	$piece, 
        	FavoriteFolder::find($request->folder_id));

        $folders = auth()->user()->favoriteFolders()->withCount(['favorites as piece_favorites_count' => function ($query) use ($piece) {
            $query->where('piece_id', $piece->id);
        }])->lastUpdated()->get();

        return response()->json([
        	'status' => $status,
            'html' => [
                'list' => view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render(),
                'flex' => null
            ]
        ]);
    }
}
