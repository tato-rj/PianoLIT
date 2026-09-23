<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Piece, Timeline, Tutorial};
use App\Events\PieceShared;
use App\Services\RecentlyViewedPieces;
use App\Services\WebApp\PieceCards;

class PiecesController extends Controller
{
    public function show(Piece $piece, RecentlyViewedPieces $recentlyViewed)
    {
        $timeline = Timeline::for($piece, 4);
        $piece->loadMissing(['tags', 'tutorials']);
        $similar = PieceCards::load($piece->similar()->take(16), false);
        $sentences = ['Tuning the piano', 'Arranging rows of comfy seats', 'Adjusting the bench', 'Warming up fingers', 'Greeting the eager audience', 'Dimming the lights', 'Wrapping up'];

        $response = response()->view('webapp.piece.index', compact(['piece', 'timeline', 'sentences', 'similar']));

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
    	return view('webapp.piece.options.composer', compact('piece'));
    }

    public function timeline(Piece $piece)
    {
        $timeline = Timeline::for($piece, 4);

        return view('webapp.piece.options.timeline', compact(['piece', 'timeline']));
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

        return view('webapp.piece.components.video.element', compact('tutorial'))->render();
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
        abort_unless(auth('web')->check() && auth('web')->user()->hasActiveSubscription(), 403);

        $storage = local() ? \Storage::disk('local') : \Storage::disk('public');

        return $storage->download($piece->score_path);
    }
}
