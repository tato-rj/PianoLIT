<?php

namespace Tests\Review;

use App\Support\Icon;
use Illuminate\Support\Facades\Blade;

class IconTest extends ReviewTestCase
{
    public function test_sparkles_renders_lucide_geometry_instead_of_the_unknown_icon_fallback()
    {
        $html = (string) Icon::render('sparkles');
        $this->assertStringContainsString('app-icon icon-sparkles mr-2', $html);
        $this->assertStringContainsString('data-lucide-name="sparkles"', $html);
        $this->assertStringContainsString('<path', $html);
        $this->assertStringNotContainsString('circle-help', $html);
    }

    public function test_blade_directive_keeps_default_spacing_and_supports_options()
    {
        $compiled = Blade::compileString("@icon('close') @icon('close', ['mr' => 0, 'weight' => 'thin'])");
        ob_start();
        eval('?>'.$compiled);
        $html = ob_get_clean();
        $this->assertStringContainsString('app-icon icon-x mr-2', $html);
        $this->assertStringContainsString('app-icon icon-x mr-0 icon-weight-thin', $html);
        $this->assertSame(2, substr_count($html, 'data-lucide-name="x"'));
        $this->assertStringNotContainsString('fa-times', $html);
    }

    public function test_labels_attributes_hidden_state_and_filled_state_are_preserved_and_escaped()
    {
        $html = (string) Icon::render('heart', [
            'mr' => 0, 'ml' => 1, 'color' => 'red', 'size' => 'lg', 'filled' => true,
            'name' => 'saved', 'if' => false, 'title' => '"><script>alert(1)</script>',
            'classes' => 'favorite-icons', 'attributes' => ['data-state' => 'saved', 'onclick' => 'alert(1)'],
        ]);
        foreach (['mr-0', 'ml-1', 'text-red', 'icon-size-lg', 'icon-filled', 'favorite-icons', 'display: none;', 'name="saved"', 'data-state="saved"', 'role="img"', '&lt;script&gt;'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('onclick=', $html);
    }

    public function test_solid_accepts_hex_rgb_and_rgba_without_changing_the_outline_color()
    {
        foreach (['#fff', '#0055fe', 'rgb(255, 255, 255)', 'rgba(0, 85, 254, 0.5)'] as $color) {
            $html = (string) Icon::render('heart', ['solid' => $color]);
            $this->assertStringContainsString('icon-filled', $html);
            $this->assertStringContainsString('fill="'.$color.'"', $html);
            $this->assertStringContainsString('style="fill: '.$color.'"', $html);
            $this->assertStringContainsString('stroke="currentColor"', $html);
        }

        $sameColor = (string) Icon::render('heart', ['solid' => true]);
        $this->assertStringContainsString('fill="currentColor"', $sameColor);
        $this->assertStringNotContainsString('style="fill:', $sameColor);

        $invalid = (string) Icon::render('heart', ['solid' => '#fff; stroke: red']);
        $this->assertStringContainsString('fill="currentColor"', $invalid);
        $this->assertStringNotContainsString('style="fill:', $invalid);
    }

    public function test_legacy_model_values_and_brand_logos_resolve_only_at_the_view_boundary()
    {
        $this->assertStringContainsString('icon-smartphone', (string) Icon::render('fas fa-mobile'));
        $this->assertStringContainsString('icon-shield-user', (string) Icon::render('user-shield'));
        $this->assertStringContainsString('app-brand-icon fab fa-apple mr-2', (string) Icon::render('fab fa-apple'));
        $this->assertStringContainsString('app-brand-icon fab fa-youtube', (string) Icon::render('brand-youtube'));
        $this->assertStringContainsString('icon-circle-help', (string) Icon::render('../../.env'));
    }

    public function test_every_alias_and_literal_view_icon_has_bundled_geometry()
    {
        $icons = json_decode(file_get_contents(resource_path('icons/lucide.json')), true);
        $aliases = json_decode(file_get_contents(resource_path('icons/aliases.json')), true);
        foreach ($aliases as $name => $resolved) {
            if (strpos($resolved, 'brand-') !== 0) $this->assertArrayHasKey($resolved, $icons, $name);
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') continue;
            preg_match_all("/@icon\\('([^']+)'/", file_get_contents($file->getPathname()), $matches);
            foreach ($matches[1] as $name) {
                $resolved = Icon::resolve($name);
                if (strpos($resolved, 'brand-') !== 0) $this->assertArrayHasKey($resolved, $icons, $file->getPathname().': '.$name);
            }
        }
    }
}
