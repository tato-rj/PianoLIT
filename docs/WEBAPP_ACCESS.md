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
| Open the Score tab | Signup card over the blurred score | Regular piece: subscription message and **Go Premium** button; free pick: no prompt |
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

Blocked collection URLs return to the Collections directory and open the matching modal. Collection totals always count the full playable list (pieces with tutorials), matching directory counts; limited views separately show **Showing 3 of 12 pieces**, for example.

These describe the web interface. Existing public file URLs and iOS behavior are unchanged.

## Deployment

Run `2026_10_16_220000_create_web_access_policies_table.php` before serving this editor or policy. The single database record stores the matrix, revision and last saving admin; an older tab cannot overwrite a newer revision. Saves validate every feature, user type and allowed status together. No shared cache delays policy changes.
