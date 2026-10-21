# Server-only subdomain provisioning

`php artisan subdomain:create blog` creates `blog.pianolit.com` on the Ubuntu
server. It uses Laravel 8 command discovery; there is no web endpoint or scheduled
job. Run it as the dedicated deployment user, **not** as root or `www-data`.
Installation below is required first. Do not run provisioning on a developer Mac.

```sh
php artisan subdomain:create blog --dry-run
php artisan subdomain:create blog --no-ssl
php artisan subdomain:create blog
```

The last command also upgrades an HTTP site previously created by this helper.
It does not take over existing manually managed sites, including the existing
PianoLIT subdomains. Creating an Nginx virtual host does not add Laravel routes;
application domain routing remains a separate application change.

## PHP and existing Nginx settings

The inspected project uses Laravel 8, declares PHP `^7.4|^8.0`, and the development
CLI reported PHP **8.3.35** on October 21, 2026. Previous project deployment notes
and the supplied environment identify `/var/www/html/public` and PHP 8.0-FPM on
Ubuntu. The current server PHP version was **not** inspected remotely. The local
Valet configuration uses a different router/socket; it cannot serve as an Ubuntu
production configuration. No production Nginx configuration was changed or copied.

Before installation, inspect the server using these read-only commands as an
operator with existing administrative access:

```sh
php -v
ls -l /run/php/*-fpm.sock
readlink -f /run/php/php-fpm.sock
sudo nginx -T
sudo certbot plugins
sudo certbot show_account
```

