<?php

namespace App\Services;

use App\Piece;

class WelcomePieces
{
    public function random(string $websiteUrl): array
    {
        // The due schedule, even before history sync, determines the current pick.
        // Historical highlights here do not receive new access grants.
        return Piece::query()->freepicks(false)->where('highlighted_at', '<=', now())
            ->free(false)->whereNotNull('cover_path')->where('cover_path', '<>', '')
            ->whereHas('tutorials')->whereHas('composer')
            ->inRandomOrder()->limit(3)->get()->map(function ($piece) use ($websiteUrl) {
                return [
                    'title' => $piece->medium_name,
                    'composer' => $piece->composer->name,
                    'image' => $websiteUrl.'/storage/'.ltrim($piece->cover_path, '/'),
                    'url' => route('webapp.pieces.show', $piece->id),
                ];
            })->all();
    }
}
