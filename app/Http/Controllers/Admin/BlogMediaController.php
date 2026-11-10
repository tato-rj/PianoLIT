<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\{BlogAudio, PostsTable};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BlogMediaController extends Controller
{
    public function audio(Request $request, PostsTable $table)
    {
        return redirect($table->modalUrl($request, 'audio'));
    }

    public function storeAudio(Request $request, BlogAudio $audio)
    {
        return $audio->save($request);
    }

    public function updateAudio(Request $request, BlogAudio $audio)
    {
        return $audio->save($request, true);
    }

    public function destroyAudio(Request $request, BlogAudio $audio)
    {
        return $audio->delete($request);
    }

    public function gifts()
    {
        return redirect()->route('admin.posts.index');
    }

    public function storeGift()
    {
        abort(410, 'Blog gifts are no longer available.');
    }

    public function destroyGift()
    {
        abort(410, 'Blog gifts are no longer available.');
    }
}
