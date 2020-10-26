<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Merchandise\Purchase;
use App\Infograph\{Infograph, Topic};
use Illuminate\Http\Request;
use App\Notifications\InfographVoted;
use App\Filters\InfographicFilters;

class InfographicsController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:2')->only('updateScore');
    }

    public function index(InfographicFilters $filters)
    {
        $infographs = Infograph::published()->latest()->filter($filters)->paginate(12);
        $topics = Topic::has('infographs', '>', 0)->ordered()->get();

        return view('infographics.index', compact(['infographs', 'topics']));        
    }

    public function load(Request $request)
    {
        $infographs = Topic::bySlug($request->topic)->infographs()->latest()->get();

        return view('infographics.load', compact('infographs'))->render();
    }

    public function search(Request $request)
    {
        $infographs = Infograph::search($request->search)->get();

        return view('infographics.load', compact('infographs'))->render();
    }

    public function show(Infograph $infograph)
    {
        $related = $infograph->related();
        
        return view('infographics.show', compact(['infograph', 'related']));
    }

    public function topic(Topic $topic)
    {
        $topics = Topic::exclude([$topic->id])->get();
        $infographs = Infograph::published()->latest()->byTopic($topic)->paginate(12);

        return view('infographics.topic', compact(['infographs', 'topics', 'topic']));
    }

    public function download(Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->increment('downloads');

            auth()->user()->purchase($infograph);
        }

        $file = request('size') == 'lg' ? $infograph->cover_path : $infograph->thumbnail_path;

        return \Storage::disk('public')->download($file);
    }

    public function updateScore(Request $request, Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->updateScore($request->liked);
            Admin::notifyAll(new InfographVoted($infograph, $request->liked));
        }

        return response(200);
    }
}
