# Great Pianists

The public directory and profiles live on Learn at `/great-pianists` and
`/great-pianists/{slug}`, preserving the `resources.pianists.*` route names.
Both use the public shell, breadcrumb, editorial layout and existing shared
card promotions. The directory uses the hub-header newsletter action; profiles
use the shared newsletter invitation.

## Editing

- `resources/views/pianists/index.blade.php`: directory header, search/country form, count, cards, pagination and empty states
- `resources/views/pianists/card.blade.php`: the shared resource card used in the directory and More pianists
- `resources/views/pianists/show.blade.php`: portrait, nationality, stored life dates, escaped biography paragraphs, recordings and more artists
- `resources/views/pianists/album.blade.php`: native recording-card template, cloned and populated by the browser
- `resources/js/views/pianists.js`: the existing Apple lookup source with loading, empty, failure/retry and pending-request states
- `resources/sass/pages/_pianists.scss`: profile and compact album layout, scoped to `.pianist-page`

The resource card's `resource-card__art--portrait` image variant uses a 4:3 crop
with faces positioned toward the top. It shares the white card, typography, border,
radius and lift with other Learn resources and has a Design System specimen.
Original portrait paths are preserved. Directory cards without artwork use
Learn's existing editable pianist SVG.

## Directory and profiles

Search runs in SQL against the name and saved country/nationality, with escaped
literal search terms. The native GET form also filters by country; its choices
only include countries represented in the directory. Results stay alphabetical,
12 per page, with both filters retained in pagination. The search input retains
`search-pianist`; loading all profiles into the browser for client filtering is
no longer required. Shared fields/buttons provide the control styling.

Profiles display the existing stored biography and available birth/death values.
Missing dates/nationality are omitted; a missing death date does not create a
living/deceased label or an inferred age. Biography text remains escaped, with
paragraph breaks. Profile metadata uses the record's creation/update timestamps
rather than a fixed historical date. More pianists links to up to three other
existing profiles in alphabetical order, without implying a curated relationship.

## Recordings

A profile with a positive numeric saved Apple artist ID loads the same
`https://itunes.apple.com/lookup` endpoint with `entity=album` and `limit=200`.
The public loader follows the admin editor's established JSONP transport and
15-second timeout. Each card shows the returned title, artwork, release year and
track count when present, and opens the existing Apple destination in a new tab.
The shared Apple Music artwork identifies the source. Profiles without a usable
ID show a neutral unavailable state and make no lookup request.

The loader filters actual album entries instead of removing the first response
row, deduplicates returned collection IDs, and reports only the number shown.
The 200-result request limit does not establish a complete catalog total.
Provider text is inserted with `textContent`; album links accept HTTPS destinations
on `music.apple.com`/`itunes.apple.com` and artwork accepts HTTPS `mzstatic.com`
hosts. Invalid destinations are omitted and missing/untrusted artwork is hidden.
External links retain `noopener noreferrer`. Missing metadata stays absent.
Failed or malformed responses expose Try again; pending requests cannot overlap.

Public routes, original media paths, administrator actions, artist records,
shared Person/Pianist serialization and native/mobile contracts are unchanged.
There is no migration, new email, paid access rule or billing/provider mutation.
Legacy public profile URLs continue through the existing Learn redirects with
raw query strings intact.

## Verification

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'PianistPagesTest|LearnSubdomainTest|AdminPianistsTest|AdminDesignSystemTest|QuizPagesTest'
node tests/js/pianists.test.cjs
npm run production
npm run test:build
git diff --check
```

The isolated PHP tests cover combined search/country pagination, literal search,
missing values, safe biography rendering, metadata/media bindings, distinct profile
links and unchanged model data/JSON. Existing Learn/admin tests cover canonical
and legacy routing and retained editing behavior. The JavaScript regression checks
provider filtering, text/link handling, metadata, deduplication, missing IDs,
loading/failure/retry, pending guards and truthful counts.

`PIANISTS_PREVIEW_DIR` optionally exports fictional HTML fixtures from
`PianistPagesTest`. The normal local directory had no profiles during review, so
browser checks used isolated sample profiles, a neutral illustrative portrait,
existing sample artwork and intercepted Apple responses. Desktop/390px layouts,
loaded images, external link attributes and failure/retry were verified without
horizontal overflow or browser errors. No real profiles or albums were created
or changed, and no live Apple catalog availability or deployed rendering is claimed.
