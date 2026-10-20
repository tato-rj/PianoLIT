# Algolia account setup

Setup started on October 20, 2026 for the new Leftlane application
`5VDLMR7E2W`. Local credentials are connected, all 13 local pieces were imported,
and the guest search page returns the expected Arabesque No.1 result. Production
preparation and the live switch are pending. Arthur runs all production commands
and edits the server environment himself.

## Existing integration

PianoLIT already uses Laravel Scout 8.6.1, Scout Extended 1.16.0 and the Algolia
PHP client 2.8.0. Keep these versions during the account change.

`App\Piece::toSearchableArray()` defines the existing piece search records.
`config/scout.php` reads `ALGOLIA_APP_ID`, `ALGOLIA_SECRET`, `SCOUT_DRIVER`,
`SCOUT_PREFIX` and `SCOUT_QUEUE`. The default index is `pieces`.

Use separate indexes in the new application:

| Environment | SCOUT_PREFIX | Index | Settings |
| --- | --- | --- | --- |
| Live | empty | `pieces` | `config/scout-pieces.php` |
| Local | `local_` | `local_pieces` | `config/scout-local-pieces.php` |

The local settings file includes the existing settings so the two definitions
cannot drift. It does not change the active connection or index.

## Credentials

In the new application's **Settings → API Keys**, the Application ID is public.
The Write API Key and Admin API Key are secrets. PianoLIT performs searches and
index updates on the server; a Search API Key alone cannot support indexing.
Keep server keys out of chat, Git, screenshots and browser JavaScript.

The existing Write API Key can be used for initial setup. For ongoing isolation,
use separate custom server keys restricted to each environment's indexes.
Runtime indexing needs `search`, `addObject` and `deleteObject`. Settings/import
operations additionally need appropriate settings and index-management rights.
Scout Extended's settings synchronizer also accesses and deletes the temporary
`temp-laravel-scout-extended` index; reimport uses `temp_<index-name>`.
Account for those names before restricting a setup key.

The owner must edit `.env` under the project's working rules. For local setup:

```dotenv
SCOUT_DRIVER=algolia
SCOUT_PREFIX=local_
SCOUT_QUEUE=false
ALGOLIA_APP_ID=5VDLMR7E2W
ALGOLIA_SECRET=PASTE_THE_PRIVATE_SERVER_KEY_HERE
```

These are local values only. Do not change the live connection until the new live
index has been populated and verified.

## Import and switch

1. Verify the effective application ID and index without printing the secret.
   Refresh local Laravel configuration after the owner updates `.env`.
2. Inspect the old live index's settings, synonyms and rules if it remains
   accessible. The repository settings are a baseline, not proof that all remote
   customizations are captured.
3. Populate `local_pieces` from the local database. On a first import into this
   confirmed new destination, the installed commands are
   `php artisan scout:sync 'App\Piece' --keep=local` and
   `php artisan scout:import 'App\Piece'`. Review synchronization prompts before
   choosing which settings to keep, and verify that the server accepts the legacy
   settings. The installed `scout:import` clears its destination before importing.
   Do not run it against the old/live index as a connection test.
4. Prepare `pieces` from the authoritative live database using a separate import
   process configured for the new application, while the running site retains
   its old connection. Use the live server's deployment/configuration mechanism;
   do not assume a shell environment override supersedes Laravel's cached config.
   Coordinate indexed-content edits during the final import/switch so updates
   are not missed.
5. Verify record IDs/counts, piece-title searches, spelling variations,
   filters and pagination in both environments. Composer/tag exact matches may
   use SQL, so those alone do not establish that Algolia works.
6. Change the live server's application ID/key only after verification. Preserve
   its existing intended index name (normally `pieces`) and refresh configuration
   and any long-running Scout workers through the normal deployment process.
   Verify the existing mobile search endpoint as well as web search; both use
   the server integration. No mobile API or app changes are part of this setup.
7. Keep the old application and credentials available for rollback until the
   new connection has been verified. A rollback after new content edits may
   require resynchronizing the old index.

`scout:reimport 'App\Piece'` is the installed alternative for rebuilding a populated
index through a temporary index. Check queue configuration and completion before
using it; do not infer completion merely from queued jobs being dispatched.

## References

- [Algolia API keys and permissions](https://www.algolia.com/doc/guides/security/api-keys)
- [Scout Extended installation](https://www.algolia.com/doc/framework-integration/laravel/getting-started/installation)

Command behavior above was checked against the installed vendor source, not
assumed from documentation for newer package versions.

## SSH steps for the owner

Deploy this setup's PHP changes first, including the Guzzle connection adapter,
its registration in `AppServiceProvider`, and `scripts/prepare-algolia.php`.
No asset rebuild or schema migration is required for these changes. Check the
old application's custom settings/rules/synonyms if available before importing.
The helper applies the repository's existing settings; it does not copy any
additional dashboard-only customizations from the old application.

From the **live Laravel project directory**, while the server environment still
points to the old Algolia application, run:

```sh
php -d error_reporting=8191 scripts/prepare-algolia.php --prepare
```

Enter the new application's **Write API Key** at the hidden prompt. Do not put
the key in a command argument. The helper requires `APP_ENV=production`, the
existing `pieces` index name and an old active application ID. It refuses an
already populated new destination or one reporting pending tasks. It imports
using synchronous, process-only settings and verifies the source/index counts
and a piece-title search. It never edits `.env`, writes the source database,
flushes the old application, restarts queues or changes running web workers.
Run it from one server with catalogue editing paused through the final switch;
there is no automatic replication between the two Algolia applications.

After successful preparation, update these values in the **live** environment:

```dotenv
SCOUT_DRIVER=algolia
SCOUT_PREFIX=
ALGOLIA_APP_ID=5VDLMR7E2W
ALGOLIA_SECRET=PASTE_THE_NEW_PRIVATE_WRITE_API_KEY_HERE
```

Keep the server's intended `SCOUT_QUEUE` setting. Refresh Laravel's cached
configuration using the normal deployment mechanism, then verify:

```sh
php artisan config:cache
php -d error_reporting=8191 scripts/prepare-algolia.php --verify
```

If Scout uses long-running queue workers, restart those through the normal
deployment process so future updates use the new credentials. Check a real
piece-title search in the live web app and the existing mobile search endpoint.
Keep the old credentials privately for rollback. Do not run `scout:flush` on the
old application.

Local verification exposed a separate compatibility defect: the locked Algolia
client's Guzzle adapter catches `RequestException`, while Guzzle 7 connection
failures throw `ConnectException`. `App\Services\Search\AlgoliaHttpClient`
converts that failure to the status-zero response expected by Algolia's existing
retry policy. This restores the standard fallback hosts while preserving
credentials, TLS verification, query/index payloads and API errors. No host
override, vendor patch or dependency update is deployed. Initial local import
used a temporary setup-process fallback-host override; the final browser check
uses the ordinary application connection with this adapter.
