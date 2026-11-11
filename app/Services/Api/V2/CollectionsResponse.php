<?php

namespace App\Services\Api\V2;

use App\{Piece, Playlist, User};
use App\Services\Collections\Catalog;
use App\Services\WebApp\PieceCards;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CollectionsResponse
{
    public function __construct(private Catalog $catalog, private DiscoverResponse $discover)
    {
    }

    public function index(User $user): array
    {
        $entries = $this->catalog->entries();
        [$featuredId] = $this->catalog->weeklySelection($entries);
        $member = $user->hasActiveSubscription();

        return [
            'featured_collection_id' => $featuredId === null ? null : (string) $featuredId,
            'collections' => $entries->map(function (Playlist $playlist) use ($featuredId, $member) {
                return $this->card($playlist, $playlist->getKey() === $featuredId, $member);
            })->all(),
        ];
    }

    public function detail(string $id, User $user): array
    {
        $entries = $this->catalog->entries();
        $playlist = $entries->keyBy('id')->get($id);
        if (! $playlist) {
            throw (new ModelNotFoundException)->setModel(Playlist::class, [$id]);
        }

        [$featuredId] = $this->catalog->weeklySelection($entries);
        $card = $this->card($playlist, $playlist->getKey() === $featuredId, $user->hasActiveSubscription());

        return [
            'collection' => $card + ['description' => $this->text($playlist->description)],
            'pieces' => $card['access'] === 'available' ? $this->pieces($playlist) : [],
        ];
    }

    private function card(Playlist $playlist, bool $featured, bool $member): array
    {
        return [
            'id' => (string) $playlist->getKey(),
            'title' => $playlist->name,
            'subtitle' => $this->text($playlist->subtitle),
            'artwork_url' => $this->catalog->artwork($playlist),
            'piece_count' => (int) $playlist->pieces_count,
            'featured_badge' => $featured ? $this->catalog->badge($playlist) : null,
            'access' => $featured || $member ? 'available' : 'membership_required',
        ];
    }

    private function pieces(Playlist $playlist): array
    {
        $pieces = $playlist->playablePieces()->get();
        $pieces = PieceCards::load($pieces, false);
        $pieces->loadExists(['tutorials as collection_has_performance' => function ($query) {
            $query->byType('performance')->where('video_url', '!=', '');
        }]);

        return $pieces->map(function (Piece $piece) {
            $summary = $this->discover->piece($piece);
            $summary['media']['performance'] = (bool) $piece->collection_has_performance;
            return $summary;
        })->all();
    }

    private function text(?string $text): ?string
    {
        $text = trim((string) $text);
        return $text === '' ? null : $text;
    }
}
