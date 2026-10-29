# Printful shop integration

The initial merchandise range is T-shirts and stickers. PianoLIT has a Manual order platform / API store named **PianoLIT**, ID `18973423`, with website `https://shop.pianolit.com`. Creating this store does not publish a storefront or create products.

## Server connection

`App\Services\Shop\Printful` provides bounded server-only requests. Catalog imports/checks use only GET requests for authorized stores, paginated manual-store products, store variants and blank-catalog metadata; the separate `PrintfulOrders` service adds guarded shipping estimates and paid production order submission. It uses Printful's v1 Products API (`/store/products`) and Catalog API (`/products/{id}`, `/products/variant/{id}`); the ecommerce-platform sync API is a different integration. Reference: [official Printful API documentation](https://developers.printful.com/docs/).

Configuration lives in `config/services.php`:

- `PRINTFUL_STORE_ID`: defaults to the PianoLIT store above
- `PRINTFUL_API_TOKEN`: optional server environment secret
- `PRINTFUL_API_TOKEN_FILE`: defaults to `storage/app/private/printful-api-token`

The installed catalog token has only **View store products** (`sync_products/read`). This is sufficient for imports/checks; production fulfillment additionally requires store-scoped Orders read/write permissions, as described in [Shared paid checkout](SHOP_CHECKOUT.md). Keep the secret file outside version control, readable by the application and scheduler identities. The local file is owner-only (`0600`); production uses a named PHP-worker read ACL with no group/other access. `storage/app/` is already ignored. Do not put tokens in frontend JavaScript, views, URLs, chat, screenshots or tracked files. No `.env` edit is required. Code deployment alone does not copy this secret file.

Run `php artisan printful:check` to verify that the token includes the configured store and read its product count. This performs two GET requests and never creates or confirms an order. Redirects are disabled, timeouts are bounded, and errors omit raw upstream responses/credentials.

The **PianoLIT Shop Development** token was created on **October 27, 2026**, scoped to this single store and `sync_products/read`, and expires **October 27, 2027**. Its secret is installed in the ignored private local file above. Replace it before expiration; private tokens cannot be refreshed automatically.

Local verification on October 27, 2026: `printful:check` successfully retrieved store `18973423` and an empty product list (**0 products**). Production activation completed on October 28, 2026, as recorded below. No orders, payments or webhook settings were changed.

## Administration

Managers can open **Shop → Merchandise**, at `/merchandise` on the admin host. The local address is `http://admin.pianolit.test/merchandise`. Admin session authentication and CSRF protection apply to every write; regular website accounts and editorial admins cannot manage this account-wide catalog.

1. Create a product in Printful, finish its design/colors/sizes and publish it to the **PianoLIT** store. A saved product template alone does not appear in the API store's list.
2. Choose **Import from Printful** in PianoLIT and import the product. Its name and variant IDs, sizes, colors, SKUs, retail prices/currencies and status are stored separately from legacy digital products. Product identity is unique per Printful store/product; retrying the same import opens the existing record.
3. Choose **Use as main**, the earlier/later arrows and **Save description** to curate the gallery. Each confirmed action saves immediately. The main image is independent of gallery order. Removing the main image selects the first remaining image; an empty gallery has no main image.
4. Use **Upload your own images** for downloaded Printful views, custom mockups or photographs. Uploads accept up to ten JPG/PNG/WebP files at a time, ten MB per file, 6,000 pixels per side and 24 megapixels. Images are decoded and re-encoded, preserving transparent sticker backgrounds. There is a forty-image active-gallery limit; identical processed images are deduplicated.
5. **Refresh from Printful** updates product/variant data and adds missing mockups, preserving local descriptions, uploads, gallery order, removed images and the main-image choice. The product thumbnail and variant files explicitly marked `preview` are eligible; original print artwork is never imported as a product photo. Additional lifestyle mockups not exposed by the API can be downloaded from Printful and uploaded here.

