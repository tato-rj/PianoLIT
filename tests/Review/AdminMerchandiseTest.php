<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Merchandise\{MerchandiseImage, MerchandiseProduct};
use App\Services\Admin\MerchandiseCatalog;
use App\Services\Shop\{MerchandiseImages, PrintfulException};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Http, Storage};

class AdminMerchandiseTest extends ReviewTestCase
{
    private $admin;
    private $remote;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = create(Admin::class, ['role' => 'manager']);
        $this->actingAs($this->admin, 'admin');
        config(['services.printful.token' => 'fake-merchandise-token', 'services.printful.store_id' => '12345']);
        Storage::fake('public');
        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->remote = $this->remoteProduct();
        $this->fakePrintful();
    }

    private function png(int $color = 0): string
    {
        $image = imagecreatetruecolor(64, 64);
        imagefill($image, 0, 0, imagecolorallocate($image, $color, 100, 200));
        ob_start(); imagepng($image); $bytes = ob_get_clean(); imagedestroy($image);
        return $bytes;
    }

    private function remoteProduct(int $id = 77): array
    {
        return ['sync_product' => ['id' => $id, 'name' => 'Piano T-shirt', 'thumbnail_url' => 'https://files.cdn.printful.com/mockup/front.png'],
            'sync_variants' => [['id' => 91, 'variant_id' => 4011, 'name' => 'Piano shirt / Black / M', 'size' => 'M', 'color' => 'Black',
                'sku' => 'PIANO-M', 'retail_price' => '25.00', 'currency' => 'USD', 'synced' => true, 'discontinued' => false, 'out_of_stock' => false,
                'files' => [
                    ['type' => 'default', 'url' => 'https://files.cdn.printful.com/private-print-file.png', 'preview_url' => 'https://files.cdn.printful.com/artwork.png'],
                    ['type' => 'preview', 'preview_url' => 'https://files.cdn.printful.com/mockup/back.png'],
                    ['type' => 'preview', 'preview_url' => 'https://files.cdn.printful.com/mockup/front.png'],
                ]]]];
    }

    private function fakePrintful(): void
    {
        Http::fake(function ($request, $options) {
            $this->assertSame('GET', $request->method());
            $this->assertFalse($options['allow_redirects']);
            if ($request->url() === 'https://api.printful.com/stores') {
                return Http::response(['code' => 200, 'result' => [['id' => 12345, 'name' => 'Review store']]]);
            }
            if (strpos($request->url(), 'https://api.printful.com/store/products/') === 0) {
                $this->assertTrue($request->hasHeader('Authorization', 'Bearer fake-merchandise-token'));
                $this->assertTrue($request->hasHeader('X-PF-Store-Id', '12345'));
                return Http::response(['code' => 200, 'result' => $this->remote]);
            }
            if (strpos($request->url(), 'https://api.printful.com/store/products?') === 0) {
                return Http::response(['code' => 200, 'result' => [['id' => 77, 'name' => 'Piano T-shirt', 'variants' => 1]], 'paging' => ['total' => 1, 'offset' => 0, 'limit' => 12]]);
            }
            if (strpos($request->url(), 'https://files.cdn.printful.com/mockup/') === 0) {
                $this->assertFalse($request->hasHeader('Authorization'));
                $this->assertFalse($request->hasHeader('X-PF-Store-Id'));
                return Http::response($this->png(strpos($request->url(), 'front') !== false ? 20 : 200), 200, ['Content-Type' => 'image/png']);
            }
            throw new \RuntimeException('Unexpected review HTTP request');
        });
    }

    private function imported(): MerchandiseProduct
    {
        return app(MerchandiseCatalog::class)->import(77)[0]->fresh();
    }

    private function action(string $action, MerchandiseProduct $product, $image = null): string
    {
        return route('admin.merchandise.'.$action, array_filter(['merchandise' => $product, 'image' => $image]));
    }

    public function test_discovery_and_import_copy_only_product_mockups_into_a_separate_catalog()
    {
        $this->get(route('admin.merchandise.discover'))->assertOk()->assertSee('Piano T-shirt')->assertSee('Import product')->assertDontSee('fake-merchandise-token');
        Http::assertSent(fn ($request) => strpos($request->url(), 'offset=0&limit=12') !== false);
        $response = $this->post(route('admin.merchandise.import'), ['printful_product_id' => 77, 'user_id' => 99, 'path' => 'forged']);
        $product = MerchandiseProduct::firstOrFail();
        $response->assertRedirect($this->action('edit', $product).'#images');
        $this->assertSame(1, MerchandiseProduct::count());
        $this->assertCount(2, $product->images);
        $this->assertSame($product->images->first()->id, (int) $product->main_image_id);
        $this->assertSame('25.00', $product->variants[0]['retail_price']);
        $this->assertArrayNotHasKey('files', $product->variants[0]);
        $this->assertArrayNotHasKey('user_id', $product->getAttributes());
        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertSame('image/png', getimagesizefromstring(Storage::disk('public')->get($image->path))['mime']);
        }
        Http::assertNotSent(fn ($request) => strpos($request->url(), 'artwork') !== false || strpos($request->url(), 'private-print') !== false);
        $this->get($this->action('edit', $product))->assertOk()->assertSee('Main image')->assertSee('Use as main')->assertSee('Product variants')->assertSee('multiple', false);
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano T-shirt')->assertSee('Manage images');
    }

    public function test_repeated_import_is_idempotent_without_more_network_or_files()
    {
        $product = $this->imported();
        $files = Storage::disk('public')->allFiles();
        $this->post(route('admin.merchandise.import'), ['printful_product_id' => 77])->assertRedirect();
        $this->assertSame(1, MerchandiseProduct::count());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame($product->revision, $product->fresh()->revision);
        Http::assertSentCount(3);
    }

    public function test_upload_main_order_and_refresh_preserve_local_choices_and_removed_mockups()
    {
        $product = $this->imported();
        $front = $product->images()->first();
        $this->post($this->action('images.upload', $product), ['revision' => $product->revision,
            'images' => [UploadedFile::fake()->image('own-mockup.png', 100, 80)], 'alt' => 'My studio photo'])->assertRedirect();
        $upload = $product->images()->where('source', 'upload')->firstOrFail();
        $this->patch($this->action('images.main', $product, $upload->id), ['revision' => $product->fresh()->revision])->assertRedirect();
        $this->patch($this->action('images.move', $product, $upload->id), ['revision' => $product->fresh()->revision, 'direction' => 'up'])->assertRedirect();
        $this->delete($this->action('images.remove', $product, $front->id), ['revision' => $product->fresh()->revision])->assertRedirect();
        $before = $product->images()->pluck('id')->all();
        $this->remote['sync_product']['name'] = 'Updated Piano T-shirt';
        $this->remote['sync_variants'][0]['retail_price'] = '29.00';
        $this->post($this->action('refresh', $product), ['revision' => $product->fresh()->revision])->assertRedirect();
        $product->refresh();
        $this->assertSame('Updated Piano T-shirt', $product->name);
        $this->assertSame('29.00', $product->variants[0]['retail_price']);
        $this->assertSame($upload->id, (int) $product->main_image_id);
        $this->assertSame($before, $product->images()->pluck('id')->all());
        $this->assertSame('My studio photo', $upload->fresh()->alt);
        $this->assertTrue(MerchandiseImage::withTrashed()->find($front->id)->trashed());
        $this->get($this->action('edit', $product))->assertOk()->assertSee('Removed images (1)')->assertSee('Restore image');
    }

    public function test_removing_the_main_image_selects_a_fallback_and_the_last_can_be_restored()
    {
        $product = $this->imported();
        $images = $product->images;
        foreach ($images as $image) {
            $this->delete($this->action('images.remove', $product, $image->id), ['revision' => $product->fresh()->revision])->assertRedirect();
        }
        $this->assertNull($product->fresh()->main_image_id);
        $this->assertSame(0, $product->images()->count());
        $this->post($this->action('images.restore', $product, $images->first()->id), ['revision' => $product->fresh()->revision])->assertRedirect();
        $this->assertSame($images->first()->id, (int) $product->fresh()->main_image_id);
        $this->assertSame(1, $product->images()->count());
    }

    public function test_wrong_product_images_and_stale_saves_cannot_change_the_gallery()
    {
        $product = $this->imported();
        $other = MerchandiseProduct::create(['name' => 'Other', 'printful_store_id' => 12345, 'printful_product_id' => 88, 'variants' => []]);
        $image = $product->images()->first();
        $this->withExceptionHandling();
        foreach (['images.main', 'images.move', 'images.update'] as $action) {
            $this->patchJson($this->action($action, $other, $image->id), ['revision' => 1, 'direction' => 'up', 'alt' => 'Forged'])->assertNotFound();
        }
        $this->deleteJson($this->action('images.remove', $other, $image->id), ['revision' => 1])->assertNotFound();
        $this->assertSame(1, $other->fresh()->revision);
        $revision = $product->revision;
        $this->patch($this->action('images.update', $product, $image->id), ['revision' => $revision, 'alt' => 'Clear description'])->assertRedirect();
        $this->patchJson($this->action('images.main', $product, $image->id), ['revision' => $revision])->assertUnprocessable();
        $files = Storage::disk('public')->allFiles();
        $this->postJson($this->action('images.upload', $product), ['revision' => $revision, 'images' => [UploadedFile::fake()->image('stale.png', 100, 80)]])->assertUnprocessable();
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame('Clear description', $image->fresh()->alt);
    }

    public function test_removed_main_cannot_be_selected_until_restored_and_duplicate_uploads_are_not_added()
    {
        $product = $this->imported();
        $image = $product->images()->first();
        $this->delete($this->action('images.remove', $product, $image->id), ['revision' => $product->revision]);
        $this->withExceptionHandling()->patchJson($this->action('images.main', $product, $image->id), ['revision' => $product->fresh()->revision])->assertNotFound();
        for ($i = 0; $i < 2; $i++) {
            $this->post($this->action('images.upload', $product), ['revision' => $product->fresh()->revision, 'images' => [UploadedFile::fake()->image('same.png', 90, 60)]])->assertRedirect();
        }
        $this->assertSame(1, $product->images()->where('source', 'upload')->count());
        $this->assertCount(3, Storage::disk('public')->allFiles());
    }

    public function test_partial_mockup_failure_keeps_the_product_and_reports_the_missing_image()
    {
        $this->remote['sync_product']['thumbnail_url'] = 'https://localhost/private';
        $this->post(route('admin.merchandise.import'), ['printful_product_id' => 77])->assertRedirect()->assertSessionHas('merchandise_notice');
        $this->assertSame(1, MerchandiseProduct::count());
        Http::assertNotSent(fn ($request) => strpos($request->url(), 'localhost') !== false);
    }

    /** @dataProvider unsafeUrls */
    public function test_only_exact_https_printful_cdn_hosts_are_accepted($url)
    {
        $this->assertFalse(app(MerchandiseImages::class)->allowedUrl($url));
    }

    public static function unsafeUrls(): array
    {
        return [['http://files.cdn.printful.com/image.png'], ['https://files.cdn.printful.com.evil.test/image.png'],
            ['https://127.0.0.1/image.png'], ['https://files.cdn.printful.com:444/image.png'], ['https://user:pass@files.cdn.printful.com/image.png'],
            ['file:///etc/passwd'], ['https://evil.test/image.png']];
    }

    public function test_invalid_files_and_input_shapes_are_rejected_before_storage_changes()
    {
        $product = $this->imported();
        $files = Storage::disk('public')->allFiles();
        $this->withExceptionHandling();
        foreach ([UploadedFile::fake()->create('fake.jpg', 1, 'image/jpeg'), UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->image('wide.png', 6001, 1)] as $file) {
            $this->postJson($this->action('images.upload', $product), ['revision' => $product->revision, 'images' => [$file]])->assertUnprocessable();
        }
        $this->postJson($this->action('images.upload', $product), ['revision' => $product->revision, 'images' => 'not-an-array'])->assertUnprocessable();
        $this->postJson(route('admin.merchandise.import'), ['printful_product_id' => '../etc/passwd'])->assertUnprocessable();
        $this->assertSame($files, Storage::disk('public')->allFiles());
    }

    public function test_database_failure_rolls_back_import_and_discards_staged_images()
    {
        MerchandiseImage::creating(function () { throw new \RuntimeException('Simulated database failure'); });
        try {
            app(MerchandiseCatalog::class)->import(77);
            $this->fail('Expected failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        } finally {
            MerchandiseImage::flushEventListeners();
        }
        $this->assertSame(0, MerchandiseProduct::count());
        $this->assertSame(0, MerchandiseImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(2, $this->imported()->images()->count());
    }

    public function test_upload_failure_rolls_back_new_rows_and_keeps_existing_files_and_main_image()
    {
        $product = $this->imported();
        $original = $product->getAttributes();
        $files = Storage::disk('public')->allFiles();
        $attempt = 0;
        MerchandiseImage::creating(function () use (&$attempt) {
            if (++$attempt === 2) throw new \RuntimeException('Simulated second image failure');
        });
        try {
            app(MerchandiseCatalog::class)->upload($product, $product->revision, [
                UploadedFile::fake()->image('first.png', 90, 60), UploadedFile::fake()->image('second.png', 100, 80),
            ], 'Studio photos');
            $this->fail('Expected a rolled-back upload');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated second image failure', $exception->getMessage());
        } finally {
            MerchandiseImage::flushEventListeners();
        }
        $this->assertSame($original, $product->fresh()->getAttributes());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame(2, $product->images()->count());
    }

    public function test_bad_refresh_identity_and_stale_revisions_preserve_the_existing_product()
    {
        $product = $this->imported();
        $original = $product->getAttributes();
        $this->remote['sync_product']['id'] = 999;
        $this->post($this->action('refresh', $product), ['revision' => $product->revision])->assertRedirect()->assertSessionHas('error');
        $this->assertSame($original, $product->fresh()->getAttributes());
        $this->withExceptionHandling()->postJson($this->action('refresh', $product), ['revision' => $product->revision - 1])->assertUnprocessable();
        config(['services.printful.store_id' => '99999']);
        $this->post($this->action('refresh', $product), ['revision' => $product->revision])->assertRedirect()->assertSessionHas('error');
        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    public function test_image_urls_are_absolute_when_app_url_has_no_scheme()
    {
        config(['app.url' => 'pianolit.test', 'filesystems.disks.public.url' => 'pianolit.test/storage']);
        Storage::forgetDisk('public');
        $image = new MerchandiseImage(['disk' => 'public', 'path' => 'shop/merchandise/preview.png']);
        $this->assertSame('http://pianolit.test/storage/shop/merchandise/preview.png', $image->url);
        config(['filesystems.disks.public.url' => '/storage']);
        Storage::forgetDisk('public');
        $this->assertSame('http://pianolit.test/storage/shop/merchandise/preview.png', $image->url);
    }

    public function test_mockup_redirects_and_oversize_responses_are_never_saved()
    {
        foreach ([Http::response('', 302, ['Location' => 'http://127.0.0.1/private']),
            Http::response($this->png(), 200, ['Content-Length' => MerchandiseImages::MAX_BYTES + 1])] as $response) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake(['*' => $response]);
            try {
                app(MerchandiseImages::class)->fromPrintful('https://files.cdn.printful.com/mockup/front.png', 'Preview');
                $this->fail('Expected a rejected response');
            } catch (PrintfulException $exception) {
                $this->assertSame('A Printful mockup could not be downloaded.', $exception->getMessage());
            }
            Http::assertSentCount(1);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_gallery_limit_is_enforced_without_changing_the_existing_selection()
    {
        $product = $this->imported();
        for ($i = 2; $i < MerchandiseImages::MAX_IMAGES; $i++) {
            $product->images()->create(['disk' => 'public', 'path' => 'review/'.$i.'.png', 'source' => 'upload',
                'checksum' => hash('sha256', (string) $i), 'alt' => 'Review image', 'width' => 64, 'height' => 64, 'position' => $i + 1]);
        }
        $files = Storage::disk('public')->allFiles();
        $this->post($this->action('images.upload', $product), ['revision' => $product->revision,
            'images' => [UploadedFile::fake()->image('extra.png', 120, 80)]])->assertRedirect()->assertSessionHas('error');
        $this->assertSame(MerchandiseImages::MAX_IMAGES, $product->images()->count());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame($product->main_image_id, $product->fresh()->main_image_id);
    }

    public function test_uploaded_sticker_transparency_survives_image_processing()
    {
        $product = $this->imported();
        $png = imagecreatetruecolor(64, 64);
        imagealphablending($png, false);
        imagesavealpha($png, true);
        imagefill($png, 0, 0, imagecolorallocatealpha($png, 255, 255, 255, 127));
        imagefilledellipse($png, 32, 32, 24, 24, imagecolorallocatealpha($png, 0, 85, 254, 0));
        ob_start(); imagepng($png); $bytes = ob_get_clean(); imagedestroy($png);
        $file = UploadedFile::fake()->createWithContent('sticker.png', $bytes);
        $this->post($this->action('images.upload', $product), ['revision' => $product->revision, 'images' => [$file]])->assertRedirect();
        $upload = $product->images()->where('source', 'upload')->firstOrFail();
        $processed = imagecreatefromstring(Storage::disk('public')->get($upload->path));
        $this->assertSame(127, imagecolorsforindex($processed, imagecolorat($processed, 0, 0))['alpha']);
        $this->assertSame(0, imagecolorsforindex($processed, imagecolorat($processed, 32, 32))['alpha']);
        imagedestroy($processed);
    }

    public function test_catalog_is_available_only_to_admin_managers_and_uses_admin_host_routes()
    {
        $product = $this->imported();
        $image = $product->images()->first();
        $this->withExceptionHandling();
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        foreach (['index', 'discover', 'edit'] as $action) $this->getJson($this->action($action, $product))->assertForbidden();
        $this->postJson(route('admin.merchandise.import'), ['printful_product_id' => 77])->assertForbidden();
        $this->postJson($this->action('refresh', $product), ['revision' => $product->revision])->assertForbidden();
        $this->postJson($this->action('images.upload', $product), [])->assertForbidden();
        $this->patchJson($this->action('images.main', $product, $image->id), ['revision' => $product->revision])->assertForbidden();
        $this->deleteJson($this->action('images.remove', $product, $image->id), ['revision' => $product->revision])->assertForbidden();
        auth('admin')->logout();
        $this->actingAs(create(User::class), 'web');
        $this->getJson(route('admin.merchandise.index'))->assertUnauthorized();
        $this->assertStringNotContainsString('/admin/', route('admin.merchandise.index'));
        $this->assertStringContainsString('admin.', parse_url(route('admin.merchandise.index'), PHP_URL_HOST));
    }

    public function test_csrf_is_required_for_import_and_gallery_mutations()
    {
        $product = $this->imported();
        $this->withExceptionHandling();
        $this->app->bind(\App\Http\Middleware\VerifyCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        $this->post(route('admin.merchandise.import'), ['printful_product_id' => 77])->assertStatus(419);
        $this->patch($this->action('images.main', $product, $product->main_image_id), ['revision' => $product->revision])->assertStatus(419);
        $this->assertSame(2, $product->fresh()->revision);
    }

    public function test_directory_pagination_search_and_remote_failures_are_safe()
    {
        for ($i = 0; $i < 13; $i++) MerchandiseProduct::create(['name' => 'Shirt '.sprintf('%02d', $i), 'printful_store_id' => 12345, 'printful_product_id' => 100 + $i, 'variants' => []]);
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Showing 1–12 of 13 results')->assertDontSee('cdn.datatables.net');
        $this->get(route('admin.merchandise.index', ['search' => 'Shirt 12']))->assertOk()->assertSee('Shirt 12')->assertDontSee('Shirt 01');
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['error' => 'fake-merchandise-token private-upstream'], 401)]);
        $this->get(route('admin.merchandise.discover'))->assertOk()->assertSee('HTTP 401')->assertSee('Try again')->assertDontSee('fake-merchandise-token')->assertDontSee('No products published');
        $this->withExceptionHandling()->getJson(route('admin.merchandise.index', ['sort' => 'password_asc']))->assertUnprocessable();
    }

    public function test_directory_filters_survive_gallery_actions_and_malformed_drafts_render_safely()
    {
        $product = $this->imported();
        $image = $product->images()->first();
        $filters = ['search' => 'Piano', 'sort' => 'name_desc', 'per_page' => 24, 'page' => 2];
        $editUrl = route('admin.merchandise.edit', ['merchandise' => $product] + $filters);
        $updateUrl = route('admin.merchandise.images.update', ['merchandise' => $product, 'image' => $image->id] + $filters);
        $this->get($editUrl)->assertOk()->assertSee(e(route('admin.merchandise.index', $filters)), false)->assertSee(e($updateUrl), false);
        $this->patch($updateUrl, ['revision' => $product->revision, 'alt' => 'Studio <script>view</script>'])->assertRedirect($editUrl.'#images');
        $this->get($editUrl)->assertOk()->assertSee('Studio &lt;script&gt;view&lt;/script&gt;', false)->assertDontSee('Studio <script>view</script>', false);
        $this->withExceptionHandling()->from($editUrl)->patch($updateUrl, ['revision' => $product->fresh()->revision, 'alt' => ['invalid'], 'editing_image_id' => ['invalid']])
            ->assertRedirect($editUrl)->assertSessionHasErrors('alt', null, 'merchandise');
        $this->get($editUrl)->assertOk()->assertSee('The alt must be a string.');
    }
}
