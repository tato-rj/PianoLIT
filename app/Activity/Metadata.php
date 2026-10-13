<?php

namespace App\Activity;

class Metadata
{
    const QUERY_KEYS = ['search', 'catalogue', 'sort', 'filters', 'facets', 'level', 'mood', 'tag', 'period', 'technique', 'length', 'composers', 'country', 'video_only', 'score_only', 'audio_only', 'page', 'folder_id'];
    const BROWSER_KEYS = ['label', 'target', 'control', 'value', 'selected', 'filters', 'search', 'media_kind', 'media_id', 'position_seconds', 'duration_seconds', 'played_seconds', 'playback_rate', 'visible_ms', 'reason', 'navigation', 'section', 'piece_id', 'playback_id', 'unique_watched_percent', 'unique_watched_seconds'];

    public static function select(array $data, array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) $result[$key] = self::value($data[$key]);
        }
        return $result;
    }

    private static function value($value, $depth = 0)
    {
        if (is_array($value)) {
            if ($depth >= 3) return null;
            $result = [];
            foreach (array_slice($value, 0, 40, true) as $key => $item) {
                // Nested filter structures must not become a route for logging secrets.
                if (preg_match('/password|token|secret|email|authorization|card|cookie|annotation|marks|comment|description|notes|text/i', (string) $key)) continue;
                $result[mb_substr((string) $key, 0, 80)] = self::value($item, $depth + 1);
            }
            return $result;
        }
        if (is_string($value)) return mb_substr($value, 0, 300);
        return is_scalar($value) || $value === null ? $value : null;
    }

    public static function url($url)
    {
        $parts = parse_url((string) $url);
        if (!$parts || !in_array($parts['scheme'] ?? 'http', ['http', 'https'], true)) return null;
        $base = isset($parts['host']) ? ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : '';
        $path = preg_replace('#(/password/reset/)[^/]+#i', '$1[redacted]', $parts['path'] ?? '/');
        $path = preg_replace('#(/email/verify/)[^/]+/[^/]+#i', '$1[redacted]', $path);
        parse_str($parts['query'] ?? '', $query);
        $query = http_build_query(self::select($query, self::QUERY_KEYS));
        return mb_substr($base.$path.($query ? '?'.$query : ''), 0, 2000);
    }
}
