<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Piece, Tag, Api};

class SearchController extends Controller
{
    protected $pieces, $total;

    public function __construct()
    {
        $this->api = new Api;
        $this->options = request()->has('lazy-load') ? ['hitsPerPage' => 20, 'page' => request()->page ?? 0] : [];
    }

    public function index(Request $request)
    {
    	$results = $this->search($request);

    	return view('search.index', compact('results'));
    }

    public function similar(Piece $piece)
    {
        $pieces = $piece->similar();

        return view('search.similar', ['results' => $pieces, 'piece' => $piece]);
    }

    public function moreRows(Request $request)
    {
        $tag = Tag::whereIn('type', ['mood', 'technique'])->has('pieces', '>', 20)->except('name', $request->tags)->inRandomOrder()->first();

        if (empty($tag))
            return null;

        $title = $tag->type == 'mood' ? 'Pieces that are' : 'Pieces good for';

        return view('components.search.results.row', [
            'playlist' => $this->api->tag($title, $tag->name)
        ])->render();
    }

    public function search(Request $request)
    {
        if ($request->has('search'))
            return $this->handle($request);

        return $this->toAdmin();
    }

    public function handle(Request $request)
    {
        $query = Piece::search($request->search)->options($this->options);

        $this->total = $query->count();

        if ($request->has('count'))
            return response()->json(['count' => $total]);

        $this->pieces = $query->get()->load(['tags', 'composer', 'favorites'])->each->isFavorited($request->user_id);

        return $this->next($request);
    }

    public function toAdmin()
    {
        $tags = Tag::display()->pluck('name');
        
        return view('admin.pages.search.index', ['pieces' => $this->pieces ?? [], 'total' => $this->total ?? null, 'tags' => $tags]);
    }

    public function next(Request $request)
    {
        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        if ($request->has('rendered'))
            return view('admin.pages.search.result-rows', ['pieces' => $this->pieces])->render();

        return $this->toAdmin();
    }
}
