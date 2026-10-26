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
        return view('admin.pages.playlists.index', $table->get(request()) + ['playlistTags' => app(\App\Services\Admin\PlaylistTags::class)->all()]);
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
            'description' => 'required|string|max:255', 'featured_tag_id' => 'nullable|integer|exists:playlist_tags,id',
            'group' => 'nullable|string|in:general,journey',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);
        $data['group'] = $data['group'] ?? ($request->boolean('is_journey') ? 'journey' : 'general');
        $cover = $request->hasFile('cover') ? $request->file('cover')->store('app/playlists', 'public') : null;
        try {
            $playlist = \DB::transaction(function () use ($data, $cover) {
                $tag = app(\App\Services\Admin\PlaylistTags::class)->assignment($data['featured_tag_id'] ?? null);
                $group = $data['group'] === 'journey' ? 'journey' : null;
                $orders = \DB::table('playlists')->where('group', $group)->lockForUpdate()->pluck('order');
                return Playlist::create([
                    'creator_id' => auth('admin')->id(), 'name' => $data['name'], 'subtitle' => $data['subtitle'],
                    'description' => $data['description'],
                    'cover_path' => $cover, 'group' => $group, 'published_at' => null,
                    'order' => $orders->isEmpty() ? 0 : $orders->max() + 1,
                ] + $tag);
            }, 3);
        } catch (\Throwable $exception) {
            if ($cover) \Storage::disk('public')->delete($cover);
            if ($exception instanceof \Illuminate\Validation\ValidationException) $exception->errorBag('playlistCreate');
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

        $finder = app(\App\Services\Admin\PlaylistPieceFinder::class);
        $restored = (string) old('_playlist_editor') === (string) $playlist->id;
        $selectedPieces = $playlist->pieces()->select('pieces.*')->get();
        if ($restored) {
            $ids = old('pieces', []);
            $ids = is_array($ids) ? array_values(array_unique(array_filter($ids, function ($id) { return is_scalar($id) && ctype_digit((string) $id); }))) : [];
            $records = Piece::select('pieces.*')->with('tags')->whereIn('id', array_slice($ids, 0, 5000))->get()->keyBy('id');
            $selectedPieces = collect($ids)->map(function ($id) use ($records) { return $records->get($id); })->filter()->values();
        }
        return view('admin.pages.playlists.edit.index', [
            'playlist' => $playlist, 'playlistTags' => app(\App\Services\Admin\PlaylistTags::class)->all(),
            'selectedPieces' => $selectedPieces, 'restored' => $restored,
        ] + $finder->get(Request::create(route('admin.playlists.pieces', $playlist))) + $finder->choices());
    }

    public function pieces(Request $request, Playlist $playlist, \App\Services\Admin\PlaylistPieceFinder $finder)
    {
        $data = ['playlist' => $playlist] + $finder->get($request) + $finder->choices();
        return response()->json(['html' => view('admin.pages.playlists.edit.finder.table', $data)->render(), 'total' => $data['pieces']->total()]);
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
        $data = $request->validate([
            'name' => 'required|string|max:255', 'subtitle' => 'required|string|max:72', 'description' => 'required|string|max:255',
            'featured_tag_id' => 'nullable|integer|exists:playlist_tags,id',
            'pieces' => 'sometimes|array|max:5000', 'pieces.*' => 'required|integer|distinct|exists:pieces,id',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);
        $cover = $request->hasFile('cover') ? $request->file('cover')->store('app/playlists', 'public') : null;
        $oldCover = null;
        try {
            \DB::transaction(function () use ($data, $cover, $playlist, &$oldCover) {
                $tag = app(\App\Services\Admin\PlaylistTags::class)->assignment($data['featured_tag_id'] ?? null);
                $record = Playlist::whereKey($playlist->id)->lockForUpdate()->firstOrFail();
                $oldCover = $record->cover_path;
                $record->update([
                    'name' => $data['name'], 'subtitle' => $data['subtitle'], 'description' => $data['description'],
                    'cover_path' => $cover ?? $oldCover,
                ] + $tag);
                $pieces = [];
                foreach (array_values($data['pieces'] ?? []) as $order => $id) $pieces[$id] = ['order' => $order];
                $record->pieces()->sync($pieces);
            }, 3);
        } catch (\Throwable $exception) {
            if ($cover) \Storage::disk('public')->delete($cover);
            throw $exception;
        }
        if ($cover && $oldCover && $cover !== $oldCover) \Storage::disk('public')->delete($oldCover);

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

        return view('components.feedback.alert', [
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
