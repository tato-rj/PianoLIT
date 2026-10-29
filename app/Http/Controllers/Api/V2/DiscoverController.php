<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Piece;
use App\Services\Api\V2\{DiscoverResponse, Response};
use App\Services\Discover\Feed;
use App\Services\RecentlyViewedPieces;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function show(Request $request, Feed $feed, DiscoverResponse $resource)
    {
        // Content parity; native media authorization remains a separate contract.
        return Response::data($resource->data($feed->rows($request->user())));
    }

    public function view(Request $request, $piece, RecentlyViewedPieces $history)
    {
        $piece = Piece::findOrFail($piece);
        $history->record($request->user(), $piece);
        return response()->noContent();
    }
}