`readlink` may report no stable alias. Do not share the full `nginx -T` output
publicly. Compare an existing PianoLIT server block's `root`, PHP location,
FastCGI parameters, upload limits and other required directives with
`scripts/subdomains/laravel-http.nginx.tpl`. The supplied HTTP-only template uses
the [Laravel 8 Nginx routing pattern](https://laravel.com/docs/8.x/deployment#nginx),
the supplied document root, query-preserving front-controller routing and
FastCGI `SCRIPT_FILENAME`. Adjust the **new root-owned template** to match any
additional existing production directives. Do not edit existing virtual hosts.
Keep the managed marker and the four placeholders. Do not include SSL listeners,
certificate directives, `default_server`, wildcard names or extra domain names.

The helper uses Python 3's standard library, independently of PHP. The Artisan
wrapper uses PHP 7.4-compatible syntax and the project's existing Symfony Process
dependency. It reports the actual CLI version on each invocation. CLI PHP and
PHP-FPM may use different versions.

With `php_fpm_socket: "auto"`, new sites use `/run/php/php-fpm.sock` if it resolves
to a socket. Otherwise exactly one `php<major>.<minor>-fpm.sock` must exist. This
works for the supplied `php8.0-fpm.sock` and future versions without changing
command code. Multiple sockets without a stable alias cause an explicit error;
set `php_fpm_socket` to the desired absolute socket path. The selected path and
resolved target are printed. A missing socket stops provisioning before writes.

After a PHP upgrade, verify the server's CLI dependencies, FPM service and alias.
Existing sites using the stable alias follow that alias. Existing sites using
versioned paths still need the normal operator-managed Nginx update; this command
never rewrites those configurations to migrate PHP. An unrelated Laravel/package
compatibility problem on a future PHP release is outside this command's scope.

## Ubuntu prerequisites

Use the server's existing Certbot installation and existing registered production
account. Do not mix apt and snap installations or replace its account/certificates.
For an apt-based server, install **missing** prerequisites through the normal
server package workflow:

```sh
sudo apt-get install --no-install-recommends python3 nginx certbot python3-certbot-nginx dnsutils curl ca-certificates sudo
```

Python 3.8+ is sufficient; no pip packages are needed. Confirm that `certbot plugins`
lists the Nginx plugin. Configure the executable's absolute path if Certbot is
installed through snap (`/snap/bin/certbot`). Retain the existing renewal timer
and account email. Do not register another account as part of provisioning.
If no account exists, an administrator must register one separately using the
real operations email and normal Certbot setup before using this command.

Create a DNS-only A record for the requested name pointing to this server's public
IPv4. Each configured resolver must return that address and no additional A
addresses. If AAAA records exist, every returned IPv6 address must be listed in
`expected_ipv6` and reach this same server; otherwise remove/correct the AAAA
record. CDN/proxy addresses fail this intentional direct-server check. DNS lookup
timeouts, NXDOMAIN and SERVFAIL fail closed. Public TCP ports 80 and 443 must reach
Nginx on **every** advertised address, with no conflicting proxy/challenge rules;
outbound DNS and ACME HTTPS must work. CAA policy must permit Let's Encrypt.
[Let's Encrypt prefers IPv6 during validation](https://letsencrypt.org/docs/ipv6-support/),
so a working A record does not compensate for a conflicting AAAA record.

## Install the privileged helper

These are **server installation instructions**, not development/test commands.
An existing administrator performs this installation. The dedicated deployment
user must have the normal access required to read/run this Laravel checkout, but
must not be the PHP-FPM user, share its UID, belong to `sudo`, or have general sudo
rights. Do not run web requests, queue workers or scheduled application jobs as
this deployment account. If needed, create the example account:

```sh
sudo adduser --disabled-password --gecos '' pianolit-deploy
```

Use that account through your existing SSH/deployment access policy. If choosing
a different existing deployment user, replace `pianolit-deploy` in both the JSON
configuration and sudoers file. Do not grant `www-data` sudo access or make the
helper, configuration, templates or Nginx directories writable by deployment/PHP.

From the reviewed server checkout at `/var/www/html`:

```sh
cd /var/www/html
sudo install -d -o root -g root -m 0755 /etc/pianolit
sudo install -d -o root -g root -m 0700 /var/lib/pianolit-subdomains
sudo install -o root -g root -m 0750 scripts/subdomains/pianolit-subdomain /usr/local/sbin/pianolit-subdomain
sudo install -o root -g root -m 0644 scripts/subdomains/laravel-http.nginx.tpl /etc/pianolit/laravel-http.nginx.tpl
sudo install -o root -g root -m 0600 scripts/subdomains/config.example.json /etc/pianolit/subdomains.json
sudo install -o root -g root -m 0600 /dev/null /var/lib/pianolit-subdomains/provision.lock
sudoedit /etc/pianolit/subdomains.json
```

The last two `install` commands are **first-install only**: do not replace a
configured JSON file or a lock file used by a running helper. Subsequent helper
updates require a maintenance window with no provisioning operation in progress;
replace only the reviewed installed helper/template as needed. Never delete
the state directory during an update.

In `/etc/pianolit/subdomains.json`:

- Replace `expected_ipv4` with the real server public IPv4. The placeholder
  deliberately prevents provisioning until configured.
- Set `expected_ipv6` to this server's advertised IPv6 addresses, or retain `[]`.
  The template adds its IPv6 HTTP listener only when this list is nonempty.
- Confirm `deploy_user`, `php_fpm_user`, base domain, document root, directories,
  template, executable paths and PHP-FPM socket selection. Paths must be absolute,
  without whitespace, shell syntax or `..`. No server settings come from CLI args.
- `certbot_account` may be empty only when exactly one registered production
  account exists under `certbot_config_dir/accounts/acme-v02.api.letsencrypt.org/directory`. Otherwise set its 32-character
  account ID, as shown by the existing Certbot installation.
- Keep `certbot_email` empty to use the selected account's registered email. If
  the account registration does not expose an email or Certbot needs an explicit
  email, set this to the existing operations email. It is passed using `--email`.
  The helper never registers an account or prompts for input.
- `certbot_config_dir` identifies the existing Certbot production installation
  (default `/etc/letsencrypt`). Account, renewal, live and archive paths derive
  from this root, and the same root is passed to Certbot using `--config-dir`.

The installed script and every ancestor of privileged inputs/executables must be
root-owned and not group/world writable. Root-owned symlinks such as snap's
Certbot launcher are supported; their resolved target/ancestors are checked too.
The helper uses an isolated Python interpreter (`-I`), fixed process environment,
absolute executable paths, closed stdin and argument arrays. It never imports
application code or runs PHP as root.

## Install the narrow sudoers rule

For the example account, the exact rule supplied in
`scripts/subdomains/sudoers.example` is:

```sudoers
Defaults:pianolit-deploy env_reset
pianolit-deploy ALL=(root) NOPASSWD: NOSETENV: /usr/local/sbin/pianolit-subdomain
```

It permits only that installed executable. Arguments are restricted by the
helper to one validated label and `--dry-run` / `--no-ssl`; no arbitrary command,
config path, domain suffix, environment override or extra option is accepted.
Do not grant sudo for Python, PHP, shells, `nginx`, `systemctl` or Certbot. Do not
use `NOEXEC`, which would also block the helper's required child processes.

Validate before installing; if changing the username, first edit a reviewed
temporary copy and validate/install that file instead:

```sh
sudo visudo -cf /var/www/html/scripts/subdomains/sudoers.example
sudo install -o root -g root -m 0440 /var/www/html/scripts/subdomains/sudoers.example /etc/sudoers.d/pianolit-subdomains
sudo visudo -c
sudo -l -U pianolit-deploy
sudo -l -U www-data
```

Check that no broader pre-existing user/group rule grants either account extra
privileges. The helper also checks the real sudo caller against `deploy_user`;
direct root invocations and PHP-FPM callers are refused. Keep the Laravel setting
`config/subdomains.php` at the default installed path. Only if intentionally
installing elsewhere, set `SUBDOMAIN_DEPLOY_SCRIPT` in the server configuration
and update the exact sudoers path together through normal deployment procedures.
The standard installed path also works with a previous Laravel configuration
cache. A custom path requires refreshing that cache through the normal release.

## Provision and verify

As the deployment user from the Laravel directory:

```sh
cd /var/www/html
php artisan subdomain:create blog --dry-run
php artisan subdomain:create blog
```

Dry-run reads the root configuration, PHP socket, template, Certbot account and
DNS. It displays the proposed HTTP configuration, paths and Certbot argument
array, without writing files, executing Nginx/Certbot, issuing certificates or
reloading services. It holds the already-installed global lock while reading.
Nginx's full configuration validation and duplicate-name check remain apply-time
checks: `nginx -t/-T` can open logs, so they are not run during dry-run.

On apply, the helper:

1. Checks authorization, strict DNS label, protected paths, PHP-FPM and DNS.
2. Locks globally against other helper runs, refuses existing site paths (including
   dangling symlinks), existing exact `server_name` declarations in enabled or
   disabled sites, and existing unmanaged certificate lineages.
3. Tests the existing Nginx configuration, then exclusively creates the new
   HTTP-only virtual host and its `sites-enabled` symlink. No nonexistent
   certificate is referenced. Its durable root-only journal identifies the site.
4. Runs `nginx -t`, then `systemctl reload nginx` only if validation passes.
   The effective `nginx -T` output must include the managed site too; a missing
   `sites-enabled` include triggers rollback before reload or Certbot.
5. Unless `--no-ssl`, runs Certbot inside the already-root helper. This is the
   equivalent of the requested command, with an explicitly selected existing
   account and separate certificate name:

   ```sh
   sudo certbot --nginx -d blog.pianolit.com --redirect --non-interactive --agree-tos --config /dev/null --config-dir /etc/letsencrypt --cert-name blog.pianolit.com --keep-until-expiring --account EXISTING_ACCOUNT_ID
   ```

   `--email EXISTING_REGISTERED_EMAIL` is appended when configured. There is no
   nested sudo or direct Certbot call from Laravel. `--config /dev/null` avoids
   unrelated defaults/hooks from a global CLI configuration. Existing account
   storage and certificate renewal metadata remain in their normal locations.
   A separate `--cert-name` avoids expanding an unrelated multi-domain certificate.
6. Tests Nginx again, reloads only after success, then verifies a trusted TLS
   connection for the requested hostname and HTTP 301/308 redirect to HTTPS.
   Connections are pinned to each DNS-verified IP, with proxy use and curl config
   loading disabled. TLS trust/hostname checks are never bypassed. HTTP 2xx–4xx is
   accepted over HTTPS because an application route may not yet exist; 5xx fails.

Certbot's Nginx installer performs its own configuration checks/reloads while
installing the certificate; the helper also performs the final explicit checks.
Subprocesses have bounded timeouts. The wrapper has no timeout that might kill
the helper halfway through a transaction. Certbot remains non-interactive even
when account/email prerequisites are missing: it fails with an actionable error.

Run only one Nginx configuration deployment at a time. This global lock serializes
this helper's runs, while Certbot has its own lock against other Certbot jobs;
manual edits and other deployment systems must honor your maintenance window.

## Failures and retries

- Validation or DNS failure before creation changes nothing. Failed new-site
  `nginx -t` removes only this helper's new file/symlink/journal without reloading.
- An HTTP reload failure removes the new site, tests the previous configuration
  and attempts to reload it. A recovery failure is reported explicitly.
- A Certbot/final-validation/HTTPS failure retains the managed site, any issued
  certificate and its journal. It does not delete certificates or run global
  Certbot rollback, which could affect another domain. If the on-disk Nginx
  configuration is invalid, fix the reported cause as an operator before retrying;
  the helper refuses to reload an invalid configuration.
- Retry the **same** Artisan command/name after fixing DNS, firewall, ACME or
  service issues. Already-working TLS after an interrupted run is verified and
  marked complete without another certificate request. Otherwise Certbot reuses
  the same account and managed certificate name with `--keep-until-expiring`.
- `--no-ssl` can create/retry HTTP sites. It cannot downgrade a partial SSL attempt
  or remove a completed certificate. A completed HTTPS retry verifies the site
  without rewriting it, reloading or requesting a certificate.
- Journals live at `/var/lib/pianolit-subdomains/<fqdn>.json`. A changed HTTP
  configuration, missing active file/symlink, changed server paths, corrupted
  journal or interrupted partially written site requires root review. The helper
  refuses to overwrite uncertain files. Preserve the journal when diagnosing.
  Do not delete a journal to bypass an existing-site check.

## Automated verification

```sh
php -d error_reporting=8191 vendor/bin/phpunit -c phpunit-review.xml --filter SubdomainCommandTest
```

This tests Artisan through fake Symfony processes and runs the standalone Python
unittest suite through `/usr/bin/python3 -I`. Python tests use temporary fixture
directories/sockets and fake DNS, Nginx, reload, Certbot and HTTP dependencies;
they never invoke the privileged entry point, sudo, real DNS or real provisioning.
They cover input validation, existing/dangling site paths, duplicate host names,
IPv4/AAAA conflicts, HTTP rollback, reload failure, Certbot/HTTPS failures,
retained/retried journals, SSL upgrade, dry-run, account/email handling, concurrency,
caller authorization and PHP socket upgrades/ambiguity.

No production provisioning or live certificate issuance was performed as local
verification. Before first real use, review the installed template against actual
server examples and exercise the workflow on a disposable Ubuntu/Nginx environment
with fake executables or a separately configured ACME staging environment. Do not
use an existing production domain/certificate as a test fixture.
