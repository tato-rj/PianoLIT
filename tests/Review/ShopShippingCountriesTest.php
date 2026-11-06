<?php

namespace Tests\Review;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{CheckoutStripe, ShopCheckout};
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeCheckoutStripe;

class ShopShippingCountriesTest extends ReviewTestCase
{
    public function test_us_shipping_allowlist_limits_the_form_and_rejects_other_destinations_before_payment()
    {
        $this->withExceptionHandling();
        config(['shop-checkout.countries' => ['US'], 'shop-checkout.mode' => 'sandbox',
            'shop-checkout.stripe_key' => 'pk_test_review', 'shop-checkout.stripe_secret' => 'sk_test_review',
            'services.printful.store_id' => 12345]);
        $gateway = new FakeCheckoutStripe;
        $this->app->instance(CheckoutStripe::class, $gateway);
        $product = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 8,
            'name' => 'Piano T-shirt', 'variants' => [['id' => 91, 'variant_id' => 4011, 'synced' => true,
                'size' => 'M', 'color' => 'Ivory', 'retail_price' => '28.00', 'currency' => 'USD']]]);
        $this->postJson(route('shop.bag.add'), ['product_id' => $product->id, 'variant_id' => 91, 'quantity' => 1])->assertOk();

        $html = $this->getJson(route('shop.checkout'))->assertOk()->json('html');
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $options = [];
        foreach ($xpath->query('//select[@id="delivery-country"]/option') as $option) $options[] = $option->getAttribute('value');
        $this->assertSame(['', 'US'], $options);

        $address = ['email' => 'shipping-review@example.test', 'name' => 'Shipping Review',
            'address1' => '123 Test Street', 'city' => 'Montreal', 'state_code' => 'QC', 'country_code' => 'CA', 'zip' => 'H2Y 1C6'];
        $this->postJson(route('shop.checkout.quote'), $address)->assertStatus(422)->assertJsonValidationErrors('country_code');
        $this->assertDatabaseCount('shop_orders', 0);
        $this->assertCount(0, $gateway->calls);
        Http::assertNothingSent();

        $this->postJson(route('shop.checkout.quote'), array_merge($address,
            ['city' => 'Newark', 'state_code' => 'NJ', 'country_code' => 'US', 'zip' => '07102']))->assertOk();
        $digitalCountries = array_column(app(ShopCheckout::class)->countries('digital'), 'code');
        $this->assertContains('CA', $digitalCountries);
        $this->assertContains('GB', $digitalCountries);
        Http::assertNothingSent();
    }
}
