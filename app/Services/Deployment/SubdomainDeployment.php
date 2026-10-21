<?php

namespace App\Services\Deployment;

use InvalidArgumentException;
use Symfony\Component\Process\Process;

class SubdomainDeployment
{
    public function validateName(string $name): void
    {
        if (! preg_match('/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/D', $name)) {
            throw new InvalidArgumentException('Use one lowercase DNS label (1–63 letters/digits/hyphens), with no leading/trailing hyphen.');
        }
    }

    public function run(string $name, bool $dryRun, bool $noSsl, callable $output): int
    {
        $this->validateName($name);
        $script = config('subdomains.script', '/usr/local/sbin/pianolit-subdomain');
        if (! is_string($script) || ! preg_match('~\A/[a-zA-Z0-9_./-]+\z~D', $script)
            || in_array('..', explode('/', $script), true)) {
            throw new InvalidArgumentException('subdomains.script must be an absolute deployment-script path.');
        }

        // Argument arrays bypass the shell. sudo -n never asks for a password.
        // The helper independently checks the caller, label and root settings.
        $arguments = ['/usr/bin/sudo', '-n', '--', $script, $name];
        if ($dryRun) $arguments[] = '--dry-run';
        if ($noSsl) $arguments[] = '--no-ssl';
        $process = $this->makeProcess($arguments);
        $process->setInput(null);

        // The helper owns bounded subprocess timeouts and the global lock. Do
        // not kill it halfway through a reload/Certbot transaction from here.
        return $process->run(function ($type, $buffer) use ($output) {
            $output($buffer);
        });
    }

    protected function makeProcess(array $arguments): Process
    {
        return new Process($arguments, '/', null, null, null);
    }
}
