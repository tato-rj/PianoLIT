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
        $key = trim((string) $piece->key);
        $resources = $this->availableResources($piece);
        $overview = $searchTitle.'.';
        if ($level) $overview .= ' Rated '.$level.' on PianoLIT.';
        $editorial = $piece->hasDescription()
            ? trim(preg_replace('/\s+/u', ' ', strip_tags($piece->description))) : '';
        $searchDescription = Str::limit($overview.' '.($editorial ?: ($resources
            ? 'Resources include '.implode(', ', $resources).'.' : 'Explore the piece and related piano repertoire.')), 160);
        $canonical = $this->hasContent($piece) ? route('webapp.pieces.show', $piece) : null;

        $composition = ['@type' => 'MusicComposition', 'name' => $name];
        if ($key) $composition['musicalKey'] = $key;
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

        return compact('searchTitle', 'searchDescription', 'searchEntity', 'canonical', 'resources');
    }

    private function availableResources(Piece $piece): array
    {
        $resources = [];
        $media = $piece->media;
        if ($piece->is_public_domain && trim((string) $piece->score_path) !== '') $resources[] = 'sheet music';
        if (!$piece->is_public_domain && trim((string) $piece->score_url) !== '') $resources[] = 'sheet music purchase link';
        if (trim((string) $piece->audio_path) !== '') $resources[] = 'audio recording';
        $hasComments = false;
        foreach (['performance' => 'performance video', 'synthesia' => 'Synthesia'] as $kind => $label) {
            $video = $media[$kind];
            if (!$video || trim((string) $video->video_url) === '') continue;
            $resources[] = $label;
            $hasComments = $hasComments || $video->moments->contains(fn ($moment) => trim((string) $moment->comment) !== '');
        }
        if ($hasComments) $resources[] = 'timed video commentary';

        return $resources;
    }
}
