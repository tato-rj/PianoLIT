<?php

namespace App\Services\Api\V2;

use App\{Piece, Tutorial, User};
use App\Services\Discover\Artwork;
use App\Services\WebApp\PieceCards;

/** Allowlisted detail DTOs; shared Eloquent serialization remains untouched. */
class PieceResponse
{
    public function data(array $content, User $user): array
    {
        $piece = $content['piece'];
        $access = app(PieceAccess::class)->forUser($user, $piece);
        $media = $piece->media;
        $summary = app(DiscoverResponse::class);
        PieceCards::load(collect([$piece])->concat($content['synthesiaReleases']->pluck('piece')), false);
        $header = $summary->piece($piece);
        $header['title'] = $piece->medium_name;
        $header['artwork_url'] = Artwork::forPiece($piece);
        $header['composer'] = array_merge($header['composer'], [
            'biography' => (string) $piece->composer->biography,
            'lifespan' => $piece->composer->lifespan,
            'born_year' => $piece->composer->born_in,
            'died_year' => $piece->composer->died_in,
            'country' => $piece->composer->country ? [
                'id' => (string) $piece->composer->country->id,
                'name' => $piece->composer->country->name,
                'flag_code' => $piece->composer->country->flag_code,
            ] : null,
        ]);

        $scoreFile = $piece->isPublicDomain && $piece->hasScore(true);
        $pdfUrl = $scoreFile ? $this->url($piece->score) : null;
        $purchaseUrl = !$piece->isPublicDomain ? $this->url($piece->score_url) : null;
        $scoreMode = !$piece->isPublicDomain ? ($purchaseUrl ? 'external' : 'unavailable') : (!$pdfUrl ? 'unavailable' : ($access['full_media'] ? 'full' : 'blocked'));
        $audioMoments = collect(optional($piece->tutorials->first())->listeningMoments() ?: [])
            ->map(function ($moment) { return $this->moment($moment, false); })->values()->all();
        $recommendations = $content['recommendationRows']->map(function ($row) use ($summary) {
            return [
                'id' => $row['key'], 'title' => $row['title'], 'query' => $row['term'] ?? null,
                'web_url' => $this->url($row['url']),
                'items' => $row['pieces']->map(function ($card) use ($summary) { return $summary->piece($card); })->values()->all(),
            ];
        })->values()->all();

        return [
            'piece' => $header,
            'metadata' => [
                'name' => $piece->getRawOriginal('name'), 'short_name' => $piece->short_name,
                'long_name' => $piece->long_name, 'nickname' => $piece->nickname,
                'key' => $piece->key, 'collection_name' => $piece->collection_name,
                'collection_number' => $this->text($piece->collection_number), 'movement_number' => $this->text($piece->movement_number),
                'period' => optional($piece->period)->name, 'level' => $piece->level_name,
                'page_count_label' => $piece->length ? $piece->number_of_pages : null,
                'composed_year' => $this->year($piece->composed_in), 'published_year' => $this->year($piece->published_in),
                'tags' => $piece->tags->map(function ($tag) {
                    return ['id' => (string) $tag->id, 'name' => $tag->name, 'type' => $tag->type];
                })->values()->all(),
            ],
            'about' => [
                'description' => $piece->hasDescription() ? $piece->description : null,
                'description_fallback' => $piece->hasDescription() ? null : 'composer_biography',
                'for_whom' => $piece->period && $piece->extended_level_name ? $piece->for_who : null,
                'rankings' => $piece->rankings,
            ],
            'account' => [
                'is_favorited' => count(app(FavoriteFolders::class)->folderIds($user, $piece->id)) > 0,
                'favorite_writes_supported' => true, 'annotations_supported' => false,
                'history_path' => '/api/v2/pieces/'.$piece->id.'/view',
            ],
            'access' => $access,
            'media' => [
                'performance' => $media['performance'] ? $this->video($media['performance'], $access['full_media']) : null,
                'synthesia' => $media['synthesia'] ? $this->video($media['synthesia'], $access['full_media']) : null,
                'lessons' => collect($media['lessons'])->map(function ($group) use ($access) {
                    return ['title' => $group['title'], 'videos' => $group['videos']->map(function ($video) use ($access) {
                        return $this->video($video, $access['full_media']);
                    })->values()->all()];
                })->values()->all(),
                'audio' => [
                    'mode' => !$piece->hasAudio() ? 'unavailable' : ($access['full_media'] ? 'full' : 'blocked'),
                    'tracks' => collect(['full' => $piece->audio_path, 'right_hand' => $piece->audio_path_rh, 'left_hand' => $piece->audio_path_lh])
                        ->filter()->map(function ($path, $kind) use ($access) {
                            return ['kind' => $kind, 'url' => $access['full_media'] ? $this->url(storage($path)) : null];
                        })->values()->all(),
                    'moments' => $audioMoments,
                ],
                'score' => [
                    'mode' => $scoreMode, 'is_public_domain' => (bool) $piece->isPublicDomain,
                    'pdf_url' => $scoreMode === 'full' ? $pdfUrl : null,
                    'purchase_url' => $scoreMode === 'external' ? $purchaseUrl : null,
                    'version' => $scoreFile ? hash('sha256', $piece->score_path) : null,
                    'editor' => $piece->score_editor, 'publisher' => $piece->score_publisher, 'copyright' => $piece->score_copyright,
                ],
            ],
            'timeline' => [
                'period' => $content['timelinePeriod'],
                'events' => $content['timeline']->map(function ($event, $index) {
                    return [
                        'id' => isset($event['id']) ? 'event-'.$event['id'] : 'milestone-'.$index,
                        'year' => (int) $event['year'], 'date' => $event['event_date'] ?: null,
                        'title' => $event['title'], 'description' => $event['description'],
                        'is_piece_milestone' => (bool) $event['highlight'],
                        'composer_milestone' => $event['composer_milestone'] ?? null,
                        'image_url' => $this->url($event['image_url'] ?? null), 'source_url' => $this->url($event['source_url'] ?? null),
                        'attribution' => $event['attribution'] ?? null, 'image_credit' => $event['image_credit'] ?? null,
                        'image_source_url' => $this->url($event['image_source_url'] ?? null),
                        'image_license' => $event['image_license'] ?? null, 'image_license_url' => $this->url($event['image_license_url'] ?? null),
                    ];
                })->values()->all(),
            ],
            'recommendations' => $recommendations,
            'synthesia_releases' => $content['synthesiaReleases']->map(function ($video) use ($summary) {
                return ['id' => (string) $video->id, 'thumbnail_url' => $this->url($video->thumbnail), 'piece' => $summary->piece($video->piece)];
            })->values()->all(),
            'share_url' => route('webapp.pieces.show', $piece),
        ];
    }

