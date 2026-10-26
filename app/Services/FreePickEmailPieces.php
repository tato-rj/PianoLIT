<?php

namespace App\Services;

use App\Piece;

class FreePickEmailPieces
{
    public function latest(string $websiteUrl, int $featuredPieceId): array
    {
        // Match the web app's latest repertoire eligibility, requiring card art.
        return Piece::with('composer')->withVideos()->whereHas('composer')
            ->where('pieces.id', '<>', $featuredPieceId)
            ->whereNotNull('cover_path')->where('cover_path', '<>', '')
            ->latest()->orderByDesc('id')->limit(3)->get()->map(function ($piece) use ($websiteUrl) {
                return [
                    'title' => $piece->medium_name,
                    'composer' => $piece->composer->name,
                    'image' => $websiteUrl.'/storage/'.ltrim($piece->cover_path, '/'),
                    'url' => route('webapp.pieces.show', $piece->id),
                ];
            })->all();
    }
}
