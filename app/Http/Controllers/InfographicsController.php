<?php

namespace App\Http\Controllers;

use App\Admin;
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
        $infographs = Infograph::published()->latest()->filter($filters)->paginate(12)->withQueryString();
        $topics = Topic::whereHas('infographs', function ($query) { $query->published(); })->ordered()->get();

        return view('infographics.index', compact('infographs', 'topics'));
    }

    public function show(Infograph $infograph)
    {
        $infograph->load('topics');
        $related = $infograph->related();
        $downloadPurchase = auth()->check() ? auth()->user()->purchasesOf($infograph)->first() : null;
        
        return view('infographics.show', compact('infograph', 'related', 'downloadPurchase'));
    }

    public function download(Infograph $infograph)
    {
        if (traffic()->isRealVisitor())
            $infograph->increment('downloads');

        $purchase = auth()->user()->purchasesOf($infograph)->first()
            ?? auth()->user()->purchase($infograph);

        return request('type') == 'download' ? 
            \Storage::disk('public')->download($infograph->cover_path) : 
            redirect(route('shop.success', ['purchase' => $purchase, 'type' => 'free']));
    }

    public function updateScore(Request $request, Infograph $infograph)
    {
        $request->validate(['liked' => 'required|boolean']);
        if (traffic()->isRealVisitor()) {
            $infograph->updateScore($request->liked);
            Admin::notifyAll(new InfographVoted($infograph, $request->liked));
        }

        return response(200);
    }
}
