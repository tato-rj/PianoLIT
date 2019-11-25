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
            $this->api->setColor('teal')->latest(),
            $this->api->setColor('purple')->composers(),
            $this->api->setColor('yellow')->ranking('abrsm'),
            $this->api->setColor('blue')->ranking('rcm'),
            $this->api->setColor('lightpink')->tag('To improve your', randval(['scales', 'left hand', 'arpeggios'])),
            $this->api->setColor('teal')->women(),
            $this->api->setColor('red')->tag('We love pieces that are', randval(['playful', 'melancholic', 'triumphant'])),
            $this->api->setColor('purple')->similar(),
        ]);

        $tags = Tag::display()->inRandomOrder()->get();

        return view('welcome.index', compact(['collections', 'tags']));
    }
}
