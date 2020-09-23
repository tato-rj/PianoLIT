<?php

namespace App\Http\Controllers;

use App\FavoriteFolder;
use App\Rules\UserMustOwnTheFolder;
use Illuminate\Http\Request;

class FavoriteFoldersController extends Controller
{
    public function store(Request $request)
    {
    	$request->validate([
    		'user_id' => 'required|exists:users,id',
    		'name' => 'required|string'
    	]);

    	return FavoriteFolder::create([
    		'user_id' => $request->user_id,
    		'name' => $request->name
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
