<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PlaylistTag;
use App\Services\Admin\PlaylistTags;
use Illuminate\Http\Request;

class PlaylistTagsController extends Controller
{
    public function store(Request $request, PlaylistTags $tags)
    {
        $tags->save($request->input('name'));
        return $this->response($tags, 'The tag has been created.', 201);
    }

    public function update(Request $request, PlaylistTag $playlistTag, PlaylistTags $tags)
    {
        $tags->save($request->input('name'), $playlistTag->id);
        return $this->response($tags, 'The tag has been updated.');
    }

    public function destroy(PlaylistTag $playlistTag, PlaylistTags $tags)
    {
        $tags->delete($playlistTag->id);
        return $this->response($tags, 'The tag has been deleted.');
    }

    private function response(PlaylistTags $service, $message, $status = 200)
    {
        $playlistTags = $service->all();
        return response()->json([
            'message' => $message,
            'tags' => $playlistTags->map(function ($tag) { return ['id' => $tag->id, 'name' => $tag->name]; })->values(),
            'html' => view('admin.pages.playlists.tags.table', compact('playlistTags'))->render(),
        ], $status);
    }
}
