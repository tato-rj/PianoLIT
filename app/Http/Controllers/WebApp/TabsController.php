<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\Api;
use App\{Composer, Piece};
use App\Services\RecentlyViewedPieces;
use App\Services\WebApp\PieceCards;

class TabsController extends Controller
{
    public function discover(Api $api, RecentlyViewedPieces $recentlyViewed)
    {
        $composers = Composer::atLeast(1)->get()->sortBy('last_name')->values();
        $rows = $api->for('webapp')->discover();

        if (auth('web')->check()) {
            $pieces = $recentlyViewed->forUser(auth('web')->user());
            if ($pieces->isNotEmpty()) {
                $api->order(2)->withAttributes($pieces, ['type' => 'piece', 'source' => route('api.pieces.find')]);
                $position = $rows->search(function ($row) { return $row['title'] === 'Latest pieces'; });
                // Personal rows must stay outside the shared discovery cache.
                $rows->splice($position === false ? 2 : $position, 0, [[
                    'title' => 'Recently viewed',
                    'row' => 'gallery',
                    'type' => 'piece',
                    'content' => $pieces,
                ]]);
            }
        }

        // Clone cached models before adding any request-local presentation data.
        $cards = [];
        $rows = $rows->map(function ($row) use (&$cards) {
            if (($row['type'] ?? null) === 'piece') {
                $row['content'] = collect($row['content'])->map(function ($piece) use (&$cards) {
                    $piece = clone $piece;
                    $cards[] = $piece;
                    return $piece;
                });
            }
            return $row;
        });
        PieceCards::load($cards, false);

        return view('webapp.discover.index', compact(['rows', 'composers']));
    }

    public function explore(Api $api)
    {
        $explore = $api->for('webapp')->explore()->map(function ($row) {
            if ($row['celltype'] === 'highlight') {
                $row['collection'] = new \Illuminate\Database\Eloquent\Collection(
                    $row['collection']->map(function ($piece) { return clone $piece; })->all()
                );
                $row['collection']->loadMissing('tags');
            }
            return $row;
        });

        return view('webapp.explore.index', compact('explore'));
    }

    public function highlights(Api $api, Request $request)
    {
        if ($request->wantsJson())
            return view('webapp.highlights.pieces', ['pieces' =>  Piece::freePicks($ordered = false)->with('tags')->filtered()->get()])->render();

        return view('webapp.highlights.index');
    }

    public function playlists(Api $api)
    {
        $playlists = $api->for('webapp')->playlists();
        $journey = $api->playlists('journey');

    	return view('webapp.playlists.index', compact(['playlists', 'journey']));
    }

    public function tour()
    {
        return view('webapp.tour.index');
    }

    public function myPieces()
    {
        $user = auth('web')->user();
        $folders = $user ? $user->favoriteFolders()->alphabetical('name')->get() : collect();
        if ($user) $user->loadMissing('favorites.tags');
        $suggestions = $user ? PieceCards::load($user->suggestions(20)->shuffle()->take(10)) : collect();

        return view('webapp.user.my-pieces.index', compact('folders', 'suggestions'));
    }

    public function settings()
    {      
    	return view('webapp.settings.index');
    }
}
