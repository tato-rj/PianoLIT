<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Piece;
use App\Services\Api\V2\{PieceResponse, Response};
use App\Services\Pieces\PageContent;
use Illuminate\Http\Request;

class PiecesController extends Controller
{
    public function show(Request $request, $piece, PageContent $content, PieceResponse $response)
    {
        $piece = Piece::findOrFail($piece);
        return Response::data($response->data($content->data($piece), $request->user()));
    }
}
