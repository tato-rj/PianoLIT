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

    public function index(Request $request, InfographicFilters $filters)
    {
        $infographs = Infograph::published()->latest()->orderByDesc('id')->filter($filters)->paginate(12)
            ->appends($request->only('topics'));
        $topics = Topic::whereHas('infographs', function ($query) { $query->published(); })->ordered()->get();
        $page = $infographs->currentPage();
        $indexable = ! $request->query->has('topics') && $infographs->isNotEmpty();
        $canonical = $indexable ? route('resources.infographs.index', $page > 1 ? ['page' => $page] : []) : null;
        $response = response()->view('infographics.index', compact('infographs', 'topics', 'canonical'));

        return $indexable ? $response : $response->header('X-Robots-Tag', 'noindex');
    }

    public function show(Infograph $infograph)
    {
        $infograph->load('topics');
        $related = collect();
        if ($topicIds = $infograph->topics->modelKeys()) {
            $related = Infograph::published()->whereNotIn('id', [$infograph->id, 53])
                ->withCount(['topics as shared_topics_count' => fn ($query) => $query->whereIn('infograph_topics.id', $topicIds)])
                ->whereHas('topics', fn ($query) => $query->whereIn('infograph_topics.id', $topicIds))
                ->orderByDesc('shared_topics_count')->latest('published_at')->orderByDesc('id')->limit(8)->get();
        }
        $downloadPurchase = auth()->check() ? auth()->user()->purchasesOf($infograph)->first() : null;
        $canonical = $infograph->published_at ? route('resources.infographs.show', $infograph) : null;
        $response = response()->view('infographics.show', compact('infograph', 'related', 'downloadPurchase', 'canonical'));

        return $canonical ? $response : $response->header('X-Robots-Tag', 'noindex');
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
