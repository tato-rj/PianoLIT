<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\FavoriteFolder;

class UsersController extends Controller
{
    public function profile()
    {
        return redirect(\App\Support\ProfileUrl::get(), 301);
    }

    public function folder(FavoriteFolder $folder)
    {
        abort_unless($folder->user_id == auth()->id(), 403);

        $policy = app(\App\Services\WebApp\AccessPolicy::class);
        if ($policy->cap('folders', auth('web')->user()) === 0) return redirect()->route('webapp.my-pieces')->with('error', 'Favorite folders are currently locked.');
        $folder->loadMissing('favorites.piece.tags');
        $folder->loadMissing(['favorites.piece.tutorials' => function ($query) {
            $query->where('type', 'Performance')->orderBy('id')->with('moments');
        }]);

        $folderColor = in_array($folder->web_color, \App\Support\FolderPalette::COLORS, true)
            ? $folder->web_color : \App\Support\FolderPalette::forUser(auth()->id())->get($folder->id);

        return view('webapp.user.my-pieces.favorites.folders.show', compact('folder', 'folderColor'));
    }
}