    private function video(Tutorial $video, bool $allowed): array
    {
        $url = $this->url($video->video_url);
        return [
            'id' => (string) $video->id, 'title' => $video->title, 'category' => $video->category,
            'mode' => !$url ? 'unavailable' : ($allowed ? 'full' : 'blocked'),
            'url' => $allowed ? $url : null,
            'moments' => collect($video->listeningMoments())->map(function ($moment) { return $this->moment($moment); })->all(),
        ];
    }

    private function moment(array $moment, bool $comment = true): array
    {
        return ['id' => (string) $moment['id'], 'start_seconds' => (float) $moment['start_time'],
            'end_seconds' => $moment['end_time'] === null ? null : (float) $moment['end_time'],
            'title' => $moment['title'], 'comment' => $comment ? $moment['comment'] : null];
    }

    private function url(?string $url): ?string
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (isset($parts['user']) || isset($parts['pass'])) return null;
        return ($parts['scheme'] ?? '') === 'https' || (app()->environment(['local', 'testing']) && ($parts['scheme'] ?? '') === 'http') ? $url : null;
    }

    private function text($value): ?string { return $value === null || $value === '' ? null : (string) $value; }
    private function year($value): ?int { return is_numeric($value) && (int) $value == $value && $value >= 1 && $value <= 9999 ? (int) $value : null; }
}
