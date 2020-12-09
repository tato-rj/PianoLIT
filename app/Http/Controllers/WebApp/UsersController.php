<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\FavoriteFolder;

class UsersController extends Controller
{
    public function profile()
    {
    	return view('webapp.user.profile.index');
    }

    public function folder(FavoriteFolder $folder)
    {
    	return view('webapp.user.my-pieces.favorites.folders.show', compact('folder'));
    }
}
