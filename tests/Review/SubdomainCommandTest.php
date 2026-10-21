<?php

namespace Tests\Review;

use App\Services\Deployment\SubdomainDeployment;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class SubdomainCommandTest extends ReviewTestCase
{
    public $mockConsoleOutput = false;

    public function test_invalid_labels_never_reach_the_privileged_process()
    {
        $service = \Mockery::mock(SubdomainDeployment::class)->makePartial();
        $service->shouldNotReceive('run');
        $this->app->instance(SubdomainDeployment::class, $service);
        foreach (['', 'Blog', 'blog.pianolit.com', '../blog', '-blog', 'blog-', 'a_b', '*',
            'a/b', 'blog;id', 'blog$(id)', "blog\n", 'é', str_repeat('a', 64)] as $name) {
            $this->assertSame(1, Artisan::call('subdomain:create', ['name' => $name]));
            $this->assertStringContainsString('lowercase DNS label', Artisan::output());
        }
    }

    public function test_valid_names_and_options_use_only_an_argument_array_and_stream_progress()
    {
        foreach (['blog', 'a', '0', 'learn-2', str_repeat('a', 63)] as $name) {
            $service = $this->fakeProcessService();
            $this->app->instance(SubdomainDeployment::class, $service);
            $this->assertSame(0, Artisan::call('subdomain:create', ['name' => $name, '--dry-run' => true, '--no-ssl' => true]));
            $this->assertSame(['/usr/bin/sudo', '-n', '--', '/usr/local/sbin/pianolit-subdomain',
                $name, '--dry-run', '--no-ssl'], $service->arguments);
            $output = Artisan::output();
            $this->assertStringContainsString('PHP CLI '.PHP_VERSION, $output);
            $this->assertStringContainsString('fake helper progress', $output);
        }
    }

    public function test_default_invocation_has_no_ssl_bypass_and_propagates_helper_failure()
    {
        $service = $this->fakeProcessService(1);
        $this->app->instance(SubdomainDeployment::class, $service);
        $this->assertSame(1, Artisan::call('subdomain:create', ['name' => 'blog']));
        $this->assertSame(['/usr/bin/sudo', '-n', '--', '/usr/local/sbin/pianolit-subdomain', 'blog'], $service->arguments);
        $this->assertStringContainsString('deployment failed (exit 1)', Artisan::output());
    }

    public function test_process_start_errors_are_reported_without_a_stack_trace()
    {
        $service = \Mockery::mock(SubdomainDeployment::class)->makePartial();
        $service->shouldReceive('run')->once()->andThrow(new \RuntimeException('sudo unavailable'));
        $this->app->instance(SubdomainDeployment::class, $service);
        $this->assertSame(1, Artisan::call('subdomain:create', ['name' => 'blog']));
        $this->assertStringContainsString('sudo unavailable', Artisan::output());
    }

    public function test_invalid_application_script_paths_cannot_reach_process_execution()
    {
        $service = $this->fakeProcessService();
        $this->app->instance(SubdomainDeployment::class, $service);
        foreach (['script', '/tmp/../script', '/tmp/a;id', "/tmp/a\n"] as $path) {
            config(['subdomains.script' => $path]);
            $this->assertSame(1, Artisan::call('subdomain:create', ['name' => 'blog']));
            $this->assertStringContainsString('absolute deployment-script path', Artisan::output());
            $this->assertNull($service->arguments);
        }
    }

    public function test_default_installed_path_works_with_an_older_configuration_cache()
    {
        config()->offsetUnset('subdomains');
        $service = $this->fakeProcessService();
        $this->app->instance(SubdomainDeployment::class, $service);
        $this->assertSame(0, Artisan::call('subdomain:create', ['name' => 'blog']));
        $this->assertSame('/usr/local/sbin/pianolit-subdomain', $service->arguments[3]);
    }

    private function fakeProcessService(int $exitCode = 0): SubdomainDeployment
    {
        return new class($exitCode) extends SubdomainDeployment {
            public $arguments;
            private $exitCode;

            public function __construct($exitCode) { $this->exitCode = $exitCode; }

            protected function makeProcess(array $arguments): Process
            {
                $this->arguments = $arguments;
                $process = \Mockery::mock(Process::class);
                $process->shouldReceive('setInput')->once()->with(null);
                $process->shouldReceive('run')->once()->andReturnUsing(function ($callback) {
                    $callback(Process::OUT, "fake helper progress\n");
                    return $this->exitCode;
                });
                return $process;
            }
        };
    }

    public function test_standalone_helper_suite_uses_only_temporary_files_and_fake_dependencies()
    {
        // This runs unittest, NOT the privileged script entry point. Every
        // Nginx/Certbot/DNS/HTTP operation is replaced by an in-memory fake.
        $process = new Process(['/usr/bin/python3', '-I', base_path('tests/deployment/subdomains_test.py')], base_path());
        $process->setTimeout(60);
        $process->run();
        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
    }
}
