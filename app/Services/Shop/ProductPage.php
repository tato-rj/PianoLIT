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
        $digitalMedia = $digital ? app(DigitalMedia::class) : null;
        $digitalState = $digital ? $digitalMedia->state($item) : null;
        $digitalGallery = $digital ? $digitalMedia->gallery($item, $digitalState) : collect();
        $previewPages = $digital ? $digitalMedia->previews($item, $digitalState) : [];
        $product = $digital ? $catalog->presentDigital($item, ['imageUrl' => $digitalGallery->first()['url'] ?? null]) : $catalog->present($item);
        $product['name'] = rtrim($product['name'], '. ');
        $product['subtitle'] = $item->subtitle;
        $product['hasPreview'] = count($previewPages) > 0;
        $product['originalPrice'] = null;
        $product['gallery'] = $digitalGallery;
        if (!$digital) $product = array_replace($product, app(MerchandiseGallery::class)->present($item, $product['variants'], $product['images']));
        $product['materials'] = $digital ? [] : app(MerchandiseMaterials::class)->forProduct($item, $product['variants']);
        $product['sizeChart'] = !$digital && $product['category'] === 't-shirts' ? SizeCharts::forProduct($item) : null;
        $breadcrumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Shop', 'url' => route('shop.home')]];
        if (isset(Storefront::SECTIONS[$product['category']])) {
            $breadcrumbs[] = ['label' => Storefront::SECTIONS[$product['category']], 'url' => $catalog->categoryUrl($product['category'])];
        }
        $breadcrumbs[] = ['label' => $product['name']];
        $user = auth('web')->user();
        $reviews = $item->publishedReviews()->latest()->get();
        $data = ['product' => $product, 'breadcrumbs' => $breadcrumbs, 'digitalProduct' => null, 'reviewProduct' => $item, 'previewPages' => $previewPages,
            'reviews' => $reviews, 'reviewCount' => $reviews->count(),
            'averageRating' => $reviews->count() ? number_format($reviews->avg('rating'), 1) : 0,
            'ownedReview' => $user ? $user->reviewFor($item) : null, 'productKind' => $product['label'],
            'shoppingBag' => app(MerchandiseBag::class)->contents($request), 'relatedProducts' => $catalog->related($item)];
        if (!$digital) return $data;

        $eligible = $user && $user->isEligibleForFreeMonthlyProduct();
        if ($eligible || $item->isFree()) $data['product']['price'] = 'Free';
        if ($item->price > 0 && ($eligible || $item->hasDiscount())) $data['product']['originalPrice'] = $catalog->money((int) round($item->price * 100), 'USD');
        $data['digitalProduct'] = $item;
        $data['formats'] = implode(' + ', array_keys($item->links()));
        $data['shareable'] = ['keywords' => $item->keywords(), 'title' => $item->title, 'description' => $item->subtitle,
            'thumbnail' => $product['imageUrl'], 'created_at' => $item->created_at, 'updated_at' => $item->updated_at];
        return $data;
    }
}
