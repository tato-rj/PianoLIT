# Digital product editors

The eBook and eScore edit pages share `admin/pages/digital/edit` and the merchandise editor's section, dropzone, field and icon-action components. Product details retain title, author/composer, subtitle, page count, price, discount, topics and the rich description. PDF and EPUB uploads remain for eBooks; PDF and audio uploads remain for eScores.

Public product-information columns also use the merchandise pattern: title and
reviews, price, compact purchase/download and optional audio actions, then the
shared minimal accordions. About contains the saved subtitle, editorial HTML and
topic links once. Details contains file formats, page count, author/composer,
publication date and optional sample availability; Membership appears only when
the existing eligibility notice renders content. The digital access note follows
the accordions. Existing download ownership, membership eligibility, checkout,
audio source and playback behavior are retained. No editorial database text or
mobile/API fields are changed by this web presentation update.

## Display artwork

`Shop\DigitalMedia` stores ordered display images, the main image and recoverable removals in `shop_digital_media`, keyed by the fixed eBook/eScore class and product ID. The same ID in another product table is a different owner. No relationship or new attribute is attached to either shared model. The first edit starts with the saved mockup and cover, without modifying those legacy fields. Once initialized, an empty gallery remains empty and cannot fall back to removed artwork.

Upload up to ten JPG/PNG/WebP images at once, each at most 10 MB, 6,000 pixels per side and 24 megapixels. There may be forty active images. Uploads reuse the existing raster decoder/re-encoder; no provider request is made. The list has 48px thumbnails, earlier/later arrows, a main-image star and recoverable removal. The public gallery starts with the selected main image. Catalog cards, related cards, checkout, Shop promotions and purchase confirmations use this web artwork. Historical order snapshots and legacy/mobile cover/mockup serialization remain unchanged.

The existing separate `shop_product_closeups` service supplies the optional **Hover image**, with digital-specific labels and guidance for an open book or styled view. Merchandise retains **Hover close-up**. The upload, revision and hover interaction behavior is shared.

That saved image also appears as the last selectable image in the public detail
gallery. `Shop\ProductPage` validates the existing owned file through `Closeups`
and appends a web-only detail-image entry, preserving the saved main image and
gallery order. eBooks/eScores include it without color filtering. Merchandise
binds it to the initial main gallery color and hides it when another color is
selected. Missing or removed hover files are omitted. Upload ownership, source
files, card hover behavior and legacy/mobile serialization remain unchanged.

## PDF samples

`Shop\DigitalPreview` invokes Poppler's `pdftoppm` with an argument array, a 90-second timeout, pages 1–7 only and a maximum 1,600-pixel image dimension. It renders the first page and up to six more pages to independent JPEGs. Shorter PDFs produce fewer images. Work files stay in a private temporary directory and are removed after success or failure.

A new PDF upload must render successfully before committing details, files, topic changes and preview metadata together. **Generate preview** creates the first sample from an existing saved PDF; **Regenerate preview** replaces it. The storefront's existing Look inside book viewer receives only generated image URLs. Legacy manually uploaded preview arrays remain unchanged for existing callers and are not used by the new storefront preview. Generation never happens on public GET requests.

### Minimal book viewer (November 6)

The product gallery opens this viewer through a 56px circular secondary button with the open-book icon and shared shadow, inset 16px from the artwork's lower-right corner. `shop.components.preview.trigger` supplies the same launcher in the gallery and Design System. The former full-width Look inside button and visible preview-page caption are removed; the button retains its accessible name and page-count description. Clicking the artwork still opens the same viewer, and gallery thumbnails remain separate.

Look inside uses the existing local Turn.js 4.1.0 library in a transparent `components.dialogs.fullscreen.panel`. The book floats over the Bootstrap backdrop without a white panel, heading, visible page counter, thumbnail strip, zoom button or instructions. Only the shared floating close control and two small page arrows remain. Each 44px arrow button uses zero padding and flex centering around a 24px chevron. The counter and product title are retained for assistive technology. The shared error alert and retry action appear only when an image fails.

Wide screens open to pages 2–3 when available, with the cover reachable by turning back. Narrow screens and one-page samples use a single page. Dimensions come from the first preview image and available viewport, preserving the page ratio. Corner dragging, Left/Right keys and the two arrows use the same Turn.js controller; dismissal stops an active turn, reopening retains the current page, and reduced motion uses zero transition duration. A readable single-page fallback remains if Turn.js cannot initialize. PDF rendering, the seven-page preview boundary, original download URLs and legacy/mobile fields are unchanged.

`shop.components.preview.magazine`, `components/_book-preview.scss` and `views/shop-product.js` also supply the Design System's Book preview specimen. `transparent: true` is opt-in on the fullscreen panel; existing full-screen dialogs retain their white surfaces. See the upstream [Turn.js display documentation](https://www.turnjs.com/docs/Method%3A_display) and [size documentation](https://www.turnjs.com/docs/Method%3A_size).

Original PDFs remain on the existing storage/delivery path. This does not add protection to original media URLs. Old download files are retained when replacing a PDF or EPUB, preserving existing encrypted download links. New staged files are removed on failed rendering, validation or transaction rollback. Previously published sample images remain available for cached pages.

All editor writes run through admin routes with session authentication and CSRF. The owning product is locked before checking the media revision and writing. Stale saves cannot replace a newer gallery, PDF or preview. Topic lists and file formats are validated; image actions resolve IDs from that product's saved gallery rather than accepting file paths.

## Deployment

1. Install Poppler (`pdftoppm`) on the application host and make it executable by the PHP worker. The default command is `pdftoppm`; `/usr/local/bin`, `/usr/bin`, `/bin` and `/opt/homebrew/bin` are also searched, including when PHP-FPM clears `PATH`. `SHOP_PDFTOPPM` can provide a custom executable path. No setting is needed with a standard installation. Configuration caches created before this feature also use the default command.
2. Run `2026_11_06_120000_create_shop_digital_media.php` before activating the matching code. The migration creates a separate table and does not rewrite any product or legacy media fields.
3. Deploy the code and views together, then refresh compiled views through the normal deployment process. No frontend asset changes, provider changes, scheduler or queue job are required.
4. Generate a PDF preview for each existing product in its editor. New PDF uploads generate the preview as part of saving. Missing or unreadable PDFs show a validation error and preserve the saved state.

The migration and renderer are installed locally. Both existing local products have generated previews (one page for the eBook's one-page PDF; seven pages for the eScore). The editor is also deployed in production as of November 6.

## November 6 preview repair

Production's configuration cache predates `digital-media.php`, so the original renderer lookup passed `null` to Symfony's executable finder. Poppler was already installed at `/usr/bin/pdftoppm`, and the Halloween PDF was readable and 1,271,206 bytes. The repair supplies the default command for missing cached configuration, supports absolute executable paths and includes Linux executable locations when the worker has no `PATH`.

Failures now identify the failed stage (source, size, renderer, temporary workspace, rendering or saved images). Logs record only the stage, exception class and process exit code. The editor uses the existing shared page alert; no second page-level error summary repeats it. Focused isolated tests pass (15 tests / 167 assertions).

Repair `70131a1df` was deployed through Git on November 6, preserving unrelated server changes and the existing configuration cache. The authenticated production Generate preview action succeeded for Halloween: the cover and six further pages are visible with one shared success notification. No original PDF, product details, environment settings or provider state were changed. Proof: `/Users/arthur/.codex/visualizations/2026/11/06/01a1b19d-9332-7293-aac3-dd63af437c66/pdf-preview-production-fixed.jpg`.
