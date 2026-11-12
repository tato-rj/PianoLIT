<?php

namespace App\Services\WebApp;

use App\Piece;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PublicRepertoire
{
    private const CONTENT_FIELDS = ['audio_path', 'score_path', 'score_url', 'description'];

    public function pieces(): Builder
    {
        return Piece::query()->whereHas('composer')->where(function (Builder $query) {
            $query->has('tutorials');
            foreach (self::CONTENT_FIELDS as $field) {
                $query->orWhereRaw("TRIM(pieces.{$field}) <> ''");
            }
        });
    }

    private function hasContent(Piece $piece): bool
    {
        if ($piece->tutorials->isNotEmpty()) return true;

        foreach (self::CONTENT_FIELDS as $field) {
            if (trim((string) $piece->getRawOriginal($field)) !== '') return true;
        }

        return false;
    }

    public function pieceMetadata(Piece $piece): array
    {
        $name = trim($piece->medium_name);
        $composer = $piece->composer->name;
        $credit = $piece->is_attributed_to ? ' attributed to ' : ' by ';
        $searchTitle = $name.$credit.$composer;
        $level = $piece->extended_level_name;
        $facts = array_filter([
            'Key or mode' => trim((string) $piece->key),
            'Period' => optional($piece->period)->name,
            'Collection' => $piece->collection_name,
            'Composed' => $piece->composed_in,
            'First published' => $piece->published_in,
            'Techniques' => $piece->tags->where('type', 'technique')->pluck('name')->implode(', '),
        ]);
        $media = $piece->media;
        $resources = array_filter([
            $media['performance'] ? 'performance video' : null,
            $media['synthesia'] ? 'Synthesia tutorial' : null,
            $media['lessons'] ? 'video lessons' : null,
            $piece->hasAudio() ? 'audio recording' : null,
            $piece->score_path && $piece->is_public_domain ? 'sheet music' : null,
            $piece->score_url ? 'sheet music link' : null,
        ]);
        $overview = $searchTitle.'.';
        if ($level) $overview .= ' Rated '.$level.' on PianoLIT.';
        $editorial = $piece->hasDescription()
            ? trim(preg_replace('/\s+/u', ' ', strip_tags($piece->description))) : '';
        $searchDescription = Str::limit($overview.' '.($editorial ?: ($resources
            ? 'Resources include '.implode(', ', $resources).'.' : 'Explore the piece and related piano repertoire.')), 160);
        $canonical = $this->hasContent($piece) ? route('webapp.pieces.show', $piece) : null;

        $composition = ['@type' => 'MusicComposition', 'name' => $name];
        if (isset($facts['Key or mode'])) $composition['musicalKey'] = $facts['Key or mode'];
        if (!$piece->is_attributed_to) {
            $composition['composer'] = ['@type' => 'Person', 'name' => $composer, 'url' => route('webapp.composers.show', $piece->composer)];
        }
        $searchEntity = [
            '@type' => 'WebPage',
            'name' => $searchTitle,
            'url' => $canonical,
            'description' => $searchDescription,
            'mainEntity' => $composition,
        ];

        return compact('searchTitle', 'searchDescription', 'searchEntity', 'canonical', 'facts', 'resources');
    }
}
