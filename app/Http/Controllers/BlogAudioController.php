<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogAudioController extends Controller
{
    public function index()
    {
    	$audio = \Storage::disk('public')->allFiles('blog/audio');

    	return view('admin.pages.blog.audio.index', compact('audio'));
    }

    public function store(Request $request)
    {
    	if (\Storage::disk('public')->exists('blog/audio/teste.mp3'))
    		abort(403, 'An audio with this name already exists');

    	$audio = $request->file('file');

        $path = $request->file('file')->storeAs('blog/audio', $audio->getClientOriginalName(), 'public');

        return asset('storage/' . $path);
    }

    public function destroy(Request $request)
    {
    	if (! \Storage::disk('public')->exists($request->path))
    		abort(404, 'This file doesn\'t exist');

    	\Storage::disk('public')->delete($request->path);
    	
    	return response(200);
    }
}
