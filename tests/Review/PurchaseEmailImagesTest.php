<?php

namespace Tests\Review;

use App\Mail\Shop\{ConfirmPurchase, MerchandiseOrderConfirmed, MerchandiseOrderShipped, MerchandiseOrderDelivered};
use App\Services\Admin\AutomaticEmails;
use App\Shop\{ShopOrder, ShopShipment, eBook, eScore};
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\{Http, Mail};

class PurchaseEmailImagesTest extends ReviewTestCase
{
    private function render($mail): array
    {
        app()->call([$mail, 'build']);
        $html = (string) app(Markdown::class)->render($mail->markdown, $mail->buildViewData());
        $text = (string) app(Markdown::class)->renderText($mail->markdown, $mail->buildViewData());
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return [$html, $text, new \DOMXPath($dom)];
    }

    private function order(): ShopOrder
    {
        return new ShopOrder(['id' => '00000000-0000-4000-8000-000000000001', 'recipient' => ['name' => 'Jamie'],
            'items' => [
                ['name' => 'Piano T-shirt', 'option' => 'Mint / M', 'quantity' => 2, 'unit_price' => 2800, 'image' => 'https://assets.example.test/mint.png'],
                ['name' => 'Piano sticker', 'option' => '3 × 3', 'quantity' => 1, 'unit_price' => 500, 'image' => 'https://assets.example.test/sticker.png'],
            ], 'subtotal' => 6100, 'shipping' => 500, 'tax' => 0, 'total' => 6600, 'currency' => 'usd']);
    }

    public function test_confirmation_and_arrival_show_each_saved_product_with_complete_text_and_totals()
    {
        $order = $this->order();
        $order->setRelation('shipments', collect([new ShopShipment(['delivered_at' => '2026-10-29'])]));
        foreach ([new MerchandiseOrderConfirmed($order, '#order'), new MerchandiseOrderDelivered($order, '#order')] as $mail) {
            [$html, $text, $xpath] = $this->render($mail);
            $images = $xpath->query('//img[@width="72"]');
            $this->assertCount(2, $images);
            $this->assertSame('https://assets.example.test/mint.png', $images->item(0)->getAttribute('src'));
            $this->assertSame('Piano T-shirt', $images->item(0)->getAttribute('alt'));
            $this->assertSame('https://assets.example.test/sticker.png', $images->item(1)->getAttribute('src'));
            $this->assertStringContainsString('Piano T-shirt · Mint / M × 2', $text);
            $this->assertStringContainsString('Piano sticker · 3 × 3 × 1', $text);
            $this->assertStringNotContainsString('assets.example.test', $text);
            $this->assertStringNotContainsString('<img', $text);
            if ($mail instanceof MerchandiseOrderConfirmed) {
                $this->assertStringContainsString('$56.00 USD', $html);
                $this->assertStringContainsString('$56.00 USD', $text);
                $this->assertStringContainsString('$66.00 USD', $text);
            }
        }
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_historical_shipping_rows_resolve_only_the_purchased_variant_in_this_package()
    {
        $order = $this->order();
        $shipment = new ShopShipment(['items' => [['name' => 'Piano T-shirt', 'option' => 'Mint / M', 'quantity' => 1]]]);
        [$html, $text, $xpath] = $this->render(new MerchandiseOrderShipped($order, $shipment, '#order'));
        $images = $xpath->query('//img[@width="72"]');
        $this->assertCount(1, $images);
        $this->assertSame('https://assets.example.test/mint.png', $images->item(0)->getAttribute('src'));
        $this->assertStringNotContainsString('Piano sticker', $html);
        $this->assertStringNotContainsString('sticker.png', $html);
        $this->assertStringContainsString('Piano T-shirt · Mint / M × 1', $text);
        $this->assertStringNotContainsString('× 2', $text);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_missing_ambiguous_or_unsafe_images_leave_readable_product_details()
    {
        $order = $this->order();
        $items = $order->items;
        $items[0]['image'] = 'javascript:alert(1)';
        $items[1]['image'] = 'https://user:secret@assets.example.test/sticker.png';
        $order->items = $items;
        [$html, $text, $xpath] = $this->render(new MerchandiseOrderConfirmed($order, '#order'));
        $this->assertCount(0, $xpath->query('//img[@width="72"]'));
        $this->assertStringContainsString('Piano T-shirt', $html);
        $this->assertStringContainsString('Piano sticker', $text);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('secret@', $html);

        $order = $this->order();
        $items = $order->items;
        $items[] = $items[0];
        $order->items = $items;
        $shipment = new ShopShipment(['items' => [['name' => 'Piano T-shirt', 'option' => 'Mint / M', 'quantity' => 1]]]);
        [, , $xpath] = $this->render(new MerchandiseOrderShipped($order, $shipment, '#order'));
        $this->assertCount(0, $xpath->query('//img[@width="72"]'), 'An ambiguous historical match must not guess a product image.');
    }

    public function test_digital_purchase_confirmations_use_the_product_mockup_or_cover()
    {
        foreach ([new eBook(['title' => 'Practice companion', 'mockup_path' => 'ebooks/mockup.png']),
            new eScore(['title' => 'Piano solos', 'cover_path' => 'escores/cover.jpg'])] as $product) {
            [$html, $text, $xpath] = $this->render(new ConfirmPurchase($product, (object) ['first_name' => 'Jamie']));
            $images = $xpath->query('//img[@width="72"]');
            $this->assertCount(1, $images);
            $expected = $product instanceof eBook ? asset('storage/ebooks/mockup.png') : asset('storage/escores/cover.jpg');
            $this->assertSame($expected, $images->item(0)->getAttribute('src'));
            $this->assertStringContainsString($product->title, $text);
            $this->assertStringContainsString(route('users.purchases'), $html);
            $this->assertStringNotContainsString('/storage/', $text);
        }
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_all_purchase_preview_types_include_an_image_without_fetching_catalogue_data()
    {
        foreach (['purchase-confirmation', 'merchandise-confirmation', 'merchandise-shipped', 'merchandise-delivered'] as $key) {
            $preview = app(AutomaticEmails::class)->render($key);
            $this->assertMatchesRegularExpression('/<img[^>]+width="72"/', $preview['html']);
            $this->assertStringNotContainsString('/images/shop/', $preview['text']);
        }
        Http::assertNothingSent(); Mail::assertNothingSent();
    }
}
