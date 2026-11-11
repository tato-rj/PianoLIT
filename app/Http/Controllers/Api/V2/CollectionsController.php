<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\V2\{CollectionsResponse, Response};
use Illuminate\Http\Request;

class CollectionsController extends Controller
{
    public function index(Request $request, CollectionsResponse $collections)
    {
        return Response::data($collections->index($request->user()));
    }

    public function show(Request $request, string $collection, CollectionsResponse $collections)
    {
        return Response::data($collections->detail($collection, $request->user()));
    }
}
