<?php

namespace App\Activity;

class Presentation
{
    public static function platform($platform)
    {
        return ['web' => 'Website', 'webapp' => 'Webapp', 'app' => 'iOS'][$platform] ?? 'Unknown platform';
    }

    public static function name($visit)
    {
        $name = trim(($visit->first_name ?? '').' '.($visit->last_name ?? ''));
        return $name ?: (!empty($visit->account_id) ? 'Deleted account #'.$visit->account_id : 'Anonymous visitor');
    }

    public static function metadata($event)
    {
        $data = json_decode($event->metadata ?: '{}', true);
        if (!is_array($data)) return [];
        $safe = Metadata::select($data, array_merge(Metadata::BROWSER_KEYS, ['result', 'folder_id', 'annotation_count', 'created_count', 'edited_count', 'removed_count', 'revision', 'status', 'plan', 'identity_verified']));
        foreach ($safe as $key => $value) {
            if (is_array($value) && $key !== 'filters') unset($safe[$key]);
            if (in_array($key, ['visible_ms', 'played_seconds', 'unique_watched_percent', 'unique_watched_seconds'], true) && !is_numeric($value)) unset($safe[$key]);
        }
        return $safe;
    }

    public static function page($page, $piece = null)
    {
        if ($piece) return $piece->name;
        $route = $page->route_name ?? '';
        if (!$route && empty($page->url)) return 'Page not recorded';
        $names = [
            'discover' => 'Discover', 'welcome' => 'Home', 'explore' => 'Explore', 'search' => 'Search',
            'favorites' => 'Favorites', 'folder' => 'Collection', 'playlists' => 'Collections',
            'highlights' => 'Highlights', 'composers' => 'Composers', 'pricing' => 'Premium',
            'checkout' => 'Subscription checkout', 'profile' => 'Profile', 'register' => 'Registration',
            'login' => 'Sign in', 'find-your-match' => 'Find your match', 'match' => 'Find your match',
        ];
        foreach ($names as $key => $title) if (strpos($route, $key) !== false) return $title;
        $path = parse_url($page->url ?? '', PHP_URL_PATH) ?: '/';
        if ($path === '/') return 'Home';
        return ucfirst(str_replace(['-', '_', '/'], [' ', ' ', ' / '], trim($path, '/')));
    }

    public static function action($event)
    {
        $m = self::metadata($event);
        if ($event->type === 'request' && ($event->status ?? 0) >= 400) return 'A request could not be completed (HTTP '.$event->status.')';
        $media = ($m['section'] ?? '') === 'synthesia' || strpos($m['media_id'] ?? '', 'synthesia') !== false ? 'Synthesia' : (($m['media_kind'] ?? '') === 'audio' ? 'audio' : 'performance video');
        $simple = [
            'favorite_changed' => !empty($m['folder_id']) ? (!empty($m['result']) ? 'Added piece to a folder' : 'Removed piece from a folder') : (!empty($m['result']) ? 'Added piece to Favorites' : 'Removed piece from Favorites'),
            'folder_created' => 'Created a folder', 'folder_updated' => 'Updated a folder', 'folder_renamed' => 'Renamed a folder', 'folder_deleted' => 'Deleted a folder',
            'annotation_saved' => 'Saved annotations', 'score_opened' => 'Opened digital score',
            'score_progress' => 'Viewed digital score', 'score_page' => 'Viewed score page '.($m['value'] ?? ''),
            'registration_started' => 'Opened registration', 'registration_completed' => 'Created an account', 'login' => 'Signed in',
            'premium_restriction' => 'Reached a preview limit', 'premium_opened' => 'Opened Premium options',
            'checkout_started' => 'Opened subscription checkout', 'trial_started' => 'Started a trial',
            'subscription_activated' => 'Subscription activated', 'subscription_canceled' => 'Scheduled subscription cancellation',
            'subscription_paid' => 'Subscription payment confirmed', 'match_completed' => 'Reached a Find your match result',
            'media_play' => 'Started '.$media, 'media_pause' => 'Paused '.$media, 'media_end' => 'Reached the end of '.$media,
            'media_seek' => 'Skipped to another position in '.$media, 'media_progress' => 'Played '.$media,
            'media_rate' => 'Changed playback speed', 'media_error' => 'Playback was interrupted',
            'filter_change' => 'Selected '.($m['value'] ?? 'a filter'), 'filters_apply' => 'Applied music filters',
            'search_submit' => 'Searched for music'.(!empty($m['search']) ? ': '.$m['search'] : ''),
            'click' => 'Selected '.($m['label'] ?? 'a navigation destination'),
            'section_open' => 'Opened '.($m['section'] ?? 'a section'), 'section_close' => 'Closed '.($m['section'] ?? 'a section'),
            'tab_open' => 'Opened '.str_replace(['tab-', '-'], ['', ' '], $m['section'] ?? 'a tab'),
            'form_submit' => 'Submitted a form', 'page_leave' => 'Page navigation or departure recorded',
        ];
        return $simple[$event->type] ?? 'Recorded activity';
    }

    public static function icon($type)
    {
        if (strpos($type, 'media_') === 0) return 'circle-play';
        if (strpos($type, 'score') === 0 || $type === 'annotation_saved') return 'book-open';
        if ($type === 'favorite_changed') return 'heart';
        if (strpos($type, 'folder_') === 0) return 'folder-open';
        if (strpos($type, 'subscription') === 0 || strpos($type, 'premium') === 0 || $type === 'trial_started') return 'crown';
        return 'arrow-right';
    }
}
