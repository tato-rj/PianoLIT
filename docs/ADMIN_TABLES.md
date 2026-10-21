# Admin table standard

On 2026-10-15 the user chose the membership directory design and a reusable
Laravel Blade component as the standard. Always reuse `admin.components.table`
for admin tables, including small fixed lists such as Emails; do not create a
page-specific table implementation. Users, Subscribers, Feedback, Emails, analytics People and the membership
directory, Playlists and Pianists use this component. Migrate existing legacy tables when their pages
are updated within the requested scope. Preserve each page’s columns, filters,
route names, session permissions, row actions and bulk behavior.

Omit `title` when the table is the page’s sole purpose, as on Users and Feedback;
the page heading already identifies it. Pass `label` for its accessible name.
Use a visible table title only to distinguish a section on a multi-section page,
such as Membership directory inside Membership analytics.

All admin pagination uses `admin.components.pagination`: Showing 1–12 of N
results stays on the left and the rounded page controls stay on the right. The
active page is dark with white text; inactive controls have a soft white surface.
The footer wraps at narrow widths, retaining those alignments. Pass `paginator`
and an accessible `label`; optional `unit` and `note` preserve list-specific copy.
Preserve the paginator's query parameters, page name and fragment. The table
component includes this footer automatically; standalone admin lists use it too.
The Design System renders this same footer with inert preview buttons.

`resources/sass/admin/_pagination.scss` owns the shared finish, scoped to admin.
Retained DataTables use it through the `admin-table-fields.js` adapter: keep their
original info/pager nodes and handlers, reuse an existing Bootstrap footer row or
wrap the plain renderer's nodes. Default summary copy and chevrons match native
tables; explicitly configured translations/labels remain supported. Keep loading
placeholders and redraw behavior intact. Do not restore local pagination styles.

## Component usage

The component owns the rounded card, heading, shared filter form, table headers,
scrolling region, empty state, result summary and pagination. The default slot
contains only trusted Blade-rendered `<tr>` rows. Cell values use escaped Blade
expressions; do not pass submitted HTML as the slot.

```blade
@component('admin.components.table', [
    'id' => 'accounts-table', 'title' => 'Accounts', 'items' => $accounts,
    'description' => number_format($accounts->total()).' matching accounts',
    'action' => route('admin.users.index'), 'state' => $filters,
    'search' => ['label' => 'Find an account', 'placeholder' => 'Name or email'],
    'sorts' => ['name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A'],
    'columns' => [
        ['label' => 'Account', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
        'Status', ['label' => 'Actions', 'hidden' => true],
    ],
    'emptyTitle' => 'No matching accounts',
    'emptyDescription' => 'Try another search.',
])
    @foreach($accounts as $account)
    <tr>
        <td><span class="admin-table__primary">{{ $account->full_name }}</span><small>{{ $account->email }}</small></td>
        <td>{{ $account->status_label }}</td>
        <td>@include('admin.pages.example.actions', ['item' => $account])</td>
    </tr>
    @endforeach
@endcomponent
```

`items` accepts a Laravel length-aware paginator or a collection. A small fixed
collection needs only `id`, `title`, `items`, `columns` and its row slot; it gets
no form or pager unless controls are explicitly supplied. Empty tables render
one full-width empty state and never invent placeholder rows.

Optional parameters:

- `filters`: select definitions with `name`, `label` and an `options` map. The
  selected value comes from `state[name]`. See Feedback’s rating selector.
- `sorts`: allowed sort values and display labels for the Sort by selector.
  A column’s optional `sort` map supplies ascending/descending header links;
  the active header exposes `aria-sort`. Both controls use the same sort keys.
- `search`: label, placeholder and optional `maxlength` (255 by default).
- `pageSizes`: defaults to `AdminPagination::PAGE_SIZES` (12, 24, 48, 96).
  Set it to `[]` for reports that retain a fixed page size. It is omitted when
  the whole result fits the default page. Only enable it if the query validates
  and applies `per_page`.
- `hidden`: known extra form query values, useful for independent page filters.
  Changing filters or sorting resets this list’s page parameter; paginator
  links retain applied filters. Sort links preserve other query parameters.
- `showReset` and `resetUrl`: override automatic reset visibility/destination.
- `filterAction`: an optional form action with a section fragment, for a directory
  inside a report. Keep `action` without a fragment for sortable header URLs.
