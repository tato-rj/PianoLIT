<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Piece, Tag, Api};

class SearchController extends Controller
{
    public function __construct()
    {
        $this->api = new Api;      
    }

    public function index(Request $request)
    {
    	$results = $this->search($request);

    	return view('search.index', compact('results'));
    }

    public function moreRows(Request $request)
    {
        $tag = Tag::whereIn('type', ['mood', 'technique'])->has('pieces', '>', 20)->except('name', $request->tags)->inRandomOrder()->first();

        if (empty($tag))
            return null;

        $title = $tag->type == 'mood' ? 'Pieces that are' : 'Pieces good for';

        return view('components.search.results.row', [
            'playlist' => $this->api->tag($title, $tag->name)
        ])->render();
    }

    public function search(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);

        $pieces = Piece::search($inputArray, $request)->get();

        $this->api->prepare($request, $pieces, $inputArray);

        return $pieces;
    }
}
