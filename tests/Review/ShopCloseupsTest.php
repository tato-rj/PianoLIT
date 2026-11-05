<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{Closeups, Storefront};
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Http, Storage};

class ShopCloseupsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['services.printful.store_id' => '12345']);
        Storage::fake('public');
        $this->actingAs(Model::withoutEvents(fn () => create(Admin::class, ['role' => 'manager'])), 'admin');
    }

    public static function kinds()
    {
        return [['merchandise'], ['ebooks'], ['escores']];
    }

    private function product(string $kind = 'merchandise', array $attributes = [])
    {
        if ($kind === 'merchandise') {
            $product = MerchandiseProduct::create($attributes + ['printful_store_id' => 12345,
                'printful_product_id' => random_int(100, 999999), 'name' => 'Piano T-shirt', 'variants' => [], 'revision' => 1]);
            $image = $product->images()->create(['path' => 'shop/original.png', 'source' => 'upload', 'position' => 1,
                'alt' => 'Original', 'checksum' => hash('sha256', 'original'), 'width' => 100, 'height' => 100]);
            $product->update(['main_image_id' => $image->id]);
            return $product;
        }
        return Model::withoutEvents(fn () => create(Closeups::PRODUCTS[$kind], $attributes + ['title' => 'Piano '.$kind,
            'published_at' => now(), 'price' => 12, 'discount' => 0, 'mockup_path' => 'shop/original.png', 'cover_path' => 'shop/cover.png']));
    }

    private function url($product, string $kind, string $action = 'store')
    {
        return route('admin.'.$kind.'.closeup.'.$action, $product);
    }

    private function upload($product, string $kind, int $revision = 0, string $name = 'closeup.jpg')
    {
        $image = imagecreatetruecolor(256, 256);
        $colors = [imagecolorallocate($image, 228, 239, 241), imagecolorallocate($image, 73, 116, 130)];
        for ($y = 0; $y < 8; $y++) for ($x = 0; $x < 8; $x++) {
            imagefilledrectangle($image, $x * 32, $y * 32, $x * 32 + 31, $y * 32 + 31, $colors[($x + $y) % 2]);
        }
        ob_start(); imagepng($image); $bytes = ob_get_clean(); imagedestroy($image);
        return $this->post($this->url($product, $kind), ['closeup_revision' => $revision,
            'closeup_image' => UploadedFile::fake()->createWithContent($name, $bytes)]);
    }

    private function browserFixture(string $name, string $html, $closeup): void
    {
        if (!$directory = getenv('PIANOLIT_SHOP_CLOSEUP_FIXTURES')) return;
        if (!is_dir($directory)) mkdir($directory, 0700, true);
        file_put_contents($directory.'/uploaded-closeup.png', Storage::disk('public')->get($closeup->path));
        $html = str_replace(['http://localhost/', 'http://admin.localhost/', 'http://shop.localhost/', 'href="/css/', 'src="/js/'],
            ['http://shop.pianolit.test/', 'http://admin.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $html);
        $html = preg_replace('#https?://[^/]+/storage/'.preg_quote($closeup->path, '#').'#', 'http://127.0.0.1:8776/uploaded-closeup.png', $html);
        $html = str_replace('http://shop.pianolit.test/storage/shop/original.png', 'http://shop.pianolit.test/images/shop/demo/shirt-mint.png', $html);
        file_put_contents($directory.'/'.$name.'.html', $html);
    }

    /** @dataProvider kinds */
    public function test_each_editor_can_upload_replace_and_remove_a_separate_closeup_without_changing_originals($kind)
    {
        $product = $this->product($kind);
        $before = (array) DB::table($product->getTable())->where('id', $product->id)->first();
        $editor = route('admin.'.$kind.'.edit', $product);
        $this->get($editor)->assertOk()->assertSee('Hover close-up')->assertSee('No close-up uploaded')
            ->assertSee('name="closeup_image"', false)->assertSee('enctype="multipart/form-data"', false);
        $this->upload($product, $kind)->assertRedirect($editor.'#hover-closeup')->assertSessionHas('status');
        $first = app(Closeups::class)->for($product);
        $this->assertSame(1, (int) $first->revision);
        $this->assertSame(256, (int) $first->width);
        Storage::disk('public')->assertExists($first->path);
        $adminPage = $this->get($editor)->assertOk()->assertSee('Replace close-up')->assertSee('Current hover close-up')->assertSee('Remove close-up');
        if ($kind === 'merchandise' && getenv('PIANOLIT_SHOP_CLOSEUP_FIXTURES')) {
            $this->product('merchandise', ['name' => 'Piano T-shirt without close-up']);
            $this->browserFixture('admin-closeup', $adminPage->getContent(), $first);
            $this->browserFixture('catalog-closeup', $this->get(route('shop.home'))->assertOk()->getContent(), $first);
        }
        $this->upload($product, $kind, 1, 'replacement.png')->assertRedirect($editor.'#hover-closeup');
        $second = app(Closeups::class)->for($product);
        $this->assertSame(2, (int) $second->revision);
        $this->assertNotSame($first->path, $second->path);
        Storage::disk('public')->assertExists($first->path);
        Storage::disk('public')->assertExists($second->path);
        $cards = app(Storefront::class)->cards(collect([$product]));
        $this->assertSame(app(Closeups::class)->url($second), $cards[0]['closeupUrl']);
        $html = view('shop.storefront.components.card', ['product' => $cards[0]])->render();
        $this->assertSame(1, substr_count($html, 'data-card-closeup'));
        $this->assertStringContainsString($cards[0]['imageUrl'], $html);
        $this->assertStringContainsString($cards[0]['closeupUrl'], $html);
        $this->delete($this->url($product, $kind, 'destroy'), ['closeup_revision' => 2])->assertRedirect($editor.'#hover-closeup');
        $removed = app(Closeups::class)->for($product);
        $this->assertNull($removed->path);
        $this->assertSame(3, (int) $removed->revision);
        $this->assertNull(app(Storefront::class)->cards(collect([$product]))[0]['closeupUrl']);
        $html = view('shop.storefront.components.card', ['product' => app(Storefront::class)->cards(collect([$product]))[0]])->render();
        $this->assertStringNotContainsString('data-card-closeup', $html);
        $this->assertSame($before, (array) DB::table($product->getTable())->where('id', $product->id)->first());
        $this->assertArrayNotHasKey('closeupUrl', $product->fresh()->toArray());
        $this->assertArrayNotHasKey('closeup_path', $product->fresh()->toArray());
        Http::assertNothingSent();
    }

    public function test_no_upload_or_missing_file_keeps_the_regular_image_and_product_types_do_not_collide()
    {
        $shirt = $this->product();
        $book = $this->product('ebooks');
        $score = $this->product('escores');
        $this->assertSame($shirt->id, $book->id);
        $this->assertSame($shirt->id, $score->id);
        $this->upload($shirt, 'merchandise')->assertRedirect();
        $this->upload($book, 'ebooks')->assertRedirect();
        $cards = app(Storefront::class)->cards(collect([$shirt, $book, $score]));
        $this->assertNotSame($cards[0]['closeupUrl'], $cards[1]['closeupUrl']);
        $this->assertNull($cards[2]['closeupUrl']);
        $record = app(Closeups::class)->for($shirt);
        Storage::disk('public')->delete($record->path);
        $this->assertNull(app(Storefront::class)->cards(collect([$shirt]))[0]['closeupUrl']);
        DB::table('shop_product_closeups')->where('product_type', eBook::class)->update(['path' => 'https://example.test/forged.png']);
        $this->assertNull(app(Storefront::class)->cards(collect([$book]))[0]['closeupUrl']);
    }

    public function test_stale_uploads_and_removals_are_rejected_and_staged_files_are_discarded()
    {
        $product = $this->product();
        $this->upload($product, 'merchandise')->assertRedirect();
        $path = app(Closeups::class)->for($product)->path;
        $this->withExceptionHandling();
        $this->upload($product, 'merchandise')->assertSessionHasErrors('closeup_revision', null, 'closeup');
        $this->assertSame($path, app(Closeups::class)->for($product)->path);
        $this->assertCount(1, Storage::disk('public')->allFiles('shop/closeups'));
        $this->delete($this->url($product, 'merchandise', 'destroy'), ['closeup_revision' => 0])->assertSessionHasErrors('closeup_revision', null, 'closeup');
        $this->delete($this->url($product, 'merchandise', 'destroy'), ['closeup_revision' => 1])->assertRedirect();
        $this->upload($product, 'merchandise', 1)->assertSessionHasErrors('closeup_revision', null, 'closeup');
        $this->assertNull(app(Closeups::class)->for($product)->path);
        $this->assertCount(1, Storage::disk('public')->allFiles('shop/closeups'));
    }

    public function test_invalid_files_paths_revision_shapes_and_oversized_images_cannot_be_saved()
    {
        $product = $this->product();
        $this->withExceptionHandling();
        foreach ([UploadedFile::fake()->create('bad.svg', 2, 'image/svg+xml'),
            UploadedFile::fake()->create('large.jpg', 11000, 'image/jpeg'), UploadedFile::fake()->image('wide.png', 6001, 2)] as $file) {
            $this->postJson($this->url($product, 'merchandise'), ['closeup_revision' => 0, 'closeup_image' => $file])->assertStatus(422);
        }
        $this->postJson($this->url($product, 'merchandise'), ['closeup_revision' => ['bad'], 'path' => 'forged.png'])->assertStatus(422);
        $this->postJson($this->url($product, 'merchandise'), ['closeup_revision' => 0, 'closeup_image' => 'https://example.test/remote.jpg'])->assertStatus(422);
        $this->assertDatabaseCount('shop_product_closeups', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles('shop/closeups'));
        Http::assertNothingSent();
    }

    public function test_merchandise_uploads_require_a_manager_and_current_active_store_product()
    {
        $product = $this->product();
        $foreign = $this->product('merchandise', ['printful_store_id' => 77]);
        $retired = $this->product('merchandise', ['name' => 'Retired T-shirt']);
        $retired->delete();
        $this->withExceptionHandling();
        $this->upload($foreign, 'merchandise')->assertNotFound();
        $this->upload($retired, 'merchandise')->assertNotFound();
        $this->actingAs(Model::withoutEvents(fn () => create(Admin::class, ['role' => 'editor'])), 'admin');
        $this->upload($product, 'merchandise')->assertForbidden();
        $this->upload($this->product('ebooks'), 'ebooks')->assertRedirect();
        auth('admin')->logout();
        $this->upload($product, 'merchandise')->assertRedirect(route('admin.login.show'));
        $this->assertNull(app(Closeups::class)->for($product));
    }

    public function test_csrf_and_service_time_product_checks_prevent_writes_and_clean_failed_staging()
    {
        $product = $this->product();
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'expected'])->postJson($this->url($product, 'merchandise'), [
            'closeup_revision' => 0, 'closeup_image' => UploadedFile::fake()->image('closeup.png'), '_token' => 'wrong',
        ])->assertStatus(419);
        $product->delete();
        try {
            app(Closeups::class)->upload($product, 0, UploadedFile::fake()->image('closeup.png'));
            $this->fail('A retired product cannot receive staged artwork.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertDatabaseCount('shop_product_closeups', 0);
            $this->assertCount(0, Storage::disk('public')->allFiles('shop/closeups'));
        }
    }

    public function test_database_failure_rolls_back_metadata_and_discards_the_new_file()
    {
        $product = $this->product();
        DB::listen(function ($query) {
            if (stripos($query->sql, 'insert into "shop_product_closeups"') !== false) throw new \RuntimeException('Simulated write failure');
        });
        try {
            app(Closeups::class)->upload($product, 0, UploadedFile::fake()->image('closeup.png'));
            $this->fail('The write should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated write failure', $exception->getMessage());
            $this->assertDatabaseCount('shop_product_closeups', 0);
            $this->assertCount(0, Storage::disk('public')->allFiles('shop/closeups'));
        }
    }
}