Mockups are copied to the public disk under `shop/merchandise/` with generated filenames; the shop uses PianoLIT image URLs. Only HTTPS downloads from the exact `files.cdn.printful.com` / `cdn.printful.com` hosts are accepted. Downloads carry no API credentials, refuse redirects and enforce byte/dimension/time limits. A failed/missing mockup does not discard the product: the editor reports it and allows another refresh/upload. The copy phase has a twelve-second budget; a large gallery may need more than one refresh.

Removed images are soft-deleted and their files retained so **Removed images → Restore image** works and refreshes do not re-add deliberately removed mockups. No purge policy is introduced by this feature. Database writes lock the owning product and check an optimistic revision to reject stale tabs; staged files are discarded if a write fails or rolls back. Image IDs from another product cannot be selected, edited, reordered, removed or restored.

### Products deleted in Printful

Deleting a product in Printful automatically removes it from the active PianoLIT merchandise catalog. The directory checks when it loads, and **Refresh from Printful** on a deleted product returns to the directory with a clear notice. `printful:sync-catalog` also runs every five minutes through the existing Laravel scheduler, with a five-minute overlap lock; background execution requires that scheduler to be running.

`PrintfulCatalogSync` verifies the token's store, reads a complete paginated catalog with stable totals/offsets and unique IDs, and then requires a product-specific HTTP 404 for every missing local product. A 404 from the catalog endpoint, incomplete/changing pages, expired credentials, rate limits, network failures and server errors do not establish deletion. Other stores' products are never retired by this store's scan. Catalog-load failures display the saved local catalog and a retry notice. An emptied final results page is clamped to the remaining last page.

Removal soft-deletes the local product under its row lock and advances its revision; curated metadata, main-image choice and all image records/files remain intact. A confirmed product deletion never calls Printful's DELETE API or removes files. If the same Printful product ID becomes available again, explicit import restores the existing local record and its image choices after a successful provider read. This is separate from order cancellation or purchase-history cleanup; neither is implemented here.

Deploy `2026_10_27_234500_add_deleted_at_to_merchandise_products.php` before this reconciliation code/schedule. It is installed locally and was verified installed in production on October 28, 2026. Arthur's deleted **Piano rainbow T** (`486690181`) was reconciled locally on October 27, 2026: the active catalog is empty and its five image records/files remain retained.

Deploy `2026_10_27_230000_create_merchandise_catalog.php` before the admin feature. It creates only `merchandise_products` and `merchandise_images`; it is installed locally and was verified installed in production on October 28, 2026. Ensure the public disk is writable and publicly served via the existing storage link. Original files are retained on normal gallery removal; database rollback does not replace an image with a partial upload. A full feature rollback must preserve/export these two tables and their files before dropping anything.

### Variant color swatches

Import and Refresh from Printful enrich the saved variant JSON with `catalog_product_id`, `color_code` and `color_code2`. `MerchandiseColors` joins each store variant's `variant_id` to the exact blank-catalog variant, checking the parent product identity. It fetches each distinct catalog product once per import; older responses without the parent ID use an exact variant lookup. It never infers a shade from the color name, a product title or mockup pixels. Printful's documented second color is shown as a two-tone swatch.

Only six-digit hex values are accepted, both at import and again before rendering. Swatches retain their color during selection/hover, while the labeled button keeps the regular selected/focus feedback. Conflicting codes under one shared color name stay neutral. Missing/invalid metadata and catalog failures preserve prior valid codes only when the sync variant ID, catalog variant ID and color name still match. Otherwise the option remains a usable text-only choice. Successful explicit null codes clear the swatch. Catalog 404s never retire a store product. An admin notice asks for a refresh when color metadata could not be retrieved.

