<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\{MerchandiseBag, ProductPage, Storefront};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MerchandiseController extends Controller
{
    public function index(Request $request, Storefront $catalog, MerchandiseBag $bag, ?string $category = null)
    {
        $pageRules = array_fill_keys(array_map(fn ($category) => $category.'_page', array_keys(Storefront::SECTIONS)), 'nullable|integer|min:1|max:10000');
        $filters = $request->validateWithBag('storefront', ['q' => 'nullable|string|max:100',
            'category' => ['nullable', Rule::in(array_keys(Storefront::CATEGORIES))],
            'topics' => 'nullable|array|max:20', 'topics.*' => 'string|max:255',
            'page' => 'nullable|integer|min:1|max:10000'] + $pageRules);
        if (!$category && $request->query->has('category')) {
            return $this->redirectWithQuery($request, $catalog->categoryUrl($filters['category'] ?? null), ['category']);
        }
        $search = trim($filters['q'] ?? '');
        $topics = in_array($category, ['ebooks', 'escores'], true) ? ($filters['topics'] ?? []) : [];
        $products = $category ? $catalog->products($search, $category, $topics) : null;
        $productGroups = $category ? collect() : $catalog->grouped($search);
        $categories = Storefront::CATEGORIES;
        if ($category === 'merchandise') $categories[$category] = 'Merchandise';
        $categoryUrls = array_map(fn ($key) => $catalog->categoryUrl($key), array_combine(array_keys($categories), array_keys($categories)));
        $breadcrumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Shop', 'url' => $category ? route('shop.home') : null]];
        if ($category) $breadcrumbs[] = ['label' => $categories[$category]];
        return view('shop.storefront.index', ['products' => $products, 'productGroups' => $productGroups,
            'hasProducts' => $category ? $products->count() > 0 : $productGroups->sum(fn ($group) => $group->count()) > 0,
            'sectionLabels' => Storefront::SECTIONS, 'search' => $search, 'category' => $category, 'topics' => $topics,
            'categories' => $categories, 'categoryUrls' => $categoryUrls, 'catalogUrl' => $catalog->categoryUrl($category),
            'breadcrumbs' => $breadcrumbs, 'shoppingBag' => $bag->contents($request)]);
    }

    public function show(Request $request, string $category, string $slug, Storefront $catalog, ProductPage $page)
    {
        $product = $catalog->query()->with('images')->where('slug', $slug)->firstOrFail();
        if ($catalog->category($product) !== $category || $product->slug !== $slug) {
            return $this->redirectWithQuery($request, $catalog->productUrl($product));
        }
        return view('shop.storefront.show', $page->data($product, $request));
    }

    public function legacy(Request $request, int $merchandise, Storefront $catalog)
    {
        $product = $catalog->query()->findOrFail($merchandise);
        return $this->redirectWithQuery($request, $catalog->productUrl($product));
    }

    private function redirectWithQuery(Request $request, string $url, array $remove = [])
    {
        $parts = array_filter(explode('&', $request->server('QUERY_STRING', '')), fn ($part) => $part !== ''
            && !in_array(urldecode(explode('=', $part, 2)[0]), $remove, true));
        return redirect()->to($url.($parts ? '?'.implode('&', $parts) : ''), 301);
    }

    public function bag(Request $request, MerchandiseBag $bag)
    {
        return view('shop.storefront.bag', ['shoppingBag' => $bag->contents($request)]);
    }

    public function add(Request $request, MerchandiseBag $bag)
    {
        $data = $request->validateWithBag('storefront', ['product_id' => 'required|integer|min:1', 'variant_id' => 'required|integer|min:1', 'quantity' => 'required|integer|min:1|max:10']);
        $contents = $bag->put($request, $data['product_id'], $data['variant_id'], $data['quantity'], true);
        if ($request->expectsJson()) {
            $line = $contents['lines'][(int) $data['product_id'].':'.(int) $data['variant_id']];
            return response()->json(['count' => $contents['count'], 'message' => 'Added to your bag',
                'item' => ['product_id' => $line['product']['id'], 'variant_id' => $line['variant']['id'], 'quantity' => $line['quantity']]]);
        }
        return redirect()->route('shop.bag')->with('status', 'Added to your bag');
    }

    public function update(Request $request, int $merchandise, int $variant, MerchandiseBag $bag)
    {
        $data = $request->validateWithBag('storefront', ['quantity' => 'required|integer|min:1|max:10']);
        abort_unless(isset($request->session()->get(MerchandiseBag::SESSION_KEY, [])[$merchandise.':'.$variant]), 404);
        $bag->put($request, $merchandise, $variant, $data['quantity']);
        return redirect()->route('shop.bag');
    }

    public function remove(Request $request, int $merchandise, int $variant)
    {
        $saved = $request->session()->get(MerchandiseBag::SESSION_KEY, []);
        unset($saved[$merchandise.':'.$variant]);
        $request->session()->put(MerchandiseBag::SESSION_KEY, $saved);
        return redirect()->route('shop.bag')->with('status', 'Removed from your bag');
    }
}
