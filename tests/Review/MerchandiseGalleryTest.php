<?php

namespace Tests\Review;

use App\{Admin, Merchandise\MerchandiseProduct};
use App\Services\Shop\Storefront;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};

class MerchandiseGalleryTest extends ReviewTestCase
{
    private function product(): MerchandiseProduct
    {
        config(['services.printful.store_id' => '12345']);
        return MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 77,
            'name' => 'Piano T-shirt', 'variants' => collect(['Pink', 'Blue', 'Ivory'])->map(fn ($color, $index) => [
                'id' => 91 + $index, 'variant_id' => 4011 + $index, 'color' => $color, 'size' => 'M', 'name' => $color.' / M', 'sku' => null,
                'retail_price' => '25.00', 'currency' => 'USD', 'synced' => true, 'discontinued' => false, 'out_of_stock' => false,
            ])->all()]);
    }

    private function image($product, string $name, ?string $color, string $source = 'upload')
    {
        return $product->images()->create(['disk' => 'public', 'path' => $name.'.png', 'source' => $source,
            'checksum' => hash('sha256', $name), 'alt' => $name, 'position' => $product->images()->count() + 1,
            'width' => 800, 'height' => 800, 'gallery_color' => $color]);
    }

    private function action($product, $image)
    {
        return route('admin.merchandise.images.update', ['merchandise' => $product, 'image' => $image->id]);
    }

    public function test_gallery_starts_in_the_main_images_color_and_never_exposes_unassigned_mockups()
    {
        $product = $this->product();
        $this->image($product, 'Unassigned import', null, 'printful');
        $this->image($product, 'Pink front', 'Pink');
        $this->image($product, 'Pink back', 'Pink');
        $main = $this->image($product, 'Blue front', 'Blue');
        $product->update(['main_image_id' => $main->id]);
        $response = $this->get(app(Storefront::class)->productUrl($product))->assertOk();
        $response->assertViewHas('product', function ($data) {
            $this->assertSame('Blue', $data['galleryColor']);
            $this->assertSame(92, $data['selectedVariant']['id']);
            $this->assertSame(['Blue', 'Pink', 'Pink'], $data['gallery']->pluck('color')->all());
            return true;
        });
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//*[@data-gallery-slide and not(@hidden)]'));
        $this->assertSame('Blue', $xpath->query('//*[@data-gallery-slide and not(@hidden)]')->item(0)->getAttribute('data-gallery-color'));
        $this->assertCount(0, $xpath->query('//*[@data-shop-gallery]//img[@alt="Unassigned import"]'));
        $this->assertCount(0, $xpath->query('//*[@data-gallery-step and not(@hidden)]'));
        $this->assertSame('92', $xpath->query('//select[@name="variant_id"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertArrayNotHasKey('gallery_color', $main->toArray());
        Http::assertNothingSent();
    }

    public function test_no_assigned_color_images_shows_an_empty_gallery_instead_of_other_colors()
    {
        $product = $this->product();
        $this->image($product, 'Unassigned import', null);
        $this->get(app(Storefront::class)->productUrl($product))->assertOk()
            ->assertSee('No images for this color')->assertDontSee('data-gallery-slide', false);
        $variants = $product->variants;
        foreach ($variants as &$variant) $variant['color'] = null;
        unset($variant);
        $product->update(['variants' => $variants]);
        $this->get(app(Storefront::class)->productUrl($product))->assertOk()->assertSee('data-gallery-slide', false);
    }

    public function test_managers_can_assign_upload_reassign_and_order_views_within_each_color()
    {
        Storage::fake('public');
        $product = $this->product();
        $front = $this->image($product, 'Pink front', null);
        $blue = $this->image($product, 'Blue front', 'Blue');
        $back = $this->image($product, 'Pink back', 'Pink');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->patch($this->action($product, $front), ['revision' => 1, 'alt' => 'Pink front', 'gallery_color' => 'Pink'])->assertRedirect();
        $this->assertSame('Pink', $front->fresh()->gallery_color);
        $this->patch(route('admin.merchandise.images.move', ['merchandise' => $product, 'image' => $back->id]),
            ['revision' => $product->fresh()->revision, 'direction' => 'up'])->assertRedirect();
        $this->assertSame([$back->id, $front->id], $product->images()->where('gallery_color', 'Pink')->pluck('id')->all());
        $this->assertSame(2, $blue->fresh()->position);
        $this->post(route('admin.merchandise.images.upload', $product), ['revision' => $product->fresh()->revision,
            'gallery_color' => 'Blue', 'images' => [UploadedFile::fake()->image('blue-back.png', 120, 120)]])->assertRedirect();
        $this->assertSame('Blue', $product->images()->reorder('id', 'desc')->firstOrFail()->gallery_color);
        $adminPage = $this->get(route('admin.merchandise.edit', $product))->assertOk()->assertSee('Pink (2)')->assertSee('Blue (2)')
            ->assertSee('Ivory (0)')->assertSee('data-mockup-row', false)->assertDontSee('Image description')->assertDontSee('Save image');
        if ($directory = getenv('PIANOLIT_GALLERY_BROWSER_FIXTURES')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            $product->update(['main_image_id' => $front->id]);
            $shopPage = $this->get(app(Storefront::class)->productUrl($product))->assertOk();
            foreach (['admin' => $adminPage, 'shop' => $shopPage] as $name => $page) {
                $html = str_replace(['http://localhost/', 'http://shop.localhost/', 'http://admin.localhost/', 'href="/css/', 'src="/js/'],
                    ['http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $page->getContent());
                $html = preg_replace_callback('~http://shop\.pianolit\.test/storage/([^"<]+)~', function ($match) {
                    $file = strpos($match[1], 'Blue') !== false || strpos($match[1], 'shop/merchandise') === 0 ? 'shirt-mint.png' : 'shirt-burgundy.png';
                    return 'http://shop.pianolit.test/images/shop/demo/'.$file;
                }, $html);
                $html = str_replace(['Pink', 'Blue', 'Ivory'], ['Burgundy', 'Mint', 'Butter'], $html);
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
        $this->patch($this->action($product, $front), ['revision' => $product->fresh()->revision, 'alt' => 'Front', 'gallery_color' => ''])->assertRedirect();
        $this->assertNull($front->fresh()->gallery_color);
        Http::assertNothingSent();
    }

    public function test_invalid_colors_wrong_product_images_and_stale_assignments_are_rejected()
    {
        $product = $this->product();
        $image = $this->image($product, 'Pink front', null);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin')->withExceptionHandling();
        foreach (['Unknown', ['Pink'], str_repeat('x', 256)] as $color) {
            $this->patchJson($this->action($product, $image), ['revision' => 1, 'alt' => 'Front', 'gallery_color' => $color])->assertUnprocessable();
        }
        $this->patchJson($this->action($product, $image), ['revision' => 1, 'alt' => 'Front', 'gallery_color' => 'Pink'])->assertRedirect();
        $this->patchJson($this->action($product, $image), ['revision' => 1, 'alt' => 'Front', 'gallery_color' => 'Blue'])->assertUnprocessable();
        $this->assertSame('Pink', $image->fresh()->gallery_color);
        $other = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 78, 'name' => 'Other shirt', 'variants' => $product->variants]);
        $this->patchJson($this->action($other, $image), ['revision' => 1, 'alt' => 'Front', 'gallery_color' => 'Pink'])->assertNotFound();
        $this->assertSame(1, $other->fresh()->revision);
        Http::assertNothingSent();
    }

    public function test_color_upload_targets_are_fixed_and_mockup_rows_have_no_name_or_color_editors()
    {
        $product = $this->product();
        $first = $this->image($product, 'Pink front', 'Pink');
        $second = $this->image($product, 'Blue front', 'Blue');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $response = $this->withSession(['_old_input' => ['editing_image_id' => (string) $first->id, 'gallery_color' => 'Ivory', 'alt' => 'Draft']])
            ->get(route('admin.merchandise.edit', $product))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(0, $xpath->query('//*[@data-mockup-row]//select|//*[@data-mockup-row]//input[@name="alt"]'));
        $this->assertCount(2, $xpath->query('//*[@data-mockup-row]//img[@width="48" and @height="48"]'));
        $this->assertCount(8, $xpath->query('//*[@data-mockup-row]//button[contains(@class,"btn-xs")]'));
        foreach (['Pink', 'Blue', 'Ivory'] as $color) $this->assertCount(1, $xpath->query('//form[@data-color-upload="'.$color.'"]/input[@name="gallery_color" and @value="'.$color.'"]'));
    }

    public function test_batch_duplicate_and_restored_uploads_use_the_target_color_and_current_product_name()
    {
        Storage::fake('public');
        $product = $this->product();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $url = route('admin.merchandise.images.upload', $product);
        $this->post($url, ['revision' => 1, 'gallery_color' => 'Ivory', 'alt' => 'Not the product name',
            'images' => [UploadedFile::fake()->image('front.png', 100, 100), UploadedFile::fake()->image('back.png', 120, 100)]])->assertRedirect();
        $uploads = $product->images()->get();
        $this->assertCount(2, $uploads);
        $this->assertSame(['Ivory', 'Ivory'], $uploads->pluck('gallery_color')->all());
        $this->assertSame([$product->name, $product->name], $uploads->pluck('alt')->all());
        $front = $uploads->first();
        $this->post($url, ['revision' => $product->fresh()->revision, 'gallery_color' => 'Blue',
            'images' => [UploadedFile::fake()->image('front.png', 100, 100)]])->assertRedirect();
        $this->assertSame('Blue', $front->fresh()->gallery_color);
        $this->assertSame(2, $product->images()->count());
        $this->delete(route('admin.merchandise.images.remove', ['merchandise' => $product, 'image' => $front->id]), ['revision' => $product->fresh()->revision])->assertRedirect();
        $this->post($url, ['revision' => $product->fresh()->revision, 'gallery_color' => 'Pink',
            'images' => [UploadedFile::fake()->image('front.png', 100, 100)]])->assertRedirect();
        $this->assertSame('Pink', $front->fresh()->gallery_color);
        $this->assertFalse($front->fresh()->trashed());
        $product->update(['name' => 'Renamed piano T-shirt']);
        $page = $this->get(app(Storefront::class)->productUrl($product))->assertOk();
        $page->assertViewHas('product', function ($data) {
            foreach ($data['gallery'] as $image) $this->assertStringStartsWith('Renamed piano T-shirt — ', $image['alt']);
            return true;
        });
        $this->patch($this->action($product, $front), ['revision' => $product->fresh()->revision, 'gallery_color' => 'Pink', 'alt' => 'Forged title'])->assertRedirect();
        $this->assertSame('Piano T-shirt', $front->fresh()->alt, 'Legacy image metadata is not an editable product title.');
        $this->assertSame('Renamed piano T-shirt', $product->fresh()->name);
        Http::assertNothingSent();
    }

    public function test_printful_library_is_compact_read_only_and_never_supplies_storefront_artwork()
    {
        Storage::fake('public');
        $product = $this->product();
        $import = $this->image($product, 'Provider original', 'Pink', 'printful');
        Storage::disk('public')->put($import->path, 'original mockup bytes');
        $product->update(['main_image_id' => $import->id]);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin')->withExceptionHandling();
        $editor = $this->get(route('admin.merchandise.edit', $product))->assertOk()->assertSee('Mockups by color')->assertSee('Printful mockups (1)');
        $dom = new \DOMDocument;
        @$dom->loadHTML($editor->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(0, $xpath->query('//details[@id="printful-mockups"][@open]'));
        $this->assertCount(0, $xpath->query('//details[@id="printful-mockups"]//article//input|//details[@id="printful-mockups"]//article//select|//details[@id="printful-mockups"]//article//button'));
        $this->assertCount(1, $xpath->query('//details[@id="printful-mockups"]//a[contains(@href,"/download")]'));
        $this->assertCount(3, $xpath->query('//form[@data-color-upload]//input[@type="file" and @multiple and @name="images[]"]'));
        $this->assertCount(4, $xpath->query('//*[@data-file-dropzone]'));
        $this->assertCount(1, $xpath->query('//script[contains(@src,"file-dropzone.js")]'));
        $this->assertNull(app(Storefront::class)->present($product->load('images'))['imageUrl']);
        $this->get(app(Storefront::class)->productUrl($product))->assertOk()->assertDontSee($import->path);
        foreach (['main', 'move', 'update'] as $action) {
            $this->patchJson(route('admin.merchandise.images.'.$action, ['merchandise' => $product, 'image' => $import->id]),
                ['revision' => 1, 'direction' => 'up', 'alt' => 'Changed', 'gallery_color' => 'Pink'])->assertForbidden();
        }
        $this->deleteJson(route('admin.merchandise.images.remove', ['merchandise' => $product, 'image' => $import->id]), ['revision' => 1])->assertForbidden();
        $download = $this->get(route('admin.merchandise.images.download', ['merchandise' => $product, 'image' => $import->id]))->assertOk();
        $this->assertSame('original mockup bytes', $download->streamedContent());
        $this->assertStringContainsString('attachment', $download->headers->get('content-disposition'));
        $other = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 78, 'name' => 'Other product', 'variants' => []]);
        $this->get(route('admin.merchandise.images.download', ['merchandise' => $other, 'image' => $import->id]))->assertNotFound();
        $other->update(['printful_store_id' => 999]);
        $foreignImage = $this->image($other, 'Other store original', null, 'printful');
        Storage::disk('public')->put($foreignImage->path, 'other store');
        $this->get(route('admin.merchandise.images.download', ['merchandise' => $other, 'image' => $foreignImage->id]))->assertNotFound();
        $this->assertSame(1, $product->fresh()->revision);
        if ($directory = getenv('PIANOLIT_GALLERY_BROWSER_FIXTURES')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            $html = str_replace(['http://localhost/', 'http://admin.localhost/', 'href="/css/', 'src="/js/'],
                ['http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $editor->getContent());
            $html = str_replace('http://shop.pianolit.test/storage/'.$import->path, 'http://shop.pianolit.test/images/shop/demo/shirt-burgundy.png', $html);
            file_put_contents($directory.'/editor.html', $html);
        }
        Http::assertNothingSent();
    }

    public function test_explicitly_reuploading_an_import_creates_a_separate_editable_copy()
    {
        Storage::fake('public');
        $product = $this->product();
        $images = app(\App\Services\Shop\MerchandiseImages::class);
        $asset = $images->fromUpload(UploadedFile::fake()->image('original.png', 100, 100), 'Provider original');
        $asset['source'] = 'printful';
        $import = $product->images()->create($asset + ['position' => 1]);
        try {
            $file = UploadedFile::fake()->createWithContent('my-copy.png', Storage::disk('public')->get($import->path));
            app(\App\Services\Admin\MerchandiseCatalog::class)->upload($product->fresh(), 1, [$file], 'My copy', 'Pink');
            $upload = $product->images()->where('source', 'upload')->firstOrFail();
            $this->assertSame($import->checksum, $upload->checksum);
            $this->assertNotSame($import->id, $upload->id);
            $this->assertNotSame($import->path, $upload->path);
            $this->assertSame('printful', $import->fresh()->source);
            $this->assertSame('Pink', $upload->gallery_color);
            $this->assertSame($upload->id, (int) $product->fresh()->main_image_id);
            $this->assertSame($upload->url, app(Storefront::class)->present($product->fresh('images'))['imageUrl']);
            Http::assertNothingSent();
        } finally {
            // The old cross-source uniqueness cannot be restored while the deliberate duplicate exists.
            $product->forceDelete();
        }
    }
}