- `emptyAction`: an optional trusted `url` / `label` map for the shared empty-state
  button, such as First page or Show all composers.
- `tableClass` and `class`: page-specific table/card hooks. Keep special cell
  sizing in scoped Sass rather than duplicating the shared structure.
- `label`: accessible table/filter/scroll-region name, defaulting to the title.
- Named `filterFields` and `filterNote` slots: optional advanced controls and
  supporting copy inside the same shared GET filter form. Use the shared form
  components for those controls.
- Named `toolbar` slot: optional bulk actions or supporting content inside the
  card and outside the GET filter form. Keep POST forms out of the GET form.

## One shared filter form

Every admin table/list filter form uses `admin.components.filters.form`. The
shared table delegates to it; standalone analytics, People, visit sorting,
folder directories and catalogue growth use it directly. The composer directory
also uses the shared table. Retained legacy filter partials delegate to this
same form; the alternate membership template delegates to the current view.

Pass a unique `id`, accessible `label`, optional GET `action`, `state`, and
`fields`. Each field has `name`, `label`, and either `options` (a value/label map)
or `type: search` / `type: text`; search fields may set `placeholder` and
`maxlength`. Numeric fields use `type: number` with optional `min`, `max`, `step`
and `required`; `max` remains a numeric upper bound rather than a text length.
Optional `buttons` definitions use the shared `filters.button` and may pass `id`
for an existing JavaScript binding;
the default is Apply filters. A submit button can retain `name` and `value`
(for example, visit order). `secondary: true` is the consistent Reverse/Clear
variant. Use `resetUrl` for the shared Reset link, the default slot for advanced
widgets, and the `note` slot for supporting copy. Avoid page-specific classes
that redefine fields or buttons. `preview: true` renders a non-submitting div
and type=button actions for Design System specimens.

`filters.hidden` recursively preserves scalar and array query values with
escaped names/values. Drop only the relevant list's page key before passing
`hidden`. Visit sorting preserves multi-platform selections, dates, tab and
other list pages; only the clicked submitter sends `visit_order`, so Apply and
Reverse cannot conflict with a hidden order value. Standard checkbox/radio
filter choices use `components.form.checkbox`, also shown in the Design System.

The shared form owns wrapping, spacing, labels, search width and responsive
layout in `admin/_filter-controls.scss`. `@input`, `@select` and `.form-control`
own field appearance; custom date/multiple-choice triggers use the same native
select arrow mixin and field tokens. All Apply actions share the same button
component and sizing. Page-header actions and primary filter actions both use
`.btn.btn-dark`; secondary filters use `.btn.btn-secondary`. Filter Sass owns
only layout/field alignment, so hover, keyboard focus, pressed and disabled
colors all come from the shared button variant. Remove old local filter styles when migrating consumers.

The Timeline events curation library retains its discovery cards and chronological
decade disclosures. Discovery uses this same filter form and its numeric year
field; shared editor sections contain discovery and saved records. Event editors
use shared inputs/textarea, primary Save changes, black source actions and the red
trash action. The event-specific validation bag restores and opens only the form
that failed. Discovery cursors, selected types, saved counts, attribution and
original session-protected request contracts remain intact; this update does not
turn the grouped curation workflow into a paginated directory.

The Playlists directory uses SQL search, publication filters, validated sorting
and the shared 12-result pagination. Journey is absent from this directory,
creation form and ordering screen; retain its records and legacy mobile API.
Arrange order uses the same table component with the complete regular-playlist
collection because saving requires a full permutation. Drag handles and labeled
up/down buttons change the draft order; the shared save footer submits it
explicitly. The server locks the group and rejects incomplete or stale lists.
Ordering this collection must never alter Journey or a playlist's piece order.
New playlists start as drafts and open their existing editor to add pieces.

The playlist editor follows the Pieces editor's shared sections for metadata,
artwork and publication status. Its selected-piece table is a complete curation
list because one save submits the entire selection in order; do not paginate it.
Drag handles and accessible up/down controls update the draft, while Remove
only removes a piece from that draft. The Add pieces modal uses the same table,
filters and native pagination with `PlaylistPieceFinder`: SQL name/composer/
catalogue/tag/ID search, composer/level filters, validated sorts and 12 results
by default. AJAX browsing preserves unsaved metadata, selection and order; added
pieces remain visibly disabled across result changes. The shared empty-row
partial and save footer cover empty playlists and explicit saving. Validation
restores the submitted selection, including a cleared playlist. All changes
apply together through the existing transactional update and featured-tag
compatibility service. Legacy DataTables requests remain supported separately;
the redesigned editor does not load or initialize DataTables.

