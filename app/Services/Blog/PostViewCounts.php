<?php

namespace App\Services\Blog;

use App\Blog\Post;
use Illuminate\Support\Facades\DB;

/** Keeps public and admin view totals separate from shared Post attributes. */
class PostViewCounts
{
    /** @return array<int, int> Saved views keyed by published post ID. */
    public function forPosts(iterable $posts): array
    {
        $slugs = [];
        foreach ($posts as $post) {
            if ($post->isPublished()) $slugs[$post->id] = $post->slug;
        }
        if (!$slugs) return [];

        $counts = [];
        foreach (DB::table('blog_post_view_counts')->whereIn('post_id', array_keys($slugs))->get(['post_id', 'slug', 'views']) as $row) {
            if ($slugs[$row->post_id] === $row->slug) $counts[$row->post_id] = (int) $row->views;
        }
        return $counts;
    }

    public function save(array $slugs, array $paths, array $counts, string $through): int
    {
        if (!$counts) return 0;

        return DB::transaction(function () use ($slugs, $paths, $counts, $through) {
            $updated = 0;
            foreach (array_chunk(array_keys($slugs), 100) as $ids) {
                $rows = [];
                $posts = Post::published()->without('topics')->whereKey($ids)->orderBy('id')
                    ->lockForUpdate()->get(['id', 'slug']);
                foreach ($posts as $post) {
                    $count = $counts[$paths[$post->id]] ?? null;
                    // The post may have changed while Google was preparing the report.
                    if ($count === null || $post->slug !== $slugs[$post->id]) continue;

                    $rows[] = [
                        'post_id' => $post->id,
                        'slug' => $post->slug,
                        'views' => $count,
                        'reported_through' => $through,
                    ];
                }
                if ($rows) {
                    DB::table('blog_post_view_counts')->upsert($rows, ['post_id'], ['slug', 'views', 'reported_through']);
                    $updated += count($rows);
                }
            }
            return $updated;
        }, 3);
    }
}
