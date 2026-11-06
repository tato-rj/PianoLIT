<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Shop\{Closeups, PrintfulException, Storefront};
use Illuminate\Http\Request;

class ShopCloseupsController extends Controller
{
    private function product(Request $request)
    {
        $kind = $request->route('closeupKind');
        abort_unless(isset(Closeups::PRODUCTS[$kind]), 404);
        // Digital editors retain their existing admin access; merchandise remains manager-only.
        if ($kind === 'merchandise') abort_unless(auth('admin')->user()->isManager(), 403);
        $model = Closeups::PRODUCTS[$kind];
        $parameter = ['merchandise' => 'merchandise', 'ebooks' => 'ebook', 'escores' => 'escore'][$kind];
        $query = $kind === 'merchandise' ? app(Storefront::class)->query() : $model::query();
        $id = $request->route($parameter);
        if ($id instanceof \Illuminate\Database\Eloquent\Model) return $query->findOrFail($id->getKey());
        return $kind === 'merchandise' ? $query->findOrFail($id) : $query->where((new $model)->getRouteKeyName(), $id)->firstOrFail();
    }

    public function store(Request $request, Closeups $closeups)
    {
        $product = $this->product($request);
        $data = $request->validateWithBag('closeup', ['closeup_revision' => 'required|integer|min:0',
            'closeup_image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000']);
        try {
            $closeups->upload($product, (int) $data['closeup_revision'], $request->file('closeup_image'));
        } catch (PrintfulException $exception) {
            return $this->back($request, $product)->with('error', $exception->getMessage());
        }
        return $this->back($request, $product)->with('status', ($request->route('closeupKind') === 'merchandise' ? 'The hover close-up has been saved.' : 'The hover image has been saved.'));
    }

    public function destroy(Request $request, Closeups $closeups)
    {
        $product = $this->product($request);
        $data = $request->validateWithBag('closeup', ['closeup_revision' => 'required|integer|min:0']);
        $closeups->remove($product, (int) $data['closeup_revision']);
        return $this->back($request, $product)->with('status', 'The hover image has been removed. Hover now keeps the regular image.');
    }

    private function back(Request $request, $product)
    {
        return redirect()->route('admin.'.$request->route('closeupKind').'.edit', $product)->withFragment('hover-closeup');
    }
}
