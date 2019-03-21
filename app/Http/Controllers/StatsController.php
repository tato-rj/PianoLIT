<?php

namespace App\Http\Controllers;

use App\{User, Piece, Tag, Composer};
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function users()
    {
    	
    }

    public function pieces()
    {
        $tagStats = Tag::whereIn('type', ['mood', 'technique'])->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $composersStats = Composer::select('name')->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $levelsStats = Tag::levels()->withCount('pieces')->get();
        $periodsStats = Tag::periods()->withCount('pieces')->get();
        $recStats = Piece::byRecordingsAvailable();

        return view('admin.pages.stats.pieces.index', compact(['tagStats', 'composersStats', 'levelsStats', 'recStats', 'periodsStats']));
    }
}
