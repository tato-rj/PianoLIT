<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{Piece, Favorite, FavoriteFolder};

class FavoritesController extends Controller
{
    public function update(Request $request, Piece $piece)
    {
        $request->validate(['folder_id' => 'nullable|integer']);
        $user = auth('web')->user();
        $folder = $request->filled('folder_id') ? $user->favoriteFolders()->findOrFail($request->folder_id) : null;
        $access = app(\App\Services\WebApp\FavoriteAccess::class);
        $status = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $piece, $folder, $access) {
            $access->lock($user);
            // Removing a saved favorite always remains possible, even above a cap.
            if (!Favorite::retrieve($user, $piece, $folder)->exists()) $access->assertAdd($user, $folder);
            return Favorite::toggle($user, $piece, $folder);
        });

        $request->attributes->set('activity.action', ['type' => 'favorite_changed', 'metadata' => ['result' => $status, 'folder_id' => $request->folder_id]]);

        $folders = auth()->user()->favoriteFolders()->withCount(['favorites as piece_favorites_count' => function ($query) use ($piece) {
            $query->where('piece_id', $piece->id);
        }])->lastUpdated()->get();

        return response()->json([
        	'status' => $status,
            'favorite' => [
                'piece_id' => $piece->id,
                'is_favorited' => $piece->favorites()->where('user_id', $user->id)->whereNotNull('favorite_folder_id')->exists(),
                'folder_id' => optional($folder)->id,
                'is_saved_in_folder' => $folder ? $status : null,
                'folder_favorite_id' => $folder && $status ? Favorite::retrieve($user, $piece, $folder)->value('id') : null,
            ],
            'html' => [
                'list' => view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render(),
                'flex' => null
            ]
        ]);
    }
}