The Pianists directory uses `Services/Admin/PianistsTable` for SQL name/nationality
search, nationality filtering, fixed sort keys and shared pagination. Portraits,
life dates and Apple Music linkage make profiles recognizable; profile previews
open on the public website. Edit links and the editor breadcrumb retain the
directory's filters and page. Creation opens the saved profile's editor; deletion
returns to the filtered directory and clamps an emptied last page. Shared editor
sections and form fields serve the add/edit flow, including native date fields,
an optional artist ID and portrait upload. Failed creation reopens its own dialog
with the draft and field errors. Existing creator/manager permissions remain in
force; legacy model serialization and public/mobile routes are unchanged.

## Table action buttons

The Tags directory uses `TagsTable` for SQL name/type search, a fixed type filter,
validated sorts, piece counts and shared 12-result pagination. `tag_type` is the
directory query parameter; `type` remains the tag form field, so a changed tag
cannot overwrite the active directory filter. Load creator names and count the
piece relation without hydrating all linked pieces. Create and Edit share one
modal with labeled shared fields and an optional display order. Validation
reopens the failed draft using the route-bound tag ID flashed by the server,
including when that tag is outside the current results. Delete has its own
confirmation with the affected piece count; tag removal and pivot detachment
are transactional. Writes preserve directory state and clamp its page. Legacy
mobile tag serialization and the separate `order` field remain unchanged.

Use `admin.components.table-action` for row actions and destructive confirmation
buttons. The Users directory is the visual reference: `kind: edit` renders the
shared small yellow button with the square-pen icon and Edit label; `kind: delete`
renders a red trash button; all other actions use the black button. Icon-only
buttons keep a descriptive accessible name and title. Delete buttons default to
icon-only; set `iconOnly: false` for a visible label or confirmation. Place the
Pieces Delete control immediately after Edit, before its other actions.

Pass one `tableAction` array: optional `kind`, `url` for a link, `type` for a
button (default button), `label`, `icon`, `iconOnly`, `class`, and trusted
`attributes`. Attribute values are escaped, true renders a boolean attribute,
and false/null omit it. Preserve each action's form association, method, CSRF,
modal target, data attributes, disabled state, route and permissions. Keep
POST/PATCH/DELETE actions in their existing forms or confirmation dialogs;
button standardization does not authorize adding mutations or changing flows.
New-tab links retain `rel="noopener noreferrer"`. The legacy
`components.datatable.actions.*` renderers delegate to this same component;
legacy tables keep their existing loaders and scripts.

The Design System's Table actions specimen uses the component in inert button
form. Use existing Bootstrap/PianoLIT button tokens; no per-table color, sizing
or radius overrides. This action standard does not restyle status controls,
filters, pagination or native details/disclosure controls.

## Queries and navigation

Use `App\Support\AdminTableFilters::fromRequest` to validate `search`, `sort`,
`per_page` and `page`, with a fixed server-owned sort map and any extra filter
rules. It returns defaults and trimmed search text. `AdminTableFilters::query`
omits default values and the first page when generating canonical return URLs.
Never treat submitted column names, directions or model names as SQL fields.
Escape LIKE wildcards when search is intended to match literal text.

Search, filters and ordering must apply to the entire database query **before**
`paginate`, with a deterministic tie-breaker. Use `AdminPagination::PER_PAGE`
(12) by default. Load only the displayed page and necessary relationships;
render model fields directly without serializing full User models or triggering
activity appends. Keep browser routes on the admin session guard.

Filters submit GET forms and pagination/header sorting use ordinary links. Each
response contains the complete table, so no DataTables initialization, Ajax
endpoint, loader, CDN stylesheet or JavaScript dependency is needed. URLs are
bookmarkable, Back/Forward work normally, and the table works without JavaScript.
Do not add a skeleton to this synchronous flow. If a future task explicitly
adds asynchronous updates, handle loading, stale requests, errors, retries and
keyboard focus once in a shared enhancement.

