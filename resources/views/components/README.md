# Finding Blade components

This folder contains components shared across the website, hubs, web app and administration. Open the folder for the kind of thing you want to edit. Components belonging to one feature live beside that feature's pages, as listed below.

## Shared components

| To edit | Open |
| --- | --- |
| Account circle and menu, signup cards and access prompts | [account/](account/) |
| Membership plans and shared merchandise/digital/membership checkout, wallets and card fields | [billing/](billing/) |
| PianoLIT logo, browser icons/splash metadata and project watermark | [branding/](branding/) |
| Resource cards, promotional cards and related-content suggestions | [cards/](cards/) |
| Standard modals, fullscreen dialogs and bottom sheets | [dialogs/](dialogs/) |
| Alerts, error messages, loading animation and reading progress | [feedback/](feedback/) |
| Inputs, selects, switches, buttons, choice grids, uploads and segmented controls | [forms/](forms/) |
| Country flags and Font Awesome brand icons | [icons/](icons/) |
| Public header/footer, editorial/catalog layouts, headings and accordions | [layout/](layout/) |
| Shared privacy policy and terms text | [legal/](legal/) |
| Video player, YouTube invitation, social sharing script and link-sharing dialog | [media/](media/) |
| Piano keyboard and composer labels | [music/](music/) |
| Breadcrumbs, hub menu, tabs, settings links, section/topic menus, tags and pagination | [navigation/](navigation/) |
| PianoLIT News invitation, subscribe button and signup form | [newsletter/](newsletter/) |
| Editorial ads, app download links, download offers and promotional popups | [promotions/](promotions/) |
| Free Pick signup form and shared subscription hidden fields | [subscriptions/](subscriptions/) |
| Shared simple table and load-more control | [tables/](tables/) |
| Browser activity script and page token | [tracking/](tracking/) |

Frequently edited files:

- Public header and footer: `layout/public/header.blade.php`, `layout/public/footer.blade.php`
- Homepage Youtube button: `navigation/youtube-button.blade.php` (also previewed in the Design System)
- Public content inset, matching the logo/menu edges: `layout/public/content.blade.php` (used by `layouts.app`; homepage cards keep their wider layout)
- Hub introduction: `layout/hub-header.blade.php`
- Public editorial page columns: `layout/editorial.blade.php`
- Resource cards and sidebar promotion cards: `cards/resource.blade.php`, `cards/promotion.blade.php`
- Inputs and switches: `forms/input.blade.php`, `forms/toggle.blade.php`
- File upload areas: `forms/upload/dropzone.blade.php` uses the standard field finish for drop/click selection, selected filenames and multipart submission; `forms/upload/file.blade.php` retains the plain native picker. The Design System previews both.
- Circular play/start control: `media/play-button.blade.php`, using the shared primary button finish and responsive circular sizing; also previewed in the Design System. Pass `label`, optional `id`/`modalId`/`descriptionId`, and `data`/`labelData` maps for the calling controller. `caption => true` displays that same accessible label below the circle in bold uppercase white text for dark media covers; leave room beneath the button for it. `stretched => true` uses Bootstrap’s stretched-link hit area to make the nearest positioned container clickable through the same native button, without adding another focus target.
- Link-sharing modal: `media/link-share.blade.php` and its reusable `media/link-share-options.blade.php` body, `resources/js/components/link-share.js` and `resources/sass/components/_link-share.scss`; the Design System renders the same dialog. The entire readonly URL field and copy icon copy the canonical link, with a brief Bootstrap Copied! tooltip. Pass `useOldInput => false` for this generated URL.
- Profile/Support section menu: `navigation/section-navigation.blade.php`
- Discover and piece recommendation buttons: `navigation/pill-tabs.blade.php`, sharing `.pill-filters` and native Bootstrap tab behavior
- Row-end View all links: `navigation/rail-view-all.blade.php`, used after cards inside the piece recommendation rails
- Accordions, including Support's `variant => minimal`: `layout/accordion.blade.php`, `layout/accordion-item.blade.php` and `resources/sass/components/_accordion.scss`
- Account prompts: `account/access-modal.blade.php`, `account/signup-card.blade.php`
- Account and Game player dropdown surface: `account/menu-panel.blade.php`
- Web-app logo header: `webapp/layouts/header.blade.php` (under `resources/views/`). Its bottom account tab lives in `webapp/layouts/menu.blade.php`, reusing `layouts/menu/user.blade.php`, the `webapp-navigation` variant of `account/account-circle.blade.php`, `account/menu.blade.php` and `navigation/hub-menu/`; other sites keep their header control. At every viewport width, the tab stays in place and the panel reuses the same `account/account-circle.blade.php` close state as public pages (`closeControl => true`) with offcanvas dismissal in a stationary corner host. Its 44px circle, 21px neutral X, white surface, thin border and shadow have one shared style in `_account-circle.scss`
- Newsletter popup: `promotions/popups/newsletter-subscription.blade.php`

## Components belonging to a section

These paths are relative to `resources/views/`:

| Section | Location |
| --- | --- |
| Homepage project gallery and project card | [home/components/](../home/components/) |
| Metronome sound menu and voice recorder | [metronome/components/](../metronome/components/) |
| Admin tables, filters and page controls | [admin/components/](../admin/components/) |
| Admin legacy DataTables | [admin/components/datatables/](../admin/components/datatables/) |
| Admin notification panel and rows | [admin/components/notifications/](../admin/components/notifications/) |
| Admin sortable playlist/piece row | [admin/components/sortable-item.blade.php](../admin/components/sortable-item.blade.php) |
| Web-app controls and piece player/editor | [webapp/components/](../webapp/components/), [webapp/piece/components/](../webapp/piece/components/) |
| Shop product, checkout and review details | [shop/components/](../shop/components/) |
| Game controls and results | [games/challenges/components/](../games/challenges/components/) |
| Email HTML/text components | [emails/components/](../emails/components/) — see the [email editing guide](../emails/README.md) |

## Adding or finding a component

- Keep the shared folder's root free of Blade files. Pick the nearest category above; put a component used by only one feature beside that feature. A Design System preview alone does not make a feature component shared.
- Name files after what they render. Keep related parts together, such as `dialogs/bottom-sheet/{panel,header,handle}.blade.php`.
- Use dotted Blade names: `@include('components.layout.public.header')` or `@component('components.dialogs.modal', [...])`.
- Existing shortcuts such as `@input`, `@select`, `@toggle`, `@modal`, `@brandIcon` and `@datatable` remain available. Their destinations are registered in `app/Providers/BladeServiceProvider.php`.
- Check the Design System before adding a control. It renders these same components.
- Before deleting a file, check direct includes, Blade shortcuts, view composers, controller responses and dynamic selectors. Ads, app links, tags and promotional popups choose some of their children dynamically. Popup request names such as `gift`, `ebook` and `newsletter-subscription` are independent of the folder path and must stay compatible.

The October 26 reorganization changes view locations only. Stylesheets, scripts, public asset URLs, form actions and mobile responses keep their existing contracts. Deploy the moved views, providers and callers together, then clear/rebuild the compiled Blade view cache and reload long-running workers through the normal release process.

Shop reuses `layout/public/header` through its `navigationBeforeAccount` slot for the Bag link (with `navigationLabel` for the accessible nav name). `layout/hub-header` supports a heading without a description and an optional `spacing` utility when placed beside search; its default Learn/Blog layout is unchanged. Shop controls use the shared button/field classes and border/radius utilities, with only layout/media rules in `storefront.scss`.
