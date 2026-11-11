<?php

namespace App\Console\Commands;

use App\Analytics\GoogleAnalyticsPageViews;
use App\Blog\Post;
use App\Services\Blog\PostViewCounts;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

class SyncBlogPostViews extends Command
{
    public const TIMEZONE = 'America/New_York';

    protected $signature = 'analytics:sync-blog-views';
    protected $description = 'Refresh saved blog post view counts from Google Analytics';

    public function handle(GoogleAnalyticsPageViews $google, PostViewCounts $saved)
    {
        if (!$google->configured()) {
            $this->error('Google Analytics reporting is not configured.');
            return 1;
        }

        try {
            $result = Cache::lock('analytics:sync-blog-views', 1800)->get(function () use ($google, $saved) {
                $through = now(self::TIMEZONE)->subDay()->toDateString();
                $posts = Post::published()->without('topics')->orderBy('id')->get(['id', 'slug']);
                $paths = $posts->mapWithKeys(fn ($post) => [$post->id => route('posts.show', $post, false)])->all();

                $counts = $google->fetch('blog.pianolit.com', array_values($paths), '2026-10-21', $through);
                $updated = $saved->save($posts->pluck('slug', 'id')->all(), $paths, $counts, $through);
                $this->info("Updated {$updated} of {$posts->count()} published posts through {$through}.");
                if ($updated < $posts->count()) $this->warn('Posts without a current report kept their previous counts.');
                return 0;
            });
        } catch (\Throwable $error) {
            // Google exceptions can contain request details. Print only a safe diagnostic.
            $status = $error instanceof RequestException ? ' HTTP '.$error->response->status() : '';
            $this->error('Blog view sync failed. Saved counts were kept. '.class_basename($error).$status);
            return 1;
        }

        if ($result === false) {
            $this->info('Blog view sync is already running.');
            return 0;
        }
        return $result;
    }
}
