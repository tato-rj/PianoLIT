<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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

    public function images()
    {
        $files = [];

        foreach (\Storage::disk('public')->allFiles('quiz/images') as $index => $file) {
            $files[$index]['file'] = $file;
            $files[$index]['created_at'] = carbon(\Storage::disk('public')->lastModified($file))->toDateString();
        }

        $count = count($files);
        $files = collect($files)->groupBy('created_at')->sortKeysDesc();

        return view('admin.pages.quizzes.images.index', compact(['files', 'count']));
    }

    public function store(Request $request, $type)
    {
        if (! in_array($type, ['audio', 'images']))
            abort(403, 'This is not a valid media type');

        $file = $request->file('file');

    	if (\Storage::disk('public')->exists('quiz/'.$type.'/' . $file->getClientOriginalName()))
    		abort(403, 'A file with this name already exists');

        $path = $request->file('file')->storeAs('quiz/'.$type, $file->getClientOriginalName(), 'public');

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
