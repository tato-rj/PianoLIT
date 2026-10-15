<?php

namespace Tests\Review;

use Illuminate\Support\HtmlString;

class AdminBladeTableTest extends ReviewTestCase
{
    public function test_small_tables_share_the_layout_without_unnecessary_controls()
    {
        $html = view('admin.components.table', [
            'id' => 'providers-table', 'title' => 'Providers', 'items' => collect(['Apple', 'Stripe']),
            'columns' => ['Provider', 'Accounts'],
            'slot' => new HtmlString('<tr><td>Apple</td><td>3</td></tr><tr><td>Stripe</td><td>5</td></tr>'),
        ])->render();
        $this->assertStringContainsString('id="providers-table"', $html);
        $this->assertSame(2, substr_count($html, '<tr><td>'));
        foreach (['<form', 'pagination', 'DataTable', 'data-admin-datatable', '<script'] as $unnecessary) {
            $this->assertStringNotContainsString($unnecessary, $html);
        }
    }

    public function test_empty_tables_show_one_accessible_empty_state_and_escape_supplied_text()
    {
        $html = view('admin.components.table', [
            'id' => 'empty-table', 'title' => '<script>Title</script>', 'items' => collect(),
            'columns' => ['Name', ['label' => 'Actions', 'hidden' => true]],
            'emptyTitle' => 'Nothing <unsafe>', 'emptyDescription' => 'Try another filter.',
            'slot' => new HtmlString('<tr><td>Fake row</td></tr>'),
        ])->render();
        foreach (['&lt;script&gt;Title&lt;/script&gt;', 'Nothing &lt;unsafe&gt;', 'colspan="2"', 'role="region"', 'tabindex="0"'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertMatchesRegularExpression('/class="visually-hidden"[^>]*>Actions/', $html);
        foreach (['Fake row', '<script>', 'pagination'] as $unexpected) $this->assertStringNotContainsString($unexpected, $html);
    }
}
