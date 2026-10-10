<?php

namespace App\Http\Controllers\WebApp;

use App\Api\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function results(Api $api, Request $request)
    {
        if ($request->wantsJson()) {
            $request->validate(['page' => 'nullable|integer|min:0', 'include_total' => 'nullable|boolean']);
            // A visitor cannot reveal additional results by requesting another page.
            if (! auth('web')->check() && (int) $request->input('page', 1) > 1) return '';

            $pieces = $request->has('catalogue')
                ? app(\App\Services\WebApp\ExploreCatalogue::class)->results($request)
                : $api->search($request)->filtered()->forWebApp();
            $response = response(view('webapp.search.results', compact('pieces'))->render());
            if ($request->attributes->has('webapp_search_total')) {
                $response->header('X-Search-Total', $request->attributes->get('webapp_search_total'));
            }
            return $response;
        }

        return view('webapp.search.index');
    }

    public function count(Api $api, Request $request)
    {
        $request->merge(['count' => true]);
        $count = $api->search($request)->get()->getData()->count;

        return view('webapp.explore.count', ['query' => $request->search, 'count' => $count])->render();
    }
}