Color enrichment is bounded to eight requests and a ten-second start budget, with at most five seconds per request; gallery downloads retain their separate budget. Public browsing reads only the saved data. No additional token scope, database migration or scheduled job is required. After deploying, refresh previously imported products once to populate their colors; subsequent imports/refreshes maintain them. This change does not generate mockups or infer color associations for uploaded images; the curated gallery/main image remain independent.

## Production activation — October 28, 2026

Arthur authorized SSH setup of the token, permissions and scheduled checks. The deployed Printful source files and both migrations match the local implementation at revision `c31e861b`; no tracked server application file was replaced. Both migrations were already installed. The initial production configuration cache had no Printful settings, and the token file was missing, explaining the verification banner.

Installed the existing local token at `/var/www/html/storage/app/private/printful-api-token` and verified an exact secure hash match without displaying its value. Owner `arthur` retains read/write access; a named `www-data` ACL grants read-only access. The private directory grants `www-data` traversal only, without listing/writing or altering other private files. Dedicated `storage/app/public/shop/merchandise` directory ACLs allow PHP-FPM writes and maintenance access. The existing `public/storage` link remains unchanged. Rebuilt the production configuration cache; no `.env` edit or new token scope.

The visible `arthur` crontab has its general Laravel scheduler disabled, and root's full schedule remains unverified. Preserved the complete existing crontab and added one Printful-only entry every five minutes, protected by `flock`. It runs the private owner-only `storage/app/private/run-printful-catalog-sync.sh`: `printful:check`, then `printful:sync-catalog`, then a success heartbeat. Owner-only logs and the heartbeat are `printful-catalog-sync.log` and `printful-catalog-sync.last-success` in the same private directory. The first unattended successful run was observed at **00:00:04 America/New_York on October 28, 2026**. Existing Analytics cron and unrelated billing/email scheduling were preserved; the full Kernel scheduler was not enabled.

A second unattended success was observed at **00:05:02** with the final permissions in place. The final audit confirms one Printful cron entry, unchanged deployed source hashes, no remaining temporary probe/image files and owner-only wrapper/log/heartbeat files.

Verification succeeded from both CLI and the actual PHP-FPM `www-data` worker: the token is readable, store `18973423` is authorized, and the product list contains **0 products**. PHP-FPM wrote a temporary public image and its exact bytes were retrieved over HTTPS through Nginx. A temporary loopback-only health probe and image were removed immediately after verification. Admin login and Shop HTTPS requests return **200**. There were no synthetic products, live transactions, order submissions or emails in these checks.

Private rollback/audit files are under server `storage/app/private/printful-setup-20261028-035817` and local `/Users/arthur/.codex/backups/pianolit/printful-server-setup-20261028`. They include the prior configuration cache, directory ACL and crontab. Preserve subsequent server changes when reverting; remove only the added Printful entry if disabling this scoped job. If a full Laravel scheduler is later confirmed/enabled, consolidate this entry with the Kernel schedule to avoid duplicate API checks. Token rotation is still required before October 27, 2027.

## Paid checkout implementation — October 28, 2026

Merchandise and paid eBooks/eScores now share the full-screen membership checkout shell, secure card fields and capability-dependent Apple Pay/Google Pay component. `ShopCheckout` stores encrypted order snapshots, verifies Stripe payments and reconciles signed webhooks before physical fulfillment or digital delivery. Live merchandise shipping options/destinations come from Printful; Stripe Tax calculates customer tax. Printful draft creation/confirmation uses durable external IDs and is independently restricted to verified paid production orders. Refund/dispute and increased-cost checks hold fulfillment for review.

This is implemented and tested locally, including actual Stripe **test** purchases, with simulated delivery/tax and no Printful requests. Production activation has not been performed: the installed product-read token cannot submit orders. Deploy the new order migration, install shop Stripe keys/webhook secret and an order-capable Printful credential, configure tax/HTTPS wallet domains, and verify minutely order reconciliation before enabling live checkout. The existing scoped catalog cron does not run the new order processor. Managers have a new Shop → Orders directory for review and safe fulfillment retries.

