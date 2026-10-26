<?php

namespace Tests\Review;

use App\Admin;

class AdminDesignSystemTest extends ReviewTestCase
{
    public function test_design_system_requires_the_admin_session()
    {
        $this->withExceptionHandling()->get(route('admin.design-system.index'))->assertRedirect(route('admin.login.show'));
    }

    public function test_sidebar_destination_renders_the_gallery_with_safe_sample_controls()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.design-system.index'))->assertOk();
        $response->assertViewIs('admin.pages.settings.index');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('ds-brand', $xpath->query('//section[contains(@class, "design-system__section")]')->item(0)->getAttribute('id'));
        $this->assertSame('#ds-brand', $xpath->query('//nav[@aria-label="Design system sections"]/a')->item(0)->getAttribute('href'));
        $this->assertSame(2, $xpath->query('//*[@id="ds-brand"]//article[contains(@class, "design-system__sample")]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="ds-brand"]//*[@data-ds-sample or @data-ds-specs]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="ds-brand"]//article//a[@download]')->length);
        $this->assertSame(2, $xpath->query('//*[@id="ds-brand"]//article//button[@type="button" and @data-bs-toggle="modal"]')->length);
        foreach (['user' => 17, 'admin' => 13] as $variant => $downloadCount) {
            $id = 'ds-brand-'.$variant.'-downloads';
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'" and @aria-labelledby="'.$id.'-title" and @aria-describedby="'.$id.'-description"]')->length);
            $this->assertSame($downloadCount, $xpath->query('//*[@id="'.$id.'"]//a[@download]')->length);
            $this->assertSame(1, $xpath->query('//button[@data-bs-target="#'.$id.'" and @aria-controls="'.$id.'"]')->length);
        }
        $this->assertStringContainsString('1238 × 1200 px', $response->getContent());
        $this->assertStringContainsString('1024 × 1024 px', $response->getContent());
        foreach (['app-icon.svg', 'admin-icon.svg', 'icon-white.svg', 'app-icon-ios-1024.png', 'icon-rounded.png', 'icon-rounded-margin.png'] as $file) {
            $this->assertSame(1, $xpath->query('//*[@id="ds-brand"]//a[@download and @href="'.asset(mix('images/brand/'.$file)).'"]')->length);
        }
        foreach (['', 'admin-'] as $prefix) {
            foreach (['favicon-16x16.png', 'favicon-32x32.png', 'favicon.ico', 'safari-pinned-tab.svg', 'apple-touch-icon.png', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'mstile-150x150.png'] as $file) {
                $this->assertSame(1, $xpath->query('//*[@id="ds-brand"]//a[@download and @href="'.asset(mix($prefix.$file)).'"]')->length);
            }
        }
        $this->assertSame(1, $xpath->query('//*[@id="ds-account-tabs" and @data-tab-navigation]')->length);
        $frame = $xpath->query('//*[@data-tab-navigation-frame][.//*[@id="ds-account-tabs"]]')->item(0);
        $this->assertNotNull($frame, 'The shared component encloses its menu and panes for atomic first render.');
        $this->assertSame(2, $xpath->query('.//*[@role="tabpanel"]', $frame)->length);
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="ds-account-tabs"'), strpos($html, 'PianoLITTabNavigation.prepare('));
        $this->assertGreaterThan(strpos($html, 'id="ds-account-billing"'), strpos($html, 'PianoLITTabNavigation.initial('));
        $this->assertLessThan(strpos($html, 'src="'.mix('js/admin.js').'"'), strpos($html, 'PianoLITTabNavigation.initial('));
        $this->assertSame(1, $xpath->query('//a[@href="'.route('admin.design-system.index').'" and @aria-current="page"]')->length);
        $this->assertSame(1, $xpath->query('//ul[@id="settings" and contains(@class, "show")]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-ds-sample]//form')->length);
        $this->assertSame(0, $xpath->query('//*[@data-ds-sample]//button[not(@type="button")]')->length);

        foreach ($xpath->query('//nav[@aria-label="Design system sections"]/a') as $link) {
            $this->assertSame(1, $xpath->query('//*[@id="'.substr($link->getAttribute('href'), 1).'"]')->length);
        }
        foreach ($xpath->query('//*[@data-ds-sample]') as $sample) {
            $this->assertSame(1, $xpath->query('.//*[@data-ds-preview]', $sample)->length, trim($sample->textContent));
            $this->assertSame(1, $xpath->query('.//*[@data-ds-specs]', $sample)->length);
        }
        $this->assertSame(1, $xpath->query('//*[@id="ds-example-modal" and @aria-labelledby="ds-example-modal-title"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="ds-example-modal"]//button[@aria-label="Close" and @data-bs-dismiss="modal"]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-payment-card-add and @data-bs-toggle="modal" and @data-bs-target="#ds-card-editor-example"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="ds-card-editor-example" and @aria-labelledby="ds-card-editor-example-title"]')->length);
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) $ids[] = $element->getAttribute('id');
        $this->assertCount(count(array_unique($ids)), $ids, 'Gallery IDs must be unique for tabs, modals and labels.');
    }

    public function test_browser_metadata_and_standalone_login_use_the_matching_versioned_brand()
    {
        foreach ([false, true] as $admin) {
            $html = view('components.branding.theme', compact('admin'))->render();
            $prefix = $admin ? 'admin-' : '';
            $this->assertStringContainsString('name="apple-mobile-web-app-capable" content="yes"', $html);
            $this->assertStringContainsString('name="mobile-web-app-capable" content="yes"', $html);
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $profiles = json_decode(file_get_contents(resource_path('data/brand-splash-screens.json')), true);
            $this->assertSame(count($profiles) * 2, $xpath->query('//link[@rel="apple-touch-startup-image"]')->length);
            foreach ($profiles as $profile) {
                foreach (['portrait', 'landscape'] as $orientation) {
                    $w = $profile['width'] * $profile['scale'];
                    $h = $profile['height'] * $profile['scale'];
                    $size = $orientation === 'portrait' ? $w.'x'.$h : $h.'x'.$w;
                    $path = 'images/brand/splash/'.($admin ? 'admin' : 'app').'-'.$size.'.png';
                    $media = 'screen and (device-width: '.$profile['width'].'px) and (device-height: '.$profile['height'].'px) and (-webkit-device-pixel-ratio: '.$profile['scale'].') and (orientation: '.$orientation.')';
                    $this->assertSame(1, $xpath->query('//link[@rel="apple-touch-startup-image" and @href="'.asset(mix($path)).'" and @media="'.$media.'"]')->length);
                }
            }
            foreach (['favicon.ico', 'favicon-32x32.png', 'favicon-16x16.png', 'apple-touch-icon.png', 'safari-pinned-tab.svg'] as $file) {
                $this->assertStringContainsString(asset(mix($prefix.$file)), $html);
                $this->assertStringContainsString('?id=', (string) mix($prefix.$file));
            }
            $metadataPrefix = $admin ? 'admin.' : '';
            $this->assertStringContainsString(asset(mix($metadataPrefix.'site.webmanifest')), $html);
            $this->assertStringContainsString(asset(mix($metadataPrefix.'browserconfig.xml')), $html);
            $this->assertStringContainsString(asset(mix('images/brand/'.($admin ? 'admin' : 'app').'-icon.svg')), $html);
            $this->assertStringContainsString('name="theme-color" content="'.($admin ? '#ffffff' : '#0055fe').'"', $html);
            $this->assertStringNotContainsString('favicon-32x32.png"', $html, 'All browser artwork should have a version query.');
        }
        $this->get(route('admin.login.show'))->assertOk()
            ->assertSee(asset(mix('images/brand/admin-icon.svg')), false)
            ->assertSee(asset(mix('admin-favicon.ico')), false)
            ->assertDontSee('images/brand/app-icon.svg', false);
    }
}
