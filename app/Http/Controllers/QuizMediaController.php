<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizMediaController extends Controller
{
    public function audio()
    {
    	$audio = \Storage::disk('public')->allFiles('quiz/audio');

    	return view('admin.pages.quizzes.audio.index', compact('audio'));
    }

    public function store(Request $request)
    {
        $audio = $request->file('file');

    	if (\Storage::disk('public')->exists('quiz/audio/' . $audio->getClientOriginalName()))
    		abort(403, 'An audio with this name already exists');

        $path = $request->file('file')->storeAs('quiz/audio', $audio->getClientOriginalName(), 'public');

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
