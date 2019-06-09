<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogMediaController extends Controller
{
    public function audio()
    {
    	$audio = \Storage::disk('public')->allFiles('blog/audio');

    	return view('admin.pages.blog.audio.index', compact('audio'));
    }

    public function storeAudio(Request $request)
    {
        $audio = $request->file('file');

    	if (\Storage::disk('public')->exists('blog/audio/' . $audio->getClientOriginalName()))
    		abort(403, 'An audio with this name already exists');

        $path = $request->file('file')->storeAs('blog/audio', $audio->getClientOriginalName(), 'public');

        return asset('storage/' . $path);
    }

    public function destroyAudio(Request $request)
    {
    	if (! \Storage::disk('public')->exists($request->path))
    		abort(404, 'This file doesn\'t exist');

    	\Storage::disk('public')->delete($request->path);
    	
    	return response(200);
    }

    public function gifts()
    {
        $gifts = \Storage::disk('public')->allFiles('gifts');

        return view('admin.pages.blog.gifts.index', compact('gifts'));
    }

    public function storeGift(Request $request)
    {
        $gift = $request->file('file');

        if (\Storage::disk('public')->exists('gifts/' . $gift->getClientOriginalName()))
            abort(403, 'A gift with this name already exists');

        $path = $request->file('file')->storeAs('gifts', $gift->getClientOriginalName(), 'public');

        return asset('storage/' . $path);
    }

    public function destroyGift(Request $request)
    {
        if (! \Storage::disk('public')->exists($request->path))
            abort(404, 'This file doesn\'t exist');

        \Storage::disk('public')->delete($request->path);
        
        return response(200);
    }
}
