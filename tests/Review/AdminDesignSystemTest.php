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
            $this->assertSame(1, $xpath->query('.//*[@data-ds-preview]', $sample)->length);
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
}
