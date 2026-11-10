<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\{QuizMedia, QuizzesTable};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuizMediaController extends Controller
{
    public function audio(Request $request, QuizzesTable $table)
    {
        return redirect($table->url($request, 'audio'));
    }

    public function images(Request $request, QuizzesTable $table)
    {
        return redirect($table->url($request, 'images'));
    }

    public function store(Request $request, string $type, QuizMedia $media, QuizzesTable $table)
    {
        $paths = $media->upload($request, $type);
        if ($request->ajax()) return asset('storage/'.$paths[0]);
        return redirect($table->url($request, $type))->with('status', count($paths) === 1 ? 'File uploaded.' : 'Files uploaded.');
    }

    public function update(Request $request, QuizMedia $media, QuizzesTable $table)
    {
        $path = $media->path($request->input('path'));
        $type = explode('/', $path)[1];
        $media->upload($request, $type, $path);
        return redirect($table->url($request, $type))->with('status', 'File replaced.');
    }

    public function destroy(Request $request, QuizMedia $media, QuizzesTable $table)
    {
        $path = $media->path($request->input('path'));
        $type = explode('/', $path)[1];
        $url = $table->url($request, $type);
        abort_unless(Storage::disk('public')->delete($path), 500, 'The file could not be deleted.');
        if ($request->ajax()) return response(200);
        return redirect($url)->with('status', 'File deleted.');
    }
}
