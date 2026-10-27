# Google Analytics and internal behavior reports

Updated 2026-10-27 at Arthur's request. GA4 means Google Analytics 4, the product
version; it does not need to be part of PianoLIT's property name.

## Ownership

| Google Analytics | Internal admin analytics |
| --- | --- |
| Website and hub pageviews, audience, sessions, landing pages, source/medium, campaigns, countries, devices and returning visitors/retention | Detailed behavior inside `my.*`: piece exploration, playback coverage, scores, annotations, saved music/folders, visit journeys and subscription steps |
| One web stream across main/www, my, Learn, Blog, Shop, Games and Metronome | Existing iOS SQL requests and imported history, with their unverified identity and missing visit/playback context stated explicitly |

No iOS SDK, API route, payload, media delivery or billing mutation is part of this
change. Existing public SQL history remains stored; it is not uploaded to Google.
Account metadata, security/billing facts and account-owned recently viewed pieces
remain separate from macro traffic analytics.

## Admin guide

Analytics → **Google Analytics** now appears above Users and opens the compact
internal guide at `admin.analytics.google` (`/analytics/google`). Twelve entries
cover visitors/sessions, views, hubs, sources, landing pages, engagement, retention,
countries, devices, campaign traffic, confirmed trials and first payments/revenue.
Each View button uses the shared outward arrow and opens the relevant PianoLIT
report in a new tab. Device category, campaign/source breakdowns and conversion
event searches are preserved in the verified report URLs. Copy and destinations
are editable in `resources/views/admin/pages/analytics/google/index.blade.php`.

