<?php

namespace Tests\Review;

use App\Services\Shop\MaterialDescription;
use PHPUnit\Framework\TestCase;

class MaterialDescriptionTest extends TestCase
{
    public function test_literal_material_list_items_are_kept_without_introductory_copy_or_other_specs()
    {
        $description = "An introductory paragraph describing a 100% cotton shirt.\n\n• 100% ring-spun cotton\n• Fabric weight: 6.1 oz/yd²\n• Relaxed fit";
        $this->assertSame('100% ring-spun cotton', MaterialDescription::extract($description));
        $this->assertSame('100% ring-spun cotton', MaterialDescription::extract('<p>A 100% cotton shirt for your collection.</p><ul><li>100% ring-spun cotton</li><li>Relaxed fit</li></ul>'));
    }

    public function test_color_qualifiers_blends_and_wrapped_material_statements_remain_literal()
    {
        $description = "• Solid colors: 100% cotton\n• Heather colors: 52% cotton,\n  48% polyester\n• Fabric content may vary by color";
        $this->assertSame("Solid colors: 100% cotton\nHeather colors: 52% cotton, 48% polyester\nFabric content may vary by color", MaterialDescription::extract($description));
        $this->assertSame('Material: 95% cotton, 5% elastane', MaterialDescription::extract('Material: 95% cotton, 5% elastane'));
    }

    public function test_unavailable_invalid_and_confirmed_empty_descriptions_are_distinct()
    {
        foreach ([null, [], str_repeat('x', 60001), "\xff", "• 100% cotton\x00"] as $description) {
            $this->assertNull(MaterialDescription::extract($description));
        }
        foreach (['', 'A promotional paragraph about a 100% cotton shirt.', '• Fabric weight: 200 g/m²', '<script>100% cotton</script>'] as $description) {
            $this->assertSame('', MaterialDescription::extract($description));
        }
        $this->assertSame('100% cotton', MaterialDescription::extract("• 100% cotton\n• 100% cotton"));
    }
}
