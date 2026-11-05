<?php

namespace App\Services\Pieces;

use App\{Piece, Tutorial};
use App\Services\Timeline\WebTimeline;
use App\Services\WebApp\PieceRecommendations;

/** The catalogue content of the browser and native piece pages. No account writes. */
class PageContent
{
    public function data(Piece $piece): array
    {
        $piece->loadMissing(['tags', 'composer.country', 'tutorials.moments']);
        $timeline = new WebTimeline;
        return [
            'piece' => $piece,
            'timelinePeriod' => $timeline->periodForPiece($piece),
            'timeline' => $timeline->forPiece($piece),
            'recommendationRows' => app(PieceRecommendations::class)->rows($piece),
            'synthesiaReleases' => $this->synthesiaReleases(),
        ];
    }

    public function synthesiaReleases()
    {
        return Tutorial::synthesia(12)->loadMissing('piece.tags');
    }
}