Definitions follow Google's [engagement guidance](https://support.google.com/analytics/answer/12195621),
[retention report](https://support.google.com/analytics/answer/11004084) and
[traffic-source scopes](https://support.google.com/analytics/answer/11080067).
The conversion descriptions follow PianoLIT's verified server events described below.

## Changes saved in Google

- Account `137232556`, property `399721623`: **PianoLIT · Website & hubs**
- Web stream `5920378860`: **PianoLIT · All website hubs**
- Measurement ID: `G-12WWWZZMJF`; existing stream URL `https://pianolit.com`
- Reporting timezone remains New York; currency remains USD. Arthur confirmed
  Small (1–10 employees). Objectives now select traffic and engagement/retention.
- Tag Manager `GTM-KJW7XGP`, published **version 6**, replaces the legacy Universal
  Analytics tag with the existing GA4 stream. Google's diagnostic explicitly
  identified the legacy connected-tag bridge as a collection-quality issue.
  The old UA settings variable remains inert; there is no firing UA analytics tag.
- Container automatic measurement and the GA destination enable page loads,
  scrolls and outbound links. Browser-history pageviews, site search, automatic
  form/video/download measurement and automatic user-provided data collection
  remain disabled. Detailed app behavior continues in the internal collector.
- Email redaction is active. These query keys are redacted: `email`, `token`,
  `signature`, `code`, `password`, `password_confirmation`, `challenge`, `handoff`,
  `attempt`, `user_id`, `subscriber_id`, `subscription_id`. UTM keys remain usable.
- The published **Website & hubs** report collection groups the custom overview,
  Hub traffic, Traffic acquisition, Landing page and Pages and screens.
- The custom overview is the default **Reports snapshot**. Its top metrics are
  Views, Active users, Sessions and Engagement rate. Summaries cover hostname,
  page titles, channels, source/medium, countries, devices, new/returning visitors,
  activity over time, retention and realtime.
- **PianoLIT · Hub traffic** defaults to Hostname, with Page location available for
  full URLs. Its metrics include views, users, views/user, average engagement time,
  sessions and engagement rate. Unexpected historical hostnames remain visible
  for auditing; rows are not deleted or silently reassigned.

[Open the default snapshot](https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/reportinghub)

The existing retention settings were inspected and retained: event data 2 months,
user data 14 months, reset on new user activity on. These raw-data limits affect
Explorations/funnels; Google's standard aggregated reports are not limited by
those settings. The user-facing returning-visitor/retention summaries are distinct
from how long Google stores raw events.

## Installation audit and source fixes

Anonymous HTTP GETs to the production hub roots returned 200. Main/www (www
redirects to main), Learn, Blog, Shop and Games included the old container.
Guest `my.*` and Metronome had no Google tracker. Individual games' dedicated
layout also omitted it. The published container initially held only legacy UA
analytics and a separate Facebook pixel, with no explicit GA4 tag.

`App\Analytics\WebsiteTracking` now centralizes production/host/account exclusions.
The web-app guest layout, Metronome and individual-game layout include the shared
head/body partials. The newly covered surfaces load **Google only**, directly
through the same `G-12WWWZZMJF` stream, avoiding an expansion of the old container's
Facebook pixel. Other already-instrumented surfaces retain the container. Each
page uses exactly one of those installation paths, avoiding duplicate pageviews.
Google's automatic first-party cookie domain shares measurement across these
subdomains; this is independent of Laravel's opt-in shared login sessions.

Unknown hosts/direct IP addresses, admin sessions/hosts, the native blog embed,
signed URLs and known credential/verification/unsubscribe destinations do not
load the tracker. Existing `ExcludedAccounts` emails and recognized-browser
opt-outs apply to both installation paths. This does not identify Arthur/Elena on
an entirely new anonymous device before an account/browser exclusion is known.

## Local internal reporting changes

New SQL journey recording and browser batch ingestion are limited to `my.*`.
Old public tabs receive a 204 acknowledgment without adding SQL events. Queries
used by Activity, People and Insights exclude public-platform history from the
current behavior reports; no historical rows are deleted. Legacy iOS recording
continues through its original logger/contracts.

Overview offers Both apps, Web app and iOS. Its activity totals/rankings exclude
general website events, and its country-map aggregate has been removed. Account,
membership, subscriber and content inventory totals remain operational facts.
Analytics → Users retains Activity, People and behavioral Insights, with a shared
link to the Google snapshot. The public pageview/hub aggregate and signup-month
cohort panel were retired; safe historical journey page labels remain supported.

`config/attribution.php` keeps general campaign-arrival collection disabled.
Arthur subsequently requested a minimal consented campaign-to-membership link:
Analytics → Campaign conversions now shows confirmed trials/first payments without
an arrivals counter. The Google Analytics menu entry opens the metric guide. See
[CAMPAIGN_ATTRIBUTION.md](CAMPAIGN_ATTRIBUTION.md) for the conversion outbox,
existing-opt-in limits and deployment instructions.

GA now has the `membership_trial_started` key event with no default revenue;
`purchase` remains the standard first-payment key event. Saved campaign/context
parameters are registered as six event-scoped Membership dimensions. The old
page-derived `webapp_membership` and `engagement__duration` events were unmarked
as key events; their records/rules were not deleted. Signup/download key events
remain separate. Filter membership reporting to the two confirmed event names.
Arthur approved the acknowledgement and private Measurement Protocol secret on
2026-10-27; both setup steps are complete and the secret is stored locally with
owner-only permissions outside Git. Server delivery still needs production secret
installation, migration/code rollout and a permitted real-event check.

## Deployment and verification

The Google property, reports, stream settings and Tag Manager publication are
already saved live. The Laravel changes are local and have **not been deployed**.
Deploy PHP/Blade/config together on all web servers and rebuild their config/view
caches through the normal release process. The subsequent conversion-link work
requires its new migration and rebuilt campaign script; follow the campaign rollout
instructions before serving that code. Keep `ACTIVITY_ENABLED` enabled for web-app/iOS behavior; disabling it is
not how public macro collection is retired.

Before claiming complete production coverage after deployment:

1. Load a normal page and a nested page on every hub, including a guest web-app
   page, individual game and Metronome. Use an ordinary, non-excluded test browser.
2. Verify a single GA `page_view` to `G-12WWWZZMJF` per real page load, the correct
   hostname/location, and shared first-party GA identity across hub navigation.
   Test signed-in pages with a synthetic account rather than a real customer.
3. Verify these hostnames in Realtime pages/Hub traffic after processing. Google
   reporting is delayed and browser blockers/opt-outs can suppress collection;
   the removed historical gaps cannot be backfilled from this installation.
4. Check Arthur/Elena and admin/unknown-host/sensitive-page exclusions. Verify
   ordinary public visits create no new activity/campaign SQL arrivals and `my.*` still records
   piece/media/folder journeys. Legacy iOS request counts must remain available.
5. Keep real billing/email mutations out of verification.

Local verification uses the isolated SQLite review suite, including
`WebsiteAnalyticsSplitTest`, historical labels, Dashboard, platform filters,
activity history/qualification/exclusions, journeys, campaigns and legacy mobile
logging. The conversion follow-up includes a rebuilt standalone script. See the dated
entry in `CODE_REVIEW.md` for the final run totals.

## Google references

- [GA4 replaced Universal Analytics](https://support.google.com/analytics/answer/11583528)
- [Cookie-domain configuration](https://developers.google.com/analytics/devguides/collection/ga4/reference/config)
- [Self-referral handling](https://support.google.com/analytics/answer/10327750)
- [Overview customization](https://support.google.com/analytics/answer/13818312)
- [Data retention and aggregate reports](https://support.google.com/analytics/answer/7667196)
