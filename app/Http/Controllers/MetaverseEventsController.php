<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Metaverse\MetaverseEvent;

class MetaverseEventsController extends Controller
{
    public function index()
    {
        $events = MetaverseEvent::schedule()->upcoming()->paginate(8);

        return view('metaverse.index', compact('events'));
    }
}
