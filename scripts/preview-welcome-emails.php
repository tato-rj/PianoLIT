<?php

// Static previews only: never load .env, contact services, or use the normal DB.
require __DIR__.'/../vendor/autoload.php';

foreach ([
    'APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => __DIR__.'/../bootstrap/cache/welcome-preview-not-used.php',
    'APP_URL' => 'https://pianolit.com', 'APP_NAME' => 'PianoLIT',
    'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'SCOUT_DRIVER' => 'null', 'LOG_CHANNEL' => 'stderr',
    'BUGSNAG_NOTIFY_RELEASE_STAGES' => '__preview_disabled__',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('tests/Review/.env-not-used');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Preview requires in-memory SQLite.');
}
config(['app.short_url' => 'pianolit.com', 'app.debug' => false, 'activity.enabled' => false, 'attribution.enabled' => false]);
$output = $argv[1] ?? sys_get_temp_dir().'/pianolit-welcome-preview';
if (!is_dir($output.'/assets')) mkdir($output.'/assets', 0755, true);
foreach (['floating-logo.png', 'app-devices.jpg', 'tablet-iphone.jpg', 'webapp-devices.jpg', 'resource-browser.jpg', 'app-store.png'] as $file) {
    copy(resource_path('images/emails/welcome/'.$file), $output.'/assets/'.$file);
}
foreach (['website' => 'account-welcome-website', 'webapp' => 'account-welcome', 'ios' => 'account-welcome-ios'] as $file => $key) {
    $sample = app(App\Services\Admin\AutomaticEmails::class)->render($key);
    $html = preg_replace('#https://pianolit\.com/images/emails/welcome/([^"?]+)\?id=[a-f0-9]+#', 'assets/$1', $sample['html']);
    file_put_contents($output.'/'.$file.'.html', $html);
    file_put_contents($output.'/'.$file.'.txt', $sample['text']);
    echo $output.'/'.$file.'.html'.PHP_EOL;
}
