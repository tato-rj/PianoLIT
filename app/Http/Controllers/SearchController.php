<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Piece, Tag};
use App\Api\Api;

class SearchController extends Controller
{
    public function index(Api $api, Request $request)
    {
        if (auth()->check())
            return redirect(route('webapp.search.results', ['lazy-load', 'search' => $request->search]));
        
    	$results = $api->search($request)->filtered()->get();

    	return view('search.index', compact('results'));
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

        return view('search.components.results.global.index', compact('results'))->render();   
    }
}
