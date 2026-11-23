<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Piece;
use App\Services\Pieces\{ScoreFiles, ScorePreview};

class ScoreFilesController extends Controller
{
    public function read(Piece $piece, ScoreFiles $scores)
    {
        abort_unless($piece->hasWebMediaAccess(auth('web')->user(), 'score'), 403);

        return $scores->response($scores->forPiece($piece));
    }

    public function download(Piece $piece, ScoreFiles $scores)
    {
        abort_unless($piece->hasWebMediaAccess(auth('web')->user(), 'exports'), 403);

        return $scores->response($scores->forPiece($piece), 'attachment');
    }

    public function preview(Piece $piece, ScoreFiles $scores, ScorePreview $preview)
    {
        return $preview->image($scores->forPiece($piece), false);
    }

    public function excerpt(Piece $piece, ScoreFiles $scores, ScorePreview $preview)
    {
        return $preview->image($scores->forPiece($piece), true);
    }
}
