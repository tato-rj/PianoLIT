<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;
use App\Shop\{eBook, eScore};
use Illuminate\Http\Request;

/** Web-only presentation; shared/mobile model serialization stays unchanged. */
class ProductPage
{
    public function data($item, Request $request): array
    {
        $catalog = app(Storefront::class);
        $digital = $item instanceof eBook || $item instanceof eScore;
        if (!$digital && !$item instanceof MerchandiseProduct) throw new \InvalidArgumentException('Unsupported shop product.');
        $product = $digital ? $catalog->presentDigital($item) : $catalog->present($item);
        $product['name'] = rtrim($product['name'], '. ');
        $product['subtitle'] = $digital ? $item->subtitle : 'A little piano, wherever you go.';
        $product['hasPreview'] = $digital && count($item->previews) > 0;
        $product['originalPrice'] = null;
        $product['gallery'] = $digital
            ? collect([$item->mockup_image(), $item->cover_path ? $item->cover_image() : null])->filter()->unique()->values()
                ->map(fn ($url) => ['url' => $url, 'alt' => $product['name'].' cover'])
            : $product['images']->map(fn ($image) => ['url' => $image->url, 'alt' => $image->alt]);
        $breadcrumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Shop', 'url' => route('shop.home')]];
        if (isset(Storefront::SECTIONS[$product['category']])) {
            $breadcrumbs[] = ['label' => Storefront::SECTIONS[$product['category']], 'url' => $catalog->categoryUrl($product['category'])];
        }
        $breadcrumbs[] = ['label' => $product['name']];
        $data = ['product' => $product, 'breadcrumbs' => $breadcrumbs, 'digitalProduct' => null,
            'shoppingBag' => app(MerchandiseBag::class)->contents($request)];
        if (!$digital) return $data;

        $user = auth('web')->user();
        $eligible = $user && $user->isEligibleForFreeMonthlyProduct();
        if ($eligible || $item->isFree()) $data['product']['price'] = 'Free';
        if ($item->price > 0 && ($eligible || $item->hasDiscount())) $data['product']['originalPrice'] = $catalog->money((int) round($item->price * 100), 'USD');
        $reviews = $item->publishedReviews()->latest()->get();
        $data['digitalProduct'] = $item;
        $data['productKind'] = $product['label'];
        $data['formats'] = implode(' + ', array_keys($item->links()));
        $data['reviews'] = $reviews;
        $data['reviewCount'] = $reviews->count();
        $data['averageRating'] = $reviews->count() ? number_format($reviews->avg('rating'), 1) : 0;
        $data['ownedReview'] = $user ? $user->reviewFor($item) : null;
        $data['relatedProducts'] = $item->suggestions(4)->get()->map(fn ($related) => $catalog->presentDigital($related));
        $data['shareable'] = ['keywords' => $item->keywords(), 'title' => $item->title, 'description' => $item->subtitle,
            'thumbnail' => $product['imageUrl'], 'created_at' => $item->created_at, 'updated_at' => $item->updated_at];
        return $data;
    }
}
