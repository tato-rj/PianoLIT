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
        $table = app(\App\Services\Admin\PlaylistsTable::class);
        if (request()->has('group')) return redirect()->route('admin.playlists.index', \App\Support\AdminTableFilters::query($table->filters(request()), 'order_asc'));
        return view('admin.pages.playlists.index', $table->get(request()));
    }

    public function order(Request $request)
    {
        if ($request->has('group')) return redirect()->route('admin.playlists.order');
        $playlists = Playlist::byGroup(null)->sorted()->orderBy('id')->get();
        return view('admin.pages.playlists.order', compact('playlists'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validateWithBag('playlistCreate', [
            'name' => 'required|string|max:255', 'subtitle' => 'required|string|max:72',
            'description' => 'required|string|max:255', 'featured' => 'nullable|string|max:16',
            'group' => 'nullable|string|in:general,journey',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);
        $data['group'] = $data['group'] ?? ($request->boolean('is_journey') ? 'journey' : 'general');
        $cover = $request->hasFile('cover') ? $request->file('cover')->store('app/playlists', 'public') : null;
        try {
            $playlist = \DB::transaction(function () use ($data, $cover) {
                $group = $data['group'] === 'journey' ? 'journey' : null;
                $orders = \DB::table('playlists')->where('group', $group)->lockForUpdate()->pluck('order');
                return Playlist::create([
                    'creator_id' => auth('admin')->id(), 'name' => $data['name'], 'subtitle' => $data['subtitle'],
                    'description' => $data['description'], 'featured' => $data['featured'] ?? null,
                    'cover_path' => $cover, 'group' => $group, 'published_at' => null,
                    'order' => $orders->isEmpty() ? 0 : $orders->max() + 1,
                ]);
            }, 3);
        } catch (\Throwable $exception) {
            if ($cover) \Storage::disk('public')->delete($cover);
            throw $exception;
        }
        return redirect()->route('admin.playlists.edit', $playlist)->with('status', 'The playlist has been created as a draft.');
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

    public function togglePublication(Playlist $playlist)
    {
        $table = app(\App\Services\Admin\PlaylistsTable::class);
        $filters = request('return_to') === 'directory' ? $table->filters(request()) : null;
        $playlist->update(['published_at' => $playlist->published_at ? null : now()]);

        return ($filters ? $table->redirect($filters) : redirect()->back())->with('status', $playlist->published_at
            ? 'The playlist has been published.'
            : 'The playlist has been unpublished.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:5000', 'ids.*' => 'required|integer|min:1|distinct',
            'original_ids' => 'sometimes|required|array|min:1|max:5000', 'original_ids.*' => 'required|integer|min:1|distinct',
            'group' => 'nullable|string|in:general,journey',
        ]);
        app(\App\Services\Admin\PlaylistOrder::class)->save($data['ids'], $data['original_ids'] ?? null, $data['group'] ?? null);
        if ($request->has('original_ids') && !$request->expectsJson()) {
            return redirect()->route('admin.playlists.index')->with('status', 'The playlist order has been updated.');
        }

        return view('components.alert', [
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
        $table = app(\App\Services\Admin\PlaylistsTable::class);
        $filters = request('return_to') === 'directory' ? $table->filters(request()) : null;
        $playlist->delete();

        return ($filters ? $table->redirect($filters) : redirect()->back())->with('status', 'The playlist has been removed');
    }
}
