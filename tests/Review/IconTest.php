<?php

namespace Tests\Review;

use App\Support\Icon;
use Illuminate\Support\Facades\Blade;

class IconTest extends ReviewTestCase
{
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
