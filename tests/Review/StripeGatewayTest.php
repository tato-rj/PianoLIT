<?php

namespace Tests\Review;

use App\Billing\Stripe\Gateway;

class StripeGatewayTest extends ReviewTestCase
{
    public function test_modern_sdk_uses_account_scoped_credentials_version_and_idempotency_without_global_mutation()
    {
        config(['services.stripe.us_secret' => 'sk_test_us_review', 'services.stripe.swiss_secret' => 'sk_test_swiss_review']);
        $originalKey = \Stripe\Stripe::getApiKey(); $originalVersion = \Stripe\Stripe::getApiVersion();
        \Stripe\Stripe::setApiKey('sk_test_legacy_review'); \Stripe\Stripe::setApiVersion('2020-03-02');
        $client = new class implements \Stripe\HttpClient\ClientInterface {
            public $calls = [];
            public function request($method, $url, $headers, $parameters, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null) {
                $this->calls[] = compact('method', 'url', 'headers', 'parameters', 'maxNetworkRetries');
                return [json_encode(['id' => 'seti_review', 'object' => 'setup_intent', 'status' => 'requires_payment_method']), 200, []];
            }
        };
        \Stripe\ApiRequestor::setHttpClient($client);
        try {
            $gateway = app(Gateway::class);
            $result = $gateway->call('us', 'setupIntents', 'create', ['customer' => 'cus_review'], null, 'operation-review:setup');
            $gateway->call('swiss', 'setupIntents', 'retrieve', [], 'seti_review');
            $this->assertSame('seti_review', $result['id']);
            $this->assertStringContainsString('Authorization: Bearer sk_test_us_review', implode('\n', $client->calls[0]['headers']));
            $this->assertStringContainsString('Authorization: Bearer sk_test_swiss_review', implode('\n', $client->calls[1]['headers']));
            $this->assertStringContainsString('Stripe-Version: 2025-03-31.basil', implode('\n', $client->calls[0]['headers']));
            $this->assertStringContainsString('Idempotency-Key: pianolit:operation-review:setup', implode('\n', $client->calls[0]['headers']));
            $this->assertSame(2, $client->calls[0]['maxNetworkRetries']);
            $this->assertSame('sk_test_legacy_review', \Stripe\Stripe::getApiKey());
            $this->assertSame('2020-03-02', \Stripe\Stripe::getApiVersion());
        } finally {
            \Stripe\ApiRequestor::setHttpClient(null); \Stripe\Stripe::setApiKey($originalKey); \Stripe\Stripe::setApiVersion($originalVersion);
        }
    }

    public function test_legacy_shop_card_replacement_keeps_old_card_and_returns_the_provider_card()
    {
        \Illuminate\Support\Facades\Event::fake();
        $user = \Illuminate\Database\Eloquent\Model::withoutEvents(function () { return create(\App\User::class); });
        $this->actingAs($user, 'web');
        $shop = \App\Billing\Customer::create(['user_id' => $user->id, 'stripe_id' => 'cus_shop', 'card_brand' => 'visa', 'card_last_four' => '1111']);
        $client = new class implements \Stripe\HttpClient\ClientInterface {
            public $calls = [];
            public function request($method, $url, $headers, $parameters, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null) {
                $this->calls[] = compact('method', 'url', 'headers', 'parameters');
                $data = strpos($url, '/sources') !== false ? ['id' => 'card_new', 'object' => 'card', 'brand' => 'visa', 'last4' => '4444'] : ['id' => 'cus_shop', 'object' => 'customer'];
                return [json_encode($data), 200, []];
            }
        };
        $originalKey = \Stripe\Stripe::getApiKey(); $originalVersion = \Stripe\Stripe::getApiVersion();
        \Stripe\Stripe::setApiKey('sk_test_legacy'); \Stripe\Stripe::setApiVersion('2020-03-02');
        \Stripe\ApiRequestor::setHttpClient($client);
        try {
            $factory = (new \ReflectionClass(\App\Billing\Factories\StripeFactory::class))->newInstanceWithoutConstructor();
            $factory->customer = \Stripe\Customer::constructFrom(['id' => 'cus_shop']);
            $card = $factory->updateCard('tok_review');
            $this->assertInstanceOf(\Stripe\Card::class, $card);
            $this->assertSame('4444', $shop->fresh()->card_last_four);
            $this->assertSame(['post', 'post'], array_column($client->calls, 'method'));
            $this->assertSame('card_new', $client->calls[1]['parameters']['default_source']);
            $this->assertStringContainsString('Stripe-Version: 2020-03-02', implode('\n', $client->calls[0]['headers']));
        } finally { \Stripe\ApiRequestor::setHttpClient(null); \Stripe\Stripe::setApiKey($originalKey); \Stripe\Stripe::setApiVersion($originalVersion); }
    }
}
