<?php

namespace App\Http\Controllers;

use App\{Api, Tag};
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
        $latest = $this->api->latest();
        $composers = $this->api->composers();
        $periods = $this->api->periods();
        $improve = $this->api->improve();
        $levels = $this->api->levels();
        
        $tags = Tag::inRandomOrder()->get();

        $collections = collect([$latest, $composers, $periods, $improve, $levels]);
// return $collections;
        return view('welcome.index', compact(['collections', 'tags']));
    }
}
