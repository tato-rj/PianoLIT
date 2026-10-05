<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Piece, Tutorial};
use App\Services\Timeline\WebTimeline;
use App\Events\PieceShared;
use App\Services\RecentlyViewedPieces;
use App\Services\WebApp\PieceCards;
use App\Services\WebApp\PieceRecommendations;

class PiecesController extends Controller
{
    public function show(Piece $piece, RecentlyViewedPieces $recentlyViewed, PieceRecommendations $recommendations)
    {
        $timelineService = new WebTimeline;
        $timelinePeriod = $timelineService->periodForPiece($piece);
        $timeline = $timelineService->forPiece($piece);
        $piece->loadMissing(['tags', 'tutorials.moments']);
        $recommendationRows = $recommendations->rows($piece);
        $sentences = ['Tuning the piano', 'Arranging rows of comfy seats', 'Adjusting the bench', 'Warming up fingers', 'Greeting the eager audience', 'Dimming the lights', 'Wrapping up'];

        $response = response()->view('webapp.piece.index', compact(['piece', 'timeline', 'timelinePeriod', 'sentences', 'recommendationRows']));

        if (request()->isMethod('GET') && auth('web')->check()) {
            $recentlyViewed->record(auth('web')->user(), $piece);
        }

        return $response;
    }

    public function collection(Piece $piece)
    {
        $siblings = PieceCards::load($piece->siblings());

    	return view('webapp.piece.options.collection', compact(['piece', 'siblings']));
    }

    public function composer(Piece $piece)
    {
        return redirect()->route('webapp.composers.show', $piece->composer_id, 301);
    }

    public function timeline(Piece $piece)
    {
        $timelineService = new WebTimeline;
        $timelinePeriod = $timelineService->periodForPiece($piece);
        $timeline = $timelineService->forPiece($piece);

        return view('webapp.piece.options.timeline', compact(['piece', 'timeline', 'timelinePeriod']));
    }

    public function appleMusic(Piece $piece)
    {
        return view('webapp.piece.options.apple-music', compact('piece'));
    }

    public function similar(Piece $piece)
    {
        $similar = PieceCards::load($piece->similar());

    	return view('webapp.piece.options.similar', compact(['piece', 'similar']));
    }

    public function audio(Piece $piece)
    {
        return view('webapp.piece.components.audio', compact('piece'))->render();
    }

    public function tutorial(Piece $piece, Tutorial $tutorial)
    {
        abort_unless($tutorial->piece_id == $piece->id, 404);
        $tutorial->loadMissing('moments');

        return view('webapp.piece.components.video.element', compact('piece', 'tutorial'))->render();
    }

    public function saveTo(Piece $piece)
    {
        $folders = auth()->user()->favoriteFolders()->withCount(['favorites as piece_favorites_count' => function ($query) use ($piece) {
            $query->where('piece_id', $piece->id);
        }])->lastUpdated()->get();
        
        return view('webapp.piece.components.saveto.index', compact(['piece', 'folders']))->render();        
    }

    public function share(Request $request, Piece $piece)
    {
        $email = $request->recipient_email;

        event(new PieceShared($piece, $email));

        return back()->with('status', 'Your email is on the way!');
    }

    public function score(Piece $piece)
    {
        abort_unless($piece->hasWebMediaAccess(auth('web')->user()), 403);

        $storage = \Storage::disk('public');

        return $storage->download($piece->score_path);
    }
}
