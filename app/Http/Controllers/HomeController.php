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
            $this->api->ranking('abrsm'),
            $this->api->ranking('rcm', 'orange'),
            $this->api->tag('To improve your', randval(['scales', 'left hand', 'arpeggios'])),
            $this->api->women(),
            $this->api->tag('We love pieces that are', randval(['playful', 'melancholic', 'triumphant'])),
            $this->api->similar(),
        ]);

        $tags = Tag::display()->inRandomOrder()->get();

        return view('welcome.index', compact(['collections', 'tags']));
    }
}
