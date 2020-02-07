<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Pianist, Piece, Timeline};
use App\Infograph\Infograph;
use App\Infograph\Topic as InfographTopic;

class ResourcesController extends Controller
{
    public function infographs()
    {
        $infographs = Infograph::published()->latest()->paginate(8);
        $topics = InfographTopic::has('infographs', '>', 0)->ordered()->get();

		return view('resources.infographs.index', compact(['infographs', 'topics']));
    }

    public function podcasts()
    {
        return view('resources.podcasts.index');
    }

    public function pianists()
    {
        $pianists = Pianist::orderBy('name')->get();
        
        return view('resources.pianists.index', compact('pianists'));
    }

    public function pianist(Pianist $pianist)
    {
        return view('resources.pianists.show', compact('pianist'));        
    }

    public function score($piece)
    {
        $piece = Piece::find($piece);
        
        return view('pieces.score', compact('piece'));
    }

    public function timeline()
    {
        $timeline = Timeline::generate();

        return view('resources.timeline.index', compact('timeline'));        
    }
}