See [Shared paid checkout](SHOP_CHECKOUT.md) for configuration, rollout, recovery, local isolation, evidence and remaining launch checks. Product imports remain manual. Legacy/mobile transaction routes and download contracts remain unchanged.

## Local sample shop and storefront (October 28, 2026)

Arthur requested that development stop using the real Printful store. `Printful::enabled()` unconditionally returns false in the `local` environment, before reading the token or sending HTTP. The optional `PRINTFUL_ENABLED` config flag can also disable the integration elsewhere; its default leaves production enabled. Both CLI checks and the scheduled reconciliation explicitly skip local execution. Existing private credentials are retained but unused; no `.env` change is needed.

Local merchandise storefront/admin queries use reserved store ID `0`, while other environments use the configured real store ID. Existing real-store records are preserved and hidden locally. Production can never publish sample records through these queries, even if development rows were accidentally copied.

Run `php artisan shop:seed-demo` locally to add six sample products: four colored Piano rainbow T-shirts and two stickers. It refuses non-local environments, makes no network calls and skips existing samples (including retired ones), preserving gallery curation. The command was run on the local database on October 28. The shirt fixtures reuse Arthur’s previously imported mockups; the two native SVG sticker illustrations are fictitious sample artwork. Trusted bundled fixture SVGs do not change the admin’s raster-only upload rules. No sample data or code was deployed to production.

The bundled samples include offline color metadata in the same fields used by real imports; those fixture shades are sample values, not live API verification. `shop:seed-demo --refresh-colors` updates only matching bundled sample variants in store `0`, preserving names, prices, images and curation. It is repeat-safe and refuses production. This was run locally on October 28 to update the existing samples; Printful remained disconnected.

Open `http://shop.pianolit.test` to browse/search/filter, select sizes, navigate galleries and add/update/remove bag items. The local admin Merchandise page identifies the samples and hides Printful import/refresh actions; image uploads and curation still work. Checkout stays unavailable; no payment or order is submitted.

The bag stores only local product ID, variant ID and quantity in the browser session. Each display/write re-resolves active products, available synced variants and current imported prices. It caps each option at ten and the bag at forty options, rejects mixed currencies and excludes retired/unavailable items. Same-host CSRF-protected writes use session locks. It is separate from digital purchases, coupons and payment routes. Images follow the curated main-image choice and gallery order; there is no automatic image-to-color mapping.

The merchandise product button shows `In bag (N) · Add more` for the exact selected color/size already in the current session's bag. Other options show `Add to bag`. Render initial counts from the validated bag contents and update them only from confirmed add responses; a delayed response must update its original product/variant even if the visitor changes their selection while waiting. The button still adds the chosen quantity, preserving the existing limits and native form fallback.

Editing map:

- `resources/views/shop/storefront/{index,show,bag,layout}.blade.php`: catalog, product details, shopping bag and shell; Details is always visible directly on the page, without a disclosure, border, radius or inset padding
- `resources/views/shop/storefront/components/`: shared header, product card/category row, gallery, price, Details, option and quantity controls; digital/merchandise action partials and digital content sections
- `resources/sass/storefront.scss`: scoped borders, colors and responsive layout, without flat offset shadows or pressed-depth movement; shared PianoLIT font families/sizes remain authoritative
- `resources/js/views/storefront.js`: progressive option selection, gallery navigation, quantity controls and add-to-bag feedback; native forms work without JavaScript
- `app/Services/Shop/{Storefront,ProductPage,ProductNames,MerchandiseBag,MerchandiseColors}.php`: public catalog/product-page projections, session bag and provider color enrichment/validation
- `app/Console/Commands/SeedDemoMerchandise.php` and `resources/images/shop/demo/`: local-only fixtures

