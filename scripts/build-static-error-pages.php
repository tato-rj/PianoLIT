<?php

// Build nginx fallbacks from the same Blade page, without loading local secrets
// or connecting to a database, session store, mailer or third-party service.
require __DIR__.'/../vendor/autoload.php';
foreach ([
    'APP_ENV' => 'testing',
    'APP_CONFIG_CACHE' => __DIR__.'/../bootstrap/cache/error-pages-not-used.php',
    'APP_URL' => 'https://pianolit.com',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array',
    'LOG_CHANNEL' => 'stderr',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('tests/Review/.env-not-used');
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['app.debug' => false, 'app.short_url' => 'pianolit.com']);
foreach (['regular' => 'pianolit.com', 'admin' => 'admin.pianolit.com'] as $brand => $host) {
    $directory = public_path('errors/'.$brand);
    if (!is_dir($directory)) mkdir($directory, 0755, true);
    foreach ([404, 413, 429, 500, 502, 503, 504] as $status) {
        // The proxy does not know whether the original request was a form post.
        // Static recovery therefore always offers a safe GET to this hub's root.
        $errorRequest = \Illuminate\Http\Request::create('https://'.$host.'/unavailable', 'POST');
        $html = view('errors.page', compact('status', 'errorRequest'))->render();
        file_put_contents($directory.'/'.$status.'.html', $html);
    }
}
echo "Built 14 static error pages from the shared Blade template.\n";
