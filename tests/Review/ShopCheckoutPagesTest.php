<?php

namespace Tests\Review;

use App\{User};
use App\Billing\Factories\StripeFactory;
use App\Events\PurchaseMade;
use App\Merchandise\Purchase;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Event, Mail, Redis, Storage};
use Stevebauman\Location\Facades\Location;

class ShopCheckoutPagesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        Event::fake([PurchaseMade::class]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    public static function products()
    {
        return [[eBook::class], [eScore::class]];
    }

    private function product($class, array $attributes = [])
    {
        return Model::withoutEvents(function () use ($class, $attributes) {
            return create($class, $attributes + ['title' => 'Piano Solos Book 1', 'subtitle' => 'Music for your next practice', 'price' => 12, 'discount' => 0, 'pages_count' => 29, 'published_at' => now(), 'cover_path' => 'checkout-fixture/cover.jpg', 'previews' => '', 'pdf_path' => 'shop/fixture.pdf']);
        });
    }

    private function account($card = false, $member = false)
    {
        $email = 'alex'.(User::count() ? '+'.User::count() : '').'@example.test';
        $user = Model::withoutEvents(function () use ($member, $email) { return create(User::class, ['first_name' => 'Alex', 'email' => $email, 'super_user' => $member]); });
        if ($card) $user->customer()->create(['stripe_id' => 'cus_disposable_shop_fixture', 'card_brand' => 'visa', 'card_last_four' => '4242']);
        return $user;
    }

    private function purchase($user, $product, array $attributes = [])
    {
        return $user->purchases()->create($attributes + ['item_type' => get_class($product), 'item_id' => $product->id, 'cost' => 8, 'charge_id' => 'ch_disposable_fixture', 'created_at' => '2025-12-10 12:00:00']);
    }

    private function successUrl($purchase)
    {
        return 'http://shop.localhost/shop/'.$purchase->id.'/thank-you';
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    private function preview($response, $name)
    {
        if (! ($directory = getenv('SHOP_CHECKOUT_PREVIEW_DIR'))) return;
        if (! is_dir($directory)) mkdir($directory, 0700, true);
        $html = str_replace(['http://shop.localhost/', 'http://localhost/', 'href="/css/', 'src="/js/'], ['http://shop.pianolit.test/', 'http://pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $response->getContent());
        file_put_contents($directory.'/'.$name.'.html', $html);
    }

    /** @dataProvider products */
    public function test_new_card_checkout_is_labeled_scoped_and_uses_the_original_shop_purchase_route($class)
    {
        $product = $this->product($class);
        $this->actingAs($this->account(), 'web');
        $response = $this->get($product->checkoutRoute())->assertOk()
            ->assertSee('Checkout')->assertSee('Order summary')->assertSee('Your account')->assertSee('Pay $12.00')
            ->assertSee('data-order-base="1200"', false)->assertSee('id="card-element"', false)
            ->assertSee('action="'.$product->purchaseRoute().'"', false)->assertDontSee('name="stripeToken"', false)
            ->assertSee('https://js.stripe.com/v3/', false)->assertSee('js/views/checkout.js', false);
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//*[@id="checkout-coupon"]'));
        $this->assertCount(1, $xpath->query('//form[@id="payment-form"]//input[@name="_token"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//*[@aria-current="page" and contains(.,"Checkout")]'));
        $this->assertCount(1, $xpath->query('//input[@name="save_card" and @role="switch" and @checked]'));
        $this->preview($response, $class === eBook::class ? 'ebook-checkout' : 'new-card');
        $this->assertDatabaseCount('purchases', 0);
        Mail::assertNothingSent();
    }

    public function test_saved_card_forms_have_unique_ids_same_host_actions_and_preserved_csrf()
    {
        $product = $this->product(eScore::class);
        $this->actingAs($this->account(true), 'web');
        $response = $this->get($product->checkoutRoute())->assertOk()->assertSee('Visa ending in 4242')
            ->assertSee('action="http://shop.localhost/shop/payment-method"', false);
        $xpath = $this->xpath($response);
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $node) $ids[] = $node->getAttribute('id');
        $this->assertSameSize(array_unique($ids), $ids);
        $this->assertCount(2, $xpath->query('//form[@data-shop-payment]//input[@name="_token"]'));
        $this->assertCount(1, $xpath->query('//form[@id="payment-form" and @hidden]'));
        $this->assertCount(1, $xpath->query('//*[@id="checkout-coupon"]'));
        $this->preview($response, 'saved-card');
    }

    /** @dataProvider products */
    public function test_free_and_member_checkouts_do_not_load_or_request_payment_details($class)
    {
        foreach ([false, true] as $member) {
            $product = $this->product($class, ['price' => $member ? 12 : 0]);
            $this->actingAs($this->account(false, $member), 'web');
            $response = $this->get($product->checkoutRoute())->assertOk()->assertSee('Get your download')
                ->assertSee('No payment details needed')->assertSee('data-order-base="0"', false)
                ->assertSee('action="'.$product->purchaseRoute().'"', false)
                ->assertDontSee('https://js.stripe.com')->assertDontSee('id="payment-form"', false)->assertDontSee('checkout-coupon');
            $this->preview($response, $member ? 'member-download' : 'free-download');
        }
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_an_owned_product_checkout_links_to_the_existing_download_instead_of_payment()
    {
        $product = $this->product(eBook::class);
        $user = $this->account();
        $purchase = $this->purchase($user, $product);
        $this->actingAs($user, 'web')->get($product->checkoutRoute())->assertOk()->assertSee('Open your download')
            ->assertSee('/shop/'.$purchase->id.'/thank-you', false)->assertDontSee('https://js.stripe.com')->assertDontSee('data-order-pay');
        $this->assertDatabaseCount('purchases', 1);
    }

    /** @dataProvider products */
    public function test_confirmation_uses_saved_order_amount_and_date_and_keeps_original_format_links($class)
    {
        $product = $this->product($class, ['price' => 99]);
        $user = $this->account();
        $purchase = $this->purchase($user, $product);
        $this->actingAs($user, 'web');
        $response = $this->get($this->successUrl($purchase))->assertOk()->assertSee('Your download is ready')
            ->assertSee('Download your files')->assertSee('Order details')->assertSee('Dec 10, 2025')->assertSee('$8.00')
            ->assertDontSee('$99.00')->assertDontSee('Time placed')->assertSee('href="http://localhost/profile/downloads"', false)
            ->assertDontSee('https://js.stripe.com')->assertDontSee('countdown-seconds');
        $xpath = $this->xpath($response);
        foreach ($product->links() as $label => $hash) {
            $links = $xpath->query('//a[@aria-label="Download '.$label.'"]');
            $this->assertCount(1, $links);
            $url = $links->item(0)->getAttribute('href');
            $this->assertStringStartsWith('http://shop.localhost/shop/'.$purchase->id.'/download?', $url);
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame(decrypt($hash), decrypt($query['path']));
        }
        $this->preview($response, $class === eBook::class ? 'ebook-confirmation' : 'confirmation');
        Mail::assertNothingSent();
    }

    public function test_no_charge_confirmation_does_not_claim_a_payment_or_email_was_sent()
    {
        $product = $this->product(eScore::class);
        $user = $this->account();
        $purchase = $this->purchase($user, $product, ['cost' => null, 'charge_id' => null]);
        $response = $this->actingAs($user, 'web')->get($this->successUrl($purchase))->assertOk()
            ->assertSee('Added to your downloads')->assertSee('$0.00')->assertSee('No payment was required')->assertDontSee('We sent an email');
        $this->preview($response, 'download-confirmation');
    }

    public function test_receipt_and_download_must_belong_to_the_current_account_even_if_it_owns_the_same_item()
    {
        $product = $this->product(eScore::class);
        $owner = $this->account();
        $other = Model::withoutEvents(function () { return create(User::class); });
        $purchase = $this->purchase($owner, $product);
        $this->purchase($other, $product);
        $this->actingAs($other, 'web')->withExceptionHandling();
        $this->get($this->successUrl($purchase).'?user_id='.$owner->id)->assertForbidden();
        $this->get('http://shop.localhost/shop/'.$purchase->id.'/download?path='.urlencode(encrypt($product->pdf_path)))->assertForbidden();
    }

    public function test_original_owned_file_download_still_returns_the_original_file()
    {
        Storage::fake('public');
        Storage::disk('public')->put('shop/fixture.pdf', 'Original download bytes');
        $product = $this->product(eScore::class);
        $user = $this->account();
        $purchase = $this->purchase($user, $product);
        $response = $this->actingAs($user, 'web')->get('http://shop.localhost/shop/'.$purchase->id.'/download?path='.urlencode(encrypt($product->pdf_path)))->assertOk()->assertDownload('fixture.pdf');
        $this->assertSame('Original download bytes', $response->streamedContent());
    }

    public function test_coupon_response_preserves_its_contract_and_adds_verified_percentage_for_the_order_total()
    {
        $stripe = \Mockery::mock(StripeFactory::class);
        $stripe->shouldReceive('getCoupon')->once()->with('SAVE25')->andReturn((object) ['valid' => true, 'name' => '25% off', 'percent_off' => 25]);
        $this->app->instance(StripeFactory::class, $stripe);
        $this->actingAs($this->account(), 'web')->getJson('http://shop.localhost/shop/validate-coupon?coupon=save25')->assertOk()
            ->assertJson(['isValid' => true, 'percentOff' => 25])->assertJsonStructure(['isValid', 'message']);
    }
}