The Shop root combines imported merchandise with published eBooks/eScores. All Products places T-shirts in the left two desktop columns and Stickers in the right two, followed by full-width eScores and then eBooks sections; unknown merchandise remains visible afterward. Each section is a single horizontal row with previous/next arrows beside its heading. Shirts/stickers show two cards at a time; full-width sections show four on desktop. Phone rows show two cards and sections stack. Native scrolling, touch, keyboard navigation and reduced-motion preferences are supported; buttons disable at the boundaries. Category pages show every matching product in the usual four/two-column grid without pagination. Only All has small section labels; filtered pages use their category heading, and product cards omit repeated category labels. Digital cards use existing mockups (cover fallback), final discounted prices and canonical product links; drafts remain hidden. Paid digital purchases use the shared checkout modal when configured; free/member/owned actions and existing download endpoints remain intact. They are not added to the merchandise bag. The header has only Bag and the shared account icon/menu beside the Shop brand, without separate catalog navigation links.

The user explicitly removed the yellow decorative star. Keep it absent. Shop’s header uses the standard hub logo size and plain Shop text with no green highlight; the shared public content wrapper keeps catalog, product and bag widths aligned with the other hubs. Category filters use the imported provider product name (retained on new imports/refreshes) and explicit legacy product names; unknown types remain under All. No API requests run during storefront browsing. No newsletter launch announcement is registered while merchandise checkout is still under development.

Every product detail route now uses `shop.storefront.show` and `ProductPage`, including eBooks/eScores at their existing URLs. The common gallery, price label, title and unboxed Details are identical components. Product-specific partials provide merchandise options or existing digital buy/download actions, plus digital descriptions, topics, reviews, previews/audio and related products. Digital payment and download behavior remains separate from the merchandise bag; no model/mobile serialization changed.

Category navigation uses real `/t-shirts`, `/stickers`, `/ebooks` and `/escores` pages with category breadcrumbs. Search/topic filters keep that category path. Merchandise cards and bag links use `/{category}/{slug}`; unclassified merchandise uses `/merchandise/{slug}`. Digital product URLs and checkout paths are preserved. Old numeric product URLs and root category-query links permanently redirect with other raw query parameters intact.

`2026_10_28_160000_add_merchandise_product_slugs.php` adds and backfills persisted merchandise slugs with a unique store/slug index. Existing legacy collisions receive deterministic numeric suffixes; names, IDs, images and curation are untouched. Retired slugs remain reserved. New imports reject duplicate product names/slug conflicts before copying images; digital admin creation and updates use the same validation. Provider renames retain the merchandise permalink. Admin binding and bag writes continue using numeric IDs.

Deployment requires the existing merchandise migrations plus the new slug migration, matching source/assets and the versioned Mix manifest. Ship the migration and import/controller changes together before resuming product imports. The new migration has been applied locally; run it on the server when deploying. No token, scope or cron change is needed. Deploy application source through Git; do not run the local seed command on production.


## Shipment notifications and customer order tracking

The signed Printful shipment handler, private tracking pages and two merchandise email templates are implemented locally. Printful v2 `shipment_sent`/`shipment_delivered` callbacks authenticate the raw request, then re-read the real order/package details; the unsigned v1 `package_shipped` event shown in the original diagram is not accepted by this new endpoint. Each shipped package has its own tracking link and one shipping email; purchase confirmation is separate and created after verified Stripe payment. A periodic read-only check recovers missed updates. Account → Track your orders lives on main at `/profile/orders`, alongside the separate My downloads link; email links also support guests without a shopping app.

Production callback registration is **not active** in this workspace rollout. The production callback path returned 404 during the October 28 check. Deploy the additional tracking migration/code, configure the token/scoped signed callback/private key read permissions and verify the two added schedules before expecting real shipment emails. Local Printful/network/email guards remain in place. Full details and editable email paths are in [Shared paid checkout](SHOP_CHECKOUT.md#merchandise-emails-and-customer-tracking).
