<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Mail\{AutomaticEmailSettings, Shop\MerchandiseOrderConfirmed, Shop\MerchandiseOrderShipped, Shop\MerchandiseOrderDelivered};
use App\Services\Shop\{OrderEmails, OrderTracking, PrintfulWebhooks, ShipmentUpdates};
use App\Shop\{ShopOrder, ShopOrderEmail, ShopShipment};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail};
use Illuminate\Support\Str;

class ShopOrderTrackingTest extends ReviewTestCase
{
    private $order, $remote, $details, $keysPath, $providerFails = false;
    private $secret = 'a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1';

    public function setUp(): void
    {
        parent::setUp(); $this->withExceptionHandling();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->app->instance('env', 'production');
        $this->keysPath = tempnam(sys_get_temp_dir(), 'pianolit-webhook-review-');
        file_put_contents($this->keysPath, json_encode(['store_id' => '12345', 'public_key' => 'review-public-key', 'secret_key' => $this->secret]));
        chmod($this->keysPath, 0600);
        config(['services.printful.enabled' => true, 'services.printful.token' => 'review-only', 'services.printful.token_file' => null,
            'services.printful.store_id' => '12345', 'shop-checkout.printful_webhook_file' => $this->keysPath, 'shop-checkout.enabled' => false]);
        $this->order = $this->order();
        $this->remote = ['id' => $this->order->printful_order_id, 'external_id' => $this->order->printful_external_id,
            'status' => 'partial', 'items' => [['id' => 501, 'sync_variant_id' => 91]],
            'shipments' => [$this->package(201)]];
        $this->details = [$this->detail(201)];
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) {
            $this->assertSame('GET', $request->method());
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer review-only'));
            $this->assertTrue($request->hasHeader('X-PF-Store-Id', '12345'));
            if ($this->providerFails) return Http::response([], 503);
            if (str_contains($request->url(), '/orders/@')) return Http::response(['code' => 200, 'result' => $this->remote]);
            if (str_contains($request->url(), '/v2/orders/')) return Http::response(['data' => $this->details, 'paging' => ['total' => count($this->details)]]);
            throw new \RuntimeException('Unexpected review request.');
        });
    }

    protected function tearDown(): void
    {
        if (is_file($this->keysPath)) unlink($this->keysPath);
        $this->app->instance('env', 'testing');
        parent::tearDown();
    }

    private function order(array $changes = []): ShopOrder
    {
        $id = (string) Str::uuid();
        return ShopOrder::create($changes + ['id' => $id, 'kind' => 'merchandise', 'mode' => 'live', 'owner_hash' => hash('sha256', 'review'),
            'printful_store_id' => 12345, 'printful_order_id' => (string) (100 + ShopOrder::count()),
            'printful_external_id' => substr(hash('sha256', $id), 0, 32), 'bag_hash' => hash('sha256', 'bag'), 'status' => 'submitted',
            'items' => [['product_id' => 1, 'sync_variant_id' => 91, 'quantity' => 2, 'unit_price' => 2800, 'name' => 'Piano T-shirt', 'option' => 'Mint / M']],
            'recipient' => ['name' => 'Jamie Review', 'email' => 'private-review@example.test', 'address1' => '123 Private Street', 'city' => 'New York', 'country_code' => 'US'],
            'currency' => 'usd', 'subtotal' => 5600, 'shipping' => 500, 'tax' => 0, 'total' => 6100, 'fulfillment_cost' => 3000,
            'shipping_id' => 'STANDARD', 'shipping_name' => 'Standard delivery', 'expires_at' => now()->addMinutes(15), 'paid_at' => now(), 'submitted_at' => now()]);
    }

    private function package(int $id): array
    {
        return ['id' => $id, 'carrier' => 'USPS', 'service' => 'Priority Mail', 'tracking_number' => 'REVIEW'.$id,
            'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=REVIEW'.$id,
            'shipped_at' => now()->timestamp, 'items' => [['item_id' => 501, 'quantity' => 1]]];
    }

    private function detail(int $id, array $changes = []): array
    {
        return $changes + ['id' => $id, 'order_id' => $this->order->printful_order_id, 'order_external_id' => $this->order->printful_external_id,
            'carrier' => 'USPS', 'service' => 'Priority Mail', 'shipment_status' => 'shipped', 'delivery_status' => 'in_transit',
            'estimated_delivery' => ['from_date' => '2026-11-01', 'to_date' => '2026-11-03'],
            'tracking_events' => [['description' => 'Accepted by carrier', 'triggered_at' => now()->toIso8601String()]]];
    }

    private function event(string $type = 'shipment_sent', int $shipment = 201): array
    {
        return ['type' => $type, 'store_id' => 12345, 'occurred_at' => '2026-10-28T16:00:00Z', 'retries' => 0,
            'data' => ['order' => ['id' => $this->order->printful_order_id, 'external_id' => $this->order->printful_external_id], 'shipment' => ['id' => $shipment]]];
    }

    private function webhook(?array $event = null, array $headers = [])
    {
        $body = json_encode($event ?? $this->event());
        return $this->call('POST', route('webhooks.printful'), [], [], [], $headers + ['CONTENT_TYPE' => 'application/json',
            'HTTP_X_PF_WEBHOOK_PUBLIC_KEY' => 'review-public-key', 'HTTP_X_PF_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $body, hex2bin($this->secret))], $body);
    }

    public function test_signed_webhook_fetches_authoritative_details_and_duplicate_retries_do_not_duplicate_emails()
    {
        $event = $this->event(); $event['data']['shipment']['tracking_url'] = 'https://untrusted.example/ignore';
        $this->webhook($event)->assertOk();
        $event['retries'] = 3; $this->webhook($event)->assertOk();
        $this->assertDatabaseCount('shop_shipments', 1); $this->assertDatabaseCount('shop_order_emails', 2);
        $this->assertDatabaseCount('printful_webhook_receipts', 1);
        $shipment = ShopShipment::first();
        $this->assertSame('REVIEW201', $shipment->tracking_number);
        $this->assertStringContainsString('tools.usps.com', $shipment->tracking_url);
        $this->assertSame('2026-11-01', $shipment->estimated_from->toDateString());
        $this->assertSame('Accepted by carrier', $shipment->events[0]['description']);
        $this->assertCount(2, Http::recorded());
        Mail::assertNothingSent();
    }

    public function test_signature_public_key_and_store_are_verified_before_any_provider_access()
    {
        $this->webhook(null, ['HTTP_X_PF_WEBHOOK_SIGNATURE' => 'invalid'])->assertStatus(400);
        $this->webhook(null, ['HTTP_X_PF_WEBHOOK_PUBLIC_KEY' => 'other-key'])->assertStatus(400);
        $body = json_encode($this->event());
        $this->webhook(null, ['HTTP_X_PF_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $body, $this->secret)])->assertStatus(400);
        $event = $this->event(); $event['store_id'] = 999; $this->webhook($event)->assertStatus(400);
        $this->assertDatabaseCount('shop_shipments', 0); Http::assertNothingSent();
    }

    public function test_raw_body_tampering_and_malformed_signed_events_return_errors_without_a_session_redirect()
    {
        $body = json_encode($this->event());
        $this->call('POST', route('webhooks.printful'), [], [], [], ['CONTENT_TYPE' => 'application/json',
            'HTTP_X_PF_WEBHOOK_PUBLIC_KEY' => 'review-public-key', 'HTTP_X_PF_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $body, hex2bin($this->secret))], $body."\n")->assertStatus(400);
        $event = $this->event(); $event['data']['order'] = null; $this->webhook($event)->assertStatus(400);
        $event = $this->event(); $event['store_id'] = []; $this->webhook($event)->assertStatus(400);
        Http::assertNothingSent(); $this->assertDatabaseCount('printful_webhook_receipts', 0);
    }

    public function test_unsigned_legacy_events_and_unrelated_or_unpaid_orders_never_trigger_customer_updates()
    {
        $this->postJson(route('webhooks.printful'), ['type' => 'package_shipped'])->assertStatus(400);
        $event = $this->event(); $event['data']['order']['external_id'] = str_repeat('9', 32); $this->webhook($event)->assertOk();
        $this->order->update(['paid_at' => null]); $this->webhook()->assertOk();
        Http::assertNothingSent(); $this->assertDatabaseCount('shop_order_emails', 0);
    }

    public function test_provider_failure_is_retried_and_mismatched_provider_or_missing_shipment_cannot_be_acknowledged()
    {
        $this->providerFails = true; $this->webhook()->assertStatus(503);
        $this->assertDatabaseCount('printful_webhook_receipts', 0);
        $this->providerFails = false;
        $external = $this->remote['external_id']; $this->remote['external_id'] = str_repeat('0', 32);
        $this->webhook()->assertStatus(503); $this->assertDatabaseCount('shop_shipments', 0);
        $this->remote['external_id'] = $external; $this->webhook($this->event('shipment_sent', 999))->assertStatus(503);
        $this->webhook()->assertOk(); $this->assertDatabaseCount('shop_shipments', 1);
    }

    public function test_split_packages_each_get_one_email_and_delivery_updates_cannot_regress_or_resend()
    {
        $this->webhook()->assertOk();
        $this->remote['shipments'][] = $this->package(202); $this->details[] = $this->detail(202); $this->remote['status'] = 'fulfilled';
        $this->webhook($this->event('shipment_sent', 202))->assertOk();
        $this->assertDatabaseCount('shop_shipments', 2); $this->assertDatabaseCount('shop_order_emails', 3);
        $this->webhook($this->event('shipment_delivered'))->assertOk();
        $this->assertSame(0, $this->order->emails()->where('kind', 'delivery')->count());
        $event = $this->event(); $event['occurred_at'] = '2026-10-28T17:00:00Z'; $this->webhook($event)->assertOk();
        $this->assertSame('delivered', ShopShipment::where('printful_id', '201')->first()->status);
        $this->webhook($this->event('shipment_delivered', 202))->assertOk();
        $this->assertSame('Delivered', app(OrderTracking::class)->status($this->order->fresh()));
        $this->assertDatabaseCount('shop_order_emails', 4);
        $this->webhook($this->event('shipment_delivered', 202))->assertOk();
        $this->assertSame(1, $this->order->emails()->where('kind', 'delivery')->count());
    }

    public function test_polling_recovers_missing_events_even_with_new_checkout_and_order_submission_disabled()
    {
        config(['shop-checkout.enabled' => false, 'shop-checkout.printful_orders_enabled' => false]);
        $this->artisan('shop:sync-shipments')->assertExitCode(0);
        $this->assertDatabaseCount('shop_shipments', 1); $this->assertDatabaseCount('shop_order_emails', 2);
        $this->assertCount(2, Http::recorded());
    }

    public function test_confirmed_and_shipping_emails_are_sent_once_and_follow_independent_admin_switches()
    {
        $this->webhook()->assertOk();
        $service = app(OrderEmails::class); $service->deliverPending(); $service->deliverPending();
        Mail::assertSent(MerchandiseOrderConfirmed::class, 1); Mail::assertSent(MerchandiseOrderShipped::class, 1);
        Mail::assertSent(MerchandiseOrderConfirmed::class, fn ($mail) => $mail->hasTo('private-review@example.test'));
        $this->assertSame(['sent', 'sent'], ShopOrderEmail::orderBy('id')->pluck('status')->all());
        $other = $this->order(); app(OrderEmails::class)->confirmation($other);
        app(AutomaticEmailSettings::class)->setEnabled('merchandise-confirmation', false, null);
        $service->deliverPending();
        $this->assertSame('suppressed', $other->emails()->first()->status);
        Mail::assertSent(MerchandiseOrderConfirmed::class, 1);
    }

    public function test_arrival_email_waits_for_earlier_messages_and_sends_only_once_to_the_order_customer()
    {
        $this->remote['status'] = 'fulfilled';
        $this->webhook($this->event('shipment_delivered'))->assertOk();
        $email = $this->order->emails()->where('kind', 'delivery')->sole();
        $service = app(OrderEmails::class);
        $service->deliver($email->id);
        $this->assertSame('pending', $email->fresh()->status);
        Mail::assertNothingSent();
        $service->deliverPending();
        Mail::assertSent(MerchandiseOrderConfirmed::class, 1);
        Mail::assertSent(MerchandiseOrderShipped::class, 1);
        Mail::assertSent(MerchandiseOrderDelivered::class, 1);
        Mail::assertSent(MerchandiseOrderDelivered::class, fn ($mail) => $mail->hasTo('private-review@example.test') && $mail->build()->subject === 'Your order arrived!');
        $this->assertSame(['sent', 'sent', 'sent'], $this->order->emails()->pluck('status')->all());
        $event = $this->event('shipment_delivered'); $event['occurred_at'] = '2026-10-28T18:00:00Z';
        $this->webhook($event)->assertOk();
        $service->delivered($this->order->fresh('shipments'));
        $service->deliverPending();
        $this->assertSame(1, $this->order->emails()->where('kind', 'delivery')->count());
        Mail::assertSent(MerchandiseOrderDelivered::class, 1);
    }

    public function test_arrival_sending_switch_is_independent_and_suppressed_messages_are_not_backfilled()
    {
        $settings = app(AutomaticEmailSettings::class);
        $settings->setEnabled('merchandise-delivered', false, null);
        $this->remote['status'] = 'fulfilled';
        $this->webhook($this->event('shipment_delivered'))->assertOk();
        app(OrderEmails::class)->deliverPending();
        Mail::assertSent(MerchandiseOrderConfirmed::class, 1); Mail::assertSent(MerchandiseOrderShipped::class, 1);
        Mail::assertNotSent(MerchandiseOrderDelivered::class);
        $this->assertSame('suppressed', $this->order->emails()->where('kind', 'delivery')->sole()->status);
        $this->assertSame('Delivered', app(OrderTracking::class)->status($this->order->fresh()));
        $settings->setEnabled('merchandise-delivered', true, null);
        $this->webhook($this->event('shipment_delivered'))->assertOk();
        app(OrderEmails::class)->deliverPending();
        Mail::assertNotSent(MerchandiseOrderDelivered::class);
    }

    public function test_polling_recovers_arrival_after_status_lag_without_emailing_historical_delivered_orders()
    {
        // Delivery can precede the provider's final order-status update.
        $this->webhook($this->event('shipment_delivered'))->assertOk();
        $this->assertSame(0, $this->order->emails()->where('kind', 'delivery')->count());
        $this->remote['status'] = 'fulfilled';
        $this->details[0]['delivery_status'] = 'delivered';
        $this->details[0]['delivered_at'] = '2026-10-28T18:00:00Z';
        app(ShipmentUpdates::class)->refresh($this->order->fresh());
        app(ShipmentUpdates::class)->refresh($this->order->fresh());
        $this->assertSame(1, $this->order->emails()->where('kind', 'delivery')->count());
        // Simulate an order already delivered before this email type existed.
        $this->order->emails()->where('kind', 'delivery')->delete();
        app(ShipmentUpdates::class)->refresh($this->order->fresh());
        $this->assertSame(0, $this->order->emails()->where('kind', 'delivery')->count());
        Mail::assertNothingSent();
    }

    public function test_ambiguous_email_delivery_is_held_instead_of_automatically_duplicated()
    {
        app(OrderEmails::class)->confirmation($this->order);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated delivery disconnect'));
        $service = app(OrderEmails::class); $service->deliverPending(); $service->deliverPending();
        $this->assertSame('uncertain', ShopOrderEmail::first()->status);
        $this->assertSame('RuntimeException', ShopOrderEmail::first()->last_error);
    }

    public function test_private_guest_tracking_link_is_read_only_and_hides_contact_payment_and_address_data()
    {
        $this->webhook()->assertOk(); $url = app(OrderTracking::class)->url($this->order);
        $page = $this->get($url)->assertOk()->assertSee('REVIEW201')->assertSee('USPS')->assertSee('Track this package')
            ->assertDontSee('123 Private Street')->assertDontSee('private-review@example.test')->assertDontSee('Jamie Review')
            ->assertDontSee('googletagmanager.com')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('shop.order.track', $this->order->id))->assertForbidden();
        $this->get($url.'&unexpected=1')->assertForbidden();
        $this->get(str_replace($this->order->id, (string) Str::uuid(), $url))->assertForbidden();
        $this->assertSame('no-store, private', $page->headers->get('Cache-Control'));
    }

    public function test_accounts_only_list_their_own_paid_merchandise_and_child_hosts_return_to_main_before_login()
    {
        $user = Model::withoutEvents(fn () => create(User::class)->setAppends(['full_name']));
        $this->order->update(['user_id' => $user->id]); $other = $this->order();
        $this->actingAs($user, 'web');
        $page = $this->get(route('users.orders').'?user_id=999')->assertOk()->assertSee($this->order->id)->assertDontSee($other->id);
        $page->assertDontSee('googletagmanager.com');
        $this->assertStringContainsString('Track your orders', $page->getContent());
        $this->get(route('users.purchases'))->assertOk()->assertSee(route('users.orders'));
        auth('web')->logout();
        $this->get('http://shop.localhost/profile/orders?filter=a%2Fb')->assertStatus(301)->assertRedirect('http://localhost/profile/orders?filter=a%2Fb');
        $this->get(route('users.orders'))->assertRedirect();
    }

    public function test_unsafe_provider_links_are_never_rendered_as_tracking_actions()
    {
        $this->remote['shipments'][0]['tracking_url'] = 'javascript:alert(1)';
        $this->webhook()->assertOk();
        $this->assertNull(ShopShipment::first()->tracking_url);
        $this->get(app(OrderTracking::class)->url($this->order))->assertOk()->assertDontSee('javascript:alert')->assertDontSee('Track this package');
    }

    public function test_local_webhooks_email_delivery_and_polling_remain_disconnected_with_live_rows_and_credentials()
    {
        app(OrderEmails::class)->confirmation($this->order);
        $this->app->instance('env', 'local');
        $this->webhook()->assertStatus(503);
        $this->artisan('shop:sync-shipments')->assertExitCode(0);
        $this->artisan('shop:send-order-emails')->assertExitCode(0);
        $this->artisan('printful:webhooks', ['--configure' => true])->assertExitCode(1);
        Http::assertNothingSent(); Mail::assertNothingSent();
        $this->assertSame('pending', ShopOrderEmail::first()->status);
    }

    public function test_editable_email_previews_render_html_and_plain_text_with_safe_fictional_links()
    {
        foreach (['merchandise-confirmation', 'merchandise-shipped', 'merchandise-delivered'] as $key) {
            $preview = app(\App\Services\Admin\AutomaticEmails::class)->render($key);
            foreach (['html', 'text'] as $format) {
                $this->assertStringContainsString('Piano rainbow T-shirt', $preview[$format]);
                $this->assertStringContainsString('00000000-0000-4000-8000-000000000001', $preview[$format]);
                $this->assertStringContainsString('#email-preview', $preview[$format]);
            }
            $this->assertStringNotContainsString('123 Private Street', $preview['html']);
            if ($key === 'merchandise-shipped') {
                $this->assertStringContainsString('PREVIEW123456', $preview['text']);
                $this->assertStringContainsString('Estimated delivery', $preview['text']);
            }
        }
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_order_email_retries_require_a_manager_and_the_matching_order()
    {
        app(OrderEmails::class)->confirmation($this->order);
        $email = ShopOrderEmail::first(); $email->update(['status' => 'uncertain']);
        $url = route('admin.shop-orders.email.retry', [$this->order->id, $email->id]);
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin')->post($url)->assertForbidden();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $other = $this->order();
        $this->post(route('admin.shop-orders.email.retry', [$other->id, $email->id]))->assertNotFound();
        $this->post($url)->assertRedirect(); $this->assertSame('pending', $email->fresh()->status);
        $this->post($url)->assertStatus(422);
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_explicit_webhook_configuration_saves_keys_privately_and_never_replaces_other_events()
    {
        config(['app.url' => 'pianolit.example']);
        $configuration = null;
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use (&$configuration) {
            if ($request->method() === 'POST') {
                $this->assertSame('https://api.printful.com/v2/webhooks', $request->url());
                $this->assertSame('https://pianolit.example/webhooks/printful', $request['default_url']);
                $configuration = $request->data() + ['public_key' => 'new-review-public-key'];
                return Http::response(['code' => 200, 'result' => $configuration + ['secret_key' => $this->secret]]);
            }
            return $configuration ? Http::response(['code' => 200, 'result' => $configuration]) : Http::response([], 404);
        });
        $this->artisan('printful:webhooks', ['--configure' => true])->assertExitCode(0);
        $this->assertSame('new-review-public-key', app(PrintfulWebhooks::class)->keys()['public_key']);
        $this->assertSame(0600, fileperms($this->keysPath) & 0777);
        $this->artisan('printful:webhooks', ['--configure' => true])->assertExitCode(0);
        $this->assertCount(1, Http::recorded(fn ($request) => $request->method() === 'POST'));
        $configuration['events'][] = ['type' => 'catalog_stock_updated'];
        $this->artisan('printful:webhooks', ['--configure' => true])->assertExitCode(1);
        $this->assertCount(1, Http::recorded(fn ($request) => $request->method() === 'POST'));
    }
}
