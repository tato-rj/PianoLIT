<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{FavoriteFolder, Piece, Favorite, User};
use Illuminate\Validation\ValidationException;

class FavoriteFoldersController extends Controller
{
    public function store(Request $request)
    {
    	$request->validate([
            'piece_id' => 'sometimes|exists:pieces,id',
    		'name' => 'required|string'
    	]);

        $folder = FavoriteFolder::create([
            'user_id' => auth()->user()->id,
            'name' => $request->name
        ]);

        if ($piece = Piece::find($request->piece_id)) {
            Favorite::toggle(
                auth()->user(), 
                $piece, 
                $folder
            );
        }

        $folders = auth()->user()->favoriteFolders()->lastUpdated()->get();

    	return response()->json([
            'html' => [
                'list' => $piece ? view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render() : null,
                'flex' => null
            ]
        ]);
    }

    public function update(Request $request, FavoriteFolder $folder)
    {
    	$request->validate([
    		'name' => 'required|string'
    	]);

        if (auth()->user()->favoriteFolders()->find($folder)->isEmpty())
            throw ValidationException::withMessages(['folder' => 'You must own this folder to make changes to it.']);

    	$folder->update([
    		'name' => $request->name,
    		'description' => $request->description
    	]);

        return back()->with(['status' => 'The folder has been updated.']);
    }

    public function destroy(Request $request, FavoriteFolder $folder)
    {
        if (auth()->user()->favoriteFolders()->find($folder)->isEmpty())
            throw ValidationException::withMessages(['folder' => 'You must own this folder to make changes to it.']);

    	$folder->delete();

    	return back()->with(['status' => 'The folder has been removed.']);
    }
}
