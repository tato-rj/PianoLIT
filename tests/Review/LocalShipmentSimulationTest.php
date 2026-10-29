<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Services\Shop\{LocalShipmentSimulator, OrderEmails, OrderTracking, ShipmentUpdates};
use App\Shop\{ShopOrder, ShopOrderEmail, ShopShipment};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail, URL};

class LocalShipmentSimulationTest extends ReviewTestCase
{
    private const TOKEN = 'local-simulation-csrf';

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->app->instance('env', 'local');
        config(['shop-checkout.mode' => 'sandbox', 'activity.enabled' => false]);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->withSession(['_token' => self::TOKEN]);
    }

    protected function tearDown(): void
    {
        $this->app->instance('env', 'testing');
        parent::tearDown();
    }

    private function sample(): ShopOrder
    {
        return app(LocalShipmentSimulator::class)->create();
    }

    private function event(ShopOrder $order, string $type)
    {
        return $this->post(route('admin.shop-orders.simulation.event', $order->id), ['_token' => self::TOKEN, 'event' => $type]);
    }

    public function test_manager_can_create_sample_simulate_tracking_and_preview_all_emails_without_network_or_mail()
    {
        $this->get(route('admin.shop-orders.index'))->assertOk()->assertSee('Create sample order');
        $this->post(route('admin.shop-orders.simulation.create'), ['_token' => self::TOKEN])->assertRedirect();
        $order = ShopOrder::sole();
        $this->assertSame('demo', $order->mode);
        $this->assertSame(0, $order->printful_store_id);
        $this->assertNull($order->stripe_payment_intent);
        $this->assertNull($order->printful_order_id);
        $this->assertNull($order->user_id);
        $this->get(route('admin.shop-orders.show', $order->id))->assertOk()->assertSee('Simulate shipped');
        $confirmation = $order->emails()->sole();
        $this->assertSame('preview', $confirmation->status);
        $this->get(route('admin.shop-orders.simulation.email', [$order->id, $confirmation->id, 'html']))->assertOk()->assertSee('Thank you for your order');
        $this->event($order, 'shipment_sent')->assertRedirect(route('admin.shop-orders.show', $order->id));
        $order = $order->fresh('shipments');
        $this->assertSame('Shipped', app(OrderTracking::class)->status($order));
        $this->assertSame('local-'.$order->id, $order->printful_order_id);
        $shipment = $order->shipments->sole();
        $this->assertStringStartsWith('LOCAL-', $shipment->tracking_number);
        $this->get(app(OrderTracking::class)->url($order))->assertOk()->assertSee('Sample carrier')->assertSee('Sample package accepted by the carrier');
        $this->get($shipment->tracking_url)->assertOk()->assertSee('Sample carrier')->assertSee('Sample piano T-shirt');
        $email = $order->emails()->where('kind', 'shipping')->sole();
        $html = $this->get(route('admin.shop-orders.simulation.email', [$order->id, $email->id, 'html']))->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $html->assertSee('Your order is on its way')->assertSee('LOCAL-')->assertSee('Track this package')->assertSee(e($shipment->tracking_url), false);
        $this->get(route('admin.shop-orders.simulation.email', [$order->id, $email->id, 'text']))->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSee('Sample carrier');
        $this->event($order, 'shipment_delivered')->assertRedirect();
        $this->get(app(OrderTracking::class)->url($order))->assertOk()->assertSee('Sample package delivered');
        $this->assertSame('Delivered', app(OrderTracking::class)->status($order->fresh('shipments')));
        $delivery = $order->emails()->where('kind', 'delivery')->sole();
        foreach (['html', 'text'] as $format) $this->get(route('admin.shop-orders.simulation.email', [$order->id, $delivery->id, $format]))->assertOk()->assertSee('Your order arrived!')->assertSee('View your order');
        $this->assertDatabaseCount('shop_shipments', 1);
        $this->assertDatabaseCount('shop_order_emails', 3);
        $this->assertDatabaseCount('printful_webhook_receipts', 2);
        app(OrderEmails::class)->deliverPending();
        foreach (ShopOrderEmail::all() as $email) { app(OrderEmails::class)->deliver($email->id); $this->assertNull($email->fresh()->sent_at); }
        Http::assertNothingSent(); Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_duplicate_replays_do_not_duplicate_emails_or_regress_delivered_tracking()
    {
        $order = $this->sample();
        $this->event($order, 'shipment_sent')->assertRedirect();
        $this->event($order, 'shipment_delivered')->assertRedirect();
        $deliveredAt = $order->shipments()->sole()->delivered_at->toIso8601String();
        $this->travel(1)->days();
        $this->event($order, 'shipment_sent')->assertRedirect()->assertSessionHas('status', 'This event was already processed. No duplicate email or shipment was created.');
        $this->event($order, 'shipment_delivered')->assertRedirect();
        $this->assertDatabaseCount('shop_shipments', 1);
        $this->assertDatabaseCount('shop_order_emails', 3);
        $this->assertDatabaseCount('printful_webhook_receipts', 2);
        $this->assertSame('delivered', $order->shipments()->sole()->status);
        $this->assertSame($deliveredAt, $order->shipments()->sole()->delivered_at->toIso8601String());
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_replay_can_preview_arrival_for_an_older_local_sample_without_another_shipment()
    {
        $order = $this->sample();
        $this->event($order, 'shipment_sent')->assertRedirect();
        $this->event($order, 'shipment_delivered')->assertRedirect();
        $order->emails()->where('kind', 'delivery')->delete();
        $at = $order->shipments()->sole()->delivered_at->toIso8601String();
        $this->event($order, 'shipment_delivered')->assertRedirect();
        $this->assertSame('preview', $order->emails()->where('kind', 'delivery')->sole()->status);
        $this->assertDatabaseCount('shop_order_emails', 3);
        $this->assertDatabaseCount('shop_shipments', 1);
        $this->assertDatabaseCount('printful_webhook_receipts', 2);
        $this->assertSame($at, $order->shipments()->sole()->delivered_at->toIso8601String());
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_delivery_before_shipping_unknown_events_and_missing_csrf_are_rejected()
    {
        $order = $this->sample();
        $this->event($order, 'shipment_delivered')->assertSessionHasErrors('event');
        $this->event($order, 'order_created')->assertSessionHasErrors('event');
        $this->post(route('admin.shop-orders.simulation.event', $order->id), ['event' => 'shipment_sent'])->assertStatus(419);
        $this->post(route('admin.shop-orders.simulation.create'))->assertStatus(419);
        $this->assertDatabaseCount('shop_shipments', 0);
        $this->assertDatabaseCount('shop_order_emails', 1);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_live_unpaid_digital_foreign_store_and_real_provider_orders_cannot_be_simulated()
    {
        foreach ([['mode' => 'live'], ['paid_at' => null], ['kind' => 'digital'], ['printful_store_id' => 12345], ['printful_order_id' => '12345']] as $changes) {
            $order = $this->sample();
            $order->update($changes);
            $this->event($order, 'shipment_sent')->assertNotFound();
        }
        $this->assertDatabaseCount('shop_shipments', 0);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_production_staging_and_testing_block_all_simulator_actions_even_with_cached_routes()
    {
        $order = $this->sample();
        $this->event($order, 'shipment_sent')->assertRedirect();
        $email = $order->emails()->first();
        $packageUrl = $order->shipments()->sole()->tracking_url;
        foreach (['production', 'staging', 'testing'] as $environment) {
            $this->app->instance('env', $environment);
            $this->post(route('admin.shop-orders.simulation.create'), ['_token' => self::TOKEN])->assertNotFound();
            $this->event($order, 'shipment_sent')->assertNotFound();
            $this->get(route('admin.shop-orders.simulation.email', [$order->id, $email->id, 'html']))->assertNotFound();
            $this->get($packageUrl)->assertNotFound();
            $this->get(route('admin.shop-orders.index'))->assertOk()->assertDontSee('Create sample order');
            try { app(LocalShipmentSimulator::class)->create(); $this->fail('Creation must be local-only'); }
            catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
            try { app(ShipmentUpdates::class)->simulate($order, [], [], 'blocked'); $this->fail('Shipment updates must be local-only'); }
            catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
            $this->assertFalse(app(LocalShipmentSimulator::class)->eligible($order->fresh()));
        }
        $this->assertDatabaseCount('shop_orders', 1);
        $this->assertDatabaseCount('shop_shipments', 1);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_captured_test_emails_cannot_be_sent_even_if_marked_pending_on_a_production_copy()
    {
        $order = $this->sample();
        $this->event($order, 'shipment_sent')->assertRedirect();
        ShopOrderEmail::query()->update(['status' => 'pending']);
        $this->app->instance('env', 'production');
        foreach (ShopOrderEmail::all() as $email) {
            app(OrderEmails::class)->deliver($email->id);
            $this->assertSame('pending', $email->fresh()->status);
            $this->assertNull($email->fresh()->sent_at);
        }
        app(OrderEmails::class)->deliverPending();
        Http::assertNothingSent(); Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_only_managers_can_mutate_or_preview_and_emails_and_packages_are_order_bound()
    {
        $order = $this->sample();
        $other = $this->sample();
        $this->event($order, 'shipment_sent')->assertRedirect();
        $this->get(route('admin.shop-orders.simulation.email', [$other->id, $order->emails()->first()->id, 'html']))->assertNotFound();
        $foreign = URL::signedRoute('shop.order.sample-package', ['order' => $other->id, 'shipment' => '1']);
        $this->get($foreign)->assertNotFound();
        $this->get(route('shop.order.sample-package', ['order' => $order->id, 'shipment' => '1']))->assertForbidden();
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->post(route('admin.shop-orders.simulation.create'), ['_token' => self::TOKEN])->assertForbidden();
        $this->event($order, 'shipment_sent')->assertForbidden();
        $this->get(route('admin.shop-orders.simulation.email', [$order->id, $order->emails()->first()->id, 'html']))->assertForbidden();
        $this->assertDatabaseCount('shop_orders', 2);
        $this->assertDatabaseCount('shop_shipments', 1);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_sandbox_orders_keep_account_ownership_and_confirmation_preview_without_real_mail()
    {
        $user = Model::withoutEvents(fn () => create(User::class));
        $order = $this->sample();
        $order->update(['mode' => 'sandbox', 'user_id' => $user->id]);
        $this->event($order, 'shipment_sent')->assertRedirect();
        $this->actingAs($user, 'web');
        $this->get(route('users.orders'))->assertOk()->assertSee('Sample piano T-shirt')->assertSee('Shipped');
        $this->actingAs(Model::withoutEvents(fn () => create(User::class)), 'web');
        $this->get(route('users.orders'))->assertOk()->assertDontSee('Sample piano T-shirt');
        $this->assertSame('preview', $order->emails()->first()->status);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }
}
