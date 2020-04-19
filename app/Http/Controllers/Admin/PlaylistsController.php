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
        $journey = Playlist::byGroup('journey')->sorted()->get();
        $general = Playlist::byGroup(null)->sorted()->get();

        return view('admin.pages.playlists.index', compact(['journey', 'general']));
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
            'cover_path' => $request->hasFile('cover') ? $request->file('cover')->store('app/playlists', 'public') : null,
            'group' => $request->group ?? null,
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
        if (Playlist::featured()->exists() && ! $playlist->is_featured)
            return redirect()->back()->with('error', Playlist::featured()->first()->title . ' is already featured.');

        $playlist->update([
            'name' => $request->name,
            'subtitle' => $request->subtitle,
            'group' => $request->group ?? null,
            'description' => $request->description,
            'featured' => $request->featured ?? null
        ]);

        $playlist->pieces()->detach();

        if ($request->has('pieces')) {
            foreach ($request->pieces as $order => $piece) {
                $playlist->pieces()->attach($piece, ['order' => $order]);
            }
        }

        if ($request->hasFile('cover')) {
            \Storage::disk('public')->delete($playlist->cover_path);
            
            $playlist->update(['cover_path' => $request->file('cover')->store('app/playlists', 'public')]);
        }

        return redirect()->back()->with('status', 'The playlist has been successfully updated');
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required']);

        Playlist::sort($request->ids);

        return view('components.alerts.alert', [
            'color' => 'green',
            'message' => '<i class="fas fa-check-circle mr-2"></i>The order has been updated',
            'temporary' => true,
            'dismissible' => true,
            'floating' => 'top'
        ])->render();
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
