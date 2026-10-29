<?php

namespace App\Activity;

/** Compact presentation of recorded gestures, without deleting stored history. */
class JourneyActions
{
    const LIFECYCLE = ['page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_leave', 'page_restore'];

    public static function compact($events)
    {
        $events = $events->reject(function ($event) {
            return strpos($event->type, 'media_') === 0
                || $event->type === 'page_view' || in_array($event->type, self::LIFECYCLE, true)
                || ($event->type === 'request' && ($event->status ?? 0) < 400);
        });
        $outcomes = [];
        foreach ($events as $event) {
            $m = Presentation::metadata($event);
            if (($event->source ?? '') === 'server' && in_array($event->type, ['synthesia_request_created', 'synthesia_request_rejected', 'tutorial_request_created', 'tutorial_request_rejected'], true)
                && !empty($m['attempt_id']) && !empty($m['control'])) $outcomes[$m['control'].':'.$m['attempt_id']] = $event;
        }
        $display = [];
        foreach ($events as $event) {
            $m = Presentation::metadata($event);
            $outcome = $outcomes[($m['control'] ?? '').':'.($m['attempt_id'] ?? '')] ?? null;
            // A server-confirmed outcome supersedes its matching browser attempt
            // only on this page, for this form and this piece. Never infer success.
            if ($event->type === 'form_submit' && ($event->source ?? '') === 'browser' && $outcome
                && $outcome->resource_type === 'piece' && (string) $outcome->resource_id === (string) ($m['piece_id'] ?? '')) continue;
            $previous = $display ? $display[count($display) - 1] : null;
            if ($previous && self::sameGesture($previous, $event)) array_pop($display);
            $display[] = $event;
        }
        return collect($display);
    }

    private static function sameGesture($before, $after)
    {
        if (!$before->occurred_at || !$after->occurred_at || ($before->source ?? '') !== 'browser' || ($after->source ?? '') !== 'browser') return false;
        $gap = Time::event($after)->valueOf() - Time::event($before)->valueOf();
        if ($gap < 0 || $gap > 1000) return false;
        $a = Presentation::metadata($before); $b = Presentation::metadata($after);
        if ($before->type === $after->type && in_array($after->type, ['click', 'tab_open', 'section_open', 'section_close'], true)) return $gap <= 500 && $a === $b;
        if ($before->type !== 'click' || JourneyContexts::boundary($before)) return false;
        $label = trim($a['label'] ?? '');
        if (in_array($after->type, ['form_submit', 'search_submit'], true)) {
            if (!empty($b['label'])) return $label === trim($b['label']) && $label !== '';
            return $label === 'Submit my request' && in_array($b['control'] ?? '', ['synthesia-request-form', 'tutorial-request-form'], true);
        }
        $section = $b['section'] ?? '';
        if ($after->type === 'tab_open') {
            if (($a['section'] ?? '') === $section && $section !== '') return true;
            if ($label === 'Send my request' && $section === 'synthesia-request-modal') return true;
            return $label !== '' && $section !== '' && mb_strtolower($label) === mb_strtolower(str_replace(['tab-', '-'], ['', ' '], $section));
        }
        return ($a['control'] ?? '') === 'summary' && in_array($after->type, ['section_open', 'section_close'], true) && $label === $section;
    }
}
