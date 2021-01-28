<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{FavoriteFolder, Piece, Favorite, User};
use App\Http\Requests\FavoriteFoldersForm;
use App\Rules\UserMustOwnTheFolder;

class FavoriteFoldersController extends Controller
{
    public function __construct()
    {
        // $this->middleware('log.app')->except(['index', 'update']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'piece_id' => 'sometimes|exists:pieces,id'
        ]);

        $user = User::find($request->user_id);

        $folders = $user->favoriteFolders()->with('favorites')->lastUpdated()->get();

        if ($request->has('piece_id'))
            $folders->each->hasPiece($request->piece_id);

        return $folders;     
    }

    public function show(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'folder_id' => 'required|exists:favorite_folders,id'
        ]);

        return FavoriteFolder::flat($request->user_id, $request->folder_id);
    }

    public function store(Request $request, FavoriteFoldersForm $form)
    {
        $folder = FavoriteFolder::create([
            'user_id' => $form->user_id,
            'name' => $form->name
        ]);

        $user = User::findOrFail($form->user_id);

        if ($piece = Piece::find($form->piece_id)) {
            Favorite::toggle(
                $user, 
                $piece, 
                $folder
            );
        }

        return response()->json(['message' => 'Saved to ' . $folder->name, 'data' => $folder]);
    }

    public function update(Request $request, FavoriteFoldersForm $form)
    {
        // $request->validate([
        //     'folder_id' => 'required|exists:favorite_folders,id',
        //     'user_id' => ['required', 
        //                   'exists:users,id',
        //                   new UserMustOwnTheFolder($request->folder_id)
        //                 ],
        //     'name' => 'required|string',
        // ]);

        $folder = FavoriteFolder::find($request->folder_id);

        $folder->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json(['message' => 'The folder has been updated.', 'data' => $folder]);
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
