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
- **Coverage gauges** divide profiles with at least one piece, a nonblank biography,
  or a nonblank portrait path by all current composers. Text/path presence is not
  an editorial-quality, image-load or file-health check. Gauges link to the missing
  records in the directory. The categories overlap and are never stacked.
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
  of piece tags. All composers and With pieces use the same unit; the latter is
  a subset, shown alongside its parent rather than stacked. Unknown/custom period
  values remain visible. Gender doughnuts count profiles equally and retain
  missing/custom values. Neither chart weights composers by repertoire size.
- **Countries** uses each composer's saved country's normalized flag code, not IP
  location, listener geography, or nationality inferred from biography. Counts are
  per profile, including composers without pieces. Percentages divide by profiles
  with a recognized country. Missing/invalid countries remain explicit. The map
  reuses the local SVG geometry, pastel rankings and selection/zoom controls from
  the other analytics pages; small territories can appear in the list without a
  separate shape. No Google chart loader or map API key is used.

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
period and profile-gap filters, and allowlisted sorting. Filters affect only the
directory; every overview chart retains the whole catalogue scope. Pagination
uses `AdminPagination::PER_PAGE` (12), retains filters/sorting, and offers recovery
from an out-of-range page. Composer names and Edit controls lead to the existing
composer editor. All database-provided text is escaped in Blade.

## Implementation and delivery

`App\Analytics\ComposerReport` makes three aggregate/scalar SQL reads regardless
of composer count. It groups pieces, views and saves independently, preventing
join multiplication. It reads the scalar composer catalogue for chart aggregation
and in-memory directory filtering/pagination; it does not hydrate User/Piece
models, biographies, image data or model appended relationships. This constant
query count is not a production MySQL timing/scale guarantee. Revisit SQL-side
directory pagination if the composer catalogue becomes substantially larger.

Ship the report/controller, composer Blade templates, scoped Sass and shared map
selector addition, built admin CSS and its Mix manifest entry together. The map
script is reused unchanged. Refresh deployed view caches normally. No migration,
new dependency, mobile update, account write, media delivery or API change is needed.

Verification uses `tests/Review/ComposerAnalyticsTest.php`, isolated SQLite sample
data and browser previews. Check the dated `CODE_REVIEW.md` entry for the final
results and broader suite limitations.