Feedback deletion keeps its rating, search, sort and page size, counts the same
filtered query after deletion, and returns to the last valid matching page.
Delete forms retain CSRF and route-bound ownership. Users retains Edit and
impersonation; Super status is managed inside Edit user. Its directory has no
bulk-selection checkbox or super-user toggle.

Subscribers uses `App\Services\Admin\SubscriptionsTable` for SQL search, fixed
sorts and pagination. Its two directory queries select only displayed fields
and resolve each displayed email's account ID through the indexed Users email
lookup, without serializing users or querying their activity/favorites/billing.
Keep `2026_10_19_180000_add_admin_subscriber_directory_index.php` in deployment
for newest-first date/ID ordering. Email exports retain their existing audiences.
Both single and bulk deletion validate return filters before changing records,
preserve the filtered URL and clamp to its last remaining page. Bulk deletion
validates the complete selection, locks rows in ID order and transactionally
keeps the existing list-detachment events; a missing row or database failure
cannot leave a partially deleted selection.

Use `admin.components.table-bulk-delete` for page-scoped bulk deletion controls,
with the shared checkboxes, buttons and confirmation modal. Pass `id`, `tableId`,
`action` and a trusted plural `label`; associate row checkboxes with
`form="{id}-form"`, `name="ids[]"` and a descriptive accessible name. Load
`js/components/admin-table-bulk-delete.js` once. Select this page never selects
other result pages, and individual deletion never reuses bulk selection. The
Design System uses the same component with `preview: true`, which renders no
submittable form and performs no deletion.

Email Reports uses `App\Services\Admin\EmailReportsTable` for campaign-summary
search, fixed count-based sorts and 12-row pagination. Percentages and underlying
email counts remain visible together. The detail page uses
`EmailReportRecipientsTable` for recipient search, delivery-status filters and
pagination; its independent totals cover the entire mailing, including while a
recipient filter is active. Both recipient rows and detail totals reuse
`ExcludedAccounts` through `EmailLog::byList`. Summary cards count unique email
activity; recipient opens/clicks retain their total event counts. Historical
campaigns remain available after an email list is deleted, including direct
reports whose summary has not been backfilled.

Report deletion locks campaign summaries in a stable order and removes summaries
and their delivery history transactionally. Bulk requests validate every selected
mailing ID before deletion, preserve directory filters and clamp the page after
removal. The shared bulk-delete component accepts `deleteDescription` for the
specific consequence; its subscriber default remains unchanged. Reports supplies
delivery/engagement-history copy, with page-bound native checkboxes and a separate
confirmation form. Report pages no longer initialize DataTables.

The Users query selects only required account fields and batches location and
billing status, preserving super-user overrides without loading their billing.
Its existing search also matches saved country names/codes, region names/codes
and cities. Terms can span account and location fields (for example, a name plus
Denver); every term must match. SQL `EXISTS` keeps ownership and pagination
intact, escapes literal LIKE wildcards, and does not require an account to have a
location. Each account cell shows its saved city, region and country with escaped
text and no repeated identical place names. The separate Locations directory and
legacy log pages were removed on 2026-10-19; current Analytics pages remain.
Keep `2026_10_15_010000_add_admin_user_directory_indexes.php` in deployment: it
supports the default date/ID ordering and membership lookup. Existing index
requirements are unchanged by this table migration.

The Pieces directory uses `PiecesTable` for SQL search, fixed sorts and native
12-row pagination. Missing-video/moments/Synthesia filters combine with AND;
missing moments requires a performance video without any saved moments. The
missing-content controls are temporary maintenance filters, rendered compactly
with the shared checkbox component and `.small.text-muted` typography utilities.
They remain visible and use the same Apply filters action and query names. Existing
creator/media URL filters remain supported and survive form submissions. Literal
LIKE wildcards are escaped. The former Rankings column displays highlight dates
and is now labeled Highlighted. Favorite counts retain analytics exclusions.
Piece titles open the web app in a new tab, while Edit keeps the admin editor.
Composer cells use `components.composer-label` for a 28px round portrait and name,
with a neutral fallback for missing images. The directory loads `cover_path` in
its existing composer batch. Level labels retain the full early/late wording and
use the existing filled-circle icon with the base level color; missing levels
show Not set without a dot.

