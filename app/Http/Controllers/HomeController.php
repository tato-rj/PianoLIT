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
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: request()->getScheme();
        $hub = fn ($name) => $scheme.'://'.$name.'.'.config('app.short_url');

        $webapp = ['key' => 'webapp', 'title' => 'Web app', 'description' => 'Find your next favorite piece.', 'destination' => 'my.pianolit', 'url' => route('webapp.discover'), 'color' => 'sky'];
        $ios = ['key' => 'ios', 'title' => 'iOS app', 'description' => 'A little inspiration. Everywhere.', 'destination' => 'App Store', 'url' => config('app.stores.ios'), 'color' => 'lavender'];
        $shop = ['key' => 'shop', 'title' => 'Shop', 'description' => 'Good things for your piano.', 'destination' => 'shop.pianolit', 'url' => route('shop.home'), 'color' => 'peach'];
        $play = ['key' => 'play', 'title' => 'Play', 'description' => 'Let your curiosity play.', 'destination' => 'play.pianolit', 'url' => $hub('play'), 'color' => 'butter'];
        $learn = ['key' => 'learn', 'title' => 'Learn', 'description' => 'Make sense of the music.', 'destination' => 'learn.pianolit', 'url' => $hub('learn'), 'color' => 'mint'];
        $blog = ['key' => 'blog', 'title' => 'Blog', 'description' => 'Stories beyond the notes.', 'destination' => 'blog.pianolit', 'url' => route('posts.index'), 'color' => 'rose'];

        $projects = [
            $shop, $play, $webapp, $ios, $learn, $blog
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
