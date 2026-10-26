<?php

namespace App\Http\Resources\Api\V2;

use App\{Composer, Piece};
use App\Services\DiscoverFeed;
use App\Services\WebApp\PieceCards;
use Illuminate\Http\Resources\Json\JsonResource;

/** JSON presentation of DiscoverFeed output; no queries selecting feed content. */
class DiscoverResource extends JsonResource
{
    public function withResponse($request, $response)
    {
        $response->headers->set('Cache-Control', 'private, no-store');
    }

    public function toArray($request)
    {
        $rows = collect($this->resource);
        $cards = $rows->where('type', 'piece')->flatMap(function ($row) {
            return collect($row['content'])->map(function ($piece) { return clone $piece; });
        });
        PieceCards::load($cards, false);
        $cards = $cards->keyBy('id');
        $sections = [];
        $hasLevels = false;
        foreach ($rows as $row) {
            if (strpos($row['id'], 'levels_') === 0) {
                if (!$hasLevels && ($levels = $this->levels($rows))) $sections[] = $levels;
                $hasLevels = true;
                continue;
            }
            $section = [
                'id' => $row['id'], 'title' => $row['display_title'],
                'kind' => $row['kind'],
                'items' => collect($row['content'])->map(function ($model) use ($cards) {
                    return $model instanceof Piece ? $this->piece($cards->get($model->id)) : $this->composer($model);
                })->values()->all(),
            ];
            if (isset($row['subtitle'])) $section['subtitle'] = $row['subtitle'];
            if ($row['id'] === 'composer_feature') {
                $section['group'] = $row['composer_group'];
                $section['composer'] = $row['featured_composer'] ? $this->composer($row['featured_composer']) : null;
                $section['biography_excerpt'] = $row['featured_composer']
                    ? DiscoverFeed::composerExcerpt($row['featured_composer']) : null;
            }
            if (isset($row['tag'])) $section['query'] = $row['tag'];
            $sections[] = $section;
        }
        return ['sections' => $sections];
    }

    private function piece(Piece $piece): array
    {
        return [
            'id' => (string) $piece->id,
            'title' => $piece->name,
            'composer' => $this->composer($piece->composer),
            'attribution' => $piece->attribution,
            'artwork_url' => $piece->discover_artwork_url,
            'difficulty' => ['name' => $piece->level_name, 'label' => $piece->extended_level_name ? ucfirst($piece->extended_level_name) : null],
            'is_new' => (bool) $piece->is_new,
            'is_free_pick' => (bool) $piece->is_free,
            'available_media' => [
                'video' => (bool) $piece->webapp_has_video,
                'audio' => $piece->hasAudio(), 'score' => $piece->hasScore(true),
                'synthesia' => (bool) $piece->webapp_has_synthesia,
            ],
            'destination' => ['kind' => 'piece', 'id' => (string) $piece->id],
        ];
    }

    private function composer(Composer $composer): array
    {
        return [
            'id' => (string) $composer->id, 'name' => $composer->name,
            'short_name' => $composer->short_name,
            'portrait_url' => $composer->cover_image ?: asset('images/misc/placeholder-image.png'),
            'piece_count' => (int) $composer->pieces_count,
            'destination' => ['kind' => 'composer_repertoire', 'id' => (string) $composer->id],
        ];
    }

    private function levels($rows): ?array
    {
        $systems = DiscoverFeed::levelSystems($rows);
        if (!$systems->contains(function ($system) { return $system['cards']->isNotEmpty(); })) return null;
        return [
            'id' => 'levels', 'title' => DiscoverFeed::LEVELS_TITLE, 'subtitle' => DiscoverFeed::LEVELS_SUBTITLE, 'kind' => 'level_systems',
            'systems' => $systems->map(function ($system) {
                return [
                    'id' => $system['key'], 'label' => $system['label'],
                    'items' => $system['cards']->map(function ($card) use ($system) {
                        $number = preg_replace('/^'.$system['key'].'\s*(?:book|level)?\s*/i', '', $card->name);
                        return [
                            'number' => $number, 'label' => $system['unit'].' '.$number,
                            'piece_count' => (int) $card->pieces_count,
                            'destination' => ['kind' => 'search', 'query' => $card->name],
                        ];
                    })->all(),
                ];
            })->all(),
        ];
    }
}
