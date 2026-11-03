<?php

namespace App\Console\Commands;

use App\Piece;
use App\Services\Api\V2\MatchTourMedia;
use App\Services\WebApp\MatchTour;
use Illuminate\Console\Command;

class PrepareMatchTourMedia extends Command
{
    protected $signature = 'api-v2:prepare-match-media';
    protected $description = 'Build private bounded audio and score samples for native match tours';

    public function handle(MatchTour $tour, MatchTourMedia $media)
    {
        $failures = 0;
        $prepare = function ($piece, $type) use ($media, &$failures) {
            try { $media->prepare($type === 'audio' ? $piece->audio_path : $piece->score_path, $type); }
            catch (\Throwable $error) { $this->error('Could not prepare '.$type.' for piece '.$piece->id.'. Check the source and media tools.'); $failures++; }
        };
        $tour->listeningPool()->select(['pieces.id', 'audio_path'])->setEagerLoads([])->withCount([])
            ->chunkById(100, function ($pieces) use ($prepare) { foreach ($pieces as $piece) $prepare($piece, 'audio'); }, 'pieces.id', 'id');
        $ids = collect($tour->scoreChoices())->filter()->pluck('id')->unique();
        foreach (Piece::whereIn('id', $ids)->select(['id', 'score_path'])->setEagerLoads([])->withCount([])->get() as $piece) $prepare($piece, 'score');
        $this->info($failures ? 'Preparation incomplete; keep native tours disabled.' : 'Samples prepared. Verify HTTPS media delivery before enabling native tours.');
        return $failures ? 1 : 0;
    }
}
