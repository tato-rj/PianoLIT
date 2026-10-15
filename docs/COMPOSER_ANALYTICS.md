# Composer analytics

The existing admin `/analytics/composers` page keeps `admin.analytics.composers`
and the admin session guard. It combines a current catalogue snapshot with legacy
piece-view records and current saved favorites. It does not query activity-event
history, change collection, or reconstruct deleted records.

## Definitions

- **Composers** counts all current composer profiles, including profiles with no
  pieces. **Linked pieces** counts existing pieces whose composer still exists.
  Orphan pieces are outside this page. The concentration figure divides pieces
  belonging to the five largest composer catalogues (or all, if fewer) by linked
  pieces. **Small catalogues** have 1–3 pieces; the four depth groups (0, 1–3,
  4–10, 11+) partition the full composer total.
- **Composers added by month** counts retained composer profiles created during
  the last 12 calendar months through the current moment. Zero months remain
  visible, the current month is partial, and missing/future timestamps are omitted.
  It is not a lifetime history of additions and deletions. The area chart has a
  zero baseline and a count scale; the expandable table gives all exact values.
- **Coverage gauges** divide distinct composers represented by at least one qualifying
  piece by all current composers, including composers with no pieces. A composer
  counts once per gauge, regardless of how many pieces, videos, folders or playlists
  contain them. The measures overlap and are never stacked. Each gauge links to
  the composers missing that coverage in the directory.
  - **With videos** requires a piece with at least one tutorial/video record having
    a nonblank `video_url`, matching the piece analytics video definition. Blank
    links, missing pieces and orphan composer IDs do not count. Links are not
    checked for file availability.
  - **In users’ folders** matches custom-folder analytics: a non-default folder with
    an existing owner, excluding team accounts and normalized `ExcludedAccounts`
    emails. The favorite must belong to that same folder owner and reference an
    existing piece. Standalone/default-folder favorites and missing/foreign-owned
    folders do not qualify. This differs from the broader Saves ranking below.
  - **In playlists** means representation in the pieces actually displayed by a
    playlist visible on the Collections page. It mirrors the existing page rules:
    no group, published through the current instant, more than five existing pieces,
    and at least five pieces with tutorial records. Only its pieces with tutorial
    records qualify, matching the collection player. Drafts, future publication,
    grouped journeys and insufficient collections do not count. Tutorial presence
    governs the existing Collections rules; the separate video gauge requires a
    saved video URL. Reading analytics does not call the collection service or
    populate its weekly selection cache.
- **Views** counts retained `piece_views` rows on existing linked pieces, including
  repeated views/tabs, with normalized `ExcludedAccounts` IDs omitted. It does not
  count unique people, media starts or all SQL activity events. Legacy identity
  cannot be verified retrospectively, including deleted owners' old view rows;
  these analytics identities never authorize anything.
- **Saves** counts distinct `(user_id, piece_id)` pairs among current favorites on
  existing linked pieces with an existing account. The same account saving the
  piece in multiple/default/custom folders counts once. Standalone favorites are
  included. Missing accounts, missing pieces and normalized `ExcludedAccounts`
  emails are omitted. **Accounts** counts distinct saving users per composer.
  Different pieces saved by the same user count as different saves, but that user
  counts once in the composer's account total. Team status is not an additional
  filter here, matching the piece analytics exclusion scope.
- Interest rankings show up to six composers each. Catalogue-size context includes
  views or saves per piece. **Small catalogues with saves** shows up to five
  composers with 1–3 pieces and at least one current save, ordered by saves per
  piece. This is an editorial starting point, not evidence of unmet demand or
  a conversion rate. Small samples are explicit. Numeric ties use stable IDs.
- **Composers by period** uses normalized composer profile values, independently
  of piece tags. All composers and With videos use the same unit; the latter counts
  composers with at least one piece with a nonblank video URL, using the same
  definition as the video gauge. It is a subset shown alongside its parent rather
  than stacked. The period video counts reconcile to the video gauge numerator. Unknown/custom period
  values remain visible. Gender doughnuts count profiles equally and retain
  missing/custom values. Neither chart weights composers by repertoire size.
- **Countries** counts saved country records and displays their saved names,
  independently of the map lookup. A linked country counts even if its flag code
  is blank, nonstandard or absent from map metadata. Only a missing/deleted linked
  country is treated as unrecorded, including by the directory's country filter.
  Recognized country codes group their records as before; countries with
  unrecognized codes remain separate, keyed by their saved country record.
  Percentages divide by all composers with a linked country. Each country's full
  count remains in the ranking, selector and table, including countries without
  geometry. The map separately reports composers whose saved countries have no
  matching shape, without calling them missing countries. No country code is
  guessed or silently assigned to a neighboring country. This uses saved profiles,
  not IP locations or inferred nationality; no country records are changed.

## Planning and directory

Upcoming dates cover today through 29 days from today, ordered chronologically
across year boundaries. The card shows the next six, with the full upcoming list
available when needed. Birthdays and death anniversaries include their year count
and available piece count. Invalid/future dates and the existing approximate birth
exception (`c. 1729`) are not scheduled. A death before a known exact birth is
invalid. February 29 is scheduled only in leap years, rather than silently moved
to another date. The directory retains saved date labels and displays lifespan
at death or age *if living*; a missing death date is not evidence someone is alive.

The directory supports literal case-insensitive name search, catalogue-size,
period, profile-gap and coverage-gap filters, and allowlisted sorting. Filters
affect only the directory; every overview chart retains the whole catalogue scope. Pagination
uses `AdminPagination::PER_PAGE` (12), retains filters/sorting, and offers recovery
from an out-of-range page. Composer names and Edit controls lead to the existing
composer editor. The table omits the profile-completeness badge column; the
existing profile-gap filter remains available. All database-provided text is
escaped in Blade.

## Implementation and delivery

`App\Analytics\ComposerReport` makes three aggregate/scalar SQL reads regardless
of composer count. It aggregates video, custom-folder and visible-collection
membership inside the composer read, and groups pieces, views and saves independently, preventing
join multiplication. It reads the scalar composer catalogue for chart aggregation
and in-memory directory filtering/pagination; it does not hydrate User/Piece
models, biographies, image data or model appended relationships. This constant
query count is not a production MySQL timing/scale guarantee. Revisit SQL-side
directory pagination if the composer catalogue becomes substantially larger.

The gauge replacement changes the report and composer Blade templates only; it
reuses the existing styles, icons and map script without an asset rebuild. For the
initial redesign, ship the report/controller, composer templates, scoped Sass and
shared map selector addition, built admin CSS and its Mix manifest entry together.
The map script is reused unchanged. Refresh deployed view caches normally. No migration,
new dependency, mobile update, account write, media delivery or API change is needed.

Verification uses `tests/Review/ComposerAnalyticsTest.php`, isolated SQLite sample
data and browser previews. Check the dated `CODE_REVIEW.md` entry for the final
results and broader suite limitations.
