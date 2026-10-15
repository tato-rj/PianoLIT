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

## Users table loading and query budget

`App\Services\Admin\UsersTable` renders the first 12 rows with the initial Users
page. The shared header accepts `ready => true` and `initialBody` containing the
trusted rendered row view. Account text is escaped inside those cell templates;
never pass request text directly as the body markup. DataTables uses
`deferLoading` with the initial total, so it makes no initial data request and
fetches fresh data only for subsequent table interactions. Do not add the empty
loading placeholder back to this populated table.

The service returns only the eight displayed fields and never serializes User
models. Its explicit account selection omits the default favorites count and
unused activity appends. Location flags and membership status fields are loaded
in batches; super-user rows skip billing entirely. Existing user status methods
retain their meaning. No shared response cache is involved.

In isolated fixtures containing both Apple and Stripe memberships, unfiltered
12/24-row requests use six queries at either page size; a nonempty search adds one
filtered count. The old path used 30/54 queries respectively. The same comparison
reduced JSON from 63,148 to 40,680 bytes for 12 rows (about 36%); these figures are
fixture measurements, not production timing guarantees. The first HTML response
also includes the ordinary admin layout's own queries.

Apply `2026_10_15_010000_add_admin_user_directory_indexes.php` during deployment.
Its non-unique indexes support the default `created_at, id` ordering and the
membership lookup by `user_id`. Locations already have a unique `user_id` index.
SQLite plan checks cover both added indexes and rollback; production MySQL plans
and timings have not been measured. The code remains functional before the
indexes are applied. No asset build is required for this PHP/Blade change.
