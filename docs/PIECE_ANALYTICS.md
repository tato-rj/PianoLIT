# Repertoire analytics

The admin Pieces analytics page is a snapshot of the current catalogue. Its existing
admin route and session guard remain in place. It does not change pieces, access
rules, mobile responses, media delivery or tracking.

The page moves from totals and growth to media coverage, classification, composer
geography/concentration, engagement and classification gaps. It uses local SVG/HTML
charts and the existing country-map geometry/script, without another chart library
or external chart requests. All exact counts are readable without hover; growth
and country charts also expose full data tables.

## Definitions

- **Catalogue totals:** all current piece rows. Represented composers count only
  existing composer records linked to a piece. Missing media counts each piece
  without all three of score, full audio and video once, even if it lacks several.
  The fixed weekly free-pick count is not shown as a catalogue metric.
- **Growth:** retained pieces grouped by their creation month, with a baseline
  before the selected 12/24/36-month window and zero-filled months. The current
  month ends at the request time. Future and missing creation timestamps are
  omitted. Deleted pieces cannot be reconstructed from the current table.
- **Media:** nonblank full-audio paths, score paths or external score links, and
  at least one tutorial with a nonblank video URL. All three means those three
  conditions hold on the same piece. Duplicate videos do not inflate the count.
  The report does not verify local-file existence or remote URL availability.
- **Level / period / length:** each piece gets one category from the matching tag
  type. Main level tags include their early/late sublevels; a sublevel without a
  main level remains missing. Tag names are trimmed and case-normalized. Duplicate
  names count once; multiple different names get a separate Multiple category.
  Unrecognized names are retained, rather than dropped. Missing categories stay
  visible. Periods come from piece tags, not composer metadata. The heatmap uses
  these same categories and counts every piece once across its cells.
- **Composer countries:** counts pieces by the saved country/flag code on their
  composer record. This is not an audience location or nationality analysis.
  Country shares use pieces with a usable country as the denominator. Unknown
  codes, missing countries and missing composers remain in the uncovered count.
  Territories missing from the simplified map still appear in the list/table.
- **Composer ranking / gender:** weighted by each composer's piece count.
  Composer shares use the full catalogue denominator; missing gender remains a
  category. Composer rankings are compact top-eight lists, not paginated tables.
- **Engagement rankings:** Most viewed pieces defaults to All views, which sums
  web and iOS views per piece before selecting the top eight. Use Platform to
  see only Web or iOS. Web views come from saved Google Analytics counts on
  `my.pianolit.com` since July 26, 2023. Most favorited pieces ranks current
  favorites. Ties use piece ID. iOS views and favorites use the shared account
  exclusions.
- **iOS views:** the chart uses the original `piece_views` records through
  the last recorded view on June 7, 2023 at 14:55:25 New York time. After that
  it reads retained app requests to
  `/api/pieces/find`, including the imported Redis requests. Each eligible
  request for a current piece counts once. Requests were logged before the
  response, so they can include retries and cannot establish that the screen
  rendered. Authenticated v2 confirmed-open requests contribute separate
  `piece_opened` events starting with deployment. This uses the existing
  activity table and takes effect on deployment without an import, migration
  or scheduled job.
- **Classification review:** missing/conflicting main levels and periods, plus
  missing composer records. These issue counts can overlap.

The old free-pick lifetime-view line was removed because it implied a chronological
comparison without feature-period attribution. The ethnicity pie was removed from
this catalogue overview. Level/period composition, composer gender and the separate
views/favorites rankings remain, with explicit denominators and missing metadata.

## Implementation and verification

`App\Analytics\PieceReport` uses 12 bounded database queries regardless of the number
of pieces. It aggregates classifications and media with SQL rather than hydrating
the Piece model and its appended attributes. No shared response cache is added.
The prior admin AJAX chart payloads remain available through the existing factory;
the redesigned page does not depend on them. No new migration is required.

Run the isolated PieceAnalyticsTest, AdminPageHeadersTest and UserCountriesTest for
counts, conflicts, date boundaries, exclusions, stable ranking limits, validation,
escaping and admin authorization. Production builds must include admin CSS, the
shared map script and their manifest entries. Verify responsive charts, keyboard
scrolling and both piece/user map labels with real rendered Blade fixtures. SQLite
tests do not establish production MySQL performance.
