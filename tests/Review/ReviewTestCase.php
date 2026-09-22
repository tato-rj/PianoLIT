<?php

namespace Tests\Review;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\{Http, Mail, Redis};

abstract class ReviewTestCase extends \Tests\TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        // Do not load developer credentials or a cached production configuration.
        $app->loadEnvironmentFrom('tests/Review/.env-not-used');
        $app->make(Kernel::class)->bootstrap();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Review tests require in-memory SQLite.');
        }

        config(['scout.driver' => null, 'mail.default' => 'array']);
        Redis::swap(\Mockery::mock());
        Http::fake();
        Mail::fake();

        return $app;
    }
}
