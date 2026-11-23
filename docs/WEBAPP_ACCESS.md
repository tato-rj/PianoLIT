# Web app access

Initial defaults: **16 October 2026**, for `my.*`. Registered user means a signed-in account **without active membership**. Free pick means the current weekly selection.

Live settings: **Settings → Access Limits** (`/access-limits` on the admin subdomain). Click a pill, choose a status, then **Save changes**. The saved database policy applies on the next web request; this guide records the initial defaults. Browsing repertoire and opening piece pages are always available.

**Available** = full access. **Blocked** = no access (scores show a blurred first page). **Limited** is offered only for media (10 seconds), search (3 results), collections (first 3 pieces per collection), favorite folders (2 folders), and pieces per folder (5). Folder limits prevent new additions above the cap; existing items stay saved and visible.

Account-owned features stay blocked for guests. Annotation access also requires score-reading access. Trials and eligible cancellation grace periods use the paying-member column; super users always have full access.

## What each user can access

| Feature | Guest | Registered user | Paying member |
| --- | --- | --- | --- |
| Search results | First 3 only | All | All |
| Favorite folders | No; heart stays visible | All | All |
| Pieces per favorite folder | No | All | All |
| Collections | Blocked | First 3 pieces per collection | All |
| Personal history and suggestions | No | Yes | Yes |
| **Free pick:** performance video | 10-second preview | Full | Full |
| **Free pick:** Synthesia | 10-second preview | Full | Full |
| **Free pick:** score | Blurred first page | Full | Full |
| **Free pick:** annotations | Blocked; controls visible | Read, add and save | Read, add and save |
| **Regular pieces:** performance video / Synthesia | Blocked immediately | 10-second preview | Full |
| **Regular pieces:** score | Blurred first page | Blurred first page | Full |
| **Regular pieces:** annotations | Blocked; controls visible | Blocked; controls visible | Read, add and save |
| Audio, including collection playback | Free pick: 10 seconds; regular: blocked immediately | Free pick: full; regular: 10 seconds | Full |
| Download & print scores, including free pick | No | No | Yes |
| Create eScores from collections / personal folders | No | No | Yes |

Score features apply to available public-domain PDFs. Active trials, eligible cancellation grace periods and super users receive member access. An expired membership returns to registered-user access.

Annotations stay saved after access changes or the free pick rotates. They return when the saved policy grants access again, provided the score PDF is unchanged.

## When signup or Premium prompts appear

These triggers follow the saved status. Guests see signup; registered nonmembers see Premium when membership unlocks the feature. A feature blocked for members shows an unavailable message. Members never receive an upgrade pitch for an administratively blocked feature.

| Moment | Guest | Registered user |
| --- | --- | --- |
| Open the Score tab | Friendly text and **Sign up free** over the blurred score | Regular piece: friendly text and **Go Premium**; free pick: no prompt |
| Click the score overlay's main button | **Sign up free** opens registration directly | **Go Premium** opens the membership dialog |
| Click score page, zoom or fullscreen controls | Score signup dialog | Regular piece: Premium dialog; free pick: works |
| Click any annotation tool, including color, thickness, undo, redo or clear | Annotation signup dialog | Regular piece: Premium dialog; free pick: works |
| Click Download or Print, including on the free pick | Signup dialog explaining membership is needed for exports | Premium dialog |
| Click Listen in the score toolbar | Follows the audio status: blocked opens signup; limited plays up to 10 seconds | Follows the saved audio status |
| Try to play or seek a regular-piece video / Synthesia | Video signup dialog immediately | Premium dialog at the 10-second limit |
| Reach the free-pick performance, Synthesia or audio limit | Watching/listening signup dialog at 10 seconds | No prompt |
| Play or seek regular-piece audio, on a piece or collection | Audio signup dialog immediately | Premium dialog at the 10-second limit |
| Click a favorite heart or Save to favorites, including collection tracks and mobile options | Favorites signup dialog | Saves / opens folder controls |
| Click Create eScore | Collection: eScore signup dialog; personal folders require login | Premium dialog in collections and folders |
| View limited or blocked search results | Signup invitation below the results / lock message | Premium invitation if membership gives full results |
| Click a blocked collection | Collections signup modal; stay on the current page | Collections Premium / unavailable modal; stay on the current page |
| Open a limited collection | If enabled: first 3 pieces | First 3 pieces, with the full collection total and a Premium invitation when more pieces exist |
| Add a folder or save a piece at a folder/piece cap | Signup required | Premium dialog; no partial save |

