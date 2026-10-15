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
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) $ids[] = $element->getAttribute('id');
        $this->assertCount(count(array_unique($ids)), $ids, 'Gallery IDs must be unique for tabs, modals and labels.');
    }
}
