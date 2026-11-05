<?php

namespace App\Http\Controllers;

use App\Piece;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $webapp = ['key' => 'webapp', 'title' => 'Web app', 'description' => 'Browse piano pieces by level, mood, and style.', 'destination' => 'my.pianolit.com', 'url' => route('webapp.discover'), 'color' => 'sky'];
        $ios = ['key' => 'ios', 'title' => 'iOS app', 'description' => 'Browse and practice piano pieces on iPhone and iPad.', 'destination' => 'App Store', 'url' => config('app.stores.ios'), 'color' => 'lavender'];
        $shop = ['key' => 'shop', 'title' => 'Shop', 'description' => 'Shop for T-shirts, stickers, eBooks, and eScores.', 'destination' => 'shop.pianolit.com', 'url' => route('shop.home'), 'color' => 'peach'];
        $games = ['key' => 'games', 'title' => 'Games', 'description' => 'Practice music theory with interactive games.', 'destination' => 'games.pianolit.com', 'url' => route('games.home'), 'color' => 'butter'];
        $learn = ['key' => 'learn', 'title' => 'Learn', 'description' => 'Study with tools, courses, quizzes, and visual guides.', 'destination' => 'learn.pianolit.com', 'url' => route('learn.home'), 'color' => 'mint'];
        $blog = ['key' => 'blog', 'title' => 'Blog', 'description' => 'Read articles about piano music and practice.', 'destination' => 'blog.pianolit.com', 'url' => route('posts.index'), 'color' => 'rose'];
        $metronome = ['key' => 'metronome', 'title' => 'Metronome', 'description' => 'Set a tempo and practice with a steady beat.', 'destination' => 'metronome.pianolit.com', 'url' => route('metronome.home')];

        $projects = [
            $webapp, $ios, $learn, $games, $shop, $metronome, $blog
        ];

        return view('home.index', compact('projects'));
    }

    public function loadPieces(Request $request)
    {
        if ($request->names) {
            $query = Piece::search(implode(' ', $request->names))->get();
            $total = $query->count();
            $pieces = $query->shuffle()->take(8);
            $label = 'Showing ' . $pieces->count() . ' of ' . $total . ' ' . str_plural('piece', $total) . ' found';
        } else {
            $query = Piece::latest()->with(['composer', 'tags']);
            $total = $query->count();
            $pieces = $query->take(8)->get();
            $label = 'Latest pieces';
        }
        
        return [
            'view' => view('search.components.results.pieces', compact('pieces'))->render(),
            'total' => $total,
            'count' => $pieces->count(),
            'label' => $label
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
