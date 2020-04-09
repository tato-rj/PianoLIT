<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Merchandise\Purchase;
use App\Infograph\{Infograph, Topic};
use Illuminate\Http\Request;
use App\Notifications\{InfographDownload, InfographVoted};

class InfographicsController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:2')->only('updateScore');
    }

    public function load(Request $request)
    {
        $infographs = Topic::bySlug($request->topic)->infographs()->latest()->get();

        return view('resources.infographs.load', compact('infographs'))->render();
    }

    public function search(Request $request)
    {
        $infographs = Infograph::search(['name', 'description'], $request->search)->get();

        return view('resources.infographs.load', compact('infographs'))->render();
    }

    public function show(Infograph $infograph)
    {
        $related = $infograph->related();

        return view('resources.infographs.show', compact(['infograph', 'related']));
    }

    public function download(Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->increment('downloads');

            auth()->user()->purchase($infograph);
            
            Admin::notifyAll(new InfographDownload($infograph));
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
