<?php

namespace App\Http\Controllers;

use App\{Api, Piece, Tag};
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

        $collection = compact(['suggestions', 'trending', 'latest', 'composers', 'periods', 'improve', 'levels', 'famous', 'flashy']);

        if (request()->wantsJson() || request()->has('api'))
            return array_values($collection);

        return view('admin.pages.discover.index', compact(['collection', 'pieces', 'inputArray']));
    }

    public function search(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);
        
        $pieces = Piece::search($inputArray, $request)->get();

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
    	
    }

    public function piece(Request $request)
    {
        $piece = Piece::findOrFail($request->search);

        $this->api->setCustomAttributes($piece, $request->user_id);

        $result[0] = $piece;

        return $result;
    }
}
