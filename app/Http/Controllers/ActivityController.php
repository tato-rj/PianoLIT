<?php

namespace App\Http\Controllers;

use App\Activity\Recorder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function store(Request $request, Recorder $recorder)
    {
        abort_if(!config('activity.enabled') || auth('admin')->check() || $request->getHost() === 'admin.'.config('app.short_url'), 403);
        if (\App\Activity\ExcludedAccounts::request($request)) return response()->noContent();
        if (\App\Activity\TrafficFilter::ignores($request)) return response()->noContent();
        abort_if(strlen($request->getContent()) > 100000, 413);
        $rules = [
            'page_token' => 'required|string|max:4096',
            'events' => 'required|array|min:1|max:'.config('activity.batch_size'),
            'events.*.id' => 'required|uuid|distinct',
            'events.*.type' => ['required', Rule::in(['page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_leave', 'page_restore', 'click', 'filter_change', 'filters_apply', 'search_submit', 'form_submit', 'section_open', 'section_close', 'tab_open', 'media_play', 'media_pause', 'media_end', 'media_seek', 'media_progress', 'media_error', 'media_rate', 'score_opened', 'score_progress', 'score_page', 'premium_restriction', 'premium_opened', 'registration_started', 'checkout_started', 'match_completed'])],
            'events.*.elapsed_ms' => 'required|integer|min:0|max:86400000',
            'events.*.sequence' => 'required|integer|min:1|max:1000000',
            'events.*.metadata' => 'sometimes|array',
            'events.*.metadata.label' => 'nullable|string|max:300',
            'events.*.metadata.target' => 'nullable|string|max:2000',
            'events.*.metadata.control' => 'nullable|string|max:300',
            'events.*.metadata.value' => 'nullable|string|max:300',
            'events.*.metadata.selected' => 'nullable|boolean',
            'events.*.metadata.filters' => 'nullable|array',
            'events.*.metadata.search' => 'nullable|string|max:300',
            'events.*.metadata.media_kind' => 'nullable|in:audio,video',
            'events.*.metadata.media_id' => 'nullable|string|max:300',
            'events.*.metadata.position_seconds' => 'nullable|numeric|min:0|max:604800',
            'events.*.metadata.duration_seconds' => 'nullable|numeric|min:0|max:604800',
            'events.*.metadata.played_seconds' => 'nullable|numeric|min:0|max:86400',
            'events.*.metadata.playback_rate' => 'nullable|numeric|min:0|max:16',
            'events.*.metadata.visible_ms' => 'nullable|integer|min:0|max:86400000',
            'events.*.metadata.reason' => 'nullable|string|max:100',
            'events.*.metadata.navigation' => 'nullable|string|max:100',
            'events.*.metadata.section' => 'nullable|string|max:300',
            'events.*.metadata.piece_id' => 'nullable|integer|min:1',
            'events.*.metadata.playback_id' => 'nullable|uuid',
            'events.*.metadata.attempt_id' => 'nullable|uuid',
            'events.*.metadata.unique_watched_percent' => 'nullable|numeric|min:0|max:100',
            'events.*.metadata.unique_watched_seconds' => 'nullable|numeric|min:0|max:604800',
        ];
        $rules['qualification'] = 'sometimes|array|max:16';
        $rules['qualification.*.page_token'] = 'required|string|max:4096';
        $rules['qualification.*.event'] = 'required|array';
        foreach ($rules as $key => $rule) if (strpos($key, 'events.*.') === 0) $rules[str_replace('events.*.', 'qualification.*.event.', $key)] = $rule;
        $data = $request->validate($rules);
        $page = $recorder->pageFromToken($request, $data['page_token']);
        abort_unless($page, 403);
        $parts = [['page' => $page, 'events' => $data['events']]];
        $qualification = [];
        foreach ($data['qualification'] ?? [] as $proof) {
            $context = $recorder->pageFromToken($request, $proof['page_token']);
            abort_unless($context && $context->visit_id === $page->visit_id, 403);
            $qualification[] = ['page' => $context, 'events' => [$proof['event']]];
        }
        $identities = [];
        foreach (array_merge($parts, $qualification) as $part) foreach ($part['events'] as $event) {
            if (isset($identities[$event['id']])) abort_unless($identities[$event['id']]['page'] === $part['page']->id && $identities[$event['id']]['event'] == $event, 422);
            $identities[$event['id']] = ['page' => $part['page']->id, 'event' => $event];
            abort_if(strlen(json_encode($event['metadata'] ?? [])) > 8000, 422);
            // Browser offsets are estimates, bounded by the page's actual age.
            abort_if($event['elapsed_ms'] > max(0, now('UTC')->diffInMilliseconds(\App\Activity\Time::date($part['page'], 'opened_at'))) + 60000, 422);
        }
        try {
            $stored = $recorder->browser($request, $page, $data['events'], $qualification);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['retry' => true], 503);
        }
        return $stored ? response()->noContent() : response()->json(['pending' => true], 202);
    }
}
