<?php

namespace Tests\Review;

use App\{Admin, Review, User};
use App\Merchandise\MerchandiseProduct;
use App\Notifications\ReviewSubmittedNotification;
use App\Services\Shop\Storefront;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Notification, Redis};
use Stevebauman\Location\Facades\Location;

class ShopMerchandiseReviewsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['services.printful.store_id' => '12345']);
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        Notification::fake();
    }

    private function merchandise(array $attributes = [])
    {
        return MerchandiseProduct::create($attributes + ['printful_store_id' => 12345,
            'printful_product_id' => random_int(1000, 999999), 'name' => 'Piano T-shirt',
            'variants' => [['id' => 91, 'synced' => true, 'retail_price' => '28.00', 'currency' => 'USD']]]);
    }

    private function digital($class, array $attributes = [])
    {
        return Model::withoutEvents(fn () => create($class, $attributes + [
            'title' => 'Piano edition', 'published_at' => now(), 'price' => 12, 'discount' => 0, 'previews' => '',
        ]));
    }

    private function user()
    {
        return Model::withoutEvents(fn () => create(User::class));
    }

    private function url($product)
    {
        return app(Storefront::class)->productUrl($product);
    }

    private function browserFixture(string $name, string $html, string $productUrl): void
    {
        if (!$directory = getenv('PIANOLIT_SHOP_BROWSER_FIXTURES')) return;
        if (!is_dir($directory)) mkdir($directory, 0700, true);
        $html = str_replace(['http://localhost/', 'http://shop.localhost/', 'href="/css/', 'src="/js/'],
            ['http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $html);
        foreach (['shirt' => 'images/shop/demo/shirt-mint.png', 'book' => 'images/shop/categories/ebooks.webp', 'score' => 'images/misc/score1.png'] as $key => $asset) {
            $html = str_replace('http://shop.pianolit.test/storage/shop/review-'.$key.'.png', 'http://shop.pianolit.test/'.$asset, $html);
        }
        $html = str_replace(str_replace('http://shop.localhost/', 'http://shop.pianolit.test/', $productUrl), 'http://127.0.0.1:8775/merchandise.html', $html);
        file_put_contents($directory.'/'.$name.'.html', $html);
    }

    public function test_card_ratings_are_published_only_and_keep_overlapping_product_ids_separate()
    {
        $shirt = $this->merchandise();
        $shirt->images()->create(['path' => 'shop/review-shirt.png', 'source' => 'upload', 'position' => 1,
            'alt' => 'Fictional sample shirt', 'checksum' => hash('sha256', 'shirt'), 'width' => 800, 'height' => 800]);
        $book = $this->digital(eBook::class, ['mockup_path' => 'shop/review-book.png']);
        $score = $this->digital(eScore::class, ['mockup_path' => 'shop/review-score.png']);
        $this->assertSame($shirt->id, $book->id);
        $this->assertSame($shirt->id, $score->id);
        foreach ([5, 4] as $rating) $shirt->reviews()->create(['rating' => $rating, 'published_at' => now()]);
        $shirt->reviews()->create(['rating' => 1, 'content' => 'Pending private review']);
        $book->reviews()->create(['rating' => 3, 'published_at' => now()]);
        $score->reviews()->create(['rating' => 1]);
        DB::enableQueryLog();
        $response = $this->get(route('shop.home'))->assertOk()->assertDontSee('Pending private review');
        $aggregates = collect(DB::getQueryLog())->filter(fn ($query) => strpos(strtolower($query['query']), 'avg(rating)') !== false);
        $this->assertCount(1, $aggregates);
        DB::disableQueryLog();
        $cards = collect($response->viewData('products')->items())->keyBy('category');
        $this->assertSame(2, $cards['t-shirts']['reviewCount']);
        $this->assertSame('4.5', $cards['t-shirts']['averageRating']);
        $this->assertSame(1, $cards['ebooks']['reviewCount']);
        $this->assertSame('3.0', $cards['ebooks']['averageRating']);
        $this->assertSame(0, $cards['escores']['reviewCount']);
        $this->assertSame(0, $cards['escores']['averageRating']);
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//article//h2/../a[@href="'.$this->url($shirt).'#reviews"]'));
        $response->assertSeeInOrder([$shirt->name, '(2)', '$28.00 USD']);
        foreach ([$shirt, $book, $score] as $product) {
            $this->assertArrayNotHasKey('averageRating', $product->fresh()->toArray());
            $this->assertArrayNotHasKey('reviewCount', $product->fresh()->toArray());
        }
        $this->assertArrayNotHasKey('reviews_count', $shirt->fresh()->toArray());
        $this->browserFixture('catalog', $response->getContent(), $this->url($shirt));
        if (getenv('PIANOLIT_SHOP_BROWSER_FIXTURES')) {
            $this->browserFixture('merchandise', $this->get($this->url($shirt))->assertOk()->getContent(), $this->url($shirt));
        }
        Http::assertNothingSent();
    }

    public function test_merchandise_reuses_the_review_form_and_requires_an_owned_moderated_submission()
    {
        $shirt = $this->merchandise();
        $url = $this->url($shirt);
        $this->get($url)->assertOk()->assertSee('Customer reviews')->assertSee('No reviews yet')
            ->assertSee('action="'.$shirt->reviewRoute().'"', false)->assertSee('js/views/shop-product.js')
            ->assertDontSee('js/vendor/turn.min.js')->assertDontSee('data-book-preview');
        $this->withExceptionHandling()->from($url)->post($shirt->reviewRoute(), ['rating' => 5])->assertRedirect('http://shop.localhost/login');
        $user = $this->user();
        $this->actingAs($user, 'web')->from($url)->post($shirt->reviewRoute(), [
            'rating' => 5, 'title' => 'Lovely shirt', 'content' => 'A comfortable shirt', 'reviewer' => 'A pianist', 'user_id' => 999,
        ])->assertRedirect($url)->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Thanks for your review. It will appear after moderation.');
        $review = $shirt->reviews()->firstOrFail();
        $this->assertSame($user->id, $review->user_id);
        $this->assertNull($review->published_at);
        $this->assertTrue($user->reviewed($shirt));
        $this->assertTrue($user->reviewFor($shirt)->is($review));
        $this->post($shirt->reviewRoute(), ['rating' => 4])->assertForbidden();
        $this->get($url)->assertOk()->assertDontSee('Thanks for your feedback!')
            ->assertDontSee('We are reviewing your review')->assertDontSee('A comfortable shirt');
        $review->update(['published_at' => now()]);
        $response = $this->get($url)->assertOk()->assertSee('Based on 1 review')->assertSee('Lovely shirt')
            ->assertSee('A comfortable shirt')->assertSee('Delete my review')->assertDontSee('You submitted a review on');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//*[@id="reviews"]//article//button[@aria-label="Delete my review" and contains(@class,"btn-red")]'));
        $this->assertSame('(1)', trim($xpath->query('//a[@href="#reviews"]')->item(0)->textContent));
        $this->browserFixture('reviews', $response->getContent(), $url);
        $this->actingAs($this->user(), 'web')->get($url)->assertOk()->assertDontSee('aria-label="Delete my review"', false);
        $this->actingAs($this->user(), 'web')->delete(route('reviews.destroy', $review))->assertForbidden();
        $this->actingAs($user, 'web')->from($url)->delete(route('reviews.destroy', $review))->assertRedirect($url);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseCount('purchases', 0);
        Http::assertNothingSent();
    }

    public function test_review_errors_preserve_the_merchandise_draft_without_opening_account_validation()
    {
        $shirt = $this->merchandise();
        $this->actingAs($this->user(), 'web')->withExceptionHandling()->from($this->url($shirt))
            ->post($shirt->reviewRoute(), ['rating' => 8, 'title' => 'Saved draft', 'reviewer' => ['invalid']])
            ->assertRedirect($this->url($shirt))->assertSessionHasErrors(['rating', 'reviewer'], null, 'review');
        $this->assertFalse(session('errors')->getBag('default')->any());
        $this->get($this->url($shirt))->assertOk()->assertSee('value="Saved draft"', false);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_other_store_and_retired_products_cannot_receive_reviews()
    {
        $other = $this->merchandise(['printful_store_id' => 99]);
        $retired = $this->merchandise(['name' => 'Retired T-shirt']);
        $retired->delete();
        $this->actingAs($this->user(), 'web')->withExceptionHandling();
        foreach ([$other, $retired] as $product) $this->post($product->reviewRoute(), ['rating' => 5])->assertNotFound();
        $this->post(route('reviews.store', ['model' => User::class, 'id' => 1]), ['rating' => 5])->assertNotFound();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_merchandise_notifications_and_admin_moderation_use_the_product_name_and_escape_content()
    {
        $shirt = $this->merchandise(['name' => 'Alpha piano T-shirt']);
        $book = $this->digital(eBook::class, ['title' => 'Zulu piano edition']);
        $review = $shirt->reviews()->create(['rating' => 4, 'title' => '<script>unsafe</script>', 'content' => '<img src=x onerror=alert(1)>']);
        $book->reviews()->create(['rating' => 5, 'published_at' => now()]);
        $message = (new ReviewSubmittedNotification($review))->toArray(null);
        $this->assertStringContainsString($shirt->name, $message['message']);
        $admin = Model::withoutEvents(fn () => create(Admin::class, ['role' => 'manager']));
        $columns = array_map(fn ($name) => ['data' => $name === 'reviewable.title' ? 'reviewable' : $name, 'name' => $name,
            'searchable' => in_array($name, ['created_at', 'reviewable.title']) ? 'true' : 'false', 'orderable' => 'true'],
            ['created_at', 'reviewable.title', 'rating', 'user', 'published', 'action']);
        $query = ['draw' => 1, 'start' => 0, 'length' => 12, 'columns' => $columns, 'search' => ['value' => 'piano'],
            'order' => [['column' => 1, 'dir' => 'asc']]];
        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.reviews.index', $query), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame(2, $response->json('recordsFiltered'));
        $this->assertStringContainsString($shirt->name, $response->json('data.0.reviewable'));
        $this->assertStringContainsString('&lt;script&gt;', $response->json('data.0.action'));
        $this->assertStringNotContainsString('<script>unsafe</script>', $response->json('data.0.action'));
        $this->patch(route('admin.reviews.update-status', $review))->assertOk();
        $this->assertNotNull($review->fresh()->published_at);
        $this->patch(route('admin.reviews.update-status', $review))->assertOk();
        $this->assertNull($review->fresh()->published_at);
    }
}
