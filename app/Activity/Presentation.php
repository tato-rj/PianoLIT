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
        $safe = Metadata::select($data, array_merge(Metadata::BROWSER_KEYS, ['result', 'folder_id', 'annotation_count', 'created_count', 'edited_count', 'removed_count', 'revision', 'status', 'plan', 'identity_verified', 'request_id']));
        foreach ($safe as $key => $value) {
            if (is_array($value) && $key !== 'filters') unset($safe[$key]);
            if (in_array($key, ['visible_ms', 'played_seconds', 'unique_watched_percent', 'unique_watched_seconds'], true) && !is_numeric($value)) unset($safe[$key]);
        }
        return $safe;
    }

    public static function page($page, $piece = null)
    {
        if ($piece) return $piece->name;
        return PageIdentity::describe($page)->name;
    }

    public static function isPreviewPlayClick($event)
    {
        if ($event->type !== 'click') return false;
        $metadata = self::metadata($event);
        return ($metadata['control'] ?? '') === 'match_piece'
            && preg_match('/^Play preview(?:\s*:|$)/i', trim($metadata['label'] ?? '')) === 1;
    }

    public static function pieceSelectionId($event)
    {
        if ($event->type !== 'click') return null;
        $metadata = self::metadata($event);
        if (!in_array($metadata['control'] ?? '', ['a', 'div'], true)) return null;
        $target = parse_url($metadata['target'] ?? '');
        if (!$target || isset($target['user']) || isset($target['pass'])) return null;
        if (isset($target['scheme']) && !in_array(strtolower($target['scheme']), ['http', 'https'], true)) return null;
        $hosts = [parse_url(route('webapp.pieces.show', 1), PHP_URL_HOST), parse_url(config('app.stores.webapp'), PHP_URL_HOST)];
        if (isset($target['host']) && !in_array(strtolower($target['host']), $hosts, true)) return null;
        if (!preg_match('#^/pieces/([1-9][0-9]*)/?$#D', $target['path'] ?? '', $match)) return null;
        // Old collectors resolved empty/fragment links to the current page.
        // Those controls do not establish a selection of another piece.
        if (($target['path'] ?? '') === parse_url($metadata['navigation'] ?? '', PHP_URL_PATH)) return null;
        return filter_var($match[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    public static function action($event, $pieces = [])
    {
        $m = self::metadata($event);
        $requestKind = ($m['control'] ?? '') === 'synthesia-request-form' ? 'Synthesia' : 'tutorial';
        if (($event->source ?? '') === 'server' && $event->type === 'tutorial_request_created') return 'Received '.$requestKind.' request';
        if (($event->source ?? '') === 'server' && $event->type === 'tutorial_request_rejected') {
            $reasons = ['pending_request' => 'another request is pending', 'already_requested' => 'this piece was already requested', 'membership_required' => 'membership required'];
            return ucfirst($requestKind).' request not accepted: '.($reasons[$m['reason'] ?? ''] ?? 'request requirements were not met');
        }
        if ($event->type === 'form_submit') {
            return in_array($m['control'] ?? '', ['synthesia-request-form', 'tutorial-request-form'], true)
                ? 'Attempted to submit '.$requestKind.' request' : 'Attempted to submit a form';
        }
        if ($event->type === 'click' && strpos($m['label'] ?? '', 'Canceled ') === 0) return $m['label'];
        if (self::isPreviewPlayClick($event)) return 'Playback attempted';
        if ($event->type === 'click') {
            $control = $m['control'] ?? ''; $label = trim($m['label'] ?? '');
            if ($control === 'match_preference_confirm' || $label === 'I like this one') return 'Confirmed preferred piece';
            if ($control === 'match_level_confirm' || $label === 'This feels right') return 'Confirmed playing level';
            if ($control === 'match_level_piece') return 'Selected difficulty example';
            if ($control === 'match_piece' && !preg_match('/^(?:Play|Pause) preview(?:\s*:|$)/i', $label)) return 'Selected a piece';
            // Resolve navigation from its destination, not the entire card's
            // text or the piece belonging to the page the visitor is leaving.
            if ($pieceId = self::pieceSelectionId($event)) return 'Selected '.($pieces[$pieceId]->medium_name_with_composer ?? 'a piece');
        }
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

    public static function icon($type, array $metadata = [])
    {
        if (strpos($type, 'media_') === 0) return ['audio' => 'volume-2', 'video' => 'video'][$metadata['media_kind'] ?? ''] ?? 'circle-play';
        if (strpos($type, 'score') === 0 || $type === 'annotation_saved') return 'book-open';
        if ($type === 'favorite_changed') return 'heart';
        if (strpos($type, 'folder_') === 0) return 'folder-open';
        if (strpos($type, 'subscription') === 0 || strpos($type, 'premium') === 0 || $type === 'trial_started') return 'crown';
        return 'arrow-right';
    }
}
