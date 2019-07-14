<?php

namespace App\Http\Controllers;

use App\{Api, Piece, Tag, User, Timeline, Composer};
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->api = new Api;        
    }

    public function discover($pieces = null, $inputArray = null)
    {
        // Collections of playlists
        $composers = $this->api->composers();
        $periods = $this->api->periods();
        $improve = $this->api->improve();
        $levels = $this->api->levels();

        // Collections of pieces
        $suggestions = request()->has('user_id') ? $this->api->forUser(request('user_id')) : [];
        $trending = $this->api->trending();
        $latest = $this->api->latest();
        $famous = $this->api->famous();
        $flashy = $this->api->flashy();

        $collection = compact(['trending', 'latest', 'composers', 'periods', 'improve', 'levels', 'famous', 'flashy']);

        if (request()->wantsJson() || request()->has('api'))
            return array_values($collection);

        return view('admin.pages.discover.index', compact(['collection', 'pieces', 'inputArray']));
    }

    public function search(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);

        $results = Piece::search($inputArray, $request);

        if ($request->has('count'))
            return response()->json(['count' => $results->count()]);

        $pieces = $results->get();

        $this->api->prepare($request, $pieces, $inputArray);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        if ($request->has('discover'))
            return $this->discover($pieces, $inputArray);

        $tags = Tag::pluck('name');
                
        return view('admin.pages.search.index', compact(['pieces', 'inputArray', 'tags']));
    }

    public function tour(Request $request)
    {
        $request->request->add(['global' => null]);

        $inputArray = $this->api->prepareInput($request);

        $level = array_shift($inputArray);
        
        $mood = array_rand($inputArray, 1);

        $tourArray = [$level, $mood];

        $pieces = Piece::search($tourArray, $request)->get();

        if (! empty($inputArray))
            $this->api->prepare($request, $pieces, $tourArray);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        $levels = Tag::levels()->get();
        $lengths = Tag::lengths()->get();
        $moods = Tag::special()->get();
                
        return view('admin.pages.tour.index', compact(['pieces', 'inputArray', 'levels', 'lengths', 'moods']));
    }

    public function piece(Request $request)
    {
        $piece = Piece::findOrFail($request->search);

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
        return Tag::orderBy('name')->get();
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
        return Timeline::generate($piece_id, 4);
    }
}
