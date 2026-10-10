<?php

namespace Tests\Review;

use App\{Admin, Tag};
use Illuminate\Support\Facades\Schema;

class TagOrderingTest extends ReviewTestCase
{
    private $tag;

    public function setUp(): void
    {
        parent::setUp();
        $admin = create(Admin::class, ['role' => 'manager']);
        $this->actingAs($admin, 'admin');
        $this->tag = create(Tag::class, ['name' => 'romantic', 'type' => 'period', 'creator_id' => $admin->id]);
        $this->withExceptionHandling();
    }

    public function test_admin_can_set_change_and_clear_order_without_changing_legacy_order()
    {
        $this->assertNull($this->tag->ordering);
        foreach (['1' => 1, '65535' => 65535, '' => null] as $input => $expected) {
            $this->patch(route('admin.tags.update', $this->tag), [
                'name' => 'romantic', 'type' => 'period', 'ordering' => $input,
            ])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($expected, $this->tag->fresh()->ordering);
            $this->assertEquals(0, $this->tag->fresh()->order);
        }
        $this->tag->update(['ordering' => 7]);
        $this->patch(route('admin.tags.update', $this->tag), ['name' => 'romantic', 'type' => 'period'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(7, $this->tag->fresh()->ordering);
        $this->patch(route('admin.tags.update', $this->tag), ['name' => 'romantic', 'type' => 'period', 'ordering' => null])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($this->tag->fresh()->ordering);
    }

    public function test_admin_rejects_zero_negative_fractional_out_of_range_and_malformed_orders()
    {
        $this->tag->update(['ordering' => 3]);
        foreach ([0, -1, 1.5, 65536, 'first', [1]] as $input) {
            $this->patchJson(route('admin.tags.update', $this->tag), [
                'name' => 'changed', 'type' => 'period', 'ordering' => $input,
            ])->assertUnprocessable()->assertJsonValidationErrors('ordering');
            $this->assertSame(3, $this->tag->fresh()->ordering);
            $this->assertSame('romantic', $this->tag->fresh()->name);
        }
    }

    public function test_ordering_keeps_existing_admin_authorization_and_mobile_serialization()
    {
        $this->tag->update(['ordering' => 2]);
        $this->assertArrayNotHasKey('ordering', $this->tag->fresh()->toArray());
        $this->assertArrayHasKey('order', $this->tag->fresh()->toArray());
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->patchJson(route('admin.tags.update', $this->tag), [
            'name' => 'romantic', 'type' => 'period', 'ordering' => 1,
        ])->assertForbidden();
        $this->assertSame(2, $this->tag->fresh()->ordering);
    }

    public function test_admin_modal_has_optional_integer_input_and_existing_value()
    {
        $this->tag->update(['ordering' => 4]);
        $html = $this->get(route('admin.tags.index'))->assertOk()->assertSee('data-ordering="4"', false)->getContent();
        preg_match('/<input\s[^>]*name="ordering"[^>]*>/s', $html, $input);
        $this->assertNotEmpty($input);
        foreach (['type="number"', 'min="1"', 'step="1"', 'max="65535"'] as $attribute) {
            $this->assertStringContainsString($attribute, $input[0]);
        }
        $this->assertDoesNotMatchRegularExpression('/\srequired(?:\s|=|>)/', $input[0]);
        $this->assertStringContainsString(".val(\$tag.attr('data-ordering'))", $html);
    }

    public function test_ordering_migration_can_be_rolled_back_and_reapplied()
    {
        require_once database_path('migrations/2026_10_10_121500_add_ordering_to_tags_table.php');
        $migration = new \AddOrderingToTagsTable;
        $migration->down();
        $this->assertFalse(Schema::hasColumn('tags', 'ordering'));
        $this->assertTrue(Schema::hasColumn('tags', 'order'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('tags', 'ordering'));
        $this->assertNull($this->tag->fresh()->ordering);
    }
}
