<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizMediaController extends Controller
{
    public function audio()
    {
        $files = [];

        foreach (\Storage::disk('public')->allFiles('quiz/audio') as $index => $file) {
            $files[$index]['file'] = $file;
            $files[$index]['created_at'] = carbon(\Storage::disk('public')->lastModified($file))->toDateString();
        }

        $count = count($files);
        $files = collect($files)->groupBy('created_at')->sortKeysDesc();

    	return view('admin.pages.quizzes.audio.index', compact(['files', 'count']));
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
