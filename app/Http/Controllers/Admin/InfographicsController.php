<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Infograph\{Infograph, Topic};

class InfographicsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax())
            return Infograph::datatable();

        $topics = Topic::ordered()->get();

        return view('admin.pages.infographs.index', compact('topics'));
    }

    public function topics()
    {
        $topics = Topic::ordered()->get();

        return view('admin.pages.infographs.topics.index', compact('topics'));
    }
}
