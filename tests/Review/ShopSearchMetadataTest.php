<?php

namespace Tests\Review;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\Storefront;
use App\Shop\{eBook, eBookTopic, eScore};
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Http, Redis};
use Stevebauman\Location\Facades\Location;

class ShopSearchMetadataTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['services.printful.store_id' => '12345', 'shop-checkout.mode' => 'demo']);
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    private function edition(string $class, string $slug, array $attributes = [])
    {
        return Model::withoutEvents(fn () => create($class, $attributes + [
            'title' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug, 'published_at' => now(),
            'subtitle' => '', 'description' => '<p>Five practice pieces &amp; their scores.</p>',
            'price' => 12, 'discount' => 0, 'pdf_path' => 'scores/fixture.pdf', 'cover_path' => 'covers/fixture.jpg',
            'mockup_path' => null, 'previews' => '',
        ]));
    }

    private function xpath($response): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());

        return new \DOMXPath($dom);
    }

    private function entity($response): array
    {
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//head/script[@type="application/ld+json"]'));
        $graph = json_decode($xpath->evaluate('string(//head/script[@type="application/ld+json"])'), true, 512, JSON_THROW_ON_ERROR)['@graph'];
        $this->assertSame('BreadcrumbList', $graph[0]['@type']);

        return $graph[1];
    }

    private function sitemap(): array
    {
        $response = $this->get(route('shop.sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        return array_map('strval', simplexml_load_string($response->getContent())->xpath('//*[local-name()="loc"]'));
    }

    public function test_newly_published_items_automatically_get_metadata_and_enter_the_sitemap()
    {
        $edition = $this->edition(eScore::class, 'preludes', ['published_at' => null, 'discount' => 25, 'pages_count' => 18, 'author' => 'Test Composer']);
        $url = $edition->showRoute();
        $draft = $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->assertCount(0, $this->xpath($draft)->query('//head/link[@rel="canonical"] | //head/script[@type="application/ld+json"]'));
        $this->assertNotContains($url, $this->sitemap());
        $this->assertNotContains(route('escores.index'), $this->sitemap());
        $edition->update(['published_at' => now()]);
        $reviewer = Model::withoutEvents(fn () => create(User::class));
        $edition->reviews()->create(['user_id' => $reviewer->id, 'rating' => 5, 'published_at' => now()]);
        $edition->reviews()->create(['rating' => 2, 'published_at' => null]);

        $response = $this->get($url.'?utm_source=audit')->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $head = $this->xpath($response);
        $this->assertSame($url, $head->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame('Preludes', $head->evaluate('string(//*[@data-product-detail]//h1)'));
        $this->assertStringContainsString('Preludes by Test Composer | Piano Sheet Music', $head->evaluate('string(//title)'));
        $entity = $this->entity($response);
        $this->assertSame('Product', $entity['@type']);
        $this->assertSame('Five practice pieces & their scores.', $entity['description']);
        $this->assertSame('9.00', $entity['offers']['price']);
        $this->assertSame('USD', $entity['offers']['priceCurrency']);
        $this->assertSame('covers/fixture.jpg', substr($entity['image'][0], -18));
        $this->assertSame(1, $entity['aggregateRating']['reviewCount']);
        $this->assertContains('18 pages', array_column($entity['additionalProperty'], 'value'));
        $this->assertContains('Test Composer', array_column($entity['additionalProperty'], 'value'));
        $this->assertContains($url, $this->sitemap());
        $this->assertContains(route('escores.index'), $this->sitemap());
        $this->call('HEAD', route('shop.sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $edition->reviews()->create(['rating' => 1, 'reviewer' => 'Editorial entry', 'published_at' => now()]);
        $this->assertArrayNotHasKey('aggregateRating', $this->entity($this->get($url)->assertOk()));
    }

    public function test_catalogue_paginates_canonically_but_excludes_filters_and_transaction_pages()
    {
        for ($i = 1; $i <= 25; $i++) $this->edition(eBook::class, 'practice-'.$i);
        $catalogue = route('ebooks.index');
        $pageTwo = $this->get($catalogue.'?page=2&utm_source=audit')->assertOk();
        $this->assertSame($catalogue.'?page=2', $this->xpath($pageTwo)->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame(25, $this->entity($pageTwo)['mainEntity']['itemListElement'][0]['position']);
        $filtered = $this->get($catalogue.'?q=practice')->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->assertCount(0, $this->xpath($filtered)->query('//head/link[@rel="canonical"]'));
        $this->get(app(Storefront::class)->categoryUrl('stickers'))->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        $this->get(route('shop.bag'))->assertOk()->assertHeader('X-Robots-Tag', 'noindex');

        $topic = eBookTopic::create(['name' => 'Practice', 'slug' => 'practice']);
        $target = route('ebooks.index', ['topics' => ['practice'], 'utm_source' => 'audit']);
        $this->get(route('ebooks.topic', ['topic' => $topic, 'utm_source' => 'audit', 'page' => 2]))
            ->assertStatus(301)->assertRedirect($target);
        $this->get($target)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
    }

    public function test_merchandise_variants_use_real_options_images_and_deep_links()
    {
        $variants = collect(['Blue', 'Pink', 'Ivory'])->map(fn ($color, $index) => [
            'id' => 100 + $index, 'color' => $color, 'size' => 'M', 'retail_price' => (string) (20 + $index),
            'currency' => 'USD', 'synced' => true, 'out_of_stock' => false, 'product_name' => 'T-shirt',
        ])->all();
        $product = MerchandiseProduct::create(['name' => 'Piano T-shirt', 'printful_store_id' => 12345, 'printful_product_id' => 77, 'variants' => $variants]);
        $product->forceFill(['materials_override' => '100% cotton'])->save();
        foreach (['Blue' => 'upload', 'Pink' => 'upload', 'Ivory' => 'printful'] as $color => $source) {
            $product->images()->create(['disk' => 'public', 'path' => $color.'.png', 'source' => $source, 'checksum' => hash('sha256', $color),
                'alt' => $color, 'position' => 1, 'width' => 800, 'height' => 800, 'gallery_color' => $color]);
        }
        $url = app(Storefront::class)->productUrl($product);
        $response = $this->get($url.'?variant=101')->assertOk();
        $head = $this->xpath($response);
        $this->assertSame($url, $head->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame('101', $head->evaluate('string(//select[@name="variant_id"]/option[@selected]/@value)'));
        $this->assertSame('Pink', $head->evaluate('string(//*[@data-gallery-slide and not(@hidden)]/@data-gallery-color)'));
        $this->assertSame('$21.00 USD', trim($head->evaluate('string(//*[@data-product-price])')));
        $entity = $this->entity($response);
        $this->assertSame('ProductGroup', $entity['@type']);
        $this->assertSame('100% cotton', $entity['material']);
        $this->assertStringContainsString('100% cotton. Available in Blue, Pink.', $entity['description']);
        $this->assertSame(['Blue', 'Pink'], array_column($entity['hasVariant'], 'color'));
        $this->assertSame($url.'?variant=101', $entity['hasVariant'][1]['offers']['url']);
        $this->assertSame('21.00', $entity['hasVariant'][1]['offers']['price']);
        $this->assertStringNotContainsString('Ivory.png', json_encode($entity));
        $this->assertContains($url, $this->sitemap());
        $this->assertNotContains($url.'?variant=101', $this->sitemap());
        $invalid = $this->get($url.'?variant=102')->assertOk();
        $this->assertSame('100', $this->xpath($invalid)->evaluate('string(//select[@name="variant_id"]/option[@selected]/@value)'));
        $product->images()->where('source', 'upload')->delete();
        $entity = $this->entity($this->get($url)->assertOk());
        $this->assertSame('Product', $entity['@type']);
        $this->assertArrayNotHasKey('offers', $entity);
        Http::assertNothingSent();
    }

    public function test_free_editions_have_zero_price_but_missing_downloads_have_no_offer()
    {
        $free = $this->edition(eBook::class, 'free-piano-book', ['discount' => 100]);
        $this->assertSame('0.00', $this->entity($this->get($free->showRoute())->assertOk())['offers']['price']);
        $missing = $this->edition(eBook::class, 'missing-files', ['pdf_path' => null, 'epub_path' => null]);
        $this->assertArrayNotHasKey('offers', $this->entity($this->get($missing->showRoute())->assertOk()));
    }
}
