<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\Api;
use App\{Composer, Piece};
use App\Services\RecentlyViewedPieces;
use App\Services\WebApp\PieceCards;
use App\Services\WebApp\GalleryGradients;

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
        $galleryIndex = 0;
        $rows = $rows->map(function ($row) use (&$cards, &$galleryIndex) {
            $isGallery = ($row['row'] ?? null) === 'gallery';
            $color = $isGallery && collect($row['content'])->isNotEmpty()
                ? GalleryGradients::at($galleryIndex++) : null;
            if ($isGallery || ($row['type'] ?? null) === 'piece') {
                $row['content'] = collect($row['content'])->map(function ($card) use (&$cards, $color) {
                    $card = clone $card;
                    if ($color) $card->color = $color;
                    if ($card instanceof Piece) $cards[] = $card;
                    return $card;
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

    public function playlists(\App\Services\WebApp\Collections $collections)
    {
        return view('webapp.playlists.index', $collections->data());
    }

    public function tour(\App\Services\WebApp\MatchTour $tour)
    {
        return view('webapp.tour.index', ['tour' => $tour->data()]);
    }

    public function myPieces()
    {
        $user = auth('web')->user();
        $folders = $user ? $user->favoriteFolders()->alphabetical('name')->get() : collect();
        $folderPreviews = collect();
        $folderSearch = collect();
        if ($user) {
            // Suggestions already need these favorites. Keep folder data on this web-only load.
            $user->setRelation('favorites', $user->favorites()
                ->withPivot('favorite_folder_id', 'order')
                ->with('tags')
                ->get());
            $folderPieces = $user->favorites
                ->filter(function ($piece) { return $piece->pivot->favorite_folder_id !== null; })
                ->groupBy(function ($piece) { return $piece->pivot->favorite_folder_id; });
            $folderPreviews = $folderPieces->map(function ($pieces) {
                return $pieces->sortBy(function ($piece) { return $piece->pivot->order; })->take(2);
            });
            $folderSearch = $folderPieces->map(function ($pieces) {
                return $pieces->map(function ($piece) {
                    return implode(' ', [$piece->name, $piece->short_name, $piece->composer->name, $piece->composer->short_name]);
                })->implode(' ');
            });
        }
        $suggestions = $user ? PieceCards::load($user->suggestions(20)->shuffle()->take(10)) : collect();

        return view('webapp.user.my-pieces.index', compact('folders', 'folderPreviews', 'folderSearch', 'suggestions'));
    }

    public function settings()
    {      
    	return view('webapp.settings.index');
    }
}
