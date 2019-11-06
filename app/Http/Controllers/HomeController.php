<?php

namespace App\Http\Controllers;

use App\{Api, Tag, Piece};
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->api = new Api;      
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $collections = collect([
            $this->api->latest(),
            $this->api->composers(),
            $this->api->tag('To improve your', 'scales'),
            $this->api->women(),
            $this->api->tag('We love pieces that are', 'playful'),
            $this->api->similar('If you like', 'For Elise'),
        ]);

        $tags = Tag::inRandomOrder()->get();

        return view('welcome.index', compact(['collections', 'tags']));
    }

    public function more(Request $request)
    {
        $tag = Tag::whereIn('type', ['mood', 'technique'])->has('pieces', '>', 20)->except('name', $request->tags)->inRandomOrder()->first();

        if (empty($tag))
            return null;

        $title = $tag->type == 'mood' ? 'Pieces that are' : 'Pieces good for';

        return view('components.cards.galleries.row', [
            'playlist' => $this->api->tag($title, $tag->name)
        ])->render();
    }

    public function search(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);

        $results = Piece::search($inputArray, $request);

        $pieces = $results->get();

        $this->api->prepare($request, $pieces, $inputArray);

        return $pieces;
    }
}
