<?php

namespace App\Services\Shop;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchMetadata
{
    private const CATALOGUE = [
        '' => ['PianoLIT Shop | Piano Books, Sheet Music & Merchandise', 'Shop for piano eBooks, downloadable sheet music, and piano-themed T-shirts and stickers from PianoLIT.'],
        't-shirts' => ['Piano T-shirts', 'Browse piano-themed T-shirts and see the available designs, colors, sizes, and materials.'],
        'stickers' => ['Piano Stickers', 'Browse stickers featuring piano and music designs. See product images and available options.'],
        'ebooks' => ['Piano eBooks', 'Browse digital books on piano technique, music theory, rhythm, and sight-reading. See the available formats for each edition.'],
        'escores' => ['Digital Piano Sheet Music', 'Browse downloadable piano sheet music, including individual pieces and collections. See edition details and available score previews.'],
        'merchandise' => ['Piano Merchandise', 'Browse physical products from PianoLIT Shop and see the available designs and options.'],
    ];

    public function catalogue(?string $category, LengthAwarePaginator $products, ?string $canonical): array
    {
        [$searchTitle, $searchDescription] = self::CATALOGUE[$category ?? ''];
        if ($category) $searchTitle .= ' | PianoLIT Shop';
        if ($products->currentPage() > 1) $searchTitle .= ' | Page '.$products->currentPage();
        $items = $products->getCollection()->values()->map(fn ($product, $index) => [
            '@type' => 'ListItem',
            'position' => $products->firstItem() + $index,
            'name' => $product['name'],
            'url' => $product['url'],
        ]);
        $searchEntity = [
            '@type' => 'CollectionPage', 'url' => $canonical, 'name' => $searchTitle, 'description' => $searchDescription,
            'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $items],
        ];

        return compact('canonical', 'searchTitle', 'searchDescription', 'searchEntity');
    }

