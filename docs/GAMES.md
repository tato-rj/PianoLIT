# PianoLIT Games

The public hub is `games.pianolit.com` (`games.pianolit.test` locally), keeping `games.home` and the existing homepage/menu destinations. Arthur confirmed that only the plural `games.*` address is used; there is no singular-host alias or redirect. Unknown game paths return 404. Shared account/profile routes keep their existing precedence.

## Reference and presentation

The eleven public games were copied from the local ArthurVillar checkout's Theory project on October 26, 2026 (source HEAD `ea87224a`) and compared with `https://theory.arthurvillar.com`. No reference database records or player identities were imported.

- Intervals Lab, Chords Lab, Pitch Detective, Chord Detective, Tone Trek, Note Python, Keys Lab, Note Nest, Note Match, Memory Wizard and Beat Hero keep their browser engines, material, bonus scoring, audio, settings, animations, 3D buttons, result variants, avatars, leaderboard podiums and browser-local records.
- The hub uses PianoLIT's public shell, breadcrumb, hub heading/newsletter button and footer. Individual game pages use `games.challenges.layout`, with no public header/footer/breadcrumb; their close/back controls return to the games hub.
- The copied skin is scoped to `.music-games` in `resources/sass/musicgames.scss`; its editable source is under `resources/sass/games/reference`. Outside the game boundary, controls and typography remain PianoLIT's. Game typography is Nunito, music symbols use Noto Music, and original Font Awesome artwork is retained with the generic `data-preserve-icons` boundary in the existing icon hydrator. This explicit reference-copy request is the exception to the normal shared control finish.
- Templates live in `resources/views/games/challenges`, PHP settings in `app/Games`, browser code in `resources/js/games`, and local artwork in `resources/images/games`. Mix versions `css/musicgames.css` and the twelve `js/games` entry points. Only game pages load the game assets.
- Local-storage keys start with `pianolit.games.`. The avatar menu's Clear records action clears only that browser's game profile/settings; it does not delete public leaderboard submissions. It does not affect PianoLIT accounts, metronome preferences or the original Theory website.
- Browser checks exposed a stale Note Match call to a removed superclass method. Its copied engine now uses Note Nest's current target-name contract and seeded clef selection. Replay URLs also normalize the reference empty-array sentinel before server validation; settings forms render validated factory values rather than raw query arrays.
- The original site's hidden Open Staff sandbox and its unrelated administration/offline/runtime features are not introduced here. The retired public Staff Generator and other retired learning pages remain absent.

## Storage and identity

`2026_10_26_180000_create_game_tables.php` creates four new, independent tables:

| Table | Contents |
| --- | --- |
| `game_scores` | Public chosen name/avatar, game, score, accuracy, completed rounds, duration and calculated final points; indexes for game/rank and game/date filtering |
| `game_duels` | UUID room, immutable game/settings per run, random seed, revision, lifecycle timestamps, historical code and unique nullable live invitation code |
| `game_duel_players` | One unique host/guest per room, SHA-256 participant-secret hash, connection marker, readiness/presence, monotonic progress, private checkpoint, result, rematch acceptance and short-lived answer feedback |
| `game_duel_archives` | Finished-match snapshot before a rematch resets the live room, omitting participant secrets and connection identifiers |

Like the reference, games and duels support anonymous browser players; a PianoLIT account is not required. A cryptographically random participant secret stays in the server-side web session. Neither submitted `user_id`, a room UUID nor the four-digit invitation code establishes participant identity. All duel mutations use same-host web sessions, CSRF and rate limits. Room creation/join serialize on the session; room updates use transactions and database row locks, with unique keys arbitrating code collisions and the guest claim.

Leaderboard submissions are explicitly public pseudonyms and avatars, separate from authenticated account data. Validate game names against the registry, avatar URLs against the bundled set, and numeric/result/settings shapes. Calculate final points on the server using the reference formula. Returning from submission restores that run's validated settings on its own game URL, without trusting an arbitrary Referer/return URL. Week/all-time rankings remain independent per game.

Browser answer checking and score generation retain the reference's trust model: plausible client scores/completions can still be fabricated by a modified client. This is not server-verified competition or an anti-cheat system. There are no prizes, billing operations, emails, mobile API changes or imported scores.

## Multiplayer on Laravel 8

