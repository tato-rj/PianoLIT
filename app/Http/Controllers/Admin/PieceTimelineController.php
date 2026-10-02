<?php

namespace App\Http\Controllers\Admin;

use App\{Piece, PieceTimelineEvent};
use App\Http\Controllers\Controller;
use App\Services\Timeline\WikimediaDiscovery;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PieceTimelineController extends Controller
{
    public function edit(Piece $piece)
    {
        $events = $piece->timelineEvents()->chronological()->get();
        return view('admin.pages.pieces.timeline.edit', compact('piece', 'events'));
    }

    public function discover(Request $request, Piece $piece, WikimediaDiscovery $discovery)
    {
        $data = $request->validate(['reference_year' => 'required|integer|between:1,9999', 'search_id' => 'nullable|uuid']);
        $year = (int) $data['reference_year'];
        $sessions = array_filter($request->session()->get('piece_timeline_searches', []), function ($state) {
            return $state['expires'] > time();
        });
        $id = $data['search_id'] ?? (string) Str::uuid();
        if (!empty($data['search_id']) && (!isset($sessions[$id]) || $sessions[$id]['piece_id'] !== $piece->id || $sessions[$id]['year'] !== $year)) {
            throw ValidationException::withMessages(['search_id' => 'This search has expired. Start a new search.']);
        }
        $state = $sessions[$id] ?? ['piece_id' => $piece->id, 'year' => $year, 'pool' => [], 'shown' => [], 'expires' => time() + 7200];
        // Existing open searches retain exclusions and any previously fetched candidates.
        $state += ['batch_index' => 0, 'has_more' => true];
        $saved = $piece->timelineEvents()->get(['source_id', 'wikidata_id', 'event_kind', 'year']);
        $excluded = array_merge(array_keys($state['shown']), $saved->pluck('source_id')->all());
        $identities = array_map([$discovery, 'identity'], array_merge(array_values($state['shown']), $saved->toArray()));
        $available = function ($pool) use ($excluded, $identities, $discovery) {
            return array_values(array_filter($pool, function ($event) use ($excluded, $identities, $discovery) {
                return !in_array($event['source_id'], $excluded, true) && !in_array($discovery->identity($event), $identities, true);
            }));
        };
        $pool = $available($state['pool']);
        $range = config('wikimedia.ranges')[0];
        try {
            for ($attempt = 0; count($pool) < 10 && $state['has_more'] && $attempt < 2; $attempt++) {
                $batch = $discovery->batch($year, $range, $state['batch_index']);
                $state['has_more'] = $batch['has_more'];
                $state['batch_index'] = $batch['next_batch'];
                $pool = collect(array_merge($pool, $available($batch['events'])))
                    ->sortBy('rank')->unique(function ($event) use ($discovery) { return $discovery->identity($event); })->values()->all();
                if (!$batch['complete']) break;
            }
            $selected = $discovery->select($pool);
            $candidates = $discovery->enrich($selected);
        } catch (\Throwable $e) {
            Log::warning('Timeline discovery failed', ['piece_id' => $piece->id, 'reference_year' => $year, 'exception_type' => get_class($e),
                'error_message' => Str::limit(preg_replace('/\s+for https?:\/\/\S+/s', '', $e->getMessage()), 1500, '')]);
            return response()->json(['message' => 'Wikimedia is unavailable right now. Please try again shortly.'], 503);
        }
        $selectedIds = array_column($selected, 'source_id');
        $state['pool'] = array_values(array_filter($pool, function ($event) use ($selectedIds) {
            return !in_array($event['source_id'], $selectedIds, true);
        }));
        foreach ($candidates as $candidate) $state['shown'][$candidate['source_id']] = $candidate;
        $state['expires'] = time() + 7200;
        // Bound session size and keep independent browser tabs/searches separate.
        unset($sessions[$id]);
        $sessions[$id] = $state;
        $request->session()->put('piece_timeline_searches', array_slice($sessions, -6, null, true));
        $more = count($state['pool']) > 0 || $state['has_more'];
        return response()->json([
            'search_id' => $id, 'count' => count($candidates), 'has_more' => $more,
            'range' => $range,
            'html' => view('admin.pages.pieces.timeline.candidates', compact('candidates'))->render(),
        ]);
    }

    public function store(Request $request, Piece $piece)
    {
        $data = $request->validate(['search_id' => 'required|uuid', 'source_id' => 'required|string|max:100']);
        $state = $request->session()->get('piece_timeline_searches.'.$data['search_id']);
        if (!$state || $state['expires'] <= time() || $state['piece_id'] !== $piece->id || !isset($state['shown'][$data['source_id']])) {
            throw ValidationException::withMessages(['source_id' => 'This candidate has expired. Find events again before saving.']);
        }
        $candidate = $state['shown'][$data['source_id']];
        // Only save content actually supplied by our discovery service, never submitted URLs/IDs.
        $event = $piece->timelineEvents()->where('source_id', $data['source_id'])->first();
        if (!$event) {
            try {
                $event = $piece->timelineEvents()->create($candidate);
            } catch (QueryException $e) {
                // The unique index also protects simultaneous Save requests from separate tabs.
                $event = $piece->timelineEvents()->where('source_id', $data['source_id'])->first();
                if (!$event) throw $e;
            }
        }
        return response()->json(['id' => $event->id, 'count' => $piece->timelineEvents()->count(), 'html' => view('admin.pages.pieces.timeline.saved', compact('piece', 'event'))->render()]);
    }

    public function update(Request $request, Piece $piece, PieceTimelineEvent $event)
    {
        abort_unless($event->piece_id === $piece->id, 404);
        $data = $request->validate([
            'year' => 'required|integer|between:1,9999',
            'event_date' => 'nullable|date_format:Y-m-d',
            'title' => 'required|string|max:255', 'description' => 'required|string|max:2000',
            'image_url' => 'nullable|url|starts_with:https://|max:2000',
            'image_source_url' => 'nullable|url|starts_with:https://|max:2000',
            'image_credit' => 'nullable|string|max:2000', 'image_license' => 'nullable|string|max:255',
            'image_license_url' => 'nullable|url|starts_with:https://|max:2000',
        ]);
        if (!empty($data['event_date']) && (int) substr($data['event_date'], 0, 4) !== (int) $data['year']) {
            throw ValidationException::withMessages(['event_date' => 'The date must be in the event year. Leave it blank for a year-only event.']);
        }
        $event->update($data);
        return redirect()->route('admin.pieces.timeline.edit', $piece)->with('status', 'The timeline event has been updated.');
    }

    public function destroy(Request $request, Piece $piece, PieceTimelineEvent $event)
    {
        abort_unless($event->piece_id === $piece->id, 404);
        $event->delete();
        if ($request->expectsJson()) {
            return response()->json(['id' => $event->id, 'source_id' => $event->source_id, 'count' => $piece->timelineEvents()->count()]);
        }
        return redirect()->route('admin.pieces.timeline.edit', $piece)->with('status', 'The timeline event has been removed.');
    }
}
