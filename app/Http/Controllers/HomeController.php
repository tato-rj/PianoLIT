<?php

namespace App\Http\Controllers;

use App\{Api, Tag, Piece};
use Illuminate\Http\Request;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;

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
        Bugsnag::notifyException(new RuntimeException("Test error"));
        $collections = collect();
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
