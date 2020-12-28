<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

class FavoriteFoldersController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app')->except(['index']);
    }

    public function index(Request $request)
    {
    	$request->validate([
    		'user_id' => 'required|exists:users,id'
    	]);

    	$user = User::find($request->user_id);

        return $user->favoriteFolders()->with('favorites')->lastUpdated()->get();     
    }
}
