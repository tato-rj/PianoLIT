<?php

// One-time preparation for PianoLIT's new Leftlane Algolia application.
// The running site's configuration and source database are never written here.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$mode = $argv[1] ?? '--help';
if (! in_array($mode, ['--prepare', '--verify'], true) || count($argv) !== 2) {
    echo "From the live Laravel project directory:\n";
    echo "  php scripts/prepare-algolia.php --prepare  # Populate the new app before changing .env\n";
    echo "  php scripts/prepare-algolia.php --verify   # Check after the owner switches .env/config\n";
    exit($mode === '--help' ? 0 : 1);
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$key = $activeKey = '';

try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    if (! $app->environment('production')) {
        throw new RuntimeException('This helper requires APP_ENV=production; local setup uses local_pieces.');
    }
    if (config('scout.driver') !== 'algolia' || (new App\Piece)->searchableAs() !== 'pieces') {
        throw new RuntimeException('Expected the existing Algolia driver and pieces index. Inspect the server configuration first.');
    }

    $targetApplication = '5VDLMR7E2W';
    $activeKey = (string) config('scout.algolia.secret');
    if ($mode === '--prepare') {
        if (config('scout.algolia.id') === $targetApplication) {
            throw new RuntimeException('The site already uses the new application. Use --verify; this helper will not flush its active index.');
        }
        $question = new Symfony\Component\Console\Question\Question('New Leftlane Algolia Write API Key (hidden): ');
        $question->setHidden(true)->setHiddenFallback(false);
        $key = trim((string) (new Symfony\Component\Console\Helper\QuestionHelper)->ask(
            new Symfony\Component\Console\Input\ArgvInput([]),
            new Symfony\Component\Console\Output\ConsoleOutput,
            $question
        ));
        if ($key === '') throw new RuntimeException('No API key entered.');
    } else {
        if (config('scout.algolia.id') !== $targetApplication) {
            throw new RuntimeException('The running configuration still uses the old application. Complete the environment switch first.');
        }
        $key = $activeKey;
    }

    // This override applies only to this CLI process, including when config is cached.
    config([
        'scout.algolia.id' => $targetApplication,
        'scout.algolia.secret' => $key,
        'scout.prefix' => '',
        'scout.queue' => false,
        'scout.synchronous' => true,
    ]);
    $app->make(Laravel\Scout\EngineManager::class)->forgetDrivers();
    $client = $app->make(Algolia\AlgoliaSearch\SearchClient::class);
    $index = $client->initIndex('pieces');
    $sourceCount = App\Piece::count();
    if ($sourceCount < 1) throw new RuntimeException('Source catalogue is empty; stopped before importing.');

    if ($mode === '--prepare') {
        foreach ($client->listIndices()['items'] as $existing) {
            if ($existing['name'] === 'pieces' && (! empty($existing['entries']) || ! empty($existing['pendingTask']))) {
                throw new RuntimeException('The new pieces index already has records or pending tasks. Inspect it before rebuilding; nothing was flushed.');
            }
        }
        echo 'Preparing new application '.$targetApplication.', index pieces, from '.$sourceCount." database pieces.\n";
        foreach (['scout:sync', 'scout:import'] as $command) {
            $arguments = ['searchable' => App\Piece::class, '--no-interaction' => true];
            if ($command === 'scout:sync') $arguments['--keep'] = 'local';
            $output = new Symfony\Component\Console\Output\BufferedOutput;
            try {
                $status = $kernel->call($command, $arguments, $output);
            } finally {
                echo str_replace([$key, $activeKey], '[redacted]', $output->fetch());
            }
            if ($status !== 0) throw new RuntimeException($command.' failed. The running site configuration was not changed.');
        }
    }

    $result = $index->search('', ['hitsPerPage' => 1, 'attributesToRetrieve' => ['objectID']]);
    if ((int) ($result['nbHits'] ?? -1) !== $sourceCount) {
        throw new RuntimeException('Indexed count differs from the source. Inspect pending tasks and catalogue edits before switching.');
    }
    $sample = App\Piece::query()->whereNotNull('name')->where('name', '!=', '')->orderBy('id')->firstOrFail();
    $objectId = Algolia\ScoutExtended\Searchable\ObjectIdEncrypter::encrypt($sample);
    $index->getObject($objectId);
    $search = $index->search($sample->long_name, ['hitsPerPage' => 1]);
    if ((int) ($search['nbHits'] ?? 0) < 1) throw new RuntimeException('Sample piece-title search returned no results.');
    echo 'Verified '.$sourceCount." records and a piece-title search in the new pieces index.\n";
    if ($mode === '--prepare') {
        echo "Preparation complete. The running site's .env/cached configuration still uses its existing connection.\n";
        echo "Next, update the server environment and refresh configuration as described in docs/ALGOLIA_SETUP.md.\n";
    }
} catch (Throwable $exception) {
    $secrets = array_values(array_filter([$key, $activeKey], function ($value) { return $value !== ''; }));
    fwrite(STDERR, 'Stopped: '.str_replace($secrets, '[redacted]', $exception->getMessage()).PHP_EOL);
    exit(1);
}
