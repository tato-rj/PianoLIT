<?php

namespace App\Console\Commands;

use App\Piece;
use App\Services\Api\V2\MatchTourMedia;
use App\Services\Api\V2\MatchTourPreparationFailure;
use App\Services\WebApp\MatchTour;
use Illuminate\Console\Command;

class PrepareMatchTourMedia extends Command
{
    protected $signature = 'api-v2:prepare-match-media
        {--check : Check CLI dependencies without reading the catalogue or creating samples}
        {--piece= : Retry one eligible piece ID}
        {--type=all : Prepare all, audio, or score}';
    protected $description = 'Build private bounded audio and score samples for native match tours';

    public function handle(MatchTour $tour, MatchTourMedia $media)
    {
        $type = $this->option('type'); $pieceId = $this->option('piece');
        if (!in_array($type, ['all', 'audio', 'score'], true)
            || ($pieceId !== null && !preg_match('/^[1-9][0-9]*$/', $pieceId))) {
            $this->error('Use --type=all, audio or score and a positive integer --piece ID.');
            return 1;
        }
        $errors = $media->preparationErrors();
        if ($errors) {
            foreach ($errors as $error) $this->error($error);
            $this->error('Preparation prerequisites failed; keep native tours disabled.');
            return 1;
        }
        if ($this->option('check')) {
            $this->info('Preparation prerequisites found. No catalogue or sample files were changed.');
            return 0;
        }
        $audio = $tour->listeningPool()->select(['pieces.id', 'audio_path'])->setEagerLoads([])->withCount([]);
        if ($pieceId !== null) $audio->where('pieces.id', $pieceId);
        $ids = $type === 'audio' ? collect() : collect($tour->scoreChoices())->filter()->pluck('id')->unique();
        $scores = Piece::whereIn('id', $ids)->select(['id', 'score_path'])->setEagerLoads([])->withCount([]);
        if ($pieceId !== null) $scores->where('id', $pieceId);
        $total = ($type === 'score' ? 0 : $audio->count()) + $scores->count();
        if (!$total) { $this->error('No eligible tour media selected. The piece must belong to the shared tour pools.'); return 1; }
        $this->info('Checking '.$total.' samples. Existing valid samples are reused.');
        $failures = 0; $completed = 0;
        $prepare = function ($piece, $type) use ($media, &$failures, &$completed, $total) {
            $this->line('['.(++$completed).'/'.$total.'] Preparing '.$type.' for piece '.$piece->id.'...');
            try { $media->prepare($type === 'audio' ? $piece->audio_path : $piece->score_path, $type); }
            catch (\Throwable $error) {
                $detail = $error instanceof MatchTourPreparationFailure ? $error->getMessage() : 'Check source readability, media tools, free disk space and private sample-directory permissions.';
                $this->error('Could not prepare '.$type.' for piece '.$piece->id.'. '.$detail);
                $failures++;
            }
        };
        if ($type !== 'score') $audio->chunkById(100, function ($pieces) use ($prepare) { foreach ($pieces as $piece) $prepare($piece, 'audio'); }, 'pieces.id', 'id');
        foreach ($scores->get() as $piece) $prepare($piece, 'score');
        $this->line('Finished: '.($completed - $failures).' ready, '.$failures.' failed.');
        $this->info($failures ? 'Preparation incomplete; keep native tours disabled.' : ($pieceId !== null || $type !== 'all'
            ? 'Selected samples prepared. Run the full preparation check before enabling native tours.'
            : 'Samples prepared. Verify HTTPS media delivery before enabling native tours.'));
        return $failures ? 1 : 0;
    }
}
