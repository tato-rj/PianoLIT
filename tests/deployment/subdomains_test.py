"""Unprivileged tests. Never invoke helper.main or real server executables."""
import importlib.machinery
import importlib.util
import json
import os
from pathlib import Path
import socket
import stat
import subprocess
import sys
import tempfile
import unittest
from types import SimpleNamespace
from unittest import mock


ROOT = Path(__file__).resolve().parents[2]
sys.dont_write_bytecode = True
LOADER = importlib.machinery.SourceFileLoader('subdomains', str(ROOT / 'scripts/subdomains/pianolit-subdomain'))
SPEC = importlib.util.spec_from_loader(LOADER.name, LOADER)
helper = importlib.util.module_from_spec(SPEC)
LOADER.exec_module(helper)
ORIGINAL_TRUSTED = helper.trusted


class SubdomainDeploymentTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='pl-sub-', dir='/tmp')
        self.addCleanup(self.temporary.cleanup)
        self.base = Path(self.temporary.name)
        self.c = json.loads((ROOT / 'scripts/subdomains/config.example.json').read_text())
        self.c['certbot_config_dir'] = str(self.base / 'letsencrypt')
        self.c.update(helper.certificate_directories(self.c))
        for key in ('document_root', 'php_fpm_socket_dir', 'sites_available', 'sites_enabled', 'state_dir',
                    'certbot_accounts_dir', 'certbot_renewal_dir', 'certbot_live_dir', 'certbot_archive_dir'):
            if not key.startswith('certbot_'):
                self.c[key] = str(self.base / key)
            Path(self.c[key]).mkdir(parents=True, exist_ok=True)
        Path(self.c['document_root'], 'index.php').write_text('<?php // fixture')
        self.c['template'] = str(self.base / 'template')
        Path(self.c['template']).write_bytes((ROOT / 'scripts/subdomains/laravel-http.nginx.tpl').read_bytes())
        self.c['expected_ipv4'] = '192.0.2.10'
        self.c['dns_resolvers'] = ['192.0.2.53', '192.0.2.54']
        self.account = 'a'*32
        account = Path(self.c['certbot_accounts_dir']) / self.account
        account.mkdir()
        (account / 'regr.json').write_text(json.dumps({'body': {'contact': ['mailto:ops@example.com']}}))
        (account / 'private_key.json').write_text('{}')
        self.sockets = []
        self.make_socket('php8.0-fpm.sock')
        self.calls = []
        self.messages = []
        self.a = ['192.0.2.10']
        self.aaaa = []
        self.dns_status = 'NOERROR'
        self.fail_certbot = False
        self.tls = False
        self.redirect = True
        self.nginx_count = 0
        self.fail_nginx_at = None
        self.reload_count = 0
        self.fail_reload_at = None
        self.other_site = ''
        self.include_sites_enabled = True
        self.initial_http = None
        # Root ownership enforcement belongs to the entry point. Test only
        # temporary files here, even if the test runner itself happens to be root.
        patcher = mock.patch.object(helper, 'trusted', side_effect=lambda p: Path(p))
        patcher.start()
        self.addCleanup(patcher.stop)
        self.fingerprint = self.snapshot()

    def make_socket(self, name):
        address = Path(self.c['php_fpm_socket_dir']) / name
        server = socket.socket(socket.AF_UNIX)
        self.addCleanup(server.close)
        server.bind(str(address))
        self.sockets.append(server)
        return address

    def snapshot(self):
        return {str(p.relative_to(self.base)): ('link', os.readlink(str(p))) if p.is_symlink()
                else ('file', p.read_bytes()) if p.is_file() else ('other', p.stat().st_mode)
                for p in self.base.rglob('*')}

    def runner(self, args, timeout=60):
        args = [str(arg) for arg in args]
        self.calls.append(args)
        program = args[0]
        if program == self.c['dig']:
            kind = args[-1]
            values = self.a if kind == 'A' else self.aaaa
            return (';; ->>HEADER<<- opcode: QUERY, status: '+self.dns_status+', id: 1\n'
                    +''.join('blog.pianolit.com. 60 IN '+kind+' '+value+'\n' for value in values), '')
        if program == self.c['nginx']:
            if args[-1] == '-t':
                self.nginx_count += 1
                if self.nginx_count == self.fail_nginx_at:
                    raise helper.DeploymentError('nginx validation fixture failure')
            enabled = ''
            if self.include_sites_enabled:
                enabled = ''.join(p.read_text() for p in Path(self.c['sites_enabled']).iterdir() if p.is_file())
            return ((self.other_site+enabled) if args[-1] == '-T' else '', '')
        if program == self.c['systemctl']:
            self.reload_count += 1
            if self.reload_count == self.fail_reload_at:
                raise helper.DeploymentError('reload fixture failure')
            return ('', '')
        if program == self.c['certbot']:
            if self.fail_certbot:
                raise helper.DeploymentError('Certbot fixture failure')
            site = self.paths()[0]
            self.initial_http = site.read_bytes()
            if 'listen 443 ssl;' not in site.read_text():
                site.write_text(site.read_text().replace('    listen 80;',
                                '    listen 80;\n    listen 443 ssl;\n    ssl_certificate /fixture/cert.pem;'))
            self.tls = True
            return ('certificate fixture installed', '')
        if program == self.c['curl']:
            if args[-1].startswith('https:'):
                if not self.tls:
                    raise helper.DeploymentError('TLS fixture failure')
                return ('200', '')
            return (('HTTP/1.1 301 Moved Permanently\r\nLocation: https://blog.pianolit.com/\r\n\r\n'
                     if self.redirect else 'HTTP/1.1 200 OK\r\n\r\n'), '')
        self.fail('Unexpected server program: '+repr(args))

    def deploy(self, **options):
        helper.Deployment(self.c, self.runner, self.messages.append).run('blog', **options)

    def paths(self):
        return (Path(self.c['sites_available']) / 'blog.pianolit.com',
                Path(self.c['sites_enabled']) / 'blog.pianolit.com',
                Path(self.c['state_dir']) / 'blog.pianolit.com.json')

    def test_strict_name_validation(self):
        for name in ('a', '0', 'learn-2', 'a'*63):
            helper.validate_name(name)
        for name in ('', 'Blog', 'blog.com', '-blog', 'blog-', 'a'*64, '../blog', 'a_b',
                     'a/b', 'a;id', 'a$(id)', 'a\n', 'é', '--dry-run', '*.blog'):
            with self.subTest(name=name), self.assertRaises(helper.DeploymentError):
                helper.validate_name(name)

    def test_complete_ssl_deployment_orders_validation_reload_and_certbot(self):
        existing = Path(self.c['sites_available']) / 'existing'
        existing.write_text('existing site never changes')
        self.deploy()
        available, enabled, state = self.paths()
        self.assertEqual(str(available), os.readlink(str(enabled)))
        body = available.read_text()
        self.assertIn('root '+self.c['document_root']+';', body)
        self.assertIn('fastcgi_pass unix:'+self.c['php_fpm_socket_dir']+'/php8.0-fpm.sock;', body)
        self.assertIn('try_files $uri $uri/ /index.php?$query_string;', body)
        self.assertNotIn(b'ssl_certificate', self.initial_http)
        self.assertNotIn(b'443', self.initial_http)
        self.assertIn('listen 443 ssl;', body)
        self.assertEqual('complete_https', json.loads(state.read_text())['phase'])
        programs = [call[0] for call in self.calls]
        certbot_at = programs.index(self.c['certbot'])
        self.assertEqual(1, sum(call[0] == self.c['systemctl'] for call in self.calls[:certbot_at]))
        self.assertEqual(2, self.reload_count)
        certbot = self.calls[certbot_at]
        for option in ('--nginx', '--redirect', '--non-interactive', '--agree-tos', '--keep-until-expiring'):
            self.assertIn(option, certbot)
        self.assertEqual('blog.pianolit.com', certbot[certbot.index('--cert-name')+1])
        self.assertEqual(self.account, certbot[certbot.index('--account')+1])
        self.assertEqual(self.c['certbot_config_dir'], certbot[certbot.index('--config-dir')+1])
        self.assertEqual('existing site never changes', existing.read_text())

    def test_no_ssl_does_not_require_or_invoke_certbot_and_can_be_upgraded(self):
        self.c['certbot_account'] = 'nonexistent'
        self.deploy(no_ssl=True)
        self.assertFalse(any(call[0] == self.c['certbot'] for call in self.calls))
        available, _, state = self.paths()
        original = available.read_bytes()
        self.assertEqual('complete_http', json.loads(state.read_text())['phase'])
        self.c['certbot_account'] = ''
        self.deploy()
        self.assertEqual(original, self.initial_http)
        self.assertEqual('complete_https', json.loads(state.read_text())['phase'])

    def test_dry_run_previews_without_writing_reloading_testing_or_certbot(self):
        self.deploy(dry_run=True)
        self.assertEqual(self.fingerprint, self.snapshot())
        self.assertTrue(all(call[0] == self.c['dig'] for call in self.calls))
        self.assertTrue(any('DRY RUN' in line for line in self.messages))
        self.assertTrue(any('--redirect' in line for line in self.messages))

    def test_unmanaged_existing_file_or_dangling_link_is_never_touched(self):
        available, enabled, _ = self.paths()
        for path in (available, enabled):
            for link in (False, True):
                with self.subTest(path=path, link=link):
                    if link:
                        path.symlink_to('/missing-site')
                    else:
                        path.write_text('do not touch')
                    before = self.snapshot()
                    with self.assertRaisesRegex(helper.DeploymentError, 'already exists'):
                        self.deploy()
                    self.assertEqual(before, self.snapshot())
                    self.assertEqual([], self.calls)
                    path.unlink()

    def test_existing_server_name_in_another_file_is_not_taken_over(self):
        self.other_site = 'server { server_name other.com blog.pianolit.com; }'
        with self.assertRaisesRegex(helper.DeploymentError, 'already declares'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())
        self.assertEqual(0, self.reload_count)

    def test_disabled_site_with_different_filename_is_not_taken_over(self):
        Path(self.c['sites_available'], 'different.conf').write_text('server_name "blog.pianolit.com";')
        before = self.snapshot()
        with self.assertRaisesRegex(helper.DeploymentError, 'already declares'):
            self.deploy()
        self.assertEqual(before, self.snapshot())

    def test_wrong_or_missing_ipv4_and_conflicting_ipv6_fail_before_changes(self):
        for a, aaaa in (([], []), (['192.0.2.11'], []), (['192.0.2.10', '192.0.2.11'], []),
                        (['192.0.2.10'], ['2001:db8::bad'])):
            with self.subTest(a=a, aaaa=aaaa):
                self.a, self.aaaa = a, aaaa
                with self.assertRaisesRegex(helper.DeploymentError, 'DNS [A]+ conflict'):
                    self.deploy()
                self.assertEqual(self.fingerprint, self.snapshot())
                self.assertEqual(0, self.reload_count)

    def test_dns_lookup_errors_are_not_mistaken_for_absent_aaaa(self):
        self.dns_status = 'SERVFAIL'
        with self.assertRaisesRegex(helper.DeploymentError, 'lookup failed'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())

    def test_expected_ipv6_enables_ipv6_listener_and_checks_both_addresses(self):
        self.c['expected_ipv6'] = ['2001:db8::10']
        self.aaaa = ['2001:0db8:0:0:0:0:0:10']
        self.deploy()
        self.assertIn('listen [::]:80;', self.paths()[0].read_text())
        self.assertTrue(any('blog.pianolit.com:443:[2001:db8::10]' in call for call in self.calls))

    def test_baseline_nginx_failure_changes_nothing(self):
        self.fail_nginx_at = 1
        with self.assertRaisesRegex(helper.DeploymentError, 'validation fixture failure'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())

    def test_new_site_validation_failure_rolls_back_only_new_files_and_can_retry(self):
        Path(self.c['sites_available'], 'existing').write_text('unchanged')
        before = self.snapshot()
        self.fail_nginx_at = 2
        with self.assertRaisesRegex(helper.DeploymentError, 'validation fixture failure'):
            self.deploy()
        self.assertEqual(before, self.snapshot())
        self.assertEqual(0, self.reload_count)
        self.deploy()
        self.assertEqual('complete_https', json.loads(self.paths()[2].read_text())['phase'])

    def test_reload_failure_restores_previous_http_configuration(self):
        self.fail_reload_at = 1
        with self.assertRaisesRegex(helper.DeploymentError, 'HTTP reload failed'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())
        self.assertEqual(2, self.reload_count)
        self.assertFalse(any(call[0] == self.c['certbot'] for call in self.calls))

    def test_certbot_failure_keeps_http_and_journal_and_retry_completes(self):
        self.fail_certbot = True
        with self.assertRaisesRegex(helper.DeploymentError, 'SSL is incomplete'):
            self.deploy()
        available, enabled, state = self.paths()
        self.assertTrue(available.is_file() and enabled.is_symlink())
        self.assertEqual('certbot_started', json.loads(state.read_text())['phase'])
        self.fail_certbot = False
        self.deploy()
        self.assertEqual('complete_https', json.loads(state.read_text())['phase'])

    def test_final_validation_failure_never_triggers_helper_ssl_reload(self):
        self.fail_nginx_at = 3
        with self.assertRaisesRegex(helper.DeploymentError, 'SSL is incomplete'):
            self.deploy()
        self.assertEqual(1, self.reload_count)
        self.assertEqual('certbot_started', json.loads(self.paths()[2].read_text())['phase'])

    def test_https_failure_retains_site_and_does_not_reissue_on_healthy_retry(self):
        original = self.runner
        fail_tls = True

        def runner(args, timeout=60):
            if fail_tls and args[0] == self.c['curl']:
                raise helper.DeploymentError('TLS fixture failure')
            return original(args, timeout)

        self.runner = runner
        with self.assertRaisesRegex(helper.DeploymentError, 'SSL is incomplete'):
            self.deploy()
        count = sum(call[0] == self.c['certbot'] for call in self.calls)
        fail_tls = False
        self.deploy()
        self.assertEqual(count, sum(call[0] == self.c['certbot'] for call in self.calls))

    def test_missing_https_redirect_is_a_failed_deployment(self):
        self.redirect = False
        with self.assertRaisesRegex(helper.DeploymentError, 'redirect verification failed'):
            self.deploy()
        self.assertEqual('certbot_started', json.loads(self.paths()[2].read_text())['phase'])

    def test_no_ssl_cannot_downgrade_a_partial_ssl_attempt(self):
        self.fail_certbot = True
        with self.assertRaises(helper.DeploymentError):
            self.deploy()
        before = self.snapshot()
        with self.assertRaisesRegex(helper.DeploymentError, 'SSL attempt is pending'):
            self.deploy(no_ssl=True)
        self.assertEqual(before, self.snapshot())

    def test_completed_site_is_verified_without_reloading_or_reissuing(self):
        self.deploy()
        self.calls = []
        before = self.snapshot()
        self.deploy()
        self.assertEqual(before, self.snapshot())
        self.assertFalse(any(call[0] in (self.c['certbot'], self.c['systemctl']) for call in self.calls))

    def test_manually_edited_http_site_is_not_overwritten_on_retry(self):
        self.deploy(no_ssl=True)
        self.paths()[0].write_text('manually changed')
        before = self.snapshot()
        with self.assertRaisesRegex(helper.DeploymentError, 'configuration has changed'):
            self.deploy()
        self.assertEqual(before, self.snapshot())

    def test_preexisting_certificate_is_never_adopted_or_changed(self):
        for key, suffix in (('certbot_renewal_dir', '.conf'), ('certbot_live_dir', ''), ('certbot_archive_dir', '')):
            with self.subTest(key=key):
                path = Path(self.c[key]) / ('blog.pianolit.com'+suffix)
                path.write_text('existing certificate fixture')
                before = self.snapshot()
                with self.assertRaisesRegex(helper.DeploymentError, 'lineage already exists'):
                    self.deploy()
                self.assertEqual(before, self.snapshot())
                path.unlink()

    def test_missing_account_is_not_registered_automatically(self):
        self.c['certbot_account'] = 'b'*32
        with self.assertRaisesRegex(helper.DeploymentError, 'existing Certbot account is missing'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())

    def test_explicit_email_is_passed_as_one_argument_from_root_settings(self):
        self.c['certbot_email'] = 'deploy@example.com'
        self.deploy()
        command = next(call for call in self.calls if call[0] == self.c['certbot'])
        self.assertEqual('deploy@example.com', command[command.index('--email')+1])

    def test_fpm_auto_uses_new_version_after_upgrade_and_rejects_ambiguity(self):
        old = Path(self.c['php_fpm_socket_dir']) / 'php8.0-fpm.sock'
        old.unlink()
        latest = self.make_socket('php8.4-fpm.sock')
        deployment = helper.Deployment(self.c, self.runner, self.messages.append)
        self.assertEqual(str(latest), deployment.fpm_socket())
        self.make_socket('php8.3-fpm.sock')
        with self.assertRaisesRegex(helper.DeploymentError, 'Cannot select PHP-FPM'):
            deployment.fpm_socket()
        self.c['php_fpm_socket'] = str(latest)
        self.assertEqual(str(latest), deployment.fpm_socket())

    def test_stable_fpm_alias_takes_precedence_over_multiple_versions(self):
        new = self.make_socket('php8.4-fpm.sock')
        alias = Path(self.c['php_fpm_socket_dir']) / 'php-fpm.sock'
        alias.symlink_to(new)
        self.assertEqual(str(alias), helper.Deployment(self.c, self.runner, self.messages.append).fpm_socket())

    def test_missing_fpm_socket_or_invalid_server_settings_make_no_changes(self):
        for key, value in (('php_fpm_socket', '/missing/php.sock'), ('document_root', '/tmp/a;id'),
                           ('expected_ipv4', 'bad'), ('base_domain', '../pianolit.com')):
            previous = self.c[key]
            self.c[key] = value
            with self.subTest(key=key), self.assertRaises((helper.DeploymentError, ValueError)):
                self.deploy()
            self.c[key] = previous
            self.assertEqual(self.fingerprint, self.snapshot())

    def test_ssl_directives_cannot_be_introduced_in_http_template(self):
        path = Path(self.c['template'])
        path.write_text(path.read_text()+'\nssl_certificate /missing/certificate;\n')
        with self.assertRaisesRegex(helper.DeploymentError, 'HTTP only'):
            self.deploy()
        self.assertFalse(self.paths()[0].exists())

    def test_global_lock_prevents_overlapping_runs_and_is_released_on_close(self):
        path = self.base / 'lock'
        path.touch()
        before = path.read_bytes()
        lock = helper.deployment_lock(path)
        try:
            with self.assertRaisesRegex(helper.DeploymentError, 'Another subdomain deployment'):
                helper.deployment_lock(path)
        finally:
            lock.close()
        with helper.deployment_lock(path):
            self.assertEqual(before, path.read_bytes())

    def test_safe_execution_is_noninteractive_shell_free_and_environment_isolated(self):
        result = subprocess.CompletedProcess([], 0, 'done', '')
        with mock.patch.object(helper.subprocess, 'run', return_value=result) as run:
            self.assertEqual(('done', ''), helper.execute(['/usr/bin/program', 'one-argument'], 12))
        args, options = run.call_args
        self.assertEqual(['/usr/bin/program', 'one-argument'], args[0])
        self.assertNotIn('shell', options)
        self.assertEqual(subprocess.DEVNULL, options['stdin'])
        self.assertEqual(helper.ENV, options['env'])
        self.assertEqual(12, options['timeout'])

    def test_only_authorized_deployment_user_can_cross_root_boundary(self):
        users = {'pianolit-deploy': SimpleNamespace(pw_uid=1001, pw_name='pianolit-deploy'),
                 'www-data': SimpleNamespace(pw_uid=33, pw_name='www-data'),
                 'root': SimpleNamespace(pw_uid=0, pw_name='root')}
        with mock.patch.object(helper.sys, 'platform', 'linux'), mock.patch.object(helper.os, 'geteuid', return_value=0), \
                mock.patch.object(helper.pwd, 'getpwnam', side_effect=lambda name: users[name]), \
                mock.patch.dict(helper.os.environ, {'SUDO_USER': 'pianolit-deploy', 'SUDO_UID': '1001'}, clear=True):
            helper.authorize_caller(self.c)
            for name in ('www-data', 'root'):
                self.c['deploy_user'] = name
                with self.assertRaisesRegex(helper.DeploymentError, 'separate non-root'):
                    helper.authorize_caller(self.c)
            self.c['deploy_user'] = 'pianolit-deploy'
            helper.os.environ['SUDO_USER'] = 'www-data'
            with self.assertRaisesRegex(helper.DeploymentError, 'Only the configured deployment user'):
                helper.authorize_caller(self.c)
        with mock.patch.object(helper.sys, 'platform', 'linux'), mock.patch.object(helper.os, 'geteuid', return_value=1001):
            with self.assertRaisesRegex(helper.DeploymentError, 'authorized sudo rule'):
                helper.authorize_caller(self.c)

    def test_missing_email_or_ambiguous_certbot_account_fails_without_prompting(self):
        registration = Path(self.c['certbot_accounts_dir'], self.account, 'regr.json')
        registration.write_text('{"body": {"contact": []}}')
        with self.assertRaisesRegex(helper.DeploymentError, 'configure certbot_email'):
            self.deploy()
        self.assertFalse(self.paths()[0].exists())
        other = Path(self.c['certbot_accounts_dir'], 'b'*32)
        other.mkdir()
        (other / 'regr.json').write_text('{}')
        with self.assertRaisesRegex(helper.DeploymentError, 'Expected one existing Certbot account'):
            self.deploy()

    def test_pending_prepared_site_resumes_without_overwriting_and_cleans_stale_journal_temp(self):
        self.deploy(no_ssl=True)
        available, _, state = self.paths()
        original = available.read_bytes()
        journal = json.loads(state.read_text())
        journal['phase'] = 'prepared'
        state.write_text(json.dumps(journal))
        state.with_suffix('.tmp').write_text('{truncated previous journal update')
        self.deploy()
        self.assertEqual(original, self.initial_http)
        self.assertFalse(state.with_suffix('.tmp').exists())
        self.assertEqual('complete_https', json.loads(state.read_text())['phase'])

    def test_file_appearing_during_creation_is_never_enabled_or_overwritten(self):
        original = helper.Deployment.check_other_sites

        def collision(deployment, domain):
            original(deployment, domain)
            self.paths()[0].write_text('another root deployment created this')

        with mock.patch.object(helper.Deployment, 'check_other_sites', collision):
            with self.assertRaisesRegex(helper.DeploymentError, 'configuration has changed'):
                self.deploy()
        self.assertEqual('another root deployment created this', self.paths()[0].read_text())
        self.assertFalse(self.paths()[1].exists())
        self.assertEqual(0, self.reload_count)

    def test_privileged_inputs_reject_writable_files_or_ancestors_and_nonroot_owners(self):
        path = Path('/trusted/config')
        regular = SimpleNamespace(st_uid=0, st_mode=stat.S_IFREG | 0o644)
        unsafe = SimpleNamespace(st_uid=0, st_mode=stat.S_IFREG | 0o664)
        with mock.patch.object(Path, 'resolve', return_value=path), \
                mock.patch.object(Path, 'lstat', return_value=regular):
            self.assertEqual(path, ORIGINAL_TRUSTED(path))
        for info in (unsafe, SimpleNamespace(st_uid=1001, st_mode=stat.S_IFREG | 0o644)):
            with mock.patch.object(Path, 'resolve', return_value=path), \
                    mock.patch.object(Path, 'lstat', return_value=info):
                with self.assertRaisesRegex(helper.DeploymentError, 'root-owned'):
                    ORIGINAL_TRUSTED(path)
        with mock.patch.object(Path, 'resolve', return_value=path), \
                mock.patch.object(Path, 'lstat', side_effect=lambda p: unsafe if p == Path('/trusted') else regular,
                                  autospec=True):
            with self.assertRaisesRegex(helper.DeploymentError, 'root-owned'):
                ORIGINAL_TRUSTED(path)

    def test_child_process_errors_and_timeouts_are_actionable(self):
        for error in (OSError('missing executable'), subprocess.TimeoutExpired('fixture', 1)):
            with mock.patch.object(helper.subprocess, 'run', side_effect=error):
                with self.assertRaisesRegex(helper.DeploymentError, '/fixture'):
                    helper.execute(['/fixture'])
        with mock.patch.object(helper.subprocess, 'run', return_value=subprocess.CompletedProcess([], 7, '', 'fixture stderr')):
            with self.assertRaisesRegex(helper.DeploymentError, 'exit 7.*fixture stderr'):
                helper.execute(['/fixture'])

    def test_partial_http_write_failure_rolls_back_its_own_incomplete_file(self):
        original = helper.os.fdopen

        class BrokenWrite:
            def __init__(self, stream):
                self.stream = stream

            def __enter__(self):
                return self

            def write(self, data):
                self.stream.write(data[:20])
                raise OSError('fixture disk write failure')

            def __exit__(self, *args):
                self.stream.close()

        def fdopen(descriptor, mode):
            stream = original(descriptor, mode)
            return BrokenWrite(stream) if mode == 'wb' else stream

        with mock.patch.object(helper.os, 'fdopen', side_effect=fdopen):
            with self.assertRaisesRegex(OSError, 'fixture disk write failure'):
                self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())
        self.assertEqual(0, self.reload_count)

    def test_invalid_nginx_recovery_reports_both_failure_and_recovery(self):
        self.fail_reload_at = 1
        self.fail_nginx_at = 3
        with self.assertRaisesRegex(helper.DeploymentError, 'HTTP reload failed:.*Recovery also failed'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())

    def test_new_journal_is_atomic_and_never_replaces_an_existing_journal(self):
        deployment = helper.Deployment(self.c, self.runner, self.messages.append)
        deployment.state_path = self.paths()[2]
        deployment.state_path.write_text('another journal')
        deployment.journal = {'domain': 'blog.pianolit.com', 'phase': 'prepared'}
        with self.assertRaises(FileExistsError):
            deployment.save(new=True)
        self.assertEqual('another journal', deployment.state_path.read_text())
        self.assertFalse(deployment.state_path.with_suffix('.tmp').exists())

    def test_completed_site_dry_run_previews_verification_without_creation_or_certbot(self):
        self.deploy()
        before = self.snapshot()
        self.calls, self.messages = [], []
        self.deploy(dry_run=True)
        self.assertEqual(before, self.snapshot())
        self.assertFalse(any('Then execute argument array' in line for line in self.messages))
        self.assertTrue(any('no site/certificate changes or reloads' in line for line in self.messages))
        self.assertTrue(all(call[0] == self.c['dig'] for call in self.calls))

    def test_base_domain_and_all_server_paths_come_from_server_configuration(self):
        self.c['base_domain'] = 'music.example.org'
        helper.Deployment(self.c, self.runner, self.messages.append).run('notes', no_ssl=True)
        path = Path(self.c['sites_available'], 'notes.music.example.org')
        self.assertTrue(path.is_file())
        self.assertIn('server_name notes.music.example.org;', path.read_text())
        self.assertIn('root '+self.c['document_root']+';', path.read_text())
        self.assertTrue(any('notes.music.example.org.' in call for call in self.calls))

    def test_template_cannot_claim_other_names_or_become_default_server(self):
        path = Path(self.c['template'])
        original = path.read_text()
        for body in (original.replace('server_name {{domain}};', 'server_name {{domain}} other.pianolit.com;'),
                     original.replace('listen 80;', 'listen 80 default_server;')):
            path.write_text(body)
            with self.assertRaisesRegex(helper.DeploymentError, 'only the requested server_name'):
                self.deploy()
            self.assertFalse(self.paths()[0].exists())

    def test_missing_sites_enabled_include_rolls_back_before_reload_or_certbot(self):
        self.include_sites_enabled = False
        with self.assertRaisesRegex(helper.DeploymentError, 'does not load this managed site'):
            self.deploy()
        self.assertEqual(self.fingerprint, self.snapshot())
        self.assertEqual(0, self.reload_count)
        self.assertFalse(any(call[0] == self.c['certbot'] for call in self.calls))


if __name__ == '__main__':
    unittest.main(verbosity=2)
