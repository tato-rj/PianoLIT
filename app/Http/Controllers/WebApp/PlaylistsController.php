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

        $query = $playlist->pieces()->has('tutorials')->with(['tutorials' => function ($query) {
            $query->where('type', 'Performance')->orderBy('id')->with('moments');
        }]);

        if (auth('web')->check()) {
            $query->withExists(['favorites as webapp_playlist_favorited' => function ($query) {
                $query->where('user_id', auth('web')->id())->whereNull('favorite_folder_id');
            }]);
        }

        $collectionCap = app(\App\Services\WebApp\AccessPolicy::class)->cap('collections', auth('web')->user());
        $pieces = $collectionCap === 0 ? collect() : ($collectionCap !== null ? $query->limit($collectionCap)->get() : $query->get());

        return view('webapp.playlists.show', compact('playlist', 'pieces', 'collectionCap'));
    }

    public function pdf(Request $request, Playlist $playlist)
    {
        abort_unless($playlist->published_at && $playlist->published_at->lte(now()), 404);
        $user = auth('web')->user();
        abort_unless(app(\App\Services\WebApp\AccessPolicy::class)->available('escores', $user), 403, 'Go Premium to create eScores.');
        $options = \App\PDF\EscoreOptions::validate($request);

        $collectionCap = app(\App\Services\WebApp\AccessPolicy::class)->cap('collections', $user);
        abort_if($collectionCap === 0, 403, 'Collections are currently locked.');
        $query = $playlist->pieces()->has('tutorials');
        if ($collectionCap !== null) $query->limit($collectionCap);
        $pieces = $query->get()->filter(function ($piece) {
            return $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user(), 'exports');
        });
        $pieces = \App\PDF\EscoreOptions::selectPieces($pieces, $options);
        abort_if($pieces->isEmpty(), 403, 'No eligible scores are available in this collection.');
        $options['creator'] = auth('web')->user()->full_name;
        if ($playlist->cover_path && !isset($options['color'])) $options['color'] = \App\PDF\EscoreOptions::IMAGE_COVER_DEFAULT_COLOR;

        try {
            $generator = app(\App\PDF\PDFGenerator::class)->pieces($pieces)->request($options, true, true);
            if ($playlist->cover_path) $generator->collectionCover($playlist->cover_path);
            $pdf = $generator->generate();
            if ($request->boolean('preview')) {
                return response()->json(array_merge($generator->metadata(), ['pdf' => base64_encode($pdf->output())]))->header('Cache-Control', 'private, no-store');
            }
            return $request->isMethod('post') ? $pdf->download() : $pdf->stream();
        } catch (\Exception $exception) {
            report($exception);
            if ($request->boolean('preview')) return response()->json(['message' => 'The preview could not be created. Adjust your selection or try again.'], 422);
            return back()->with('error', 'The eScore could not be created. Please try again later.');
        }
    }
}
