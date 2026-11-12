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
        if (!$digital) {
            $variant = filter_var($request->query('variant'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $product = array_replace($product, app(MerchandiseGallery::class)->present($item, $product['variants'], $product['images'], $variant ?: null));
            if ($selected = $product['selectedVariant']) {
                $product['price'] = $selected['price'];
                $product['priceVaries'] = false;
            }
        }
        $multipleColors = !$digital && collect($product['variants'])->pluck('color')->unique()->count() > 1;
        $mainColor = $product['galleryColor'] ?? null;
        if (!$multipleColors || $product['gallery']->contains('color', $mainColor)) {
            $closeups = app(Closeups::class);
            $hoverUrl = $closeups->url($closeups->for($item));
            if ($hoverUrl && !$product['gallery']->contains('url', $hoverUrl)) {
                // Bind the extra view to the initial main color, without changing sellable colors.
                $product['gallery']->push(['url' => $hoverUrl, 'alt' => $product['name'].' · Detail image', 'color' => $mainColor, 'detail' => true]);
            }
        }
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
        $metadata = app(SearchMetadata::class);
        if (!$digital) return $data + $metadata->product($data, true, false);

        $eligible = $user && $user->isEligibleForFreeMonthlyProduct();
        if ($eligible || $item->isFree()) $data['product']['price'] = 'Free';
        if ($item->price > 0 && ($eligible || $item->hasDiscount())) $data['product']['originalPrice'] = $catalog->money((int) round($item->price * 100), 'USD');
        $data['digitalProduct'] = $item;
        $data['formats'] = implode(' + ', array_keys($item->links()));
        return $data + $metadata->product($data, $item->isPublished(), $eligible && !$item->isFree());
    }
}
