<?php

namespace App\Console\Commands;

use App\Analytics\{GoogleAnalyticsPageViews, PieceWebViewCounts};
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\{Cache, DB};

class SyncPieceWebViews extends Command
{
    public const TIMEZONE = 'America/New_York';

    protected $signature = 'analytics:sync-piece-web-views';
    protected $description = 'Refresh saved web app piece views from Google Analytics';

    public function handle(GoogleAnalyticsPageViews $google, PieceWebViewCounts $saved)
    {
        if (!$google->configured()) {
            $this->error('Google Analytics reporting is not configured.');
            return 1;
        }

        try {
            $result = Cache::lock('analytics:sync-piece-web-views', 1800)->get(function () use ($google, $saved) {
                $through = now(self::TIMEZONE)->subDay()->toDateString();
                $paths = DB::table('pieces')->orderBy('id')->pluck('id')
                    ->mapWithKeys(fn ($id) => [route('webapp.pieces.show', $id, false) => $id])->all();

                // July 26, 2023 is the earliest piece-page view in this GA property.
                $report = $google->fetch('my.pianolit.com', array_keys($paths), '2023-07-26', $through);
                $counts = [];
                foreach ($report as $path => $views) {
                    $counts[$paths[$path]] = $views;
                }

                $updated = $saved->save($counts, $through);
                $total = count($paths);
                $this->info("Updated web views for {$updated} of {$total} pieces through {$through}.");
                if ($updated < $total) $this->warn('Pieces without a current report kept their previous counts.');
                return 0;
            });
        } catch (\Throwable $error) {
            // Google exceptions can contain request details. Print only a safe diagnostic.
            $status = $error instanceof RequestException ? ' HTTP '.$error->response->status() : '';
            $this->error('Piece web views sync failed. Saved counts were kept. '.class_basename($error).$status);
            return 1;
        }

        if ($result === false) {
            $this->info('Piece web views sync is already running.');
            return 0;
        }
        return $result;
    }
}
