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
            $this->api->order(0)->latest('Latest pieces'),
            $this->api->order(1)->composers('Most famous composers'),
            $this->api->order(2)->women('Women composers'),
            $this->api->order(3)->levels('Levels'),
            $this->api->order(4)->ranking('rcm', 'Equivalent to the RCM levels'),
            $this->api->order(5)->ranking('abrsm', 'Equivalent to the ABRSM levels'),
            $this->api->order(6)->improve('Improve your'),
            $this->api->order(7)->tag('Pieces that are '),
            $this->api->order(8)->periods('Periods'),
        ]);

        $tags = Tag::display()->inRandomOrder()->get();

        return view('welcome.index', compact(['collections', 'tags']));
    }

    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function contact()
    {
        return view('contact.index');
    }
}
