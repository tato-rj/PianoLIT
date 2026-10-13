<?php

namespace App\Activity;

class JourneyContexts
{
    public static function map($events)
    {
        $map = []; $sections = []; $active = null; $latest = null; $playbacks = [];
        foreach ($events as $event) {
            $metadata = Presentation::metadata($event);
            $boundary = self::boundary($event);
            $playback = strpos($event->type, 'media_') === 0 ? ($metadata['playback_id'] ?? null) : null;
            if ($boundary === 'open') $active = null;
            if ($boundary || self::belongs($event, $metadata)) {
                $key = $playback && isset($playbacks[$playback]) ? $playbacks[$playback] : $active;
                if (!$key && $boundary === 'close') $key = $latest;
                if (!$key) {
                    $key = 'match-'.count($sections);
                    $sections[$key] = ['key' => $key, 'title' => 'Find your match', 'event' => $event, 'opener' => null, 'closed' => null];
                }
                $map[$event->id] = $key;
                if ($boundary === 'open' || $boundary === 'shown') $sections[$key]['opener'] = $sections[$key]['opener'] ?: $event;
                if ($boundary === 'close') $sections[$key]['closed'] = $event;
                if ($playback) $playbacks[$playback] = $key;
                $latest = $key;
                $active = $boundary === 'close' ? null : $key;
            } elseif (!in_array($event->type, ['page_view', 'request', 'page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_leave', 'page_restore'], true)) {
                // Older history has no context on arbitrary buttons. Do not
                // assign unrelated actions merely because a tour opened earlier.
                $active = null;
            }
        }
        return ['events' => $map, 'sections' => $sections];
    }

    public static function nest($moments, array $contexts)
    {
        $display = []; $positions = [];
        foreach ($moments as $moment) {
            $key = $contexts['events'][$moment['event']->id] ?? null;
            if (!$key) { $display[] = $moment; continue; }
            if (!isset($positions[$key])) {
                $positions[$key] = count($display);
                $display[] = ['section' => $contexts['sections'][$key], 'event' => $contexts['sections'][$key]['event'], 'playback' => null, 'children' => collect()];
            }
            if (!self::boundary($moment['event'])) $display[$positions[$key]]['children']->push($moment);
        }
        return collect($display);
    }

    private static function belongs($event, array $metadata)
    {
        if (($metadata['section'] ?? '') === 'find-your-match' || $event->type === 'match_completed') return true;
        if (strpos($event->type, 'media_') === 0 && strpos($metadata['media_id'] ?? '', 'match-preview-') === 0) return true;
        if ($event->type !== 'click') return false;
        return in_array($metadata['control'] ?? '', ['match_piece', 'match_choice', 'match_level_piece', 'match_preference_confirm', 'match_level_confirm'], true)
            || in_array(trim($metadata['label'] ?? ''), ['I like this one', 'This feels right'], true);
    }

    public static function boundary($event)
    {
        $metadata = Presentation::metadata($event);
        $section = $metadata['section'] ?? '';
        $label = trim($metadata['label'] ?? '');
        if ($event->type === 'click' && (($metadata['control'] ?? '') === 'tour-button'
            || ($label === 'Find your match' && parse_url($metadata['target'] ?? '', PHP_URL_PATH) === '/tour'))) return 'open';
        if (($event->type === 'tab_open' && $section === 'match-tour-modal') || ($event->type === 'section_open' && $section === 'find-your-match')) return 'shown';
        if (($event->type === 'section_close' && $section === 'find-your-match') || ($event->type === 'click' && $label === 'Close Find your match')) return 'close';
        return null;
    }
}
