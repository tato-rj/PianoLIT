<?php

namespace Tests\Review;

use App\Shop\{eBook, eScore, ShopOrder};
use App\Services\Shop\{CheckoutStripe, MerchandiseFulfillment};
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Event, Http, Mail};
use Tests\Support\FakeCheckoutStripe;

class DigitalCheckoutTest extends ReviewTestCase
{
    private $user, $gateway, $ebook, $score;

    public function setUp(): void
    {
        parent::setUp(); $this->withExceptionHandling();
        Event::fake();
        config(['shop-checkout.mode' => 'sandbox', 'shop-checkout.stripe_key' => 'pk_test_review', 'shop-checkout.stripe_secret' => 'sk_test_review']);
        $this->gateway = new FakeCheckoutStripe; $this->app->instance(CheckoutStripe::class, $this->gateway);
        $this->user = Model::withoutEvents(fn () => create(User::class)->setAppends(['full_name']));
        $this->actingAs($this->user, 'web');
        $this->ebook = Model::withoutEvents(fn () => create(eBook::class, ['title' => 'Piano practice', 'price' => 10, 'discount' => 0, 'published_at' => now()]));
        $this->score = Model::withoutEvents(fn () => create(eScore::class, ['title' => 'Piano solos', 'price' => 5, 'discount' => 0, 'published_at' => now()]));
    }

    private function quote(string $type = 'ebooks', array $changes = []): array
    {
        return $this->postJson(route('shop.checkout.quote'), $changes + ['kind' => 'digital', 'product_type' => $type,
            'product_id' => $type === 'ebooks' ? $this->ebook->id : $this->score->id,
            'email' => 'untrusted@example.test', 'name' => 'Test Customer', 'address1' => '123 Test Street',
            'city' => 'New York', 'state_code' => 'NY', 'country_code' => 'US', 'zip' => '10001'])->assertOk()->json();
    }

    public function test_both_paid_digital_products_render_the_shared_modal_without_a_printful_request()
    {
        foreach ([$this->ebook, $this->score] as $product) {
            $html = $this->getJson($product->checkoutRoute())->assertOk()->json('html');
            $this->assertStringContainsString('data-checkout-modal', $html);
            $this->assertStringContainsString('data-kind="digital"', $html);
            $this->assertStringContainsString('Billing details', $html);
            $this->assertStringContainsString('data-checkout-wallets', $html);
            $this->assertStringContainsString('Your total updates automatically', $html);
            $this->assertStringNotContainsString('data-delivery-quote', $html);
            $this->assertStringNotContainsString('Delivery method', $html);
        }
        Http::assertNothingSent(); $this->assertCount(0, $this->gateway->calls);
    }

    public function test_verified_payment_grants_only_the_owned_digital_product_once_and_never_sends_it_to_printful()
    {
        foreach (['ebooks', 'escores'] as $type) {
            $quote = $this->quote($type);
            $order = ShopOrder::find($quote['order_id']);
            $this->assertSame('digital', $order->kind);
            $this->assertSame(0, $order->shipping);
            $this->assertSame($this->user->email, $order->recipient['email']);
            $this->postJson(route('shop.checkout.prepare'), ['order_id' => $order->id])->assertOk();
            $order->refresh(); $this->gateway->succeed($order->stripe_payment_intent);
            $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertOk();
            $this->postJson(route('shop.checkout.complete'), ['order_id' => $order->id])->assertOk();
            $this->get(route('shop.order', $order->id))->assertOk()->assertSee('Open your download');
            app(MerchandiseFulfillment::class)->process($order->id);
        }
        $this->assertDatabaseCount('purchases', 2);
        $this->assertSame(1, $this->user->purchasesOf($this->ebook)->count());
        $this->assertSame(1, $this->user->purchasesOf($this->score)->count());
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_recalculating_digital_checkout_cancels_only_an_unpaid_intent_and_preserves_coupon_pricing()
    {
        $quote = $this->quote('ebooks', ['coupon' => 'HALF']);
        $this->assertSame(500, $quote['total']);
        $this->assertSame(500, $quote['discount']);
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertOk();
        $order = ShopOrder::find($quote['order_id']);
        $replacement = $this->quote('ebooks', ['coupon' => 'HALF']);
        $this->assertNotSame($quote['order_id'], $replacement['order_id']);
        $this->assertSame('abandoned', $order->fresh()->status);
        $this->assertSame('canceled', $this->gateway->intents[$order->stripe_payment_intent]['status']);
        $this->assertSame(1, ShopOrder::whereNotNull('active_key')->count());
    }

    public function test_a_full_coupon_grants_the_edition_without_creating_a_zero_value_payment()
    {
        $quote = $this->quote('ebooks', ['coupon' => 'FREE']);
        $this->assertSame(0, $quote['total']);
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertOk()->assertJson(['paid' => true]);
        $this->assertDatabaseCount('purchases', 1); $this->assertCount(0, $this->gateway->intents);
        Http::assertNothingSent();
    }

    public function test_guest_or_another_account_cannot_pay_for_or_receive_a_digital_order()
    {
        $quote = $this->quote();
        $other = Model::withoutEvents(fn () => create(User::class)->setAppends(['full_name']));
        $this->actingAs($other, 'web');
        $this->postJson(route('shop.checkout.prepare'), ['order_id' => $quote['order_id']])->assertNotFound();
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_background_purchase_confirmation_uses_the_purchase_owner_instead_of_a_browser_login()
    {
        $purchase = \App\Merchandise\Purchase::withoutEvents(fn () => $this->user->purchases()->create([
            'item_type' => get_class($this->ebook), 'item_id' => $this->ebook->id, 'cost' => 10,
        ]));
        auth('web')->logout();
        (new \App\Listeners\NewPurchaseListener)->handle(new \App\Events\PurchaseMade($purchase));
        Mail::assertSent(\App\Mail\Shop\ConfirmPurchase::class, fn ($mail) => $mail->hasTo($this->user->email) && $mail->user->id === $this->user->id);
    }
}
