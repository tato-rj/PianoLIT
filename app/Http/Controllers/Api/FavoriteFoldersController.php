<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{FavoriteFolder, Piece, Favorite, User};
use App\Http\Requests\FavoriteFoldersForm;
use App\Rules\UserMustOwnTheFolder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

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

        $folders = $user->favoriteFolders()->with('favorites')->alphabetical('name')->get();

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

    public function pdf(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'folder_id' => 'required|exists:favorite_folders,id'
        ]);

        return (new \App\PDF\PDFGenerator)->pieces(FavoriteFolder::flat($request->user_id, $request->folder_id))
                                          ->folder(FavoriteFolder::find($request->folder_id))
                                          ->download();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'piece_id' => 'sometimes|exists:pieces,id',
            'name' => [
                'required', 
                'string',
                'min:3',
                'max:36',
                Rule::unique('favorite_folders')->where(function ($query) use ($request) {
                    return $query->where(['user_id' => $request->user_id, 'name' => $request->name]);
                })]
        ]);

        if ($validator->fails())
            return response()->json(['message' => $validator->messages()->first(), 'valid' => false]);

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

        return response()->json(['message' => 'Saved to ' . $folder->name, 'data' => $folder, 'valid' => true]);
    }

    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:favorite_folders,id',
            'user_id' => [
                'required', 
                'exists:users,id',
                new UserMustOwnTheFolder($request->folder_id)],
            'ids' => 'required'
        ]);

        if ($validator->fails())
            return response()->json(['message' => $validator->messages()->first(), 'valid' => false]);

        $folder = FavoriteFolder::find($request->folder_id)->sort($request->ids);

        return view('components.alert', [
            'color' => 'green',
            'message' => '<i class="fas fa-check-circle mr-2"></i>The order has been updated',
            'temporary' => true,
            'dismissible' => true,
            'floating' => 'top'
        ])->render();
        
        // return response()->json(['message' => 'Favorites in this folder have been reordered.', 'data' => $folder, 'valid' => true]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:favorite_folders,id',
            'user_id' => [
                'required', 
                'exists:users,id',
                new UserMustOwnTheFolder($request->folder_id)],
            'name' => [
                'required', 
                'string',
                'min:3',
                'max:36',
                Rule::unique('favorite_folders')->where(function ($query) use ($request) {
                    return $query->where(['user_id' => $request->user_id, 'name' => $request->name]);
                })]
        ]);

        if ($validator->fails())
            return response()->json(['message' => $validator->messages()->first(), 'valid' => false]);

        $folder = FavoriteFolder::find($request->folder_id);

        $folder->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json(['message' => 'The folder has been updated.', 'data' => $folder, 'valid' => true]);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:favorite_folders,id',
            'user_id' => ['required', 
                          'exists:users,id',
                          new UserMustOwnTheFolder($request->folder_id)]
        ]);

        if ($validator->fails())
            return response()->json(['message' => $validator->messages()->first(), 'valid' => false]);

        FavoriteFolder::find($request->folder_id)->delete();

        return response()->json(['message' => 'The folder has been removed.', 'valid' => true]);
    }
}
