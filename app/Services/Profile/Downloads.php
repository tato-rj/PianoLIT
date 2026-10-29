<?php

namespace App\Services\Profile;

use App\Infograph\Infograph;
use App\Merchandise\Purchase;
use App\Shop\{eBook, eScore};
use App\User;

/** Browser library presentation only; original purchase and download contracts stay intact. */
class Downloads
{
    public const TYPES = ['ebooks' => eBook::class, 'escores' => eScore::class, 'infographics' => Infograph::class];
    public const LABELS = ['ebooks' => 'eBooks', 'escores' => 'eScores', 'infographics' => 'Infographics'];

    public function page(User $user, array $filters)
    {
        $query = $user->purchases()->with('item');
        if ($filters['type'] !== '') {
            $class = self::TYPES[$filters['type']];
            $query->where('item_type', (new $class)->getMorphClass());
        }
        if ($filters['q'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['q'])).'%';
            $query->whereHasMorph('item', array_values(self::TYPES), function ($item, $type) use ($like) {
                $column = $item->getModel()->qualifyColumn($type === Infograph::class ? 'name' : 'title');
                $item->whereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$like]);
            });
        }
        $direction = $filters['sort'] === 'oldest' ? 'asc' : 'desc';
        return $query->reorder()->orderBy('created_at', $direction)->orderBy('id', $direction)->paginate(8)->withQueryString();
    }

    public function card(Purchase $purchase): array
    {
        $item = $purchase->item;
        $image = null;
        if ($item) {
            // Use the existing preview rather than loading the full infographic poster.
            $image = $item instanceof Infograph
                ? ($item->cover_path ? $item->thumbnail_image() : null)
                : ($item->mockup_image() ?: ($item->cover_path ? $item->cover_image() : null));
            if ($image && !in_array(strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], true)) {
                $image = null;
            }
        }

        return [
            'title' => $item ? rtrim($item->title, '. ') : 'Resource unavailable',
            'type' => $purchase->type === 'Infograph' ? 'Infographic' : $purchase->type,
            'image' => $image,
            'links' => $item && !($item instanceof Infograph && !$item->cover_path) ? $item->links() : [],
        ];
    }
}
