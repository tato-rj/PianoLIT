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
        $topics = Topic::has('infographs')->ordered()->get();

        return view('infographics.index', compact(['infographs', 'topics']));        
    }

    public function show(Infograph $infograph)
    {
        $related = $infograph->related();
        
        return view('infographics.show', compact(['infograph', 'related']));
    }

    public function download(Infograph $infograph)
    {
        if (traffic()->isRealVisitor())
            $infograph->increment('downloads');

        if (! auth()->user()->purchasesOf($infograph)->exists()) {
            $purchase = auth()->user()->purchase($infograph);
        } else {
            $purchase = auth()->user()->purchasesOf($infograph)->first();
        }

        return request('type') == 'download' ? 
            \Storage::disk('public')->download($infograph->cover_path) : 
            redirect(route('shop.success', ['purchase' => $purchase, 'type' => 'free']));
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
