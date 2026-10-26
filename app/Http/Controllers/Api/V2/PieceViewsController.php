<?php

namespace App\Http\Controllers\Api\V2;

use App\Api\V2\{Input, Responses};
use App\Http\Controllers\Controller;
use App\Piece;
use App\Services\RecentlyViewedPieces;
use Illuminate\Http\Request;

class PieceViewsController extends Controller
{
    public function store(Request $request, $pieceId, RecentlyViewedPieces $history, Responses $responses)
    {
        Input::validate($request);
        $piece = Piece::query()->setEagerLoads([])->withCount([])->findOrFail($pieceId);
        $history->record($request->user('api_v2'), $piece);
        return $responses->success($request, ['accepted' => true]);
    }
}
