<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Pianist;

class PianistsController extends Controller
{
    public function index()
    {
        $pianists = Pianist::orderBy('name')->get();
        
        return view('pianists.index', compact('pianists'));
    }

    public function show(Pianist $pianist)
    {
        return view('pianists.show', compact('pianist'));        
    }
}
