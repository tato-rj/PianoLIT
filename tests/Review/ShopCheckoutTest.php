<?php

namespace Tests\Review;

use App\Shop\ShopOrder;
use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{CheckoutEnvironment, CheckoutStripe, MerchandiseBag, ShopCheckout, MerchandiseFulfillment};
use Illuminate\Support\Facades\{DB, Http};
use Tests\Support\FakeCheckoutStripe;

class ShopCheckoutTest extends ReviewTestCase
{
    private $product, $gateway;

    protected function tearDown(): void
    {
        $this->app->instance('env', 'testing');
        parent::tearDown();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config(['shop-checkout.mode' => 'sandbox', 'shop-checkout.stripe_key' => 'pk_test_review',
            'shop-checkout.stripe_secret' => 'sk_test_review', 'services.printful.store_id' => 12345]);
        $this->gateway = new FakeCheckoutStripe;
        $this->app->instance(CheckoutStripe::class, $this->gateway);
        $this->product = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 8,
            'name' => 'Piano T-shirt', 'variants' => [['id' => 91, 'variant_id' => 4011, 'synced' => true,
                'size' => 'M', 'color' => 'Mint', 'retail_price' => '28.00', 'currency' => 'USD']]]);
        $this->postJson(route('shop.bag.add'), ['product_id' => $this->product->id, 'variant_id' => 91, 'quantity' => 1])->assertOk();
    }

    private function address(array $changes = []): array
    {
        return $changes + ['email' => 'checkout@example.test', 'name' => 'Test Customer', 'address1' => '123 Test Street',
            'city' => 'New York', 'state_code' => 'NY', 'country_code' => 'US', 'zip' => '10001'];
    }

    private function quote(array $changes = []): array
    {
        $response = $this->postJson(route('shop.checkout.quote'), $this->address($changes));
        $this->assertSame(200, $response->status(), $response->getContent());
        return $response->json();
    }

    private function prepared(): ShopOrder
    {
        $quote = $this->quote();
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertOk();
        return ShopOrder::findOrFail($quote['order_id']);
    }

    private function live(?array &$remote, bool $loseConfirm = false, bool $expensive = false): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->app->instance('env', 'production');
        config(['shop-checkout.mode' => 'live', 'shop-checkout.enabled' => true, 'shop-checkout.printful_orders_enabled' => true,
            'shop-checkout.stripe_key' => 'pk_live_review', 'shop-checkout.stripe_secret' => 'sk_live_review',
            'shop-checkout.stripe_webhook_secret' => 'whsec_live_review',
            'services.printful.token' => 'review-only', 'services.printful.token_file' => null]);
        Http::fake(function ($request) use (&$remote, &$loseConfirm, $expensive) {
            $url = $request->url();
            if (str_ends_with($url, '/countries')) return Http::response(['code' => 200, 'result' => [['code' => 'US', 'name' => 'United States', 'states' => [['code' => 'NY', 'name' => 'New York']]]]]);
            if (str_ends_with($url, '/shipping/rates')) return Http::response(['code' => 200, 'result' => [['id' => 'STANDARD', 'name' => 'Standard', 'rate' => '5.00', 'currency' => 'USD']]]);
            if (str_ends_with($url, '/orders/estimate-costs')) return Http::response(['code' => 200, 'result' => ['costs' => ['currency' => 'USD', 'shipping' => '5.00', 'total' => '20.00']]]);
            if (str_contains($url, '/orders/@')) return $remote ? Http::response(['code' => 200, 'result' => $remote]) : Http::response([], 404);
            if (str_ends_with($url, '/orders')) {
                $remote = $request->data() + ['id' => 100, 'status' => 'draft', 'costs' => ['currency' => 'USD', 'total' => $expensive ? '21.00' : '20.00']];
                return Http::response(['code' => 200, 'result' => $remote]);
            }
            if (str_ends_with($url, '/confirm')) {
                $remote['status'] = 'pending';
                if ($loseConfirm) { $loseConfirm = false; return Http::response([], 500); }
                return Http::response(['code' => 200, 'result' => $remote]);
            }
            throw new \RuntimeException('Unexpected review HTTP request.');
        });
    }

    public function test_quote_recalculates_price_and_encrypts_delivery_details_without_contacting_printful()
    {
        $html = $this->getJson(route('shop.checkout'))->assertOk()->json('html');
        $this->assertStringContainsString('data-checkout-modal', $html);
        $this->assertStringNotContainsString('checkout-test-note', $html);
        $this->assertStringNotContainsString('data-delivery-quote', $html);
        $this->assertStringContainsString('data-delivery-retry hidden', $html);
        $quote = $this->quote(['subtotal' => 1, 'tax' => 0, 'shipping' => 0, 'user_id' => 99]);
        $this->assertSame(2800, $quote['subtotal']);
        $this->assertSame(500, $quote['shipping']);
        $this->assertSame(3300, $quote['total']);
        $this->assertSame(ShopOrder::find($quote['order_id'])->expires_at->toIso8601String(), $quote['expires_at']);
        $raw = DB::table('shop_orders')->where('id', $quote['order_id'])->first();
        $this->assertStringNotContainsString('checkout@example.test', $raw->recipient);
        $this->assertStringNotContainsString('123 Test Street', $raw->recipient);
        $this->assertNull($raw->user_id);
        $this->assertSame('checkout@example.test', ShopOrder::find($raw->id)->recipient['email']);
        Http::assertNothingSent();
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_wallet_preview_accepts_a_redacted_address_and_uses_saved_prices_without_creating_an_order()
    {
        $html = $this->getJson(route('shop.checkout'))->assertOk()->json('html');
        $this->assertLessThan(strpos($html, 'data-shipping-form'), strpos($html, 'data-checkout-wallets'));
        $this->assertStringContainsString('data-wallet-amount="2800"', $html);
        if ($directory = getenv('SHOP_WALLET_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            file_put_contents($directory.'/checkout.html', $html);
        }
        $response = $this->postJson(route('shop.checkout.wallet-quote'), ['country_code' => 'US', 'state_code' => 'NY',
            'city' => 'New York', 'zip' => '10001', 'shipping_id' => 'EXPRESS', 'subtotal' => 1, 'shipping' => 0, 'total' => 1, 'user_id' => 99]);
        $response->assertOk()->assertJson(['subtotal' => 2800, 'shipping' => 1200, 'total' => 4000, 'shipping_id' => 'EXPRESS', 'currency' => 'usd']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertArrayNotHasKey('order_id', $response->json());
        $this->assertDatabaseCount('shop_orders', 0);
        $this->assertCount(0, $this->gateway->calls);
        Http::assertNothingSent();
    }

    public function test_bag_offers_express_checkout_and_a_native_link_to_the_full_checkout_page()
    {
        $response = $this->get(route('shop.bag'))->assertOk()->assertSee("Checkout with card", false)
            ->assertSee('Express checkout')->assertSee('data-wallet-only="true"', false)->assertSee('js/components/express-checkout.js');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $link = $xpath->query('//a[@href="'.route('shop.checkout').'"]');
        $this->assertCount(1, $link);
        $this->assertFalse($link->item(0)->hasAttribute('data-checkout-open'));
        $this->assertCount(1, $xpath->query('//*[@data-wallet-only="true"]//form[@data-shipping-form and @hidden]'));
        $this->assertCount(0, $xpath->query('//*[@data-wallet-only="true"]//*[@data-stripe-field]'));
        if ($directory = getenv('SHOP_WALLET_PREVIEW_DIR')) file_put_contents($directory.'/bag-page.html', $response->getContent());
        $this->get(route('shop.bag', ['checkout' => 'merchandise', 'modal' => 'payment-checkout-modal']))->assertRedirect(route('shop.checkout'));
        $this->assertDatabaseCount('shop_orders', 0);
        $this->assertCount(0, $this->gateway->calls);
        Http::assertNothingSent();
    }

    public function test_checkout_keeps_express_before_guest_contact_and_delivery_and_uses_a_collapsible_summary()
    {
        $response = $this->get(route('shop.checkout'))->assertOk()->assertSee('Express checkout')->assertSee('Contact')->assertSee('Sign in')->assertSee('Delivery');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//details[@data-checkout-summary]/summary//*[@data-checkout-mobile-total]'));
        $this->assertCount(3, $xpath->query('//*[@data-checkout-sections]//button[@data-bs-toggle="collapse"]'));
        foreach (['checkout-contact' => 'Contact', 'checkout-delivery' => 'Delivery address', 'checkout-payment-method' => 'Payment method'] as $id => $label) {
            $toggle = $xpath->query('//button[@aria-controls="'.$id.'"]');
            $this->assertCount(1, $toggle);
            $this->assertSame($label, trim($toggle->item(0)->textContent));
            $this->assertCount(1, $xpath->query('//*[@id="'.$id.'" and @data-bs-parent="#checkout-sections"]'));
        }
        $this->assertCount(1, $xpath->query('//form[@data-shipping-form]//input[@type="email" and @name="email"]'));
        $this->assertCount(1, $xpath->query('//form[@data-shipping-form]//a[@href="'.route('login').'"]'));
        if ($directory = getenv('SHOP_WALLET_PREVIEW_DIR')) file_put_contents($directory.'/checkout-page.html', $response->getContent());
        $user = \Illuminate\Database\Eloquent\Model::withoutEvents(fn () => create(\App\User::class, ['email' => 'account@example.test']));
        $signedIn = $this->actingAs($user, 'web')->get(route('shop.checkout'))->assertOk()->assertSee('account@example.test');
        @$dom->loadHTML($signedIn->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//form[@data-shipping-form]//input[@type="hidden" and @name="email" and @value="account@example.test"]'));
        $this->assertCount(0, $xpath->query('//form[@data-shipping-form]//input[@type="email" and @name="email"]'));
        $this->assertDatabaseCount('shop_orders', 0);
    }

    public function test_an_owned_pending_wallet_address_follows_the_customer_to_checkout_without_leaking_to_another_account()
    {
        $user = \Illuminate\Database\Eloquent\Model::withoutEvents(fn () => create(\App\User::class));
        $this->actingAs($user, 'web');
        $quote = $this->quote();
        $this->get(route('shop.checkout'))->assertOk()->assertSee('value="123 Test Street"', false)->assertSee('value="10001"', false);
        $other = \Illuminate\Database\Eloquent\Model::withoutEvents(fn () => create(\App\User::class));
        $this->actingAs($other, 'web')->get(route('shop.checkout'))->assertOk()->assertDontSee('value="123 Test Street"', false);
        $this->actingAs($user, 'web');
        ShopOrder::findOrFail($quote['order_id'])->update(['expires_at' => now()->subMinute()]);
        $this->get(route('shop.checkout'))->assertOk()->assertDontSee('value="123 Test Street"', false);
        $this->assertCount(0, $this->gateway->intents);
    }

    public function test_wallet_preview_rejects_unknown_destinations_and_rates_and_obeys_checkout_activation()
    {
        $url = route('shop.checkout.wallet-quote');
        $this->postJson($url, ['country_code' => 'US', 'state_code' => 'UNKNOWN'])->assertJsonValidationErrors('state_code');
        $this->postJson($url, ['country_code' => 'XX'])->assertJsonValidationErrors('country_code');
        $this->postJson($url, ['country_code' => 'US', 'state_code' => 'NY', 'shipping_id' => 'FREE'])->assertStatus(422);
        $this->postJson($url, ['country_code' => 'US', 'state_code' => 'NY', 'email' => 'invalid'])->assertJsonValidationErrors('email');
        $remote = null;
        $this->live($remote);
        config(['shop-checkout.enabled' => false]);
        $this->postJson($url, ['country_code' => 'US', 'state_code' => 'NY'])->assertStatus(422);
        $this->assertDatabaseCount('shop_orders', 0);
        $this->assertCount(0, $this->gateway->intents);
        Http::assertNothingSent();
    }

    public function test_live_wallet_preview_uses_provider_rates_and_shared_tax_without_saving_private_contact_or_payment()
    {
        $remote = null;
        $this->live($remote);
        $this->postJson(route('shop.checkout.wallet-quote'), ['country_code' => 'US', 'state_code' => 'NY', 'city' => 'New York', 'zip' => '10001'])
            ->assertOk()->assertJson(['subtotal' => 2800, 'shipping' => 500, 'tax' => 200, 'total' => 3500]);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/shipping/rates')
            && !isset($request['recipient']['name']) && !isset($request['recipient']['address1']));
        $this->assertSame('tax.calculations', $this->gateway->calls[0]['resource']);
        $this->assertSame('10001', $this->gateway->calls[0]['parameters']['customer_details']['address']['postal_code']);
        $this->assertCount(0, $this->gateway->intents);
        $this->assertNull($remote);
        $this->assertDatabaseCount('shop_orders', 0);
    }

    public function test_wallet_preview_can_estimate_a_known_full_address_and_does_not_reserve_the_first_order_offer()
    {
        app(\App\Services\Shop\FirstOrderDiscount::class)->grant('checkout@example.test');
        $this->postJson(route('shop.checkout.wallet-quote'), $this->address())
            ->assertOk()->assertJson(['subtotal' => 2520, 'shipping' => 500, 'total' => 3020, 'discount' => 280]);
        $this->assertNull(DB::table('shop_first_order_discounts')->first()->reserved_order_id);
        $this->assertNull(DB::table('shop_first_order_discounts')->first()->redeemed_order_id);
        $this->assertDatabaseCount('shop_orders', 0);
        $this->assertCount(0, $this->gateway->intents);
        Http::assertNothingSent();
    }

    public function test_payment_preparation_is_idempotent_and_unpaid_confirmation_never_fulfills()
    {
        $order = $this->prepared();
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $order->id])->assertOk();
        $this->assertCount(1, $this->gateway->intents);
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertStatus(422);
        $this->assertNull($order->fresh()->paid_at);
        Http::assertNothingSent();
    }

    public function test_lost_stripe_creation_response_recovers_the_same_payment_intent()
    {
        $quote = $this->quote();
        $this->gateway->lostCreateResponse = true;
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertStatus(422);
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertOk();
        $this->assertCount(1, $this->gateway->intents);
    }

    public function test_sandbox_payment_completes_without_printful_and_clears_bag_only_once()
    {
        $order = $this->prepared();
        $this->gateway->succeed($order->stripe_payment_intent);
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertOk();
        $this->assertSame('test_complete', $order->fresh()->status);
        $this->assertEmpty(session(MerchandiseBag::SESSION_KEY));
        $this->postJson(route('shop.bag.add'), ['product_id' => $this->product->id, 'variant_id' => 91, 'quantity' => 1])->assertOk();
        $this->get(route('shop.order', $order->id))->assertOk()->assertSee('Test order');
        $this->assertCount(1, session(MerchandiseBag::SESSION_KEY));
        Http::assertNothingSent();
    }

    public function test_order_receipts_and_payment_writes_require_the_owning_session()
    {
        $quote = $this->quote();
        $this->flushSession();
        $this->get(route('shop.order', $quote['order_id']))->assertNotFound();
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertNotFound();
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $quote['order_id']])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_expired_or_changed_bag_quotes_cannot_create_payments()
    {
        $quote = $this->quote();
        ShopOrder::whereKey($quote['order_id'])->update(['expires_at' => now()->subMinute()]);
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertStatus(422)->assertJson(['refresh_quote' => true]);
        $quote = $this->quote();
        $this->product->update(['variants' => [array_merge($this->product->variants[0], ['retail_price' => '30.00'])]]);
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertStatus(422);
        $this->assertCount(0, $this->gateway->intents);
    }

    public function test_unknown_country_state_and_forged_shipping_method_are_rejected()
    {
        $this->postJson(route('shop.checkout.quote'), $this->address(['country_code' => 'ZZ']))->assertStatus(422);
        $this->postJson(route('shop.checkout.quote'), $this->address(['state_code' => 'ZZ']))->assertStatus(422);
        $this->postJson(route('shop.checkout.quote'), $this->address(['shipping_id' => 'FREE']))->assertStatus(422);
        $this->assertDatabaseCount('shop_orders', 0);
    }

    public function test_local_checkout_offers_and_accepts_states_beyond_the_original_samples_without_printful()
    {
        $countries = collect(app(ShopCheckout::class)->countries());
        $states = $countries->firstWhere('code', 'US')['states'];
        $this->assertCount(51, $states);
        $this->assertSame('New Jersey', collect($states)->firstWhere('code', 'NJ')['name']);
        $this->assertContains('DC', array_column($states, 'code'));
        $this->assertCount(13, $countries->firstWhere('code', 'CA')['states']);
        $html = $this->getJson(route('shop.checkout'))->assertOk()->json('html');
        $this->assertStringContainsString('New Jersey', $html);
        $this->assertStringContainsString('Quebec', $html);

        foreach ([['state_code' => 'NJ', 'zip' => '07302'],
            ['country_code' => 'CA', 'state_code' => 'QC', 'city' => 'Montreal', 'zip' => 'H2Y 1C6'],
            ['country_code' => 'CA', 'state_code' => 'YT', 'city' => 'Whitehorse', 'zip' => 'Y1A 0A1']] as $destination) {
            $quote = $this->quote($destination);
            $this->assertSame($destination['state_code'], ShopOrder::findOrFail($quote['order_id'])->recipient['state_code']);
            $this->assertSame(3300, $quote['total']);
        }
        $this->postJson(route('shop.checkout.quote'), $this->address(['state_code' => 'ON']))->assertStatus(422);
        $this->assertCount(0, $this->gateway->calls);
        Http::assertNothingSent();
    }

    public function test_offline_demo_cannot_use_real_payment_or_fulfillment()
    {
        config(['shop-checkout.mode' => 'demo']);
        $quote = $this->quote();
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $quote['order_id']])->assertOk();
        $this->assertSame('test_complete', ShopOrder::find($quote['order_id'])->status);
        $this->assertCount(0, $this->gateway->calls);
        Http::assertNothingSent();
    }

    public function test_live_order_uses_verified_payment_and_exact_synced_variant_only_once()
    {
        $remote = null; $this->live($remote);
        $order = $this->prepared();
        $this->assertSame(3500, $order->total);
        $this->gateway->succeed($order->stripe_payment_intent);
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertOk();
        app(MerchandiseFulfillment::class)->process($order->id);
        $this->assertSame('submitted', $order->fresh()->status);
        $this->assertSame(1, $order->emails()->where('kind', 'confirmation')->count());
        $this->assertSame(91, $remote['items'][0]['sync_variant_id']);
        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/orders')));
        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/confirm')));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/shipping/rates') && $request['items'][0]['variant_id'] === 4011);
    }

    public function test_lost_printful_confirmation_response_recovers_without_a_second_order_or_charge()
    {
        $remote = null; $this->live($remote, true);
        $order = $this->prepared(); $this->gateway->succeed($order->stripe_payment_intent);
        app(ShopCheckout::class)->paid($order->id);
        app(MerchandiseFulfillment::class)->process($order->id);
        $this->assertNull($order->fresh()->submitted_at);
        app(MerchandiseFulfillment::class)->process($order->id);
        $this->assertNotNull($order->fresh()->submitted_at);
        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/orders')));
        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/confirm')));
    }

    public function test_higher_fulfillment_cost_is_held_for_review_instead_of_confirmed()
    {
        $remote = null; $this->live($remote, false, true);
        $order = $this->prepared(); $this->gateway->succeed($order->stripe_payment_intent);
        app(ShopCheckout::class)->paid($order->id);
        app(MerchandiseFulfillment::class)->process($order->id);
        $this->assertSame('review_required', $order->fresh()->status);
        $this->assertCount(0, Http::recorded(fn ($request) => str_ends_with($request->url(), '/confirm')));
    }

    public function test_mismatched_payment_amount_currency_mode_or_identity_never_fulfills()
    {
        foreach ([['amount' => 1], ['currency' => 'eur'], ['livemode' => true], ['metadata' => ['pianolit_shop_order' => 'other']]] as $change) {
            $order = $this->prepared();
            $this->gateway->succeed($order->stripe_payment_intent);
            $this->gateway->intents[$order->stripe_payment_intent] = array_replace($this->gateway->intents[$order->stripe_payment_intent], $change);
            $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertStatus(422);
            $this->assertNull($order->fresh()->paid_at);
        }
        Http::assertNothingSent();
    }

    public function test_refunded_or_disputed_payments_are_held_before_printful_submission()
    {
        $remote = null; $this->live($remote);
        foreach ([['amount_refunded' => 100], ['disputed' => true]] as $charge) {
            $order = $this->prepared();
            $this->gateway->succeed($order->stripe_payment_intent);
            $this->gateway->intents[$order->stripe_payment_intent]['latest_charge'] = $charge;
            app(ShopCheckout::class)->paid($order->id);
            app(MerchandiseFulfillment::class)->process($order->id);
            $this->assertSame('review_required', $order->fresh()->status);
            $this->assertNull($order->fresh()->submitted_at);
        }
        $this->assertCount(0, Http::recorded(fn ($request) => str_ends_with($request->url(), '/orders') || str_ends_with($request->url(), '/confirm')));
    }

    public function test_order_administration_requires_a_manager_and_cannot_retry_unpaid_orders()
    {
        $order = $this->prepared();
        $order->update(['mode' => 'live']);
        $this->get(route('admin.shop-orders.index'))->assertRedirect();
        $this->actingAs(create(\App\Admin::class, ['role' => 'editor']), 'admin');
        $this->get(route('admin.shop-orders.show', $order->id))->assertForbidden();
        $this->post(route('admin.shop-orders.retry', $order->id))->assertForbidden();
        $this->actingAs(create(\App\Admin::class, ['role' => 'manager']), 'admin');
        $this->get(route('admin.shop-orders.index'))->assertOk()->assertSee($order->id)->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('admin.shop-orders.show', $order->id))->assertOk()->assertSee('123 Test Street')->assertDontSee('Retry fulfillment');
        $this->post(route('admin.shop-orders.retry', $order->id))->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_local_mode_cannot_enable_real_fulfillment_even_with_live_settings()
    {
        $this->app->instance('env', 'local');
        config(['shop-checkout.mode' => 'live', 'shop-checkout.enabled' => true, 'shop-checkout.printful_orders_enabled' => true,
            'shop-checkout.stripe_key' => 'pk_live_review', 'shop-checkout.stripe_secret' => 'sk_live_review']);
        $this->assertSame('demo', app(CheckoutEnvironment::class)->mode());
        $this->assertFalse(app(\App\Services\Shop\Printful::class)->enabled());
        Http::assertNothingSent();
    }

    public function test_local_membership_accounts_also_reject_live_payment_keys()
    {
        $this->app->instance('env', 'local');
        $accounts = app(\App\Billing\Stripe\Accounts::class);
        foreach (['us', 'swiss'] as $account) {
            config(['services.stripe.'.$account.'_key' => 'pk_live_review', 'services.stripe.'.$account.'_secret' => 'sk_live_review']);
            foreach (['key', 'secret'] as $method) {
                try { $accounts->{$method}($account); $this->fail('Local billing must reject live keys.'); }
                catch (\App\Billing\Stripe\BillingException $exception) { $this->assertStringContainsString('Live payments are disabled locally', $exception->getMessage()); }
            }
            config(['services.stripe.'.$account.'_key' => 'pk_test_review', 'services.stripe.'.$account.'_secret' => 'sk_test_review']);
            $this->assertSame('pk_test_review', $accounts->key($account));
            $this->assertSame('sk_test_review', $accounts->secret($account));
        }
        Http::assertNothingSent();
    }

    public function test_local_fixture_variants_without_a_real_catalog_id_stay_in_the_simulated_delivery_path()
    {
        $this->product->update(['variants' => [array_replace($this->product->variants[0], ['variant_id' => 0])]]);
        $this->assertSame(3300, $this->quote()['total']);
        Http::assertNothingSent();
    }

    public function test_signed_webhook_retrieves_payment_and_duplicate_delivery_does_not_repeat_fulfillment()
    {
        config(['shop-checkout.stripe_webhook_secret' => 'whsec_checkout_review']);
        $order = $this->prepared(); $this->gateway->succeed($order->stripe_payment_intent);
        $body = json_encode(['id' => 'evt_checkout', 'type' => 'payment_intent.succeeded', 'livemode' => false,
            'data' => ['object' => ['id' => $order->stripe_payment_intent, 'metadata' => ['pianolit_shop_order' => $order->id]]]]);
        $time = time();
        $signature = 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$body, 'whsec_checkout_review');
        $this->call('POST', route('webhooks.shop-checkout'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->call('POST', route('webhooks.shop-checkout'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertSame('test_complete', $order->fresh()->status);
        $this->call('POST', route('webhooks.shop-checkout'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'bad', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        Http::assertNothingSent();
    }

    public function test_checkout_posts_keep_same_host_csrf_and_session_locking()
    {
        foreach (['shop.checkout.quote', 'shop.checkout.wallet-quote', 'shop.checkout.prepare', 'shop.checkout.complete'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertSame('shop.localhost', $route->getDomain());
            $this->assertContains('web', $route->middleware());
            $this->assertGreaterThan(0, $route->locksFor());
        }
    }
}
