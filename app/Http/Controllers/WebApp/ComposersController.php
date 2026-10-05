<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Composer;
use Illuminate\Support\Facades\DB;

class ComposersController extends Controller
{
    public function index()
    {
        $composers = Composer::atLeast(1)->get()->sortByDesc('pieces_count');

        // Only titles are needed for directory search; avoid serializing piece media.
        $composerWorks = DB::table('pieces')->whereIn('composer_id', $composers->pluck('id'))
            ->select('composer_id', 'name')->get()->groupBy('composer_id');

        return view('webapp.composers.index', compact('composers', 'composerWorks'));
    }

    public function show(Composer $composer)
    {
        return view('webapp.composers.show', compact('composer'));
    }
}
