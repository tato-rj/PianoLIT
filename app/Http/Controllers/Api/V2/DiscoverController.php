<?php

namespace App\Http\Controllers\Api\V2;

use App\Api\V2\{Input, Responses};
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\DiscoverResource;
use App\Services\DiscoverFeed;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function __invoke(Request $request, DiscoverFeed $feed, Responses $responses)
    {
        Input::validate($request);
        $resource = new DiscoverResource($feed->forUser($request->user('api_v2')));
        return $responses->success($request, $resource->resolve($request));
    }
}
