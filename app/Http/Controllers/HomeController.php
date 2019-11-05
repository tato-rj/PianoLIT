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
            $this->api->tag('scales', 'blue'),
            $this->api->tag('passionate', 'red'),
            $this->api->tag('melancholic', 'purple'),
        ]);

        
        $tags = Tag::inRandomOrder()->get();

        return view('welcome.index', compact(['collections', 'tags']));
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