Piece titles use the shared `clamp-2` utility with the full title available on
hover. The Tags column and its visible sort choices were removed on October 19;
levels display their names as plain text and retain difficulty sorting. The
index no longer renders or loads quick-edit dialogs/scripts. Existing admin
quick-edit routes and the prior authenticated admin-only Ajax response remain
available for compatibility. The directory's row actions are Edit and confirmed
Delete. Freepick and creation-date actions now live in the piece editor's labeled
Options row, with their existing PATCH routes and CSRF protection. Deletion
validates return filters and clamps the page.
Public/mobile serialization and media delivery remain unchanged.

## Appearance and verification

Shared styling lives in `resources/sass/admin/_tables.scss` and uses PianoLIT
font tokens and the membership directory’s colors, subtle row separators and
spacing. Search and select filters compose the shared `@input` / `@select`
components and default Design System field styles, including borders, size,
focus, disabled and validation states. Keep only width/layout rules in table
styles. Page-specific badges and cells retain their meaning.
Filter action buttons use `admin.components.filters.button` and the shared
`field-control-sizing` mixin, including responsive touch sizing. Do not copy a
filter form or action button into a page; configure the common components.
Wide tables scroll horizontally within the card; do not shrink text to fit a
phone. Tables expand to fit every row and use the page for vertical scrolling.
Never add a fixed/max-height table viewport or independent vertical scrollbar.
Editable tables use `admin.components.table-save-actions` beneath the table: save status on the left, the Save changes button on the right. Keep the button in the component slot and use the shared primary button with a check icon.
The table's accessible name uses `aria-label`; a negatively margined hidden
caption creates vertical overflow inside the horizontal scrolling region.
Button links (`a.btn`) keep their button appearance on hover without a text
underline; ordinary table links retain their link-hover styling.

Rebuild the tracked admin CSS and its Mix manifest hash when shared styles
change. Visually verify desktop/phone layouts, empty results, search, every
supported sort, pagination, retained filter state and row/bulk actions. Use
isolated SQLite fixtures and fake services as described in `AGENTS.md`; never
perform destructive verification against real accounts.

## DataTables awaiting migration

Legacy DataTables search/page-size forms also come from the common form. The
`filters.datatable` partial supplies an inert Blade template for every
`@datatable` / `@datatableRaw`; `admin-table-fields.js` moves the original feature
nodes and input/select elements into that template before the first draw. This
retains DataTables handlers, search, state, requests and vendor row structure,
while using the same labels, fields and Apply button. The standard default and
menu use `AdminPagination`; explicit settings and restored sizes are preserved,
including pages loading a later Bootstrap/DataTables adapter. The initialization
skeleton uses the same form with disabled fields. Keep the adapter shared;
never add per-page filter-generation JavaScript.

Other pages retain the legacy `admin.components.table-card`, standard
`@datatable` layout and `admin.components.datatable-frame`. Keep preloaded first
rows, trusted `initialBody`, `ready`, non-sortable metadata, `deferLoading`, empty
bypass and the existing skeleton/error/retry behavior consistent until each page
is migrated. Do not globally remove the shared DataTables assets while these
pages still depend on them. Do not migrate unrelated pages as part of a narrow
request.


## Composers directory and editor

Composers uses `Services/Admin/ComposersTable` for SQL name/country/nationality
search, period/famous/pedagogical filters, fixed sorts and 12-result pagination.
Rows reuse `components.composer-label`, the shared switch, and Edit/Delete actions.
Names open the profile; piece counts link directly to its Repertoire section.
The editor breadcrumb retains directory filters and page, while repertoire has
its own `pieces_page` paginator and fragment. Profile and Biography use the shared
editor sections and form fields, also reused by the named-error creation modal.
Read-only profiles disable a native fieldset; update/delete policy checks remain
authoritative. New records open the editor after creation.

The Famous switch submits an explicit desired state through the existing PATCH
route with `return_to=directory`; filtered returns clamp to the last valid page.
Legacy requests without that marker retain the existing JSON toggle response.
Deletion uses the shared confirmation with its optional escaped `deleteDescription`
to explain that the composer's assigned pieces are also removed. Piece-title
links open `webapp.pieces.show` in a new tab with `noopener noreferrer`; explicit
Edit links retain the admin editor. Public/mobile models and payloads are unchanged.
