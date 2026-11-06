<?php

namespace Tests\Review;

use App\Admin;
use App\Shop\{eBook, eScore};
use App\Services\Shop\{DigitalMedia, DigitalPreview, Storefront};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Http, Storage};

class DigitalProductEditorTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        \Illuminate\Support\Facades\Redis::shouldReceive('get')->andReturn(null);
        \Stevebauman\Location\Facades\Location::shouldReceive('get')->andReturn(false);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    public static function kinds(): array { return [['ebooks', eBook::class], ['escores', eScore::class]]; }

    private function product(string $class = eBook::class, array $attributes = [])
    {
        return Model::withoutEvents(fn () => create($class, $attributes + ['title' => 'Piano edition', 'subtitle' => 'Ten piano pieces',
            'author' => 'Test author', 'price' => 12, 'discount' => 0, 'pages_count' => 20, 'published_at' => now(),
            'cover_path' => 'legacy/cover.jpg', 'mockup_path' => 'legacy/mockup.jpg', 'previews' => serialize(['legacy/preview.jpg']),
            'pdf_path' => 'legacy/book.pdf']));
    }

    private function state($product): array { return app(DigitalMedia::class)->state($product); }

    private function details(array $extra = []): array
    {
        return $extra + ['media_revision' => 0, 'title' => 'Updated edition', 'author' => 'Author', 'subtitle' => 'Description',
            'description' => '<p>Saved description</p>', 'pages_count' => 10, 'price' => 14, 'discount' => 10];
    }

    private function pdf(int $pages): string
    {
        $pdf = new \FPDF;
        for ($index = 1; $index <= $pages; $index++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 20);
            $pdf->Cell(0, 20, 'Page '.$index);
        }
        return $pdf->Output('S');
    }

    /** @dataProvider kinds */
    public function test_both_editors_select_save_and_clear_their_own_topics($kind, $class)
    {
        $topicClass = $kind === 'ebooks' ? \App\Shop\eBookTopic::class : \App\Shop\eScoreTopic::class;
        $otherClass = $kind === 'ebooks' ? \App\Shop\eScoreTopic::class : \App\Shop\eBookTopic::class;
        $topic = $topicClass::create(['name' => 'Practice', 'slug' => 'practice']);
        $foreign = $otherClass::create(['id' => 999, 'name' => 'Foreign topic', 'slug' => 'foreign']);
        $product = $this->product($class);
        $product->topics()->attach($topic);
        $this->get(route('admin.'.$kind.'.create'))->assertOk()->assertSee('Practice')->assertDontSee('Foreign topic');
        $this->get(route('admin.'.$kind.'.edit', $product))->assertOk()->assertSee('Topics')->assertSee('Practice')->assertDontSee('Foreign topic');
        $this->patch(route('admin.'.$kind.'.update', $product), $this->details(['topics' => [$topic->id]]))->assertRedirect();
        $this->assertSame([$topic->id], $product->topics()->pluck($topic->getTable().'.id')->all());
        $this->withExceptionHandling();
        $this->post(route('admin.'.$kind.'.store'), ['title' => 'Another edition', 'topics' => [$foreign->id]])->assertSessionHasErrors('topics.0');
        $this->patch(route('admin.'.$kind.'.update', $product), $this->details(['media_revision' => 1, 'topics' => [$foreign->id]]))->assertSessionHasErrors('topics.0');
        $this->patch(route('admin.'.$kind.'.update', $product), $this->details(['media_revision' => 1]))->assertRedirect();
        $this->assertSame(0, $product->topics()->count());
    }

    /** @dataProvider kinds */
    public function test_editors_share_sections_and_keep_only_the_correct_product_files($kind, $class)
    {
        $product = $this->product($class);
        $response = $this->get(route('admin.'.$kind.'.edit', $product))->assertOk()->assertSee('Product details')
            ->assertSee('Display images')->assertSee('Hover image')->assertSee('Look inside')
            ->assertSee('name="images[]"', false)->assertSee('multiple', false)
            ->assertSee('name="pdf_file"', false)->assertSee('name="'.($kind === 'ebooks' ? 'epub_file' : 'audio_file').'"', false)
            ->assertDontSee('name="cover_image"', false)->assertDontSee('name="mockup_image"', false)->assertDontSee('filesDropzone');
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertCount(2, $xpath->query('//*[@data-display-image-row]'));
        $this->assertCount(0, $xpath->query('//form//form'));
        $this->assertCount(0, $xpath->query('//form[not(.//input[@name="_token"])]'));
        Http::assertNothingSent();
    }

    /** @dataProvider kinds */
    public function test_multiple_uploads_order_main_remove_restore_and_stale_edits_are_owned($kind, $class)
    {
        $product = $this->product($class);
        $original = (array) DB::table($product->getTable())->find($product->id);
        $this->post(route('admin.'.$kind.'.images.upload', $product), ['media_revision' => 0,
            'images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.png')]])->assertRedirect();
        $state = $this->state($product);
        $this->assertCount(4, $state['images']);
        $selected = $state['images'][3]['id'];
        $action = route('admin.'.$kind.'.images.action', [$product, $selected]);
        $this->patch($action, ['media_revision' => 1, 'action' => 'up'])->assertRedirect();
        $this->assertSame($selected, $this->state($product)['images'][2]['id']);
        $this->patch($action, ['media_revision' => 2, 'action' => 'main'])->assertRedirect();
        $url = asset('storage/'.$state['images'][3]['path']);
        $this->assertSame($url, app(Storefront::class)->cards(collect([$product]))[0]['imageUrl']);
        $this->app['auth']->shouldUse('web');
        $this->get($product->showRoute())->assertOk()->assertViewHas('product', fn ($data) => parse_url($data['gallery']->first()['url'], PHP_URL_PATH) === parse_url($url, PHP_URL_PATH));
        $this->withExceptionHandling();
        $this->patch($action, ['media_revision' => 2, 'action' => 'remove'])->assertSessionHasErrors('media_revision');
        $other = $this->product($class);
        $this->patch(route('admin.'.$kind.'.images.action', [$other, $selected]), ['media_revision' => 0, 'action' => 'main'])->assertNotFound();
        $this->patch($action, ['media_revision' => 3, 'action' => 'remove'])->assertRedirect();
        $this->assertNotSame($url, app(Storefront::class)->presentDigital($product)['imageUrl']);
        Storage::disk('public')->assertExists($state['images'][3]['path']);
        $this->patch($action, ['media_revision' => 4, 'action' => 'restore'])->assertRedirect();
        $this->assertCount(4, app(DigitalMedia::class)->gallery($product));
        $this->assertSame($original, (array) DB::table($product->getTable())->find($product->id));
        $this->assertArrayNotHasKey('media', $product->fresh()->toArray());
        Http::assertNothingSent();
    }

    public function test_upload_failure_and_stale_upload_discard_all_staged_assets()
    {
        $product = $this->product();
        $url = route('admin.ebooks.images.upload', $product);
        $this->withExceptionHandling();
        $this->post($url, ['media_revision' => 7, 'images' => [UploadedFile::fake()->image('one.jpg')]])->assertSessionHasErrors('media_revision');
        $this->assertSame([], Storage::disk('public')->allFiles('shop/digital/images'));
        $this->post($url, ['media_revision' => 0, 'images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->create('unsafe.svg', 5, 'image/svg+xml')]])->assertSessionHasErrors('images.1');
        $this->assertSame([], Storage::disk('public')->allFiles('shop/digital/images'));
        $this->assertSame(0, $this->state($product)['revision']);
    }

    public function test_removing_every_legacy_image_does_not_restore_the_old_cover_on_the_web()
    {
        $product = $this->product();
        foreach ($this->state($product)['images'] as $index => $image) app(DigitalMedia::class)->imageAction($product, $index, $image['id'], 'remove');
        $this->assertNull(app(Storefront::class)->presentDigital($product)['imageUrl']);
        $this->assertNull(app(Storefront::class)->cards(collect([$product]))[0]['imageUrl']);
        $this->assertSame('legacy/cover.jpg', $product->fresh()->cover_path);
    }

    public function test_media_owners_do_not_collide_and_an_admin_session_is_required()
    {
        $book = $this->product();
        $score = $this->product(eScore::class);
        $this->assertSame($book->id, $score->id);
        app(DigitalMedia::class)->upload($book, 0, [UploadedFile::fake()->image('book.jpg')]);
        $this->assertSame(1, $this->state($book)['revision']);
        $this->assertSame(0, $this->state($score)['revision']);
        auth('admin')->logout();
        $this->app['auth']->shouldUse('web');
        $this->withExceptionHandling();
        $this->post(route('admin.ebooks.images.upload', $book), ['media_revision' => 1])->assertRedirect();
        $this->post(route('admin.ebooks.pdf-preview', $book), ['media_revision' => 1])->assertRedirect();
        $this->patch(route('admin.ebooks.images.action', [$book, $this->state($book)['images'][0]['id']]),
            ['media_revision' => 1, 'action' => 'remove'])->assertRedirect();
        $this->assertSame(1, $this->state($book)['revision']);
    }

    public function test_removed_images_free_capacity_and_restore_cannot_exceed_the_limit()
    {
        $product = $this->product();
        $media = app(DigitalMedia::class);
        $media->change($product, 0, function ($state) {
            $state['images'] = array_map(fn ($n) => ['id' => (string) $n, 'path' => 'image-'.$n.'.jpg', 'removed' => $n === 41], range(1, 41));
            return $state;
        });
        try { $media->imageAction($product, 1, '41', 'restore'); $this->fail('Expected capacity rejection'); }
        catch (\Illuminate\Validation\ValidationException $exception) { $this->assertArrayHasKey('images', $exception->errors()); }
        $media->imageAction($product, 1, '1', 'remove');
        $media->upload($product, 2, [UploadedFile::fake()->image('replacement.jpg')]);
        $this->assertCount(40, $media->gallery($product));
    }

    public function test_pdf_renderer_creates_at_most_seven_pages_and_handles_short_and_invalid_pdfs()
    {
        Storage::disk('public')->put('long.pdf', $this->pdf(10));
        $pages = app(DigitalPreview::class)->render('long.pdf');
        $this->assertCount(7, $pages);
        foreach ($pages as $page) {
            $size = getimagesize(Storage::disk('public')->path($page));
            $this->assertSame('image/jpeg', $size['mime']);
            $this->assertLessThanOrEqual(1600, max($size[0], $size[1]));
        }
        Storage::disk('public')->put('short.pdf', $this->pdf(2));
        $this->assertCount(2, app(DigitalPreview::class)->render('short.pdf'));
        $before = Storage::disk('public')->allFiles('shop/digital/previews');
        Storage::disk('public')->put('invalid.pdf', 'invalid');
        try { app(DigitalPreview::class)->render('invalid.pdf'); $this->fail('Expected invalid PDF rejection'); }
        catch (\Illuminate\Validation\ValidationException $exception) { $this->assertArrayHasKey('pdf_file', $exception->errors()); }
        $this->assertSame($before, Storage::disk('public')->allFiles('shop/digital/previews'));
    }

    /** @dataProvider kinds */
    public function test_pdf_replacement_generates_preview_and_retains_original_download_and_other_file($kind, $class)
    {
        $product = $this->product($class);
        $original = $product->getAttributes();
        Storage::disk('public')->put($product->pdf_path, $this->pdf(8));
        $this->patch(route('admin.'.$kind.'.update', $product), $this->details([
            'pdf_file' => UploadedFile::fake()->createWithContent('edition.pdf', $this->pdf(9)),
        ]))->assertRedirect();
        $fresh = $product->fresh();
        $this->assertSame('Updated edition', $fresh->title);
        $this->assertSame($original['slug'], $fresh->slug);
        $this->assertSame($original['cover_path'], $fresh->cover_path);
        $this->assertSame($original['mockup_path'], $fresh->mockup_path);
        $extra = $kind === 'ebooks' ? 'epub_path' : 'audio_path';
        $this->assertSame($original[$extra] ?? null, $fresh->$extra);
        Storage::disk('public')->assertExists($original['pdf_path']);
        Storage::disk('public')->assertExists($fresh->pdf_path);
        $this->assertCount(7, app(DigitalMedia::class)->previews($fresh));
        $this->app['auth']->shouldUse('web');
        $this->get($fresh->showRoute())->assertOk()->assertSee('alt="Preview page 7 of 7"', false)
            ->assertDontSee('alt="Preview page 8', false)->assertDontSee('legacy/preview.jpg')->assertDontSee($fresh->pdf_path);
        $this->withExceptionHandling();
        $this->patch(route('admin.'.$kind.'.update', $fresh), $this->details(['title' => 'Stale title',
            'pdf_file' => UploadedFile::fake()->createWithContent('replacement.pdf', $this->pdf(2))]))->assertSessionHasErrors('media_revision');
        $this->assertSame('Updated edition', $fresh->fresh()->title);
        $this->assertCount(7, Storage::disk('public')->allFiles('shop/digital/previews'));
        $this->assertCount(1, Storage::disk('public')->allFiles('app/'.$kind.'/pdf'));
    }

    public function test_failed_render_preserves_current_product_files_and_preview()
    {
        $product = $this->product();
        Storage::disk('public')->put($product->pdf_path, $this->pdf(2));
        $this->post(route('admin.ebooks.pdf-preview', $product), ['media_revision' => 0])->assertRedirect();
        $before = $this->state($product);
        config(['digital-media.pdftoppm' => '/missing/renderer']);
        $this->withExceptionHandling();
        $this->patch(route('admin.ebooks.update', $product), $this->details(['media_revision' => 1,
            'pdf_file' => UploadedFile::fake()->createWithContent('edition.pdf', $this->pdf(3))]))->assertSessionHasErrors('pdf_file');
        $this->assertSame('Piano edition', $product->fresh()->title);
        $this->assertSame('legacy/book.pdf', $product->fresh()->pdf_path);
        $this->assertSame($before, $this->state($product));
        $this->assertSame([], Storage::disk('public')->allFiles('app/ebooks/pdf'));
    }

    public function test_pdf_preview_works_with_an_old_config_cache_and_no_worker_path()
    {
        Storage::disk('public')->put('book.pdf', $this->pdf(2));
        config(['digital-media' => []]);
        $path = getenv('PATH');
        putenv('PATH=');
        try {
            $this->assertCount(2, app(DigitalPreview::class)->render('book.pdf'));
        } finally {
            $path === false ? putenv('PATH') : putenv('PATH='.$path);
        }
    }

    public function test_pdf_preview_accepts_an_absolute_renderer_path()
    {
        $renderer = (new \Symfony\Component\Process\ExecutableFinder)->find('pdftoppm', null, ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin']);
        $this->assertNotNull($renderer);
        config(['digital-media.pdftoppm' => $renderer]);
        Storage::disk('public')->put('book.pdf', $this->pdf(1));
        $this->assertCount(1, app(DigitalPreview::class)->render('book.pdf'));
    }

    public function test_preview_failure_uses_only_the_existing_shared_page_alert()
    {
        $product = $this->product();
        Storage::disk('public')->put($product->pdf_path, $this->pdf(1));
        config(['digital-media.pdftoppm' => '/missing/renderer']);
        $this->withExceptionHandling();
        $this->post(route('admin.ebooks.pdf-preview', $product), ['media_revision' => 0])
            ->assertSessionHasErrors(['pdf_file' => 'The PDF preview renderer is unavailable on the server. Check the renderer installation and configuration.']);
        $response = $this->get(route('admin.ebooks.edit', $product))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//div[@role="alert"]'));
        $this->assertCount(1, $xpath->query('//*[contains(@class, "alert-top")]//*[@role="alert"]'));
    }
}
