<?php

namespace App\Services\Shop;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DigitalEditor
{
    public function update(Request $request, $product, string $kind): void
    {
        $title = app(ProductNames::class)->validate($request->input('title'), $product);
        $extra = $kind === 'ebooks' ? 'epub' : 'audio';
        $data = $request->validate([
            'media_revision' => 'required|integer|min:0', 'title' => 'required|string|max:120',
            'author' => 'nullable|string|max:120', 'subtitle' => 'required|string|max:238',
            'description' => 'nullable|string|max:100000', 'pages_count' => 'required|integer|min:1|max:10000',
            'price' => 'required|numeric|min:0|max:10000', 'discount' => 'nullable|integer|min:0|max:100',
            'topics' => 'nullable|array', 'topics.*' => 'integer|distinct|exists:'.($kind === 'ebooks' ? 'e_book_topics' : 'e_score_topics').',id',
            'pdf_file' => 'nullable|file|mimes:pdf|max:51200',
            $extra.'_file' => $extra === 'epub' ? 'nullable|file|mimes:epub,zip|max:51200' : 'nullable|file|mimes:mp3,wav,ogg,m4a,mp4|max:51200',
        ]);
        $data['title'] = $title;
        $files = [];
        $pages = null;
        try {
            foreach (['pdf', $extra] as $format) {
                if ($request->hasFile($format.'_file')) {
                    $path = $request->file($format.'_file')->store('app/'.$kind.'/'.$format, 'public');
                    if (!$path) throw new \RuntimeException('The file could not be saved.');
                    $files[$format.'_path'] = $path;
                }
            }
            if (isset($files['pdf_path'])) $pages = app(DigitalPreview::class)->render($files['pdf_path']);
            app(DigitalMedia::class)->change($product, (int) $data['media_revision'], function ($state, $locked) use ($data, $files, $pages) {
                $locked->update(array_intersect_key($data, array_flip(['title', 'author', 'subtitle', 'description', 'pages_count', 'price']))
                    + ['discount' => $data['discount'] ?? 0] + $files);
                $locked->topics()->sync($data['topics'] ?? []);
                if ($pages !== null) {
                    $state['preview_pages'] = $pages;
                    $state['preview_source'] = hash('sha256', $files['pdf_path']);
                }
                return $state;
            });
            // Retain previous download files: historical encrypted links must still work.
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_merge(array_values($files), $pages ?? []));
            throw $exception;
        }
    }
}
