<?php

namespace App\Http\Controllers\Admin;

use App\Clip;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClipsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax())
            return Clip::datatable();

        $clips = Clip::latest();

        return view('admin.pages.clips.index', compact('clips'));
    }

    public function edit(Clip $clip)
    {
        return view('admin.pages.clips.edit', compact('clip'));        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required', 'url' => 'required']);

        Clip::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'url' => $request->url
        ]);

        return back()->with('status', 'The clip has been saved!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Clip  $clip
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Clip $clip)
    {
        $request->validate(['name' => 'required', 'url' => 'required']);

        $clip->update([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'url' => $request->url
        ]);

        return back()->with('status', 'The clip has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Clip  $clip
     * @return \Illuminate\Http\Response
     */
    public function destroy(Clip $clip)
    {
        $clip->delete();

        return back()->with('status', 'The clip has been removed.');
    }
}
