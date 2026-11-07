<?php

namespace Tests\Review;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{MerchandiseBag, Storefront};
use App\Shop\{eBook, eScore};
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Http, Mail};

class MerchandiseStorefrontTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['services.printful.store_id' => '12345']);
    }

    protected function product(array $attributes = []): MerchandiseProduct
    {
        return MerchandiseProduct::create($attributes + [
            'printful_store_id' => 12345, 'printful_product_id' => random_int(1000, 999999),
            'name' => 'Piano rainbow T-shirt', 'variants' => [$this->variant()],
        ]);
    }

    protected function variant(array $attributes = []): array
    {
        return $attributes + ['id' => 91, 'variant_id' => 4011, 'synced' => true, 'name' => 'Black / M', 'size' => 'M', 'color' => 'Black',
            'retail_price' => '28.50', 'currency' => 'USD', 'out_of_stock' => false, 'discontinued' => false];
    }

    protected function image(MerchandiseProduct $product, string $path, int $position)
    {
        return $product->images()->create(['path' => $path, 'source' => 'upload', 'checksum' => hash('sha256', $path),
            'alt' => $product->name.' view '.$position, 'position' => $position, 'width' => 600, 'height' => 600]);
    }

    protected function digitalProduct(string $model, array $attributes = [])
    {
        return Model::withoutEvents(fn () => create($model, $attributes + [
            'title' => 'Piano practice guide', 'published_at' => now(), 'price' => 20, 'discount' => 25,
            'cover_path' => 'shop/digital-cover.jpg', 'mockup_path' => 'shop/digital-mockup.jpg',
            'pdf_path' => 'private-original-never-in-catalog.pdf',
        ]));
    }

    public function test_related_products_are_bounded_same_category_owned_store_and_above_reviews()
    {
        $current = $this->product(['name' => 'Current T-shirt']);
        $expected = collect();
        for ($i = 0; $i < 14; $i++) $expected->push($this->product(['name' => 'Related T-shirt '.$i, 'created_at' => now()->subMinutes(20 - $i)]));
        $this->product(['name' => 'Other sticker']);
        $this->product(['name' => 'Other-store T-shirt', 'printful_store_id' => 99999]);
        $retired = $this->product(['name' => 'Retired T-shirt']);
        $retired->delete();
        $this->digitalProduct(eBook::class);
        $url = app(Storefront::class)->productUrl($current);
        $response = $this->get($url)->assertOk()->assertSee('You may also like');
        $response->assertSee('href="'.app(Storefront::class)->categoryUrl('t-shirts').'" class="text-nowrap">View all</a>', false)
            ->assertDontSee('data-product-row-controls', false)->assertDontSee('PianoLITProductRow', false);
        $related = $response->viewData('relatedProducts');
        $this->assertCount(Storefront::RELATED_LIMIT, $related);
        $this->assertSame($expected->reverse()->take(Storefront::RELATED_LIMIT)->pluck('id')->values()->all(), $related->pluck('id')->all());
        $this->assertSame(['t-shirts'], $related->pluck('category')->unique()->values()->all());
        $this->assertFalse($related->pluck('url')->contains($url));
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="reviews"'), strpos($html, 'id="product-related-title"'));
        Http::assertNothingSent();
    }

    public function test_related_row_is_omitted_when_no_other_product_in_the_category_exists()
    {
        $current = $this->product(['name' => 'Only T-shirt']);
        $this->product(['name' => 'Other sticker']);
        $this->get(app(Storefront::class)->productUrl($current))->assertOk()
            ->assertViewHas('relatedProducts', fn ($products) => $products->isEmpty())->assertDontSee('data-product-row', false);
    }

    public function test_all_includes_published_downloads_without_colliding_ids_or_merchandise_bag_actions()
    {
        $shirt = $this->product();
        $ebook = $this->digitalProduct(eBook::class);
        $score = $this->digitalProduct(eScore::class, ['title' => 'Piano solos', 'discount' => 100, 'mockup_path' => null]);
        $this->assertSame($shirt->id, $ebook->id);
        $this->assertSame($shirt->id, $score->id);
        foreach ([eBook::class, eScore::class] as $model) {
            $this->digitalProduct($model, ['title' => 'Unpublished download', 'published_at' => null]);
        }
        $response = $this->get(route('shop.home'))->assertOk()->assertSee($shirt->name)
            ->assertSee($ebook->title)->assertSee($score->title)->assertSee('$15.00 USD')->assertSee('Free')
            ->assertSee('shop/digital-mockup.jpg')->assertSee('shop/digital-cover.jpg')
            ->assertSee('href="'.$ebook->showRoute().'"', false)->assertSee('href="'.$score->showRoute().'"', false)
            ->assertDontSee('Unpublished download')->assertDontSee('private-original-never-in-catalog.pdf')->assertDontSee('Best deals')
            ->assertViewHas('products', fn ($products) => $products->total() === 3 && $products->count() === 3);
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(3, $xpath->query('//article'));
        foreach ([$ebook, $score] as $digital) {
            $this->assertCount(0, $xpath->query('//article[.//a[@href="'.$digital->showRoute().'"]]//form'));
        }
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_catalog_uses_one_mixed_grid_with_24_products_per_page_and_stable_boundaries()
    {
        $createdAt = now()->subDay();
        for ($i = 0; $i < 17; $i++) $this->product(['name' => 'Piano T-shirt '.$i, 'created_at' => $createdAt]);
        for ($i = 0; $i < 16; $i++) $this->digitalProduct(eBook::class, ['title' => 'Piano guide '.$i, 'created_at' => $createdAt]);
        for ($i = 0; $i < 16; $i++) $this->digitalProduct(eScore::class, ['title' => 'Piano score '.$i, 'created_at' => $createdAt]);
        $identities = collect();
        foreach ([1 => 24, 2 => 24, 3 => 1] as $page => $count) {
            $response = $this->get(route('shop.home', ['page' => $page]))->assertOk()
                ->assertDontSee('data-category-track')->assertDontSee('data-category-step')->assertDontSee('shop-category-title');
            $products = $response->viewData('products');
            $this->assertSame(49, $products->total());
            $this->assertSame(24, $products->perPage());
            $this->assertSame($page, $products->currentPage());
            $this->assertCount($count, $products);
            $identities = $identities->concat(collect($products->items())->pluck('url'));
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertCount(1, $xpath->query('//div[@aria-label="Products"]'));
            $this->assertCount($count, $xpath->query('//article'));
            $this->assertCount(1, $xpath->query('//*[@id="pagination-links"]'));
        }
        $this->assertCount(49, $identities->unique());
        $this->assertSame($identities->take(24)->all(), collect($this->get(route('shop.home'))->viewData('products')->items())->pluck('url')->all());
        $this->get(route('shop.home', ['page' => 99]))->assertRedirect(route('shop.home', ['page' => 3]));
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_category_pagination_starts_after_24_and_preserves_search_and_topics()
    {
        $topic = Model::withoutEvents(fn () => create(\App\Shop\eBookTopic::class, ['name' => 'Practice', 'slug' => 'practice']));
        for ($i = 0; $i < 24; $i++) {
            $book = $this->digitalProduct(eBook::class, ['title' => 'Piano guide '.$i]);
            $book->topics()->attach($topic);
        }
        $this->digitalProduct(eBook::class, ['title' => 'Other guide']);
        $this->product(['name' => 'Piano T-shirt']);
        $filters = ['q' => 'Piano guide', 'topics' => ['practice']];
        $this->get(route('ebooks.index', $filters))->assertOk()->assertDontSee('id="pagination-links"', false)
            ->assertViewHas('products', fn ($products) => $products->count() === 24 && $products->total() === 24);
        $book = $this->digitalProduct(eBook::class, ['title' => 'Piano guide 24']);
        $book->topics()->attach($topic);
        $response = $this->get(route('ebooks.index', $filters))->assertOk()
            ->assertDontSee('Other guide')->assertDontSee('Piano T-shirt')->assertSee('id="pagination-links"', false);
        $products = $response->viewData('products');
        $this->assertSame(25, $products->total());
        $next = $products->nextPageUrl();
        $this->assertSame('/ebooks', parse_url($next, PHP_URL_PATH));
        parse_str(parse_url($next, PHP_URL_QUERY), $query);
        $this->assertSame('Piano guide', $query['q']);
        $this->assertSame(['practice'], $query['topics']);
        $this->assertSame('2', $query['page']);
        $second = $this->get($next)->assertOk()->assertDontSee('Other guide')->assertDontSee('Piano T-shirt');
        $this->assertCount(1, $second->viewData('products'));
        $this->assertCount(25, collect($products->items())->concat($second->viewData('products')->items())->pluck('url')->unique());
        $this->get(route('ebooks.index', $filters + ['page' => 3]))->assertRedirect($next);
        foreach (['%', '_', '!'] as $literal) {
            $this->get(route('shop.home', ['q' => $literal]))->assertOk()->assertSee('No products found')->assertDontSee('id="pagination-links"', false);
        }
        $this->withExceptionHandling();
        foreach ([['page' => ['bad']], ['page' => -1], ['page' => 10001], ['ebooks_page' => ['bad']]] as $query) {
            $this->getJson(route('shop.home', $query))->assertStatus(422);
        }
    }

    public function test_shop_home_reuses_the_newsletter_modal_without_prompting_on_products_or_the_bag()
    {
        config(['shop-checkout.mode' => 'sandbox', 'shop-checkout.stripe_key' => 'pk_test_review', 'shop-checkout.stripe_secret' => 'sk_test_review']);
        $home = $this->get(route('shop.home'))->assertOk()
            ->assertSee('data-shop-welcome="true"', false)->assertSee('data-shop-welcome-retry="false"', false)
            ->assertSee('shop-newsletter-subscription-form-email')->assertSee('pianolit-input')
            ->assertSee('action="http://shop.localhost/newsletter/subscribe"', false)
            ->assertSee('Get my 10% discount')->assertSee('10% off your first order')->assertSee('one monthly email')->assertSee('js/views/shop-welcome.js');
        $this->assertSame(1, substr_count($home->getContent(), 'id="modal-newsletter-subscription"'));
        $product = $this->product();
        foreach ([app(Storefront::class)->productUrl($product), route('shop.bag'), route('ebooks.index')] as $url) {
            $this->get($url)->assertOk()->assertSee('data-shop-welcome-auto="false"', false)
                ->assertSee('data-shop-welcome-launcher', false)->assertSee('js/views/shop-welcome.js');
        }
    }

    public function test_shop_newsletter_validation_returns_to_the_same_form_without_account_modal_errors()
    {
        $this->withExceptionHandling()->from(route('shop.home'))->post('http://shop.localhost/newsletter/subscribe', [
            'email' => 'invalid', 'started_at' => now()->subSeconds(10)->toDateTimeString(),
            'form_id' => 'shop-newsletter-subscription-form',
        ])->assertRedirect(route('shop.home'))->assertSessionHasErrors(['email'], null, 'storefront');
        $this->assertFalse(session('errors')->getBag('default')->any());
        $this->assertFalse(session()->has('newsletter_subscribed'));
        $this->get(route('shop.home'))->assertOk()->assertSee('data-shop-welcome-retry="true"', false)
            ->assertSee('aria-invalid="true"', false)->assertSee('value="invalid"', false);
        Mail::assertNothingSent();
    }

    public function test_shop_header_reuses_guest_and_authenticated_account_controls_next_to_the_bag()
    {
        $product = $this->product();
        foreach ([route('shop.home'), app(\App\Services\Shop\Storefront::class)->productUrl($product), route('shop.bag')] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('data-account-navigation-toggle', false)->assertDontSee('Shop all');
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//nav[@aria-label="Bag and account"]//a');
            $this->assertCount(1, $links);
            $this->assertSame(route('shop.bag'), $links->item(0)->getAttribute('href'));
            $this->assertCount(1, $xpath->query('//nav[@aria-label="Bag and account"]//button[@data-bs-target="#public-hub-menu"]'));
            $this->assertCount(1, $xpath->query('//*[@id="public-hub-menu"]//a[@href="http://shop.localhost/login"]'));
        }
        $user = Model::withoutEvents(fn () => create(User::class, ['first_name' => 'Sample']));
        $this->actingAs($user, 'web')->get(route('shop.home'))->assertOk()
            ->assertSee('aria-label="Account menu for Sample"', false)->assertSee('My profile')->assertSee('Track your orders')->assertDontSee('My downloads')
            ->assertSee('href="'.route('users.profile').'"', false)
            ->assertSee('action="http://shop.localhost/logout"', false)->assertSee('js/components/account-menu.js', false);
    }

    public function test_public_catalog_uses_only_active_manually_imported_products_and_chosen_main_image()
    {
        $product = $this->product();
        $this->image($product, 'shop/first.png', 1);
        $main = $this->image($product, 'shop/chosen.png', 2);
        $removed = $this->image($product, 'shop/removed.png', 3);
        $removed->delete();
        $product->update(['main_image_id' => $main->id]);
        $this->product(['name' => 'Retired product'])->delete();
        $this->product(['name' => 'Another store', 'printful_store_id' => 55]);
        $response = $this->get(route('shop.home'))->assertOk()->assertSee('Piano rainbow T-shirt')
            ->assertSee('shop/chosen.png')->assertSee('$28.50 USD')->assertDontSee('Retired product')->assertDontSee('Another store')->assertDontSee('shop/removed.png');
        $this->assertStringNotContainsString('shop/first.png', $response->getContent());
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_search_and_category_filters_keep_the_matching_grid()
    {
        $this->product(['name' => 'Rainbow sticker']);
        for ($i = 0; $i < 13; $i++) $this->product(['name' => 'Piano T-shirt '.$i]);
        $this->get(route('shop.category', ['category' => 'stickers']))->assertOk()->assertSee('Rainbow sticker')->assertDontSee('Piano T-shirt');
        $this->get(route('shop.home', ['q' => 'Rainbow']))->assertOk()->assertSee('Rainbow sticker')->assertDontSee('Piano T-shirt');
        $this->get(route('shop.home', ['q' => '%']))->assertOk()->assertSee('No products found')->assertDontSee('Rainbow sticker');
        $this->get(route('shop.category', ['category' => 't-shirts']))->assertOk()->assertSee('Piano T-shirt 0')->assertDontSee('Rainbow sticker');
        $this->withExceptionHandling()->getJson(route('shop.home', ['q' => ['bad']]))->assertStatus(422);
        $this->getJson(route('shop.home', ['category' => 'bad']))->assertStatus(422);
    }

    public function test_product_gallery_and_options_preserve_curation_without_exposing_provider_details()
    {
        $product = $this->product(['variants' => [$this->variant(), $this->variant(['id' => 92, 'size' => 'L', 'retail_price' => '29.00'])]]);
        $this->image($product, 'shop/front.png', 1);
        $main = $this->image($product, 'shop/back.png', 2);
        $product->images()->update(['gallery_color' => 'Black']);
        $product->update(['main_image_id' => $main->id]);
        $response = $this->get(app(\App\Services\Shop\Storefront::class)->productUrl($product))->assertOk()->assertViewIs('shop.storefront.show')->assertSee('Add to bag')
            ->assertSee('data-color="Black"', false)->assertSee('data-size="L"', false)->assertSee('data-gallery-thumb', false)
            ->assertDontSee('Next image')->assertDontSee('data-gallery-step', false)
            ->assertSee('Checkout is not available yet')->assertDontSee('variant_id&quot;:4011', false);
        $this->assertLessThan(strpos($response->getContent(), 'shop/front.png'), strpos($response->getContent(), 'shop/back.png'));
        Http::assertNothingSent();
    }

    public function test_detail_options_use_labeled_color_buttons_smaller_sizes_and_one_item_per_add()
    {
        $product = $this->product(['variants' => [
            $this->variant(['color_code' => '#14191e']),
            $this->variant(['id' => 92, 'size' => 'L', 'color_code' => '#14191e']),
        ]]);
        $this->image($product, 'shop/black-option.png', 1)->update(['gallery_color' => 'Black']);
        $url = app(Storefront::class)->productUrl($product);
        $html = $this->get($url.'?utm_source=sample')->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//*[@data-option-group="color"]//button[not(contains(@class,"btn-sm")) and @aria-pressed="true"]//span[contains(@style,"#14191e")]'));
        $this->assertCount(2, $xpath->query('//*[@data-option-group="size"]//button[contains(@class,"btn-sm")]'));
        $this->assertCount(0, $xpath->query('//*[@data-product-options]//*[@data-quantity]'));
        $this->assertCount(1, $xpath->query('//*[@data-product-options]//input[@type="hidden" and @name="quantity" and @value="1"]'));
        $this->assertCount(1, $xpath->query('//*[@data-product-options]//*[@data-option-pickers]/following-sibling::*[.//*[@data-product-price]]'));
        $this->assertSame($url, $xpath->query('//*[@id="product-share-modal"]//input[@readonly]')->item(0)->getAttribute('value'));
        $this->post(route('shop.bag.add'), ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 1])->assertRedirect();
        $this->post(route('shop.bag.add'), ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 1])->assertRedirect();
        $this->assertSame(2, session(MerchandiseBag::SESSION_KEY)[$product->id.':91']['quantity']);
        Http::assertNothingSent();
    }

    public function test_swatches_reject_invalid_saved_css_and_ambiguous_shared_color_names()
    {
        $product = $this->product(['variants' => [
            $this->variant(['color' => 'Mint', 'color_code' => '#b9d8d1']),
            $this->variant(['id' => 92, 'color' => 'Mixed', 'color_code' => '#abcdef']),
            $this->variant(['id' => 93, 'color' => 'Mixed', 'size' => 'L', 'color_code' => '#010203']),
            $this->variant(['id' => 94, 'color' => 'Invalid', 'color_code' => 'red; background:url(https://evil.test)']),
        ]]);
        $response = $this->get(app(\App\Services\Shop\Storefront::class)->productUrl($product))->assertOk()
            ->assertSee('--swatch-color: #b9d8d1', false)->assertDontSee('evil.test')->assertDontSee('#abcdef')->assertDontSee('#010203');
        $this->assertSame(1, substr_count($response->getContent(), 'class="shop-color-swatch"'));
        Http::assertNothingSent();
    }

    public function test_bag_is_session_owned_and_recalculates_price_instead_of_trusting_submitted_totals()
    {
        $product = $this->product();
        $payload = ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 2, 'price' => '0.01', 'user_id' => 999];
        $this->postJson(route('shop.bag.add'), $payload)->assertOk()->assertJson(['count' => 2]);
        $this->assertSame([['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 2]], array_values(session(MerchandiseBag::SESSION_KEY)));
        $this->get(route('shop.bag'))->assertOk()->assertSee('$57.00 USD')->assertSee('Checkout is not available yet');
        $product->update(['variants' => [$this->variant(['retail_price' => '30.00'])]]);
        $this->get(route('shop.bag'))->assertOk()->assertSee('$60.00 USD')->assertDontSee('$57.00 USD');
        $this->flushSession();
        $this->get(route('shop.bag'))->assertOk()->assertSee('Your bag is empty')->assertDontSee($product->name);
        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_repeat_add_updates_quantity_and_patch_remove_have_native_fallbacks()
    {
        $product = $this->product();
        $data = ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 1];
        $this->post(route('shop.bag.add'), $data)->assertRedirect(route('shop.bag'))->assertSessionHas('status', 'Added to your bag');
        $this->postJson(route('shop.bag.add'), $data)->assertJson(['count' => 2]);
        $params = ['merchandise' => $product->id, 'variant' => 91];
        $this->patch(route('shop.bag.update', $params), ['quantity' => 3])->assertRedirect(route('shop.bag'));
        $this->get(route('shop.bag'))->assertSee('$85.50 USD');
        $this->delete(route('shop.bag.remove', $params))->assertRedirect(route('shop.bag'))->assertSessionHas('status', 'Removed from your bag');
        $removed = $this->get(route('shop.bag'))->assertSee('Your bag is empty')->assertSee('Removed from your bag')->assertDontSee('shop-toast');
        $dom = new \DOMDocument;
        @$dom->loadHTML($removed->getContent());
        $this->assertCount(1, (new \DOMXPath($dom))->query('//div[@role="alert" and contains(@class,"alert-green") and contains(.,"Removed from your bag") and not(ancestor::template)]'));
    }

    public function test_product_button_reports_only_this_sessions_quantity_for_each_exact_option()
    {
        $product = $this->product(['variants' => [$this->variant(), $this->variant(['id' => 92, 'size' => 'L'])]]);
        $other = $this->product(['name' => 'Other T-shirt']);
        $url = app(Storefront::class)->productUrl($product);
        $this->postJson(route('shop.bag.add'), ['product_id' => $other->id, 'variant_id' => 91, 'quantity' => 4])->assertOk();
        $this->get($url)->assertSee('Add to bag')->assertDontSee('In bag (');
        $data = ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 2];
        $this->postJson(route('shop.bag.add'), $data)->assertOk()
            ->assertJson(['count' => 6, 'item' => ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 2]]);
        $response = $this->get($url)->assertOk()->assertSee('In bag (2) · Add more');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('2', $xpath->query('//select[@name="variant_id"]/option[@value="91"]')->item(0)->getAttribute('data-in-bag'));
        $this->assertSame('0', $xpath->query('//select[@name="variant_id"]/option[@value="92"]')->item(0)->getAttribute('data-in-bag'));
        $this->postJson(route('shop.bag.add'), $data)->assertJson(['count' => 8, 'item' => ['quantity' => 4]]);
        $params = ['merchandise' => $product->id, 'variant' => 91];
        $this->patch(route('shop.bag.update', $params), ['quantity' => 1])->assertRedirect();
        $this->get($url)->assertSee('In bag (1) · Add more');
        $this->delete(route('shop.bag.remove', $params))->assertRedirect();
        $this->get($url)->assertSee('Add to bag')->assertDontSee('In bag (');
        $this->postJson(route('shop.bag.add'), $data)->assertOk();
        $this->flushSession();
        $this->get($url)->assertSee('Add to bag')->assertDontSee('In bag (');
        Http::assertNothingSent();
    }

    public function test_invalid_options_quantity_and_mixed_currencies_never_modify_the_bag()
    {
        $product = $this->product();
        $data = ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 1];
        $this->withExceptionHandling();
        foreach ([['variant_id' => 999], ['quantity' => 0], ['quantity' => 11], ['quantity' => 1.5], ['quantity' => [1]]] as $invalid) {
            $this->postJson(route('shop.bag.add'), array_merge($data, $invalid))->assertStatus(422);
        }
        $this->assertEmpty(session(MerchandiseBag::SESSION_KEY));
        $this->postJson(route('shop.bag.add'), $data + ['price' => 'forged'])->assertOk();
        $this->postJson(route('shop.bag.add'), array_merge($data, ['quantity' => 10]))->assertStatus(422);
        $other = $this->product(['variants' => [$this->variant(['currency' => 'EUR'])]]);
        $this->postJson(route('shop.bag.add'), array_merge($data, ['product_id' => $other->id]))->assertStatus(422);
        $this->assertSame(1, session(MerchandiseBag::SESSION_KEY)[$product->id.':91']['quantity']);
        $this->from(route('shop.bag'))->post(route('shop.bag.add'), array_merge($data, ['quantity' => 0]))
            ->assertRedirect(route('shop.bag'))->assertSessionHasErrors(['quantity'], null, 'storefront');
        $invalid = $this->get(route('shop.bag'))->assertOk()->assertDontSee('shop-form-error');
        $dom = new \DOMDocument;
        @$dom->loadHTML($invalid->getContent());
        $this->assertCount(1, (new \DOMXPath($dom))->query('//div[@role="alert" and contains(@class,"alert-red") and not(ancestor::template)]'));
    }

    public function test_retired_products_and_unavailable_variants_cannot_be_added_or_counted()
    {
        $product = $this->product();
        $data = ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 1];
        $this->postJson(route('shop.bag.add'), $data)->assertOk();
        $product->update(['variants' => [$this->variant(['out_of_stock' => true])]]);
        $unavailable = $this->get(route('shop.bag'))->assertSee('Some items are no longer available')->assertDontSee('$28.50 USD');
        $dom = new \DOMDocument;
        @$dom->loadHTML($unavailable->getContent());
        $this->assertCount(1, (new \DOMXPath($dom))->query('//div[@role="alert" and contains(@class,"alert-yellow") and contains(.,"Some items are no longer available")]'));
        $this->withExceptionHandling()->postJson(route('shop.bag.add'), $data)->assertStatus(422);
        $product->delete();
        $this->get(app(\App\Services\Shop\Storefront::class)->productUrl($product))->assertNotFound();
        $this->postJson(route('shop.bag.add'), $data)->assertNotFound();
    }

    public function test_unusable_variant_prices_and_statuses_have_no_purchase_control()
    {
        foreach ([['synced' => false], ['retail_price' => null], ['retail_price' => '-1'], ['retail_price' => '0.00'], ['currency' => null], ['discontinued' => true]] as $invalid) {
            $product = $this->product(['variants' => [$this->variant($invalid)]]);
            $this->assertSame([], app(Storefront::class)->variants($product));
            $this->get(app(\App\Services\Shop\Storefront::class)->productUrl($product))->assertOk()->assertSee('Currently unavailable')->assertDontSee('Add to bag');
        }
    }

    public function test_bag_writes_keep_csrf_and_session_locks_on_the_shop_host()
    {
        foreach (['shop.bag.add', 'shop.bag.update', 'shop.bag.remove'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertSame('shop.localhost', $route->getDomain());
            $this->assertContains('web', $route->middleware());
            $this->assertGreaterThan(0, $route->locksFor());
        }
        $this->app['env'] = 'local';
        $this->withExceptionHandling()->postJson(route('shop.bag.add'), ['product_id' => 1, 'variant_id' => 91, 'quantity' => 1])->assertStatus(419);
    }

    public function test_provider_product_names_classify_designs_without_guessing_unknown_types()
    {
        $shirt = $this->product(['name' => 'Piano rainbow', 'variants' => [$this->variant(['product_name' => 'Unisex organic cotton t-shirt'])]]);
        $sticker = $this->product(['name' => 'Piano rainbow', 'variants' => [$this->variant(['product_name' => 'Kiss-cut stickers'])]]);
        $unknown = $this->product(['name' => 'Piano rainbow']);
        $this->assertSame('t-shirts', app(Storefront::class)->category($shirt));
        $this->assertSame('stickers', app(Storefront::class)->category($sticker));
        $this->assertSame('merchandise', app(Storefront::class)->category($unknown));
    }
}
