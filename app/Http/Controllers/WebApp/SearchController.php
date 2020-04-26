<?php

namespace App\Http\Controllers\WebApp;

use App\Api\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function results(Api $api, Request $request)
    {
        if ($request->wantsJson())
            return view('webapp.search.results', ['pieces' =>  $api->search($request)->filtered()->get()])->render();

        return view('webapp.search.index');
    }

    public function count(Api $api, Request $request)
    {
        if ($api->search($request))
            return view('webapp.explore.count', ['query' => $request->search, 'count' => $api->search($request)->get()->getData()->count])->render();

        return abort(416, 'Empty search');
    }
}
