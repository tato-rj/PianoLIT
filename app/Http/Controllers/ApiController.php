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
    }

    public function discover($pieces = null, $inputArray = null)
    {        
        $collection = collect([
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

        if (request()->wantsJson() || request()->has('api'))
            return $collection->toArray();

        return view('admin.pages.discover.index', compact(['collection', 'pieces', 'inputArray']));
    }

    public function search(Request $request)
    {
        $pieces = Piece::search($request->search)->get();

        if ($request->has('count'))
            return response()->json(['count' => $pieces->count()]);

        $pieces->load(['tags', 'composer', 'favorites'])->each->isFavorited($request->user_id);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        $tags = Tag::display()->pluck('name');
        
        return view('admin.pages.search.index', compact(['pieces', 'tags']));
    }

    public function tour(Request $request)
    {
        // if (! empty($inputArray)) {
        //     $level = array_shift($inputArray);
        //     dd($inputArray);
        //     $mood = $inputArray[array_rand($inputArray, 1)];

        //     $inputArray = [$level, $mood];
        // }

        $pieces = Piece::search($request->search)->get()->load(['tags', 'composer', 'favorites'])->shuffle()->each->isFavorited($request->user_id);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        $levels = Tag::levels()->get();
        $lengths = Tag::lengths()->get();
        $moods = Tag::special()->get();
                
        return view('admin.pages.tour.index', compact(['pieces', 'levels', 'lengths', 'moods']));
    }

    public function piece(Request $request)
    {
        $piece = Piece::with(['composer', 'tags', 'favorites'])->findOrFail($request->search);

        $this->api->setCustomAttributes($piece, $request->user_id);

        $result[0] = $piece;

        return $result;
    }

    public function user(Request $request)
    {
        return $request->all();
    }

    public function suggestions(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['User not found']);

        $suggestions = $user->suggestions(10);

        $suggestions->each(function($piece) use ($user) {
            $this->api->setCustomAttributes($piece, $user->id);
        });

        return $suggestions;
    }

    public function tags()
    {
        return Tag::display()->orderBy('name')->get();
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
        return Playlist::journey()->sorted()->get();
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
