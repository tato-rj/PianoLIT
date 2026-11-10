<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Storage, Validator};
use Illuminate\Validation\ValidationException;

class BlogAudio
{
    public function all(): array
    {
        $files = Storage::disk('public')->allFiles('blog/audio');
        natcasesort($files);
        return array_values($files);
    }

    public function key(string $path): string
    {
        return 'blogAudio'.substr(sha1($path), 0, 16);
    }

    private function ownedPath($path): string
    {
        abort_unless(is_string($path) && strpos($path, 'blog/audio/') === 0
            && !preg_match('#[\\\\\x00-\x1f]|(?:^|/)\.{1,2}(?:/|$)#', $path), 404);
        $disk = Storage::disk('public');
        $root = realpath($disk->path('blog/audio'));
        $file = realpath($disk->path($path));
        abort_unless($root && $file && is_file($file) && strpos($file, $root.DIRECTORY_SEPARATOR) === 0, 404);
        return $path;
    }

    public function save(Request $request, bool $replace = false)
    {
        $path = $replace ? $this->ownedPath($request->input('path')) : null;
        $url = app(PostsTable::class)->modalUrl($request, 'audio');
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:2048|mimes:mp3,wav,m4a,ogg,oga',
        ]);
        $validator->after(function ($validator) use ($request, $path) {
            if ($validator->errors()->has('file')) return;
            $file = $request->file('file');
            $name = $file->getClientOriginalName();
            if (!in_array(strtolower($file->getClientOriginalExtension()), ['mp3', 'wav', 'm4a', 'ogg', 'oga'], true)) {
                $validator->errors()->add('file', 'Choose an MP3, WAV, M4A or OGG file.');
            } elseif (mb_strlen($name) > 180 || preg_match('#[/\\\\\x00-\x1f]#', $name) || in_array($name, ['.', '..'], true)) {
                $validator->errors()->add('file', 'Choose a file with a shorter name and no path separators.');
            } elseif ($path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== strtolower($file->getClientOriginalExtension())) {
                $validator->errors()->add('file', 'Use the same file format when replacing audio.');
            } elseif (!$path && Storage::disk('public')->exists('blog/audio/'.$name)) {
                $validator->errors()->add('file', 'An audio file with this name already exists. Use Replace file to update it.');
            }
        });
        if ($validator->fails()) throw (new ValidationException($validator))
            ->errorBag($path ? $this->key($path) : 'blogAudioCreate')->redirectTo($url);
        $file = $request->file('file');
        // Replacement retains the URL already embedded in existing articles.
        $path = $path ?? 'blog/audio/'.$file->getClientOriginalName();
        Storage::disk('public')->makeDirectory(dirname($path));
        $staged = $file->store('blog/audio-staging', 'public');
        try {
            $disk = Storage::disk('public');
            if ($replace) {
                if (!rename($disk->path($staged), $disk->path($path))) throw new \RuntimeException('Audio could not be saved.');
            } elseif (!@link($disk->path($staged), $disk->path($path))) {
                if (!$disk->exists($path)) throw new \RuntimeException('Audio could not be saved.');
                $validator->errors()->add('file', 'An audio file with this name already exists. Use Replace file to update it.');
                throw (new ValidationException($validator))->errorBag('blogAudioCreate')->redirectTo($url);
            }
        } finally {
            Storage::disk('public')->delete($staged);
        }
        if ($request->ajax()) return asset('storage/'.$path);
        return redirect($url)->with('status', $replace ? 'Audio replaced. Existing article links are unchanged.' : 'Audio uploaded.');
    }

    public function delete(Request $request)
    {
        $path = $this->ownedPath($request->input('path'));
        $url = app(PostsTable::class)->modalUrl($request, 'audio');
        abort_unless(Storage::disk('public')->delete($path), 500, 'Audio could not be deleted.');
        if ($request->ajax()) return response('', 200);
        return redirect($url)->with('status', 'Audio deleted.');
    }
}
