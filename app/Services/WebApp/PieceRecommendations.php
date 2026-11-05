<?php

namespace App\Services\WebApp;

use App\{Piece, Tag};

class PieceRecommendations
{
    private const ROW_LIMIT = 6;

    public function rows(Piece $piece)
    {
        $piece->loadMissing('tags');
        $rows = collect([[
            'key' => 'collection',
            'title' => 'From the same collection',
            'url' => route('webapp.pieces.collection', $piece),
            'pieces' => $this->collection($piece),
        ], [
            'key' => 'best-matches',
            'title' => 'Best matches',
            'color' => GalleryGradients::at(0),
            'url' => route('webapp.pieces.similar', $piece),
            'pieces' => (new SimilarPieces)->forPiece($piece, self::ROW_LIMIT),
        ]]);

        $moods = $piece->mood();
        $matches = [
            ['similar-mood', 'Similar mood', GalleryGradients::at(1), $moods->isNotEmpty() ? $moods->random() : null],
            ['similar-difficulty', 'Similar difficulty', GalleryGradients::at(2), $piece->level],
            ['same-period', 'Same period', GalleryGradients::at(3), $piece->period],
        ];

        foreach ($matches as [$key, $title, $color, $tag]) {
            $pieces = $tag ? Piece::where('pieces.id', '!=', $piece->id)
                ->whereHas('tags', function ($query) use ($tag) {
                    $query->where('tags.id', $tag->id);
                })
                ->without('composer')->inRandomOrder()->limit(self::ROW_LIMIT)->get() : collect();

            $rows->push([
                'key' => $key,
                'title' => $title,
                'color' => $color,
                'term' => $tag->name ?? null,
                'url' => $tag ? route('webapp.search.results', ['search' => $tag->name, 'model' => Tag::class]) : null,
                'pieces' => $pieces,
            ]);
        }

        $rows->push([
            'key' => 'same-composer',
            'title' => 'Same composer',
            'url' => route('webapp.composers.show', $piece->composer_id),
            'pieces' => Piece::where('pieces.id', '!=', $piece->id)
                ->where('composer_id', $piece->composer_id)
                ->without('composer')->inRandomOrder()->limit(self::ROW_LIMIT)->get(),
        ]);

        // Batch tags, composers and media badges across the new galleries.
        PieceCards::load($rows->pluck('pieces')->collapse(), false);

        // Keep every recommendation tab, including empty categories. Collection
        // artwork gets its own row only when actual siblings exist.
        return $rows->filter(function ($row) {
            return $row['key'] !== 'collection' || $row['pieces']->isNotEmpty();
        })->values();
    }

    private function collection(Piece $piece)
    {
        if ((! $piece->collection_name && ! $piece->catalogue_number) || $piece->single) return collect();

        $query = Piece::where('pieces.id', '!=', $piece->id)->without('composer');
        // These named collections intentionally span catalogue numbers and,
        // for Anna Magdalena's notebook, composers, just like siblings().
        if (in_array($piece->collection_name, ['Notebook for Anna Magdalena Bach', 'The Well-Tempered Clavier Book 1'], true)) {
            $query->where('collection_name', $piece->collection_name);
        } else {
            $query->where([
                'composer_id' => $piece->composer_id,
                'collection_name' => $piece->collection_name,
                'catalogue_name' => $piece->catalogue_name,
                'catalogue_number' => $piece->catalogue_number,
            ]);
        }

        return $query->orderByRaw('cast(collection_number as unsigned)')
            ->orderByRaw('cast(movement_number as unsigned)')->orderBy('pieces.id')->limit(self::ROW_LIMIT)->get();
    }
}
