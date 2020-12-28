<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{FavoriteFolder, Piece, Favorite, User};
use App\Rules\UserMustOwnTheFolder;

class FavoriteFoldersController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::find($request->user_id);

        return $user->favoriteFolders()->with('favorites')->lastUpdated()->get();     
    }

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

        return response()->json(['message' => 'The folder has been created.', 'data' => $folder]);
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
        ]);

        $folder = FavoriteFolder::find($request->folder_id);

        $folder->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json(['message' => 'The folder has been created.', 'data' => $folder]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'folder_id' => 'required|exists:favorite_folders,id',
            'user_id' => ['required', 
                          'exists:users,id',
                          new UserMustOwnTheFolder($request->folder_id)]
        ]);

        FavoriteFolder::find($request->folder_id)->delete();

        return response()->json(['message' => 'The folder has been removed.']);
    }
}
