<?php

namespace App\Http\Controllers;

use App\{FavoriteFolder, Piece, Favorite, User};
use App\Rules\UserMustOwnTheFolder;
use Illuminate\Http\Request;

class FavoriteFoldersController extends Controller
{
    public function store(Request $request)
    {
    	$request->validate([
    		'user_id' => 'required|exists:users,id',
            'piece_id' => 'sometimes|exists:pieces,id',
    		'name' => 'required|string'
    	]);

        $folder = FavoriteFolder::create([
            'user_id' => $request->user_id,
            'name' => $request->name
        ]);

        $user = User::findOrFail($request->user_id);

        if ($piece = Piece::find($request->piece_id)) {
            Favorite::toggle(
                $user, 
                $piece, 
                $folder
            );
        }

        $folders = $user->favoriteFolders()->lastUpdated()->get();

    	return response()->json([
            'html' => [
                'list' => view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render(),
                'flex' => null
            ]
        ]);
    }

    public function update(Request $request)
    {
    	$request->validate([
    		'folder_id' => 'required|exists:favorite_folders,id',
    		'user_id' => ['required', 
    					  'exists:users,id',
    					  new UserMustOwnTheFolder($request->folder_id)
    					],
    		'name' => 'required|string',
    		'description' => 'sometimes|string',
    	]);

    	$folder = FavoriteFolder::findOrFail($request->folder_id);

    	$folder->update([
    		'name' => $request->name,
    		'description' => $request->description
    	]);

    	return $folder;
    }

    public function destroy(Request $request)
    {
    	$request->validate([
    		'folder_id' => 'required|exists:favorite_folders,id',
    		'user_id' => ['required', 
    					  'exists:users,id',
    					  new UserMustOwnTheFolder($request->folder_id)
    					]
    	]);

    	FavoriteFolder::findOrFail($request->folder_id)->delete();

    	return response()->json('The folder has been removed.');
    }
}
