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

        $pieces = $query->get();

        return view('webapp.playlists.show', compact('playlist', 'pieces'));
    }

    public function pdf(Request $request, Playlist $playlist)
    {
        abort_unless($playlist->published_at && $playlist->published_at->lte(now()), 404);
        $options = \App\PDF\EscoreOptions::validate($request);

        $pieces = $playlist->pieces()->has('tutorials')->get()->filter(function ($piece) {
            return $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user());
        });
        $pieces = \App\PDF\EscoreOptions::selectPieces($pieces, $options);
        abort_if($pieces->isEmpty(), 403, 'No eligible scores are available in this collection.');
        $options['creator'] = auth('web')->user()->full_name;

        try {
            $generator = app(\App\PDF\PDFGenerator::class)->pieces($pieces)->request($options);
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
