<?php

namespace App\Services\Newsletter;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{DB, Route};
use Illuminate\Support\Str;

class PublishedContent
{
    // Read public content directly without changing shared/mobile model behavior.
    private const SOURCES = [
        'post' => ['posts', 'published_at', 'title', 'description', 'posts.show', 'Blog', 'Story'],
        'course' => ['crash_courses', 'published_at', 'title', 'description', 'crashcourses.show', 'Learn', 'Crash course'],
        'infographic' => ['infographs', 'published_at', 'name', 'description', 'resources.infographs.show', 'Learn', 'Infographic'],
        'quiz' => ['quizzes', 'published_at', 'title', 'description', 'quizzes.show', 'Learn', 'Quiz'],
        'pianist' => ['pianists', 'created_at', 'name', 'biography', 'resources.pianists.show', 'Learn', 'Pianist'],
        'ebook' => ['e_books', 'published_at', 'title', 'description', 'ebooks.show', 'Shop', 'eBook'],
        'escore' => ['e_scores', 'published_at', 'title', 'description', 'escores.show', 'Shop', 'eScore'],
    ];

    public function forMonth($month, array $keys = null)
    {
        $start = CarbonImmutable::parse($month.'-01', 'America/New_York')->startOfDay();
        $end = $start->addMonth();
        $items = collect();
        foreach (self::SOURCES as $type => [$table, $date, $title, $description, $route, $hub, $label]) {
            $query = DB::table($table)
                ->where($date, '>=', $start->setTimezone(config('app.timezone'))->toDateTimeString())
                ->where($date, '<', $end->setTimezone(config('app.timezone'))->toDateTimeString())
                ->where($date, '<=', now());
            if ($keys !== null) {
                $ids = collect($keys)->filter(function ($key) use ($type) {
                    return is_string($key) && preg_match('/\A'.preg_quote($type, '/').':([0-9]+)\z/', $key);
                })->map(function ($key) { return substr($key, strpos($key, ':') + 1); });
                $query->whereIn('id', $ids);
            }
            foreach ($query->get() as $record) {
                $items->push((object) [
                    'key' => $type.':'.$record->id, 'hub' => $hub, 'type' => $label,
                    'title' => $record->$title, 'description' => $this->summary($record->$description),
                    'url' => route($route, $record->slug), 'published_at' => $record->$date,
                ]);
            }
        }

        // Code-based launches have no database publication record. Their dated
        // release entries travel with the feature and are collected automatically.
        foreach (config('newsletter.releases', []) as $id => $release) {
            $key = 'release:'.$id;
            if ($keys !== null && !in_array($key, $keys, true)) continue;
            if (empty($release['published_at']) || empty($release['title']) || empty($release['route']) || !Route::has($release['route'])) continue;
            $published = CarbonImmutable::parse($release['published_at'], 'America/New_York');
            if ($published->lt($start) || $published->gte($end) || $published->isFuture()) continue;
            $items->push((object) [
                'key' => $key, 'hub' => $release['hub'] ?? 'PianoLIT', 'type' => $release['type'] ?? 'New feature',
                'title' => $release['title'], 'description' => $this->summary($release['description'] ?? ''),
                'url' => route($release['route'], $release['parameters'] ?? []),
                'published_at' => $published->setTimezone(config('app.timezone'))->toDateTimeString(),
            ]);
        }

        return $items->sortBy(function ($item) { return $item->published_at.' '.$item->key; }, SORT_STRING, true)->values();
    }

    private function summary($text)
    {
        return Str::limit(preg_replace('/\s+/u', ' ', trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'))), 240);
    }
}
