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
            $this->api->order(0)->latest(),
            $this->api->order(1)->composers(),
            $this->api->order(2)->ranking('abrsm'),
            $this->api->order(3)->ranking('rcm'),
            $this->api->order(4)->tag('To improve your', randval(['scales', 'left hand', 'arpeggios'])),
            $this->api->order(5)->women(),
            $this->api->order(6)->tag('We love pieces that are', randval(['playful', 'melancholic', 'triumphant'])),
            $this->api->order(7)->similar(),
        ]);

        $tags = Tag::display()->inRandomOrder()->get();

        return view('welcome.index', compact(['collections', 'tags']));
    }
}
