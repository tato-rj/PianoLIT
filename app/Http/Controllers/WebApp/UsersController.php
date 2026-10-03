<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\FavoriteFolder;

class UsersController extends Controller
{
    public function profile()
    {
        if ($user = auth('web')->user()) $user->loadMissing(['membership', 'subscription.lists']);

    	return view('webapp.user.profile.index');
    }

    public function folder(FavoriteFolder $folder)
    {
        abort_unless($folder->user_id == auth()->id(), 403);

        $folder->loadMissing('favorites.piece.tags');
        $folder->loadMissing(['favorites.piece.tutorials' => function ($query) {
            $query->where('type', 'Performance')->orderBy('id')->with('moments');
        }]);

    	return view('webapp.user.my-pieces.favorites.folders.show', compact('folder'));
    }
}
