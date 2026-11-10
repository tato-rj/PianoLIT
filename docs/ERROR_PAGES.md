# Shared error pages

The full-page browser errors share `resources/views/errors/page.blade.php` and
`illustration.blade.php`. Edit the message map in the page; explicit status views
and unknown HTTP errors use it. The original layouts remain compatibility aliases.
Browser 404s on `my.*` now show the shared page instead of silently redirecting.
JSON/API response behavior, authentication redirects and HTTP status/headers stay
unchanged. Shared-session rotation retains its explicit sign-in recovery.

The current November 10 request allows light humor on these pages. Other product
copy still follows the literal-language rule. Keep specific recovery instructions,
and do not claim that work was saved, data is safe, or an incident is being fixed
without evidence.

`Support/ErrorPage` chooses same-origin recovery links and the brand from the exact
configured admin hostname. All other hosts use the regular icon. Forms never get
a reload/POST-replay action. The shared brand component's `standalone` option
embeds the existing SVG without session, auth, Mix or remote-asset dependencies.
The page also embeds its stylesheet and illustration, and needs no JavaScript,
web font, database or external request. Motion stops after 4.6 seconds and is
disabled by reduced-motion preferences.

Styles come from `resources/sass/errors.scss`, using the shared design tokens,
Bootstrap buttons and shared button foundations. Rebuild the checked-in CSS with
`npm run errors:styles`; ordinary development/production builds include this step.
Settings → Error pages (`admin.error-pages.index`, `/error-pages`) has its own
admin page showing the actual 404, admin 500 and 413 templates in isolated, inert
previews. It is separate from Design System.

## Errors generated before Laravel

An upload rejected by nginx never reaches Laravel. The generated static pages in
`public/errors/{regular,admin}/` cover these failures and remain usable if PHP is
unavailable. Regenerate them after changing templates, logos or styles:

```sh
npm run errors:build
```

This command skips `.env` and uses isolated in-memory configuration. It does not
run migrations or alter the web server. Static recovery always links to the
current hub root, because the original request might have been a form submission.

Production nginx needs the following wiring before proxy-generated errors use
the new pages. Add this map in the `http` context:

```nginx
map $host $pianolit_error_brand {
    default regular;
    admin.pianolit.com admin;
}
```

In each PianoLIT browser-host server block, retaining its existing document root:

```nginx
error_page 413 /errors/$pianolit_error_brand/413.html;
error_page 500 /errors/$pianolit_error_brand/500.html;
error_page 502 /errors/$pianolit_error_brand/502.html;
error_page 503 /errors/$pianolit_error_brand/503.html;
error_page 504 /errors/$pianolit_error_brand/504.html;

location ^~ /errors/ {
    internal;
    try_files $uri =404;
    add_header Cache-Control "no-store" always;
}
```

Do not add an `=200` override, replace Laravel's existing front-controller/404
routing, or enable `fastcgi_intercept_errors` globally: Laravel's JSON responses
must remain JSON. Keep API locations' existing behavior, especially for uploads.
Only apply to the browser-serving configuration after checking those boundaries.
Keep any API-specific `error_page` configuration separate. Validate `nginx -t`
and test an oversized synthetic upload on a disposable browser endpoint before
reloading production. Local Valet and production nginx configuration have not been
changed by this task; upstream activation remains a deployment step.

## Verification

Use the isolated review suite for `ErrorPagesTest` and `SharedWebSessionTest`.
Coverage includes all eight hubs, standard and fallback HTTP codes, matching
logos, exception-detail exclusion, response headers, JSON contracts, database /
session / manifest independence and safe recovery from failed POSTs. Browser QA
checks the actual page at desktop and phone sizes. No migration is needed.
