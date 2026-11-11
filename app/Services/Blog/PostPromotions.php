<?php

namespace App\Services\Blog;

use App\Blog\Post;
use Illuminate\Support\Facades\DB;

/** Web-only article promotion settings; never adds attributes to shared post JSON. */
class PostPromotions
{
    const DEFAULT_CHOICE = 'default';
    const CHOICES = [
        'default' => 'Shop, Games and Learn',
        'shop' => 'Shop',
        'games' => 'Games',
        'learn' => 'Learn',
        'webapp' => 'Web app',
        'metronome' => 'Metronome',
        'none' => 'No promotion',
    ];

    private const HUBS = [
        'shop' => ['category' => 'Shop', 'title' => 'PianoLIT Shop', 'description' => 'Browse merchandise, eBooks and digital sheet music.', 'action' => 'Visit Shop', 'hubIcon' => 'shopping-basket', 'route' => 'shop.home', 'color' => 'cream'],
        'games' => ['category' => 'Games', 'title' => 'PianoLIT Games', 'description' => 'Play music games and test your knowledge.', 'action' => 'Visit Games', 'hubIcon' => 'trophy', 'route' => 'games.home', 'color' => 'lavender'],
        'learn' => ['category' => 'Learn', 'title' => 'PianoLIT Learn', 'description' => 'Find music tools, courses, infographics and quizzes.', 'action' => 'Visit Learn', 'hubIcon' => 'graduation-cap', 'route' => 'learn.home', 'color' => 'mint'],
        'webapp' => ['category' => 'Web app', 'title' => 'PianoLIT Web app', 'description' => 'Browse piano pieces, recordings, videos and scores.', 'action' => 'Visit Web app', 'hubIcon' => 'pianolit', 'route' => 'webapp.discover', 'color' => 'sky'],
        'metronome' => ['category' => 'Metronome', 'title' => 'PianoLIT Metronome', 'description' => 'Set a tempo, choose a sound and follow the beat.', 'action' => 'Open Metronome', 'hubIcon' => 'metronome', 'route' => 'metronome.home', 'color' => 'mint'],
    ];

    public function choiceFor(Post $post): string
    {
        $choice = DB::table('blog_post_promotions')->where('post_id', $post->id)->value('hub');
        return is_string($choice) && isset(self::CHOICES[$choice]) ? $choice : self::DEFAULT_CHOICE;
    }

    public function cards(string $choice): array
    {
        if ($choice === 'none') return [];
        $keys = isset(self::HUBS[$choice]) ? [$choice] : ['shop', 'games', 'learn'];
        return array_map(function ($key) {
            $hub = self::HUBS[$key];
            return $hub + ['url' => route($hub['route'])];
        }, $keys);
    }

    public function save(Post $post, string $choice): void
    {
        if (!isset(self::CHOICES[$choice])) throw new \InvalidArgumentException('Unknown post promotion.');
        DB::transaction(function () use ($post, $choice) {
            Post::whereKey($post->id)->lockForUpdate()->firstOrFail();
            DB::table('blog_post_promotions')->updateOrInsert(['post_id' => $post->id], ['hub' => $choice]);
        });
    }
}
