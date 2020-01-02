<?php

namespace App\Http\Controllers;

use App\{Api, Piece, Tag, User, Timeline, Composer, Playlist};
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->api = new Api;
        $this->middleware('search.exact')->only('search');
        $this->middleware('log.app');
    }

    public function discover($pieces = null, $inputArray = null)
    {
        $collection = \Cache::remember('app.discover', days(1), function() {
            return collect([
                $this->api->order(0)->free('Free weekly pick'),
                $this->api->order(1)->composers('Composers'),
                $this->api->order(2)->latest('Latest pieces'),
                $this->api->order(3)->women('From women composers'),
                $this->api->order(4)->tag('Pieces that are'),
                $this->api->order(5)->improve('Improve your'),
                $this->api->order(6)->levels('Levels'),
                $this->api->order(7)->ranking('rcm', 'Equivalent to the RCM levels'),
                $this->api->order(8)->ranking('abrsm', 'Equivalent to the ABRSM levels'),
                $this->api->order(9)->periods('Periods'),
            ]);
        });

        if (request()->wantsJson() || request()->has('api'))
            return $collection->toArray();

        return view('admin.pages.discover.index', compact(['collection', 'pieces', 'inputArray']));
    }

    /**
     * Performs search for the tour tab on the app
     * @param  Request $request
     * @return Collection of pieces
     */
    public function tour(Request $request)
    {
        $pieces = Piece::search($request->search)->get()->load(['tags', 'composer', 'favorites'])->shuffle()->each->isFavorited($request->user_id);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        $levels = Tag::levels()->get();
        $lengths = Tag::lengths()->get();
        $moods = Tag::special()->get();
                
        return view('admin.pages.tour.index', compact(['pieces', 'levels', 'lengths', 'moods']));
    }

    /**
     * Returns piece to the app
     * @param  Request $request
     * @return Array with one piece
     */
    public function piece(Request $request)
    {
        $piece = Piece::with(['composer', 'tags', 'favorites'])->findOrFail($request->search);

        $piece->isFavorited($request->user_id);

        return [$piece];
    }

    public function user(Request $request)
    {
        return $request->all();
    }

    public function suggestions(Request $request)
    {
        $suggestions = User::findOrFail($request->user_id)->suggestions(10);

        return $suggestions;
    }

    public function tags()
    {
        return \Cache::remember('app.tags.all', weeks(1), function() {
            return Tag::display()->orderBy('name')->get();
        });
    }

    public function composers()
    {
        return Composer::all();
    }

    public function users()
    {
        return User::all();
    }

    public function timeline($piece_id)
    {
        return Timeline::for($piece_id, 4);
    }

    public function playlists($group)
    {
        return \Cache::remember('app.tags.all', weeks(1), function() {
            return Playlist::journey()->sorted()->get();
        });
    }

    public function playlist(Playlist $playlist)
    {
        $pieces = $playlist->pieces;
        
        $pieces->each(function($result) {
            $this->api->setCustomAttributes($result, request()->user_id);
        });

        return $pieces;
    }
}
