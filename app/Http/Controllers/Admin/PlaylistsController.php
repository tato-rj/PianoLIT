<?php

namespace App\Http\Controllers\Admin;

use App\{Playlist, Piece};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PlaylistsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax())
            return Playlist::sorted()->datatable();

        $playlists = Playlist::sorted()->get();

        return view('admin.pages.playlists.index', compact('playlists'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Playlist::create([
            'creator_id' => auth()->user()->id,
            'name' => $request->name,
            'subtitle' => $request->subtitle,
            'group' => strtolower($request->group),
            'description' => $request->description
        ]);

        return redirect()->back()->with('status', 'The playlist has been created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Playlist  $playlist
     * @return \Illuminate\Http\Response
     */
    public function show(Playlist $playlist)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Playlist  $playlist
     * @return \Illuminate\Http\Response
     */
    public function edit(Playlist $playlist)
    {
        if (request()->ajax())
            return Piece::with(['tags', 'composer'])->orderBy('updated_at', 'desc')->datatable(view('admin.pages.playlists.edit.table.actions'));

        return view('admin.pages.playlists.edit.index', compact(['playlist']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Playlist  $playlist
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Playlist $playlist)
    {
        $playlist->update([
            'name' => $request->name,
            'subtitle' => $request->subtitle,
            'group' => strtolower($request->group),
            'description' => $request->description
        ]);

        $playlist->pieces()->detach();

        if ($request->has('pieces')) {
            foreach ($request->pieces as $order => $piece) {
                $playlist->pieces()->attach($piece, ['order' => $order]);
            }
        }

        return redirect()->back()->with('status', 'The playlist has been successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Playlist  $playlist
     * @return \Illuminate\Http\Response
     */
    public function destroy(Playlist $playlist)
    {
        $playlist->delete();

        return redirect()->back()->with('status', 'The playlist has been removed');
    }
}
