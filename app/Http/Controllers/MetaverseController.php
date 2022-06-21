<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Metaverse;

class MetaverseController extends Controller
{
    public function index()
    {
        $events = Metaverse::all();

        return view('metaverse.index', compact('events'));
    }
}
