# Digital product editors

The eBook and eScore edit pages share `admin/pages/digital/edit` and the merchandise editor's section, dropzone, field and icon-action components. Product details retain title, author/composer, subtitle, page count, price, discount, topics and the rich description. PDF and EPUB uploads remain for eBooks; PDF and audio uploads remain for eScores.

## Display artwork

`Shop\DigitalMedia` stores ordered display images, the main image and recoverable removals in `shop_digital_media`, keyed by the fixed eBook/eScore class and product ID. The same ID in another product table is a different owner. No relationship or new attribute is attached to either shared model. The first edit starts with the saved mockup and cover, without modifying those legacy fields. Once initialized, an empty gallery remains empty and cannot fall back to removed artwork.

Upload up to ten JPG/PNG/WebP images at once, each at most 10 MB, 6,000 pixels per side and 24 megapixels. There may be forty active images. Uploads reuse the existing raster decoder/re-encoder; no provider request is made. The list has 48px thumbnails, earlier/later arrows, a main-image star and recoverable removal. The public gallery starts with the selected main image. Catalog cards, related cards, checkout, Shop promotions and purchase confirmations use this web artwork. Historical order snapshots and legacy/mobile cover/mockup serialization remain unchanged.

The existing separate `shop_product_closeups` service supplies the optional **Hover image**, with digital-specific labels and guidance for an open book or styled view. Merchandise retains **Hover close-up**. The upload, revision and hover interaction behavior is shared.

## PDF samples

`Shop\DigitalPreview` invokes Poppler's `pdftoppm` with an argument array, a 90-second timeout, pages 1–7 only and a maximum 1,600-pixel image dimension. It renders the first page and up to six more pages to independent JPEGs. Shorter PDFs produce fewer images. Work files stay in a private temporary directory and are removed after success or failure.

A new PDF upload must render successfully before committing details, files, topic changes and preview metadata together. **Generate preview** creates the first sample from an existing saved PDF; **Regenerate preview** replaces it. The storefront's existing Look inside book viewer receives only generated image URLs. Legacy manually uploaded preview arrays remain unchanged for existing callers and are not used by the new storefront preview. Generation never happens on public GET requests.

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

Failures now identify the failed stage (source, size, renderer, temporary workspace, rendering or saved images). Logs record only the stage, exception class and process exit code. The editor uses the existing shared page alert; no second page-level error summary repeats it. Focused isolated tests pass (15 tests / 167 assertions). Live verification of the repair is pending.
