<?php

namespace App\Services\Blog;

use App\Blog\Post;
use Illuminate\Support\Facades\DB;

/** Web-only article promotion settings; never adds attributes to shared post JSON. */
class PostPromotions
{
    const DEFAULT_CHOICE = 'default';
    const HUB_CHOICES = [
        'shop' => 'Shop', 'games' => 'Games', 'learn' => 'Learn',
        'webapp' => 'Web app', 'metronome' => 'Metronome',
    ];
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
        'shop' => ['category' => 'Shop', 'title' => 'PianoLIT Shop', 'description' => 'Browse merchandise, eBooks and digital sheet music.', 'action' => 'Visit Shop', 'route' => 'shop.home', 'color' => 'cream'],
        'games' => ['category' => 'Games', 'title' => 'PianoLIT Games', 'description' => 'Play music games and test your knowledge.', 'action' => 'Visit Games', 'route' => 'games.home', 'color' => 'lavender'],
        'learn' => ['category' => 'Learn', 'title' => 'PianoLIT Learn', 'description' => 'Find music tools, courses, infographics and quizzes.', 'action' => 'Visit Learn', 'route' => 'learn.home', 'color' => 'mint'],
        'webapp' => ['category' => 'Web app', 'title' => 'PianoLIT Web app', 'description' => 'Browse piano pieces, recordings, videos and scores.', 'action' => 'Visit Web app', 'route' => 'webapp.discover', 'color' => 'sky'],
        'metronome' => ['category' => 'Metronome', 'title' => 'PianoLIT Metronome', 'description' => 'Set a tempo, choose a sound and follow the beat.', 'action' => 'Open Metronome', 'route' => 'metronome.home', 'color' => 'mint'],
    ];

    public function choiceFor(Post $post): string
    {
        $choice = DB::table('blog_post_promotions')->where('post_id', $post->id)->value('hub');
        return is_string($choice) && isset(self::CHOICES[$choice]) ? $choice : self::DEFAULT_CHOICE;
    }

    public function selectedFor(Post $post): array
    {
        $setting = DB::table('blog_post_promotions')->where('post_id', $post->id)->first();
        if ($setting && $setting->hubs !== null) {
            $selection = json_decode($setting->hubs, true);
            if (is_array($selection)) {
                try {
                    return $this->selection($selection);
                } catch (\InvalidArgumentException $exception) {
                    // Retain the saved legacy choice if stored JSON is invalid.
                }
            }
        }
        $choice = $setting->hub ?? self::DEFAULT_CHOICE;
        return $this->selection(isset(self::CHOICES[$choice]) ? $choice : self::DEFAULT_CHOICE);
    }

    public function selection($choices): array
    {
        if (is_string($choices) && isset(self::CHOICES[$choices])) {
            return $choices === 'none' ? [] : ($choices === self::DEFAULT_CHOICE ? ['shop', 'games', 'learn'] : [$choices]);
        }
        if (!is_array($choices)) throw new \InvalidArgumentException('Unknown post promotion.');
        foreach ($choices as $choice) {
            if (!is_string($choice) || !isset(self::HUB_CHOICES[$choice])) {
                throw new \InvalidArgumentException('Unknown post promotion.');
            }
        }
        return array_values(array_intersect(array_keys(self::HUB_CHOICES), $choices));
    }

    public function cards($choices): array
    {
        $keys = $this->selection($choices);
        return array_map(function ($key) {
            $hub = self::HUBS[$key];
            return $hub + [
                'url' => route($hub['route']),
                'image' => mix('images/home/editorial/'.$key.'.webp'),
            ];
        }, $keys);
    }

    public function save(Post $post, $choices): void
    {
        $selection = $this->selection($choices);
        $choice = is_string($choices) ? $choices : (!$selection ? 'none' : (count($selection) === 1 ? $selection[0] : self::DEFAULT_CHOICE));
        DB::transaction(function () use ($post, $choice, $choices, $selection) {
            Post::whereKey($post->id)->lockForUpdate()->firstOrFail();
            DB::table('blog_post_promotions')->updateOrInsert(['post_id' => $post->id], [
                'hub' => $choice, 'hubs' => is_array($choices) ? json_encode($selection) : null,
            ]);
        });
    }
}