The reference uses Laravel 12/Reverb. PianoLIT retains its existing Laravel 8/PHP/Node/Mix stack. `DuelTransport` uses same-origin authenticated HTTP snapshots approximately every 800 ms, with bounded requests, backoff/reconnect, server-time offset measurement, feedback deduplication and cancellation. No WebSocket server, Echo/Pusher packages, broker credentials or framework upgrade are required.

The original lobby/game flow remains: create a four-digit invitation, join, both players ready, shared server countdown/seed, opponent progress and answer feedback, reconnect/refresh checkpoints, first-finisher waiting screen, final comparison and mutual rematch. Finished rematches archive the previous run before resetting. Delayed gameplay/rematch requests carrying an old seed cannot mutate the new run. Presence uses 15-second heartbeats, a 30-second departure grace and a 120-second missing-heartbeat timeout; idle prompts/leave behavior are retained. Two normal tabs in the same browser session are the same participant, so test opponents with separate browser profiles/devices/sessions.

Answer feedback expires from snapshots after three seconds and affects neither progress nor scores. Only participants receive room state; checkpoints are returned only to their owner. Duel infrastructure routes omit ordinary request analytics to avoid turning polling/heartbeats into thousands of activity rows. The hub and game HTML/browser activity retain the standard SQL tracking, campaign rules and `ExcludedAccounts` behavior. All legacy APIs, media routes and model serialization remain unchanged.

## Deployment

The additive migration was applied only to the guarded local development database. No production deployment or source-site data migration was performed.

1. Back up the target database and deploy the scoped migration before serving the matching game code:

   ```sh
   php artisan migrate --force --path=database/migrations/2026_10_26_180000_create_game_tables.php
   ```

2. Deploy the PHP/views, built game/main assets and manifest together. Use `npm ci` and `npm run production` with the pinned Node 24 toolchain when building. The six new browser/build libraries are pinned in the lockfile; existing dependencies/framework pins remain intact. The original skin currently produces Sass legacy warnings; main app/admin/shared Sass checks remain warning-free.
3. Point `games.*` DNS/TLS/vhosts at this same Laravel public directory. HTTPS is needed for microphone-based games. Fonts and Tone 14.8.49 retain the reference's HTTPS CDN delivery; allow them in any deployed CSP.
4. Use server-side file/database/Redis sessions. Shared sessions remain opt-in under the existing rules; keep `SESSION_DOMAIN` unset. All app servers must share the configured session/lock stores. Do not change to cookie-only sessions. There is no Reverb setup.
5. Keep the ordinary Laravel scheduler running. `games:cleanup-duels` runs every minute without overlap to close abandoned/expired rooms and release live invitation codes. It does not delete completed matches or unrelated records. No queue worker is required for games.
6. Recheck two independent browsers over production-like HTTPS/MySQL before public release, including refresh/reconnect, simultaneous joins, microphone permission, mobile/background behavior and mutual rematches. The October launch entry is `games-2026` in `config/newsletter.php`; it uses a stable explicit date, with the existing newsletter guards.

Completed scores/match archives currently have no automatic retention/deletion schedule. Decide that policy explicitly before adding cleanup; do not reuse the unrelated email-report retention job.

## Verification

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter 'GameDuelsTest|GamesPagesTest|PublicProjectPagesTest'
npm run test:games
npm run test:js
npm run production
npm run test:build
git diff --check
```

Final focused verification passes **76 PHP tests / 1,328 assertions** across games, public hub navigation, shared sessions and newsletters, plus **87 game JavaScript checks**, PHP syntax, production/CSS/build validation and whitespace checks. The all-project PHP run initially reported 39 failures: its three game/placeholder expectations were corrected and pass in the final focused run; all **36 remaining failures** were reproduced separately with the original route provider and placeholder Games routes preloaded from HEAD, without modifying the workspace. These cover existing admin/dashboard, catalogue/query budgets, palette, quizzes/retired resources, shop/billing, timeline and email expectations. The general JavaScript runner still encounters its unchanged search-copy expectation (`24 results` versus `We found 24 results`); its icon compatibility checks and the independent game suite pass. These are not reported as a clean whole-project test run.

The review suite is isolated SQLite with fake services; it is not proof of concurrent MySQL/multi-server behavior. Browser verification used a separate disposable SQLite preview and an independent HTTP guest session, with no normal-database fixture writes, real signups or scores. It covered the PianoLIT shell, original settings/3D controls/icons, playable staff/answer feedback, duel creation, opponent join/navigation, readiness/countdown and active gameplay. Hardware microphone quality and a full two-device HTTPS run remain release checks.
