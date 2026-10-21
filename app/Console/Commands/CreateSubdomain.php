<?php

namespace App\Console\Commands;

use App\Services\Deployment\SubdomainDeployment;
use Illuminate\Console\Command;
use Throwable;

class CreateSubdomain extends Command
{
    protected $signature = 'subdomain:create {name : One lowercase DNS label, for example blog}
        {--dry-run : Check prerequisites and preview without changing the server}
        {--no-ssl : Create HTTP only; skip Certbot and HTTPS verification}';

    protected $description = 'Provision a subdomain through the restricted root-owned deployment helper.';

    public function handle(SubdomainDeployment $deployment)
    {
        if (PHP_SAPI !== 'cli') {
            $this->error('Subdomain deployment is available only from the deployment user’s CLI.');
            return 1;
        }

        try {
            $name = (string) $this->argument('name');
            $deployment->validateName($name);
            $this->line('PHP CLI '.PHP_VERSION.'; the helper checks PHP-FPM independently.');
            $status = $deployment->run($name, (bool) $this->option('dry-run'), (bool) $this->option('no-ssl'), function ($buffer) {
                $this->output->write($buffer, false, \Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);
            });
            if ($status !== 0) {
                $this->error('Subdomain deployment failed (exit '.$status.'). Review the helper output above; retry the same command after correcting the cause.');
            }
            return $status;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
