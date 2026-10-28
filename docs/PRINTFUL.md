# Printful shop integration

The initial merchandise range is T-shirts and stickers. PianoLIT has a Manual order platform / API store named **PianoLIT**, ID `18973423`, with website `https://shop.pianolit.com`. Creating this store does not publish a storefront or create products.

## Server connection

`App\Services\Shop\Printful` supports only GET requests for authorized stores, paginated manual-store products, and a product's variants. It uses Printful's v1 Products API (`/store/products`); the ecommerce-platform sync API is a different integration. Reference: [official Printful API documentation](https://developers.printful.com/docs/).

Configuration lives in `config/services.php`:

- `PRINTFUL_STORE_ID`: defaults to the PianoLIT store above
- `PRINTFUL_API_TOKEN`: optional server environment secret
- `PRINTFUL_API_TOKEN_FILE`: defaults to `storage/app/private/printful-api-token`

Prefer a store-scoped private token with only **View store products** (`sync_products/read`) for this development phase. Keep the secret file outside version control with mode `0600`, readable by the application owner. `storage/app/` is already ignored. Do not put tokens in frontend JavaScript, views, URLs, chat, screenshots or tracked files. No `.env` edit is required. Production needs its own operational secret installation; code deployment alone does not copy this local file.

Run `php artisan printful:check` to verify that the token includes the configured store and read its product count. This performs two GET requests and never creates or confirms an order. Redirects are disabled, timeouts are bounded, and errors omit raw upstream responses/credentials.

The **PianoLIT Shop Development** token was created on **October 27, 2026**, scoped to this single store and `sync_products/read`, and expires **October 27, 2027**. Its secret is installed in the ignored private local file above. Replace it before expiration; private tokens cannot be refreshed automatically.

Local verification on October 27, 2026: `printful:check` successfully retrieved store `18973423` and an empty product list (**0 products**). No orders, payments, files or webhook settings were changed. Production secret installation and deployment are still pending.

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

Deploy `2026_10_27_234500_add_deleted_at_to_merchandise_products.php` before this reconciliation code/schedule. It is applied locally only. Arthur's deleted **Piano rainbow T** (`486690181`) was reconciled locally on October 27, 2026: the active catalog is empty and its five image records/files remain retained.

Deploy `2026_10_27_230000_create_merchandise_catalog.php` before the admin feature. It creates only `merchandise_products` and `merchandise_images`; it is applied locally, including a successful local MySQL migration, and is not deployed to production. Ensure the public disk is writable and publicly served via the existing storage link. Original files are retained on normal gallery removal; database rollback does not replace an image with a partial upload. A full feature rollback must preserve/export these two tables and their files before dropping anything.

## Remaining shop work

Create and publish the actual T-shirt/sticker designs and variants in Printful, then import them through admin. The public merchandise storefront, shipping destinations/rates, taxes and checkout remain to be implemented. Retail prices/currencies are now imported from Printful for review. The existing shop homepage and digital-product checkout are unchanged.

Order submission/confirmation, payments, fulfillment and webhooks are not implemented. Add those as a separate change with explicit write scopes, durable order/payment identity, idempotent retries and verified webhook handling. Confirming a Printful order can incur fulfillment charges; connection verification must remain read-only. Existing digital purchases, Stripe memberships and mobile APIs remain independent.
