# Analytics account exclusions

Implemented locally on 2026-10-13 at the user's request. Arthur (`arthurvilar@gmail.com`) and Elena (`elena.dare@gmail.com`) are excluded through `config/activity.php` and `App\Activity\ExcludedAccounts`. Email comparison trims whitespace and ignores case. The same defaults apply if an older config cache lacks the new key. Refresh the deployment config cache normally; no new migration is needed.

## Collection and reporting

Authenticated web/member and admin sessions identify these accounts. Browser collection, page tokens, account activity events and pending visit qualification skip them. An encrypted, HttpOnly, SameSite privacy cookie remembers the exclusion after logout, shared across the site's domains when appropriate. It carries only an opt-out flag, not account/visit identity. Signing in with an ordinary member clears the flag on a shared browser. This is separate from authorization and `User::isAdmin()`; no access rule is changed.

On verified recognition, the current browser's previously anonymous activity context is removed, together with its unclaimed campaign touch. A visit already linked to another member is preserved. Cleanup failures retain the anonymous session context for retry and suppress further collection. Unrecognized anonymous browsers cannot be attributed before login; no IP guessing or fingerprinting is used.

Existing mobile logging and piece-view writes also skip excluded account IDs resolved from the same email list, retaining the legacy response formats and traffic exclusions. These submitted mobile IDs remain unverified analytics associations, never authorization. Recently viewed pieces, automatic location lookup/storage, public view/quiz-result counters, campaign attribution and local recipient email-event logging skip recognized exclusions. Saved favorites/score annotations, account settings and ordinary feature responses continue to work.

Administration's Overview, Activity, People, Insights, journeys, account log readers, country map, membership/super-user reports, older analytics charts/rankings, location reports, email recipient reports and activity-based exports exclude these accounts. The account-management directory remains available for managing their actual accounts. Excluded analytics profiles and journeys return 404; legacy log readers return empty data. `logs_count` and `last_active_at` retain their existing API fields with zero/null values. Google Tag Manager and the auth page's Meta pixel are omitted for recognized excluded requests.

## Remove stored history

After deploying the exclusion to every application server and restarting workers so older requests have drained, run these commands on the intended database host:

```sh
php artisan activity:purge-excluded --legacy-redis
php artisan activity:purge-excluded --execute --legacy-redis
php artisan activity:purge-excluded --legacy-redis
```

The first and last commands are read-only previews. The command prints the configured database target and counts before deletion. Confirm the matching account count against the two requested emails. With `--execute`, it removes only directly owned/effectively linked visits, their pages/events/links, ungrouped owned events, recent-piece history, piece views, legacy SQL log summaries, locations, quiz results, campaign visits/subscriptions, local recipient email logs and Apple membership reporting evidence. Visit batches are bounded to 200 and transactional; failures roll back the current batch and can be retried. A final check requires matching SQL tracking counts to reach zero.

Only affected shared email campaign counters are recalculated from the remaining recipients' rows; unrelated campaign reports are preserved. Their cached counts require this cleanup to remove historical contributions. Account rows, billing memberships/sources/payment receipts, favorites, score annotations and authentication/security/access-grant audit records are preserved.

`--legacy-redis` inspects/deletes only the matching accounts' exact `user:<id>:web`, `user:<id>:webapp` and `user:<id>:app` hashes, considering the configured legacy application prefix. A non-hash at an expected key aborts before SQL cleanup. Redis failures are surfaced and can be retried; SQL-only cleanup can be run without this option if legacy Redis is unavailable. It never flushes Redis, scans/deletes unrelated application data or subtracts guessed contributions from shared daily counters. `activity:import-redis`, including resume and verification, intentionally skips excluded accounts so deleted history is not reintroduced.

The local `mysql / 127.0.0.1 / pianolit` preview on 2026-10-13 found zero matching accounts, tracking rows and legacy hashes. Nothing was deleted locally. Production deployment and cleanup were not performed.

## Limits and verification

Identity-free historical totals (for example old scalar page-view counters), unattributed anonymous history, backups and external infrastructure/provider logs cannot be selectively removed by this command. The changes suppress website analytics tags for recognized requests and local email-event records; they do not retroactively purge Google/Meta/Mailgun data or disable operational security logging.

Isolated Review tests cover both normalized emails, browser/login/logout behavior, submitted-ID independence, linked history, report exclusion before cleanup, unchanged mobile JSON and full media access, exact Redis key deletion, SQL rollback/idempotence, shared email counter reconstruction and preservation of other members and saved/billing data. SQLite and mocked integrations do not establish production MySQL concurrency or provider behavior. No asset build is required for this PHP/Blade-only change.
