<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Shop\{DigitalMedia, DigitalPreview, PrintfulException};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DigitalMediaController extends Controller
{
    private function product(Request $request)
    {
        $kind = $request->route('digitalKind');
        abort_unless(isset(DigitalMedia::PRODUCTS[$kind]), 404);
        $model = DigitalMedia::PRODUCTS[$kind];
        $value = $request->route($kind === 'ebooks' ? 'ebook' : 'escore');
        return $value instanceof $model ? $value : $model::where((new $model)->getRouteKeyName(), $value)->firstOrFail();
    }

    public function upload(Request $request, DigitalMedia $media)
    {
        $product = $this->product($request);
        $data = $request->validate(['media_revision' => 'required|integer|min:0', 'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000']);
        try { $media->upload($product, (int) $data['media_revision'], $request->file('images')); }
        catch (PrintfulException $exception) { return $this->back($request, $product)->with('error', $exception->getMessage()); }
        return $this->back($request, $product)->with('status', 'Display images uploaded.');
    }

    public function action(Request $request, DigitalMedia $media)
    {
        $product = $this->product($request);
        $data = $request->validate(['media_revision' => 'required|integer|min:0', 'action' => 'required|in:up,down,main,remove,restore']);
        $media->imageAction($product, (int) $data['media_revision'], (string) $request->route('image'), $data['action']);
        return $this->back($request, $product)->with('status', 'Display images updated.');
    }

    public function preview(Request $request, DigitalMedia $media, DigitalPreview $preview)
    {
        $product = $this->product($request);
        $data = $request->validate(['media_revision' => 'required|integer|min:0']);
        abort_unless($product->pdf_path, 422);
        $pages = $preview->render($product->pdf_path);
        try {
            $media->change($product, (int) $data['media_revision'], function ($state, $locked) use ($product, $pages) {
                abort_unless($locked->pdf_path === $product->pdf_path, 409);
                $state['preview_pages'] = $pages;
                $state['preview_source'] = hash('sha256', $product->pdf_path);
                return $state;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($pages);
            throw $exception;
        }
        return $this->back($request, $product, 'look-inside')->with('status', 'Look inside preview generated.');
    }

    private function back(Request $request, $product, string $fragment = 'display-images')
    {
        return redirect()->route('admin.'.$request->route('digitalKind').'.edit', $product)->withFragment($fragment);
    }
}
