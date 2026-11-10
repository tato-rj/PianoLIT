<?php

namespace App\Services\Admin;

use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Storage, Validator};
use Illuminate\Validation\ValidationException;

class QuizMedia
{
    public function type(string $type): string
    {
        abort_unless(in_array($type, ['audio', 'images'], true), 404);
        return $type;
    }

    public function path($path): string
    {
        abort_unless(is_string($path) && preg_match('#\Aquiz/(audio|images)/.+\z#D', $path)
            && !preg_match('#[\\\\\x00-\x1f]|(?:^|/)\.{1,2}(?:/|$)#', $path), 404);
        $disk = Storage::disk('public');
        $root = realpath($disk->path('quiz/'.explode('/', $path)[1]));
        $file = realpath($disk->path($path));
        abort_unless($root && $file && is_file($file) && strpos($file, $root.DIRECTORY_SEPARATOR) === 0, 404);
        return $path;
    }

    public function files(Request $request, string $type, array $returnFilters)
    {
        $this->type($type);
        $data = $request->validate(['media_page' => 'nullable|integer|min:1|max:1000000']);
        $disk = Storage::disk('public');
        $files = collect($disk->allFiles('quiz/'.$type))->map(fn ($path) => [
            'path' => $path, 'name' => basename($path), 'modified' => $disk->lastModified($path),
        ])->sort(function ($a, $b) {
            return ($b['modified'] <=> $a['modified']) ?: strcmp($a['path'], $b['path']);
        })->values();
        $page = min((int) ($data['media_page'] ?? 1), max(1, (int) ceil($files->count() / AdminPagination::PER_PAGE)));
        return new LengthAwarePaginator($files->forPage($page, AdminPagination::PER_PAGE)->values(), $files->count(), AdminPagination::PER_PAGE, $page, [
            'path' => route('admin.quizzes.index'), 'pageName' => 'media_page',
            'query' => $returnFilters + ['manage' => $type],
        ]);
    }

    public function upload(Request $request, string $type, ?string $path = null): array
    {
        $this->type($type);
        $url = app(QuizzesTable::class)->url($request, $type);
        $bag = $path ? 'quizMedia'.md5($path) : 'quizMediaUpload';
        $multiple = !$path && $request->has('files');
        $field = $multiple ? 'files' : 'file';
        $fileRules = $type === 'audio'
            ? 'required|file|mimes:mp3,wav,ogg,m4a|max:2048'
            : 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120';
        $validator = Validator::make($request->all(), $multiple
            ? ['files' => 'required|array|min:1|max:8', 'files.*' => $fileRules] : ['file' => $fileRules]);
        if ($validator->fails()) throw (new ValidationException($validator))->errorBag($bag)->redirectTo($url);
        $files = $multiple ? array_values($request->file('files')) : [$request->file('file')];
        $extensions = $type === 'audio' ? ['mp3', 'wav', 'ogg', 'm4a'] : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $targets = [];
        foreach ($files as $file) {
            $name = $path ? basename($path) : $file->getClientOriginalName();
            $target = $path ?? 'quiz/'.$type.'/'.$name;
            if (preg_match('/[\/\\\\\x00-\x1f]/', $name) || in_array($name, ['.', '..'], true) || mb_strlen($name) > 200) {
                $validator->errors()->add($field, 'Choose a file with a shorter, valid name.');
            } elseif (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions, true)
                || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== strtolower($file->getClientOriginalExtension())) {
                $validator->errors()->add($field, $path ? 'Use the same file format when replacing a file.' : 'Choose a supported file format.');
            } elseif (!$path && (Storage::disk('public')->exists($target) || in_array($target, $targets, true))) {
                $validator->errors()->add($field, 'A file with this name already exists.');
            }
            $targets[] = $target;
        }
        if ($validator->errors()->any()) throw (new ValidationException($validator))->errorBag($bag)->redirectTo($url);
        $saved = [];
        try {
            foreach ($files as $index => $file) {
                $target = $targets[$index];
                abort_unless($file->storeAs(dirname($target), basename($target), 'public'), 500, 'The file could not be saved.');
                $saved[] = $target;
            }
        } catch (\Throwable $exception) {
            if (!$path) Storage::disk('public')->delete($saved);
            throw $exception;
        }
        return $targets;
    }
}