The defaults make signup unlock full free-pick media, its score and annotations, favorites, full search and a preview of each collection. Downloads, printing and full regular-piece access require membership. Guest copy follows the saved policy when these defaults change.

All signup/Premium dialogs share a concise body title, user/crown icon and a green-check list of at most six actual upgrades from the saved matrix. Signup always says **Sign up free**, emphasizes that the account is free, and prioritizes search, favorites, the weekly free pick and its annotations. Premium highlights full regular-piece media/scores/annotations, download/print, complete collections and eScores; expanded favorite limits appear when membership actually increases them. Features already included in the current tier are not advertised as new upgrades. A Premium title matches the triggering action, including watching/listening, score tools, exports and favorite limits. The blurred score itself uses only a friendly sentence and one button, without an embedded card or checklist. Its guest button opens registration directly; its Premium button opens the score upgrade dialog.

Blocked collection URLs return to the Collections directory and open the matching modal. Collection totals always count the full playable list (pieces with tutorials), matching directory counts; limited views separately show **Showing 3 of 12 pieces**, for example.

## Piece score files

On the admin host, Repertoire, piece editors and collection editors link to an admin-session preview. Previously copied `/storage/app/score/…` links on admin also check that session. A signed-in customer without an admin session gets the admin login prompt.

On the web-app host, the score reader checks the saved **Read the score** permission on each PDF request, including range requests. Download and print use the separate **Download & print** permission. Restricted readers receive a JPEG of page one with a maximum dimension of 64 pixels. Find your match receives only the center strip of page one through a signed excerpt URL. The preview uses Poppler and the tour crop uses GD. Generated images are cached for one day by file identity and size. A missing or unreadable source cannot fall back to the original. Full PDFs use private, no-store responses. Markings still use the original score identity.

The legacy iOS app requests original files from the main site without a per-file credential. Anyone with a legacy delivery URL can still fetch it. The protection applies to the browser subdomains. Audio and video continue to follow their existing delivery rules.

### Activate the Nginx score rule

Deploy the matching application revision and versioned JavaScript, then install a root-owned copy of `support/nginx/browser-score-files.conf` in the Nginx snippets directory and include it at **server** scope in the active Nginx configuration serving the browser hubs. The rule passes the original request URI to the existing PHP front controller before static-file handling. It matches admin, my, shop, learn, games, blog and metronome. The main site and www keep static delivery. Preserve the existing FastCGI `REQUEST_URI` setting. No migration is required.

Validate with `nginx -t`, reload Nginx and invalidate any existing shared-cache copies of those browser-host score paths if a cache is present. Verify an existing admin score in a fresh signed-out session and again as an admin. On my, verify the raw path returns 404, a restricted piece preview returns a tiny JPEG, and an entitled account can read the PDF. Check GET, HEAD and a byte range on the main host using an existing legacy score URL. The legacy response should still contain the original PDF bytes. Local Valet, the PHP development router and Apache have matching guards in the repository. 
Production activated revision `bae29a370` and the root-owned Nginx snippet on November 22, 2026. Nginx passed configuration validation and reloaded. The seven original browser vhost files are backed up under `/var/backups/pianolit-score-7sf91eil` on the server. Signed-out GET, HEAD and range requests to the reported admin PDF redirected to admin login. The web-app raw path returned 404, the protected reader returned 403, and the preview returned a 47 × 64 JPEG. The tour returned a cropped 1,237 × 495 JPEG. The legacy origin served the same 397,253 bytes as the stored PDF, with matching SHA-256 and a successful 206 range response. Shipped iOS device playback was not exercised.

## Deployment

Run `2026_10_16_220000_create_web_access_policies_table.php` before serving this editor or policy. The single database record stores the matrix, revision and last saving admin; an older tab cannot overwrite a newer revision. Saves validate every feature, user type and allowed status together. No shared cache delays policy changes.
