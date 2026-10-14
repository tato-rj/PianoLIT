# Favorite folder analytics

The existing admin `/analytics/favorites` page reports current, user-created custom
folders. It keeps the admin session guard and `admin.analytics.favorites` route.
The legacy AJAX DataTables response remains available; the redesigned page uses
server-rendered cards and ordinary GET links/forms without a new JavaScript dependency.

## Scope and definitions

- Default folders are omitted, following the former `userCreated` list. Existing
  `User::team()` accounts and normalized `ExcludedAccounts` emails are excluded from
  all summary, trend, ranking, directory and contents queries. Folders with missing
  owners are omitted. No account, folder or favorite records are changed.
- **Custom folders** counts retained custom folder rows. **Users with folders** counts
  their distinct owners. Adoption divides those owners by all current registered
  accounts after the same team/account exclusions, including accounts without folders.
- **Pieces in folders** counts distinct existing pieces within each custom folder.
  A piece saved in two folders counts twice. Standalone favorites and favorites in
  default folders are outside this report. Legacy favorites whose `user_id` differs
  from the folder's owner, and favorites whose piece no longer exists, are omitted.
- **Average folder size** divides pieces in folders by all custom folders, including
  empty folders. The filled gauge divides folders containing pieces by all custom
  folders. The five size groups (0, 1, 2–5, 6–10, 11+) partition the same folder total.
  All percentages have a zero-safe denominator.
- **New folders by month** groups retained folders by creation date over the last
  12 calendar months through the current moment. Zero months remain visible; the
  current month is partial. Unknown/future timestamps are omitted from the trend.
  Deleted folders and lifetime create/save/delete actions are not reconstructed.
- **Most saved in folders** ranks five pieces by distinct custom folder count, with
  distinct owner count alongside. **Users with the most folders** ranks five owners
  by folder count, then piece placements. Ties use stable IDs.
- Each folder card reports its distinct existing composer count. Missing composer
  records are not invented and appear as `Composer unavailable` in contents/rankings.
  **Latest saved piece** is the latest creation timestamp among its remaining valid
  favorites, not a reliable last-use/last-edit timestamp. Removing that favorite can
  move the date backward. Contents use original folder order and each piece's first
  retained save timestamp.

## Exploration and pagination

Search matches a literal folder name, account email or all words in the owner's
first/last name; `%`, `_` and `!` are escaped. Inputs and sort/size keys are validated.
Directory filters never change overview totals/charts/rankings. Clicking a size keeps
search and sorting while resetting the directory page and selected contents.

The directory uses `page`; selected folder contents use independent `pieces_page`.
Both use `AdminPagination::PER_PAGE` (12). Opening/closing a folder retains directory
filters and page. Switching folders resets the contents page. Excluded/default/
missing folders return 404; out-of-range pages offer recovery links. Empty folders
remain inspectable. Owner links point to existing admin account pages. Piece viewing links open
`webapp.pieces.show` in a new tab with `target="_blank"` and
`rel="noopener noreferrer"`, following the shared admin viewing-link convention.

## Verification and delivery

`tests/Review/FolderAnalyticsTest.php` covers metrics, exclusions, legacy bad ownership,
orphans, dates, literal search, size groups, ordering, stable independent pagination,
HTML escaping, empty/out-of-range states, admin authorization, input validation and
legacy AJAX shape. The report performs eight queries regardless of directory fixture
size, and returns bounded lists without hydrating User/Piece models or their appended
analytics/relationship attributes. This does not establish production MySQL query
plans or timing.

Ship `FolderReport`, the existing controller change, three folder Blade templates,
scoped Sass, generated Lucide icons, built admin CSS and its manifest entry together.
Refresh deployment view caches normally. No migration, account write, logging change,
API change, mobile update or media-delivery change is required.
