<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Piece;
use App\Services\Pieces\ScoreFiles;

class ScoreFilesController extends Controller
{
    public function piece(Piece $piece, ScoreFiles $scores)
    {
        return $scores->response($scores->saved($piece->score_path));
    }

    public function show(string $filename, ScoreFiles $scores)
    {
        return $scores->response($scores->adminFile($filename));
    }
}
