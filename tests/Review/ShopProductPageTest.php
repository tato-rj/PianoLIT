<?php

namespace Tests\Review;

use App\{Review, User};
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Notification, Redis};
use Stevebauman\Location\Facades\Location;

class ShopProductPageTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        Notification::fake();
    }

    public static function products()
    {
        return [[eBook::class], [eScore::class]];
    }

    private function product($class = eScore::class, array $attributes = [])
    {
        $product = Model::withoutEvents(function () use ($class, $attributes) {
            return create($class, $attributes + [
                'title' => 'A collection for piano', 'subtitle' => 'Music for your next practice',
                'description' => '<h3>Inside this edition</h3><p>A thoughtfully selected collection.</p>',
                'published_at' => now(), 'price' => 12, 'discount' => 0,
                'previews' => serialize(['previews/cover.jpg', 'previews/page-2.jpg', 'previews/page-3.jpg', 'previews/page-4.jpg', 'previews/page-5.jpg']),
                'pdf_path' => 'editions/fixture.pdf', 'cover_path' => 'previews/cover.jpg', 'mockup_path' => null,
            ]);
        });
        if ($product->previews) {
            app(\App\Services\Shop\DigitalMedia::class)->change($product, 0, function ($state) use ($product) {
                $state['preview_pages'] = $product->previews;
                $state['preview_source'] = hash('sha256', $product->pdf_path);
                return $state;
            });
        }
        return $product;
    }

    private function user()
    {
        return Model::withoutEvents(function () { return create(User::class); });
    }

    /** @dataProvider products */
    public function test_related_digital_products_are_published_same_category_and_before_reviews($class)
    {
        $current = $this->product($class);
        $related = $this->product($class, ['title' => 'Another piano edition']);
        $this->product($class, ['title' => 'Unpublished edition', 'published_at' => null]);
        $this->product($class === eBook::class ? eScore::class : eBook::class, ['title' => 'Different category']);
        $response = $this->get($current->showRoute())->assertOk()->assertSee('You may also like')->assertDontSee('More to explore');
        $this->assertSame([$related->showRoute()], $response->viewData('relatedProducts')->pluck('url')->all());
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="reviews"'), strpos($html, 'id="product-related-title"'));
        $this->assertSame(1, substr_count($html, 'data-product-row>'));
    }

    /** @dataProvider products */
    public function test_both_product_pages_render_previews_without_fetching_remote_image_dimensions($class)
    {
        $product = $this->product($class);
        $response = $this->get($product->showRoute())->assertOk()
            ->assertViewIs('shop.storefront.show')
            ->assertSee('data-storefront', false)->assertSee('data-shop-gallery', false)->assertSee('shop-price-tag', false)
            ->assertSee('aria-label="Bag and account"', false)->assertSee('css/storefront.css', false)
            ->assertDontSee('product-detail__hero', false)->assertDontSee('data-add-to-bag', false)->assertDontSee('Checkout is coming soon')
            ->assertSee('data-book-preview', false)
            ->assertSee('data-book-page="5"', false)
            ->assertSee('data-bs-target="#preview-product"', false)
            ->assertSee('data-bs-target="#review-modal"', false)
            ->assertSee('data-book-magnify', false)
            ->assertSee('name="rating" value="5"', false)
            ->assertSee('action="'.$product->reviewRoute().'"', false)
            ->assertSee($product->checkoutRoute())
            ->assertSee('js/views/shop-product.js', false)
            ->assertDontSee('flipbook-model', false);
        $this->assertDatabaseCount('purchases', 0);

        // Optional disposable HTML for browser QA, using public sample images.
        if ($directory = getenv('PIANOLIT_SHOP_BROWSER_FIXTURES')) {
            if (! is_dir($directory)) mkdir($directory, 0700, true);
            $html = str_replace(['http://localhost/', 'http://shop.localhost/', 'href="/css/', 'src="/js/'], ['http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $response->getContent());
            $html = preg_replace('~http://shop\.pianolit\.test/storage/previews/[^"< ]+~', 'http://shop.pianolit.test/images/misc/score1.png', $html);
            file_put_contents($directory.'/'.($class === eBook::class ? 'ebook' : 'escore').'.html', $html);
        }
    }

    /** @dataProvider products */
    public function test_unpublished_and_other_product_reviews_never_appear_and_ratings_without_text_count($class)
    {
        $product = $this->product($class);
        $other = $this->product();
        $user = $this->user();
        $product->reviews()->create(['user_id' => $user->id, 'rating' => 4, 'published_at' => now()]);
        $other->reviews()->create(['rating' => 5, 'content' => 'Other product review', 'published_at' => now()]);
        $product->reviews()->create(['rating' => 1, 'content' => 'Pending review']);
        $this->get($product->showRoute())->assertOk()->assertSee('Customer reviews')
            ->assertSee('Based on 1 review')->assertSee('4.0')
            ->assertDontSee('Other product review')->assertDontSee('Pending review')
            ->assertDontSee('aria-label="Delete my review"', false);
        $response = $this->actingAs($user, 'web')->get($product->showRoute())->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//*[@id="reviews"]//article//button[@aria-label="Delete my review" and contains(@class,"btn-red")]'));
        $this->assertSame('(1)', trim($xpath->query('//a[@href="#reviews"]')->item(0)->textContent));
    }

    /** @dataProvider products */
    public function test_product_sharing_uses_the_canonical_url_and_discount_prices_show_original_first($class)
    {
        $product = $this->product($class, ['discount' => 25]);
        $html = $this->get($product->showRoute().'?utm_source=sample&return_token=private')->assertOk()
            ->assertSee('js/components/link-share.js', false)->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//*[@data-product-detail]//button[@data-bs-target="#product-share-modal"]/following-sibling::h2[1]'));
        $this->assertSame($product->showRoute(), $xpath->query('//*[@id="product-share-modal"]//input[@readonly]')->item(0)->getAttribute('value'));
        $this->assertCount(6, $xpath->query('//*[@id="product-share-modal"]//a[starts-with(@aria-label,"Share via ")]'));
        $price = $xpath->query('//*[@data-product-price]')->item(0);
        $this->assertSame('$9.00 USD', trim($price->textContent));
        $this->assertSame('$12.00 USD', trim($xpath->query('preceding-sibling::del', $price)->item(0)->textContent));
        $email = $xpath->query('//*[@id="product-share-modal"]//a[starts-with(@href,"mailto:")]')->item(0);
        parse_str(parse_url($email->getAttribute('href'), PHP_URL_QUERY), $query);
        $this->assertSame('Product from PianoLIT Shop', $query['subject']);
        $this->assertSame($product->title."\r\n\r\n".$product->showRoute(), $query['body']);
    }

    public function test_reviewer_names_are_escaped()
    {
        $product = $this->product();
        $product->reviews()->create(['rating' => 5, 'content' => 'Great edition', 'reviewer' => '<img src=x onerror=alert(1)>', 'published_at' => now()]);
        $this->get($product->showRoute())->assertOk()->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    /** @dataProvider products */
    public function test_authenticated_review_submission_is_owned_and_waits_for_moderation($class)
    {
        $product = $this->product($class);
        $user = $this->user();
        $this->actingAs($user, 'web')->from($product->showRoute())
            ->post($product->reviewRoute(), ['rating' => 5, 'title' => 'Enjoyable music', 'content' => 'Clear and easy to read', 'reviewer' => 'A pianist', 'user_id' => 999])
            ->assertRedirect($product->showRoute())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'reviewable_id' => $product->id, 'reviewable_type' => $class, 'rating' => 5, 'published_at' => null]);
        $this->withExceptionHandling()->post($product->reviewRoute(), ['rating' => 4])->assertForbidden();
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_invalid_review_fields_use_the_review_error_bag_and_preserve_the_draft()
    {
        $product = $this->product();
        $this->actingAs($this->user(), 'web')->withExceptionHandling()->from($product->showRoute())
            ->post($product->reviewRoute(), ['rating' => 8, 'title' => 'Saved draft', 'content' => str_repeat('x', 221), 'reviewer' => ['invalid']])
            ->assertRedirect($product->showRoute())->assertSessionHasErrors(['rating', 'content', 'reviewer'], null, 'review');
        $response = $this->get($product->showRoute())->assertOk()->assertSee('value="Saved draft"', false);
        $html = $response->getContent();
        $authHtml = substr($html, strpos($html, 'id="modal-auth"'));
        $this->assertStringNotContainsString('is-invalid', $authHtml);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_models_are_allowlisted_and_missing_products_return_404()
    {
        $this->actingAs($this->user(), 'web')->withExceptionHandling();
        $this->post(route('reviews.store', ['model' => User::class, 'id' => 1]), ['rating' => 5])->assertNotFound();
        $this->post(route('reviews.store', ['model' => eScore::class, 'id' => 999]), ['rating' => 5])->assertNotFound();
    }

    /** @dataProvider products */
    public function test_existing_paid_free_member_and_owned_download_actions_are_preserved($class)
    {
        $product = $this->product($class);
        $user = $this->user();
        $this->actingAs($user, 'web')->get($product->showRoute())->assertOk()->assertSee('Buy now')->assertDontSee('Download now');
        $product->update(['price' => 0]);
        $this->get($product->showRoute())->assertOk()->assertSee('Download now')
            ->assertSee('action="'.$product->purchaseRoute().'"', false)->assertSee('name="_token"', false);
        $product->update(['price' => 12]);
        $user->update(['super_user' => true]);
        $this->get($product->showRoute())->assertOk()->assertSee('Download now')->assertSee('data-product-price>Free', false);
        $this->assertDatabaseCount('purchases', 0);
        $user->purchases()->create(['item_id' => $product->id, 'item_type' => $class, 'cost' => 12]);
        $this->get($product->showRoute())->assertOk()->assertSee('Download it again')
            ->assertSee('href="http://localhost/profile/downloads"', false)->assertDontSee('Download now');
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_preview_is_absent_when_no_samples_exist_and_reviews_require_sign_in()
    {
        $product = $this->product(eBook::class, ['previews' => '']);
        $this->get($product->showRoute())->assertOk()->assertDontSee('id="preview-product"', false)->assertSee('Write a review');
        $this->withExceptionHandling()->post($product->reviewRoute(), ['rating' => 5])->assertRedirect('http://shop.localhost/login');
        $this->assertDatabaseCount('reviews', 0);
    }
}
