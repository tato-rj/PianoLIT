# Admin table standard

The user approved Feedback as the standard for admin tables on 2026-10-14.
Users and Feedback share this pattern. Adopt it when creating or redesigning a
table; retain each page's columns, filters, routes, permissions and actions.

Use the existing white Bootstrap card with 24px corners and 24px padding via
`admin.components.table-card`. Keep the page heading outside the card. Place
filters, totals, bulk actions and load-error feedback inside it, above the table.
Use `admin-table-filters` for wrapping filters with 12px gaps and a 16px bottom
margin. Table text retains the shared PianoLIT typography tokens.

Use `table align-middle w-100`, ordinary header/row separators, and a
`table-responsive` wrapper so wide tables scroll within the card on phones.
Preserve readable cell text rather than reducing font size to fit more columns.
Avoid zebra stripes, hover fills and separate outer table borders. Status badges,
checkboxes, toggles and action buttons retain their existing meaning and behavior.
Headers for icon-only action columns must remain accessible.

For the existing shared DataTable header component, opt in without changing its
legacy presentation on other pages:

```blade
@component('admin.components.table-card')
    {{-- Optional filters, bulk actions, totals and errors go here. --}}
    <div class="table-responsive">
        @datatable(['table' => 'example', 'standard' => true, 'columns' => ['Date', 'Name', '']])
    </div>
@endcomponent
```

Reuse the project's existing Bootstrap DataTables integration. Keep page size
and search above the table; show the result summary and arrow/number pagination
below. Default to `App\Support\AdminPagination::PER_PAGE` (12), with 24, 48 and 96
as the larger page sizes for migrated tables. Keep existing default sorting and
search/filter behavior. Sorting and searching must cover the full dataset, not
only the currently rendered page. Handle failed requests with a visible retry
control and clear processing indicators; keep stale requests from replacing the
current request's status. Preserve checkbox selections across redraws on pages
that support bulk selection.

Shared card/filter styling lives in `resources/sass/admin/_tables.scss`; keep
page-specific cells and badges in their own stylesheets. Rebuild tracked admin
CSS and update its Mix manifest hash when shared styles change. Visually verify
desktop and phone layouts, the empty state, search/pagination, and any row/bulk
controls. This standard does not change public/mobile pagination or contracts.
