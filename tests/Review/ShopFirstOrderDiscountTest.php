<?php

namespace Tests\Review;

use App\{EmailList, User, Merchandise\Purchase, Merchandise\MerchandiseProduct, Shop\eBook, Shop\ShopOrder};
use App\Services\Shop\{CheckoutStripe, FirstOrderDiscount};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Event, Http, Mail};
use Tests\Support\FakeCheckoutStripe;

class ShopFirstOrderDiscountTest extends ReviewTestCase
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
        $this->withExceptionHandling(); Event::fake([\App\Events\PurchaseMade::class]);
        config(['shop-checkout.mode' => 'sandbox', 'shop-checkout.stripe_key' => 'pk_test_review',
            'shop-checkout.stripe_secret' => 'sk_test_review', 'services.printful.store_id' => 12345]);
        $this->gateway = new FakeCheckoutStripe;
        $this->app->instance(CheckoutStripe::class, $this->gateway);
        $this->product = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 8,
            'name' => 'Piano T-shirt', 'variants' => [['id' => 91, 'variant_id' => 4011, 'synced' => true,
                'size' => 'M', 'color' => 'Mint', 'retail_price' => '28.00', 'currency' => 'USD']]]);
        $this->add();
    }

    private function add(int $quantity = 1): void
    {
        $this->postJson(route('shop.bag.add'), ['product_id' => $this->product->id, 'variant_id' => 91, 'quantity' => $quantity])->assertOk();
    }

    private function signup(string $email = 'welcome@example.test')
    {
        return $this->from(route('shop.home'))->post('http://shop.localhost/newsletter/subscribe', [
            'email' => $email, 'form_id' => 'shop-newsletter-subscription-form', 'started_at' => now()->subSeconds(10)->toDateTimeString(),
        ])->assertRedirect(route('shop.home'))->assertSessionHas('status');
    }

    private function quote(array $changes = []): array
    {
        $response = $this->postJson(route('shop.checkout.quote'), $changes + [
            'email' => 'welcome@example.test', 'name' => 'Test Customer', 'address1' => '123 Test Street',
            'city' => 'New York', 'state_code' => 'NY', 'country_code' => 'US', 'zip' => '10001',
        ]);
        $this->assertSame(200, $response->status(), $response->getContent());
        return $response->json();
    }

    private function prepare(array $quote)
    {
        return $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']]);
    }

    public function test_signup_grants_only_the_newsletter_offer_and_prefills_guest_checkout_email()
    {
        $this->signup('Welcome@Example.test');
        $this->assertDatabaseCount('shop_first_order_discounts', 1);
        $this->assertSame([EmailList::NEWSLETTER], DB::table('email_lists')->pluck('name')->all());
        $this->assertDatabaseCount('email_list_subscription', 1);
        $row = DB::table('shop_first_order_discounts')->first();
        $this->assertSame(app(FirstOrderDiscount::class)->emailHash('welcome@example.test'), $row->email_hash);
        $this->assertNull($row->user_id);
        $this->assertNull($row->redeemed_order_id);
        $this->assertStringContainsString('value="welcome@example.test"', $this->getJson(route('shop.checkout'))->assertOk()->json('html'));
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_other_newsletter_forms_do_not_implicitly_grant_a_shop_promotion()
    {
        $this->from('http://blog.localhost')->post('http://blog.localhost/newsletter/subscribe', [
            'email' => 'reader@example.test', 'started_at' => now()->subSeconds(10)->toDateTimeString(),
        ])->assertRedirect('http://blog.localhost');
        $this->assertDatabaseCount('shop_first_order_discounts', 0);
        $this->assertSame(0, $this->quote(['email' => 'reader@example.test', 'discount' => 2800, 'first_order_discount_id' => 1])['discount']);
    }

    public function test_unconfigured_checkout_keeps_the_newsletter_signup_available_without_advertising_an_unusable_offer()
    {
        config(['shop-checkout.stripe_key' => null, 'shop-checkout.stripe_secret' => null]);
        $this->get(route('shop.home'))->assertOk()->assertSee('Keep me in the loop')->assertDontSee('10% off your first order');
        $this->signup();
        $this->assertDatabaseCount('shop_first_order_discounts', 1);
        $this->assertStringContainsString('saved for when Shop checkout is available', session('status'));
    }

    public function test_discount_changes_the_quoted_and_charged_amount_but_not_shipping_or_fulfillment_cost()
    {
        $this->signup();
        $quote = $this->quote(['email' => 'WELCOME@example.test', 'subtotal' => 1]);
        $this->assertSame(280, $quote['discount']);
        $this->assertSame('First-order discount (10%)', $quote['discount_label']);
        $this->assertSame(2520, $quote['subtotal']);
        $this->assertSame(500, $quote['shipping']);
        $this->assertSame(3020, $quote['total']);
        $order = ShopOrder::findOrFail($quote['order_id']);
        $this->assertSame(2180, $order->fulfillment_cost);
        $this->assertSame(2800, $order->items[0]['unit_price']);
        $this->assertSame(2520, $order->items[0]['line_total']);
        $this->prepare($quote)->assertOk();
        $this->assertSame(3020, $this->gateway->intents[$order->fresh()->stripe_payment_intent]['amount']);
        $this->assertArrayNotHasKey('checkout_email_hash', $order->toArray());
        $this->assertArrayNotHasKey('first_order_discount_id', $order->toArray());
        $this->assertSame(0, $this->quote(['email' => 'someone-else@example.test'])['discount']);
        Http::assertNothingSent();
    }

    public function test_rounding_takes_exactly_ten_percent_of_the_whole_subtotal()
    {
        $this->product->update(['variants' => [array_replace($this->product->variants[0], ['retail_price' => '19.99'])]]);
        $this->add(2); $this->signup();
        $quote = $this->quote();
        $this->assertSame(600, $quote['discount']);
        $this->assertSame(5397, $quote['subtotal']);
        $this->assertSame(5397, array_sum(array_column(ShopOrder::find($quote['order_id'])->items, 'line_total')));
    }

    public function test_paid_redemption_is_idempotent_and_repeat_signups_do_not_restore_it()
    {
        $this->signup(); $quote = $this->quote(); $this->prepare($quote)->assertOk();
        $order = ShopOrder::findOrFail($quote['order_id']); $this->gateway->succeed($order->stripe_payment_intent);
        for ($i = 0; $i < 2; $i++) $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertOk();
        $this->assertSame($order->id, DB::table('shop_first_order_discounts')->value('redeemed_order_id'));
        $this->get(route('shop.order', $order->id))->assertOk()->assertSee('First-order discount (10%)')->assertSee('2.80');
        $mail = new \App\Mail\Shop\MerchandiseOrderConfirmed($order->fresh(), 'https://example.test/order');
        $mail->build();
        $markdown = clone app(\Illuminate\Mail\Markdown::class);
        $text = (string) $markdown->renderText($mail->markdown, $mail->buildViewData());
        $this->assertStringContainsString('First-order discount (10%): −$2.80 USD', $text);
        $this->assertStringContainsString('Products: $28.00 USD', $text);
        $this->travel(1)->minutes(); $this->signup(); $this->add();
        $this->assertSame(0, $this->quote()['discount']);
        $this->assertDatabaseCount('shop_first_order_discounts', 1);
    }

    public function test_existing_guest_paid_orders_and_account_purchases_are_ineligible_but_free_downloads_are_not()
    {
        $old = $this->quote(); ShopOrder::whereKey($old['order_id'])->update(['paid_at' => now()]);
        $this->signup();
        $this->assertSame(0, $this->quote()['discount']);
        $user = Model::withoutEvents(fn () => create(User::class, ['email' => 'account@example.test']));
        $product = Model::withoutEvents(fn () => create(eBook::class));
        $purchase = Purchase::withoutEvents(fn () => $user->purchases()->create(['item_type' => eBook::class, 'item_id' => $product->id, 'cost' => null]));
        app(FirstOrderDiscount::class)->grant($user->email);
        $this->assertSame(280, $this->quote(['email' => $user->email])['discount']);
        $purchase->update(['cost' => 12]);
        $this->assertSame(0, $this->quote(['email' => strtoupper($user->email)])['discount']);
    }

    public function test_two_quotes_cannot_reserve_two_discounted_payments()
    {
        $this->signup(); $first = $this->quote();
        $this->prepare($first)->assertOk();
        $owner = session('shop_checkout_owner');
        $this->flushSession(); $this->add(); $second = $this->quote();
        $this->prepare($second)->assertStatus(422)->assertJsonFragment(['message' => 'Your welcome discount is already being used in another checkout. Please finish that order first.']);
        $this->assertCount(1, $this->gateway->intents);
        $this->withSession(['shop_checkout_owner' => $owner]);
        $this->prepare($first)->assertOk();
        $this->assertCount(1, $this->gateway->intents);
    }

    public function test_abandoned_expired_payment_is_canceled_before_a_replacement_can_use_the_offer()
    {
        $this->signup(); $first = $this->quote(); $this->prepare($first)->assertOk();
        $order = ShopOrder::findOrFail($first['order_id']);
        $this->travel(16)->minutes();
        $replacement = $this->quote(); $this->prepare($replacement)->assertOk();
        $this->assertSame('canceled', $this->gateway->intents[$order->stripe_payment_intent]['status']);
        $this->assertSame('abandoned', $order->fresh()->status);
        $this->assertSame($replacement['order_id'], DB::table('shop_first_order_discounts')->value('reserved_order_id'));
        $this->prepare($first)->assertStatus(422)->assertJsonPath('refresh_quote', true);
    }

    public function test_succeeded_but_unconfirmed_payment_cannot_be_replaced_by_another_discount()
    {
        $this->signup(); $first = $this->quote(); $this->prepare($first)->assertOk();
        $order = ShopOrder::findOrFail($first['order_id']); $this->gateway->succeed($order->stripe_payment_intent);
        $this->travel(16)->minutes(); $replacement = $this->quote();
        $this->prepare($replacement)->assertStatus(422);
        $this->assertCount(1, $this->gateway->intents);
        $this->assertSame($order->id, DB::table('shop_first_order_discounts')->value('reserved_order_id'));
    }

    public function test_lost_stripe_creation_response_keeps_a_durable_reservation_and_recovers_before_replacement()
    {
        $this->signup(); $first = $this->quote();
        $this->gateway->lostCreateResponse = true;
        $this->prepare($first)->assertStatus(422);
        $this->assertNull(ShopOrder::find($first['order_id'])->stripe_payment_intent);
        $this->assertSame($first['order_id'], DB::table('shop_first_order_discounts')->value('reserved_order_id'));
        $this->flushSession(); $this->add();
        $this->prepare($this->quote())->assertStatus(422);
        $this->travel(16)->minutes(); $this->prepare($this->quote())->assertOk();
        $this->assertCount(2, $this->gateway->intents);
        $this->assertSame('canceled', array_values($this->gateway->intents)[0]['status']);
    }

    public function test_correcting_an_address_replaces_only_the_same_browsers_unpaid_payment()
    {
        $this->signup(); $first = $this->quote(); $this->prepare($first)->assertOk();
        $order = ShopOrder::findOrFail($first['order_id']);
        $replacement = $this->quote(['address1' => '124 Test Street']); $this->prepare($replacement)->assertOk();
        $this->assertSame('canceled', $this->gateway->intents[$order->stripe_payment_intent]['status']);
        $this->assertSame('abandoned', $order->fresh()->status);
        $this->assertSame($replacement['order_id'], DB::table('shop_first_order_discounts')->value('reserved_order_id'));
    }

    public function test_offline_demo_checkout_redeems_the_discount_without_any_provider_calls()
    {
        config(['shop-checkout.mode' => 'demo']);
        $this->signup(); $quote = $this->quote();
        $this->prepare($quote)->assertOk()->assertJsonPath('demo', true);
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $quote['order_id']])->assertOk();
        $this->assertSame(3020, ShopOrder::findOrFail($quote['order_id'])->total);
        $this->assertSame($quote['order_id'], DB::table('shop_first_order_discounts')->value('redeemed_order_id'));
        $this->assertCount(0, $this->gateway->calls); Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_live_tax_calculation_uses_discounted_lines_and_the_original_delivery_amount()
    {
        // Production behavior with entirely fake keys and HTTP. No live payment or order.
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->app->instance('env', 'production');
        config(['shop-checkout.mode' => 'live', 'shop-checkout.enabled' => true, 'shop-checkout.printful_orders_enabled' => true,
            'shop-checkout.stripe_key' => 'pk_live_review', 'shop-checkout.stripe_secret' => 'sk_live_review',
            'shop-checkout.stripe_webhook_secret' => 'whsec_review', 'services.printful.token' => 'review-only', 'services.printful.token_file' => null]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/countries')) return Http::response(['code' => 200, 'result' => [['code' => 'US', 'name' => 'United States']]]);
            if (str_ends_with($request->url(), '/shipping/rates')) return Http::response(['code' => 200, 'result' => [['id' => 'STANDARD', 'name' => 'Standard', 'rate' => '5.00', 'currency' => 'USD']]]);
            if (str_ends_with($request->url(), '/orders/estimate-costs')) return Http::response(['code' => 200, 'result' => ['costs' => ['currency' => 'USD', 'shipping' => '5.00', 'total' => '20.00']]]);
            throw new \RuntimeException('Unexpected review HTTP request.');
        });
        app(FirstOrderDiscount::class)->grant('welcome@example.test');
        $quote = $this->quote();
        $tax = collect($this->gateway->calls)->firstWhere('resource', 'tax.calculations');
        $this->assertSame(2520, $tax['parameters']['line_items'][0]['amount']);
        $this->assertSame(500, $tax['parameters']['shipping_cost']['amount']);
        $this->assertSame(3220, $quote['total']);
        $this->app->instance('env', 'testing');
    }

    public function test_digital_checkout_uses_account_email_and_the_same_one_use_offer_without_stacking_coupons()
    {
        $user = Model::withoutEvents(fn () => create(User::class, ['email' => 'welcome@example.test']));
        $this->actingAs($user, 'web'); $this->signup();
        $book = Model::withoutEvents(fn () => create(eBook::class, ['price' => 10, 'discount' => 0, 'published_at' => now()]));
        $data = ['kind' => 'digital', 'product_type' => 'ebooks', 'product_id' => $book->id, 'email' => 'untrusted@example.test'];
        $coupon = $this->quote($data + ['coupon' => 'HALF']);
        $this->assertSame(500, $coupon['total']);
        $this->assertNull(ShopOrder::find($coupon['order_id'])->first_order_discount_id);
        $quote = $this->quote($data);
        $this->assertSame(100, $quote['discount']); $this->assertSame(900, $quote['total']);
        $this->prepare($quote)->assertOk();
        $order = ShopOrder::findOrFail($quote['order_id']); $this->gateway->succeed($order->stripe_payment_intent);
        $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertOk();
        $this->assertSame(9.0, (float) $user->purchasesOf($book)->firstOrFail()->cost);
        $this->assertSame(0, $this->quote()['discount']);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_legacy_guest_order_email_hashes_are_backfilled_without_changing_encrypted_recipient_or_ownership()
    {
        $quote = $this->quote(); $id = $quote['order_id'];
        $before = DB::table('shop_orders')->where('id', $id)->first();
        $migration = new \AddShopFirstOrderDiscounts;
        $migration->down(); $migration->up();
        $after = DB::table('shop_orders')->where('id', $id)->first();
        $this->assertSame($before->recipient, $after->recipient);
        $this->assertNull($after->user_id);
        $this->assertSame(app(FirstOrderDiscount::class)->emailHash('welcome@example.test'), $after->checkout_email_hash);
    }
}
