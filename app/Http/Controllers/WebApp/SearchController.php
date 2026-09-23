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
            $request->validate(['page' => 'nullable|integer|min:0']);
            // A visitor cannot reveal additional results by requesting another page.
            if (! auth('web')->check() && (int) $request->input('page', 1) > 1) return '';

            $pieces = $api->search($request)->filtered()->forWebApp();
            return view('webapp.search.results', compact('pieces'))->render();
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