    public function product(array $page, bool $public, bool $personalPrice): array
    {
        $product = $page['product'];
        $edition = $page['digitalProduct'];
        $name = $product['name'];
        $canonical = $public ? $product['url'] : null;
        $title = $name;
        $author = $edition ? $this->text($edition->author) : '';
        if ($author !== '' && $product['category'] === 'escores' && !Str::contains(Str::lower($title), Str::lower($author))) {
            $title .= ' by '.$author;
        }
        $searchTitle = $title.($edition ? ($product['category'] === 'ebooks' ? ' | eBook' : ' | Piano Sheet Music') : '').' | PianoLIT Shop';
        $facts = [];
        if ($edition) {
            $facts['Edition'] = $product['label'].' · Digital download';
            if ($page['formats']) $facts['Format'] = $page['formats'];
            if ($edition->pages_count) $facts['Length'] = $edition->pages_count.' pages';
            if ($author !== '') $facts[$product['category'] === 'ebooks' ? 'Author' : 'Composer'] = $author;
        }
        $materials = $product['materials'];
        $material = !$edition && count($materials) === 1 && $materials[0]['label'] === null ? $this->text($materials[0]['text']) : '';
        $description = $this->text($product['subtitle'] ?? '') ?: $this->text($edition ? $edition->description : $product['description']);
        if ($description === '') {
            $description = $title.' is '.($edition ? 'a digital '.($product['category'] === 'ebooks' ? 'piano book' : 'piano sheet music edition')
                : ($product['category'] === 'merchandise' ? 'an item' : 'a '.Str::lower($product['label']))).' from PianoLIT Shop.';
            if ($edition && $page['formats']) $description .= ' Available in '.$page['formats'].' format.';
            if (!$edition && $material !== '') $description .= ' Material: '.rtrim($material, '.').'.';
            if (!$edition && ($colors = array_diff($product['galleryColors'], ['Original']))) {
                $description .= ' Available in '.implode(', ', $colors).'.';
            }
        }
        $searchDescription = Str::limit($description, 157, '…');
        $gallery = $product['gallery']->reject(fn ($image) => !empty($image['detail']));
        $searchImage = $this->images($product['digital'] ? $gallery : $gallery->where('color', $product['galleryColor']))[0] ?? null;
        $searchEntity = [
            '@type' => 'Product', '@id' => $product['url'].'#product', 'name' => $name, 'url' => $product['url'],
            'description' => $description, 'category' => $edition && $product['category'] === 'escores' ? 'Piano sheet music' : $product['label'],
        ];
        if ($images = $this->images($gallery)) $searchEntity['image'] = $images;
        if ($material !== '') $searchEntity['material'] = $material;
        if ($facts) {
            $searchEntity['additionalProperty'] = [];
            foreach ($facts as $label => $value) $searchEntity['additionalProperty'][] = ['@type' => 'PropertyValue', 'name' => $label, 'value' => $value];
        }
        // The public summary can include editorial entries; only mark it up if all ratings come from customers.
        if ($page['reviewCount'] > 0 && $page['reviews']->every(fn ($review) => $review->user_exists && $review->rating >= 1 && $review->rating <= 5)) {
            $searchEntity['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => (float) $page['averageRating'], 'reviewCount' => $page['reviewCount']];
        }

        $checkout = app(CheckoutEnvironment::class);
        if ($public && $edition && !$personalPrice && $page['formats']) {
            $price = (int) $edition->finalPrice(true);
            if ($edition->isFree() || ($price > 0 && $checkout->available('digital'))) {
                $searchEntity['offers'] = $this->offer($product['url'], 'USD', $edition->isFree() ? 0 : $price);
            }
        }
        if ($public && !$edition && $product['selectedVariant'] && $checkout->available()) {
            $searchEntity = $this->merchandiseOffers($searchEntity, $product, $gallery);
        }

        return compact('canonical', 'searchTitle', 'searchDescription', 'searchImage', 'searchEntity');
    }

    private function merchandiseOffers(array $entity, array $product, Collection $gallery): array
    {
        $variants = collect($product['variants'])->filter(fn ($variant) => in_array($variant['color'], $product['galleryColors'], true))->values();
        $dimensions = array_filter(['color', 'size'], fn ($key) => $variants->pluck($key)->unique()->count() > 1);
        if (!$dimensions) {
            $variant = $product['selectedVariant'];
            $entity['offers'] = $this->offer($product['url'], $variant['currency'], $variant['cents']);

            return $entity;
        }

        $entity['@type'] = 'ProductGroup';
        $entity['productGroupID'] = 'pianolit-merchandise-'.$product['id'];
        $entity['variesBy'] = array_values(array_map(fn ($key) => 'https://schema.org/'.$key, $dimensions));
        $entity['hasVariant'] = $variants->map(function ($variant) use ($product, $gallery, $entity) {
            $url = $product['url'].'?variant='.$variant['id'];
            return [
                '@type' => 'Product', '@id' => $url.'#product', 'sku' => 'pianolit-variant-'.$variant['id'], 'url' => $url,
                'name' => $product['name'].' ('.$variant['color'].', '.$variant['size'].')',
                'description' => $entity['description'].' Color '.$variant['color'].'. Size '.$variant['size'].'.',
                'color' => $variant['color'], 'size' => $variant['size'], 'image' => $this->images($gallery->where('color', $variant['color'])),
                'offers' => $this->offer($url, $variant['currency'], $variant['cents']),
            ];
        })->all();

        return $entity;
    }

    private function offer(string $url, string $currency, int $cents): array
    {
        return [
            '@type' => 'Offer', 'url' => $url, 'price' => number_format($cents / 100, 2, '.', ''),
            'priceCurrency' => $currency, 'availability' => 'https://schema.org/InStock',
            'seller' => ['@type' => 'Organization', 'name' => 'PianoLIT'],
        ];
    }

    private function images(Collection $gallery): array
    {
        return $gallery->pluck('url')->filter()->unique()->values()->all();
    }

    private function text(?string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace('><', '> <', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
