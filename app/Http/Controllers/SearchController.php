<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Piece, Tag, Api};

class SearchController extends Controller
{
    public function index(Request $request)
    {
    	// $results = $this->search($request);

    	// return view('search.index', compact('results'));
    }

    public function global(Request $request)
    {
        $searchables = [
            \App\Blog\Post::class,
            \App\Quiz\Quiz::class,
            \App\CrashCourse\CrashCourse::class
        ];

        $results = collect();

        foreach ($searchables as $searchable) {
            $query = $searchable::published()->search($request->search);

            if ($query->count())
                $results->push(['model' => class_basename($searchable), 'data' => $query->get()]);
        }

        return view('components.search.results.global.index', compact('results'))->render();   
    }

    public function similar(Piece $piece)
    {
        $pieces = $piece->similar();

        return view('search.similar', ['results' => $pieces, 'piece' => $piece]);
    }

    public function moreRows(Request $request)
    {
        $tag = Tag::whereIn('type', ['mood', 'technique'])->has('pieces', '>', 20)->except('name', $request->tags)->inRandomOrder()->first();

        if (empty($tag))
            return null;

        $title = $tag->type == 'mood' ? 'Pieces that are' : 'Pieces good for';

        return view('components.search.results.row', [
            'playlist' => []//$this->api->tag($title, $tag->name)
        ])->render();
    }
}
