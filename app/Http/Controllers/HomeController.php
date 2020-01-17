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
        $latest = Piece::latest()->with(['composer', 'tags'])->take(8)->get();
        $tags = Tag::byTypes(['ranking', 'length', 'period', 'genre'])->reverse();
        $testimonials = testimonials();
        shuffle($testimonials);

        return view('welcome.index', compact(['latest', 'tags', 'testimonials']));
    }

    public function loadPieces(Request $request)
    {
        // $query = Piece::whereHas('tags', function($query) use ($request) {
        //     foreach ($request->ids as $id) {
        //         $query->where('id', $id);                
        //     }
        // })->with(['composer', 'tags']);

        // $total = $query->count();
        // $pieces = $query->inRandomOrder()->take(8)->get();

        $query = Piece::search(implode(' ', $request->names))->get();

        $total = $query->count();
        $pieces = $query->shuffle()->take(8);
        
        return [
            'view' => ! $pieces->isEmpty() ? view('components.pieces.display', compact('pieces'))->render() : view('components.pieces.empty')->render(),
            'total' => $total,
            'count' => $pieces->count()
        ];
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
