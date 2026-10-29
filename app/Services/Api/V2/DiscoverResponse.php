<?php

namespace App\Services\Api\V2;

use App\{Composer, Piece};
use App\Services\Discover\Levels;
use App\Services\WebApp\PieceCards;
use Illuminate\Support\Str;

/** Explicit screen DTOs. Never serialize shared Eloquent models into v2. */
class DiscoverResponse
{
    public function data($rows): array
    {
        $pieces = collect($rows)->flatMap(function ($row) { return collect($row['content'])->filter(function ($item) { return $item instanceof Piece; }); });
        PieceCards::load($pieces, false);
        $sections = [];
        $levelsAdded = false;
        foreach ($rows as $row) {
            $title = $row['title'];
            if (Str::startsWith($title, 'Equivalent to ')) {
                if (!$levelsAdded && ($levels = $this->levels($rows))) $sections[] = $levels;
                $levelsAdded = true;
                continue;
            }
            $content = collect($row['content']);
            if ($content->isEmpty()) continue;
            $map = [
                'Free weekly pick' => ['free_pick', 'featured_piece'],
                'Composers' => ['composers', 'portrait_rail'],
                'Recently viewed' => ['recently_viewed', 'compact_piece_rail'],
                'Latest pieces' => ['latest_pieces', 'artwork_piece_rail'],
                'For you' => ['for_you', 'personalized_piece_rail'],
                'From women composers' => ['women_composers', 'composer_feature'],
                'From black composers' => ['black_composers', 'composer_feature'],
                'Past highlights' => ['past_highlights', 'highlight_rail'],
                "Like today's free pick" => ['similar_to_free_pick', 'compact_piece_rail'],
            ];
            $definition = $map[$title] ?? (Str::startsWith($title, 'Pieces that are ') ? ['mood', 'compact_piece_rail'] : null);
            if (!$definition) continue;
            [$id, $kind] = $definition;
            $section = ['id' => $id, 'kind' => $kind,
                'title' => $id === 'similar_to_free_pick' ? "Like this week's free pick" : ($id === 'black_composers' ? 'From Black composers' : $title),
                'items' => $content->map(function ($item) { return $item instanceof Composer ? $this->composer($item) : $this->piece($item); })->values()->all(),
                'action' => null, 'featured_composer' => null,
            ];
            if ($kind === 'composer_feature') $section['featured_composer'] = $this->composer($content->first()->composer);
            if ($id === 'composers') $section['action'] = $this->target('composers', route('webapp.composers.index'));
            if ($id === 'latest_pieces') $section['action'] = $this->target('catalogue', route('webapp.search.results', ['catalogue' => 1, 'search' => '']));
            if ($id === 'past_highlights') $section['action'] = $this->target('highlights', route('webapp.highlights'));
            $sections[] = $section;
        }
        return ['sections' => $sections];
    }

    private function piece(Piece $piece): array
    {
        return ['kind' => 'piece', 'id' => (string) $piece->id, 'title' => $piece->name,
            'featured_title' => $piece->collection_name === 'Sonata' ? $piece->getRawOriginal('name') : ($piece->nickname ?: $piece->getRawOriginal('name')),
            'subtitle' => $piece->attribution.$piece->composer->short_name, 'catalogue' => $piece->catalogue,
            'composer' => $this->composer($piece->composer), 'artwork_url' => $piece->discover_artwork_url ?? $piece->web_image_background,
            'difficulty' => $piece->extended_level_name, 'is_new' => (bool) $piece->is_new, 'is_free_pick' => (bool) $piece->is_free,
            // Keep the v2 compatibility key for retired community submissions.
            'media' => ['performance' => false, 'synthesia' => (bool) $piece->webapp_has_synthesia,
                'tutorial' => (bool) $piece->webapp_has_video, 'audio' => (bool) $piece->hasAudio(), 'score' => (bool) $piece->hasScore(true)],
            'target' => $this->target('piece', route('webapp.pieces.show', $piece), (string) $piece->id),
        ];
    }

    private function composer(Composer $composer): array
    {
        return ['kind' => 'composer', 'id' => (string) $composer->id, 'name' => $composer->name,
            'short_name' => $composer->short_name, 'portrait_url' => $composer->cover_image ?: asset('images/misc/placeholder-image.png'),
            'biography' => Str::limit(trim(strip_tags($composer->biography ?: $composer->curiosity ?: 'Explore this composer’s piano repertoire.')), 180),
            'piece_count' => (int) $composer->pieces_count,
            'target' => $this->target('composer_pieces', route('webapp.search.results', ['search' => $composer->name, 'model' => Composer::class]), (string) $composer->id),
        ];
    }

    private function levels($rows): ?array
    {
        $groups = Levels::groups($rows)->map(function ($group) {
            return ['id' => $group['key'], 'label' => $group['label'], 'items' => $group['cards']->map(function ($card) use ($group) {
                preg_match('/(\d+)\D*$/', $card->name, $match);
                return ['kind' => 'level', 'id' => $group['key'].'-'.($match[1] ?? $card->name), 'title' => $group['unit'].' '.($match[1] ?? ''),
                    'number' => (int) ($match[1] ?? 0), 'piece_count' => (int) $card->pieces_count,
                    'target' => $this->target('search', route('webapp.search.results', ['search' => $card->name]), null, $card->name)];
            })->all()];
        });
        $first = $groups->first(function ($group) { return count($group['items']) > 0; });
        return $first ? ['id' => 'levels', 'kind' => 'level_selector', 'title' => 'Find pieces at your level',
            'subtitle' => 'Similar difficulty to RCM, ABRSM or Suzuki books.', 'default_group' => $first['id'], 'groups' => $groups->all()] : null;
    }

    private function target(string $kind, string $url, ?string $id = null, ?string $query = null): array
    {
        return ['kind' => $kind, 'id' => $id, 'query' => $query, 'web_url' => $url];
    }
}
