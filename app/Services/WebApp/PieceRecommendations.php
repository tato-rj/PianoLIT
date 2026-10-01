<?php

namespace App\Services\WebApp;

use App\{Piece, Tag};

class PieceRecommendations
{
    public function rows(Piece $piece)
    {
        $piece->loadMissing('tags');
        $rows = collect([[
            'title' => 'More like this',
            'color' => GalleryGradients::at(0),
            'url' => route('webapp.pieces.similar', $piece),
            'pieces' => $piece->similar()->where('id', '!=', $piece->id)->take(16),
        ]]);

        $moods = $piece->mood();
        $matches = [
            ['Similar mood', GalleryGradients::at(1), $moods->isNotEmpty() ? $moods->random() : null],
            ['Similar difficulty', GalleryGradients::at(2), $piece->level],
            ['Same period', GalleryGradients::at(3), $piece->period],
        ];

        foreach ($matches as [$title, $color, $tag]) {
            if (! $tag) continue;

            $pieces = Piece::where('pieces.id', '!=', $piece->id)
                ->whereHas('tags', function ($query) use ($tag) {
                    $query->where('tags.id', $tag->id);
                })
                ->without('composer')->inRandomOrder()->limit(16)->get();

            $rows->push([
                'title' => $title,
                'color' => $color,
                'term' => $tag->name,
                'url' => route('webapp.search.results', ['search' => $tag->name, 'model' => Tag::class]),
                'pieces' => $pieces,
            ]);
        }

        // Batch tags, composers and media badges across the new galleries.
        PieceCards::load($rows->pluck('pieces')->collapse(), false);

        return $rows->filter(function ($row) { return $row['pieces']->isNotEmpty(); })->values();
    }
}
