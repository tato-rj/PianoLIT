<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Playlist;

class PlaylistsController extends Controller
{
    public function show(Playlist $playlist)
    {
        abort_unless($playlist->published_at && $playlist->published_at->lte(now()), 404);

        $query = $playlist->pieces()->has('tutorials');

        if (auth('web')->check()) {
            $query->withExists(['favorites as webapp_playlist_favorited' => function ($query) {
                $query->where('user_id', auth('web')->id())->whereNull('favorite_folder_id');
            }]);
        }

        $pieces = $query->get();

        return view('webapp.playlists.show', compact('playlist', 'pieces'));
    }

    public function pdf(Request $request, Playlist $playlist)
    {
        abort_unless($playlist->published_at && $playlist->published_at->lte(now()), 404);
        $request->validate([
            'title' => 'required|string|max:160',
            'subtitle' => 'required|string|max:160',
            'comment' => 'required|string|max:160',
        ]);

        $pieces = $playlist->pieces()->has('tutorials')->get()->filter(function ($piece) {
            return $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user());
        });
        abort_if($pieces->isEmpty(), 403, 'No eligible scores are available in this collection.');

        try {
            return app(\App\PDF\PDFGenerator::class)->pieces($pieces)
                ->request($request->only(['title', 'subtitle', 'comment']))->generate()->stream();
        } catch (\Exception $exception) {
            report($exception);
            return back()->with('error', 'The eScore could not be created. Please try again later.');
        }
    }
}
