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
        abort_if(strlen($request->getContent()) > 100000, 413);
        $data = $request->validate([
            'page_token' => 'required|string|max:4096',
            'events' => 'required|array|min:1|max:'.config('activity.batch_size'),
            'events.*.id' => 'required|uuid|distinct',
            'events.*.type' => ['required', Rule::in(['page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_leave', 'page_restore', 'click', 'filter_change', 'filters_apply', 'search_submit', 'form_submit', 'section_open', 'section_close', 'tab_open', 'media_play', 'media_pause', 'media_end', 'media_seek', 'media_progress', 'media_error'])],
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
        ]);
        $page = $recorder->pageFromToken($request, $data['page_token']);
        abort_unless($page, 403);
        foreach ($data['events'] as $event) {
            abort_if(strlen(json_encode($event['metadata'] ?? [])) > 8000, 422);
            // Browser offsets are estimates, bounded by the page's actual age.
            abort_if($event['elapsed_ms'] > max(0, now()->diffInMilliseconds(\Carbon\Carbon::parse($page->opened_at))) + 60000, 422);
        }
        try {
            $recorder->browser($request, $page, $data['events']);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['retry' => true], 503);
        }
        return response()->noContent();
    }
}
