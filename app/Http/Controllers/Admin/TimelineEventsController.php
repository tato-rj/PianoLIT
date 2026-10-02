<?php

namespace App\Http\Controllers\Admin;

use App\TimelineEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimelineEventForm;
use App\Services\Timeline\{TimelineDiscoverySession, WikimediaDiscovery};
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class TimelineEventsController extends Controller
{
    public function index()
    {
        $events = TimelineEvent::chronological()->get();
        return view('admin.pages.pieces.timeline.edit', ['events' => $events, 'isLibrary' => true]);
    }

    public function discover(Request $request, WikimediaDiscovery $discovery, TimelineDiscoverySession $search)
    {
        return $search->discover($request, $discovery,
            TimelineEvent::get(['source_id', 'wikidata_id', 'event_kind', 'year']));
    }

    public function store(Request $request, TimelineDiscoverySession $search, WikimediaDiscovery $discovery)
    {
        $candidate = $search->candidate($request);
        $existing = function () use ($candidate, $discovery) {
            return TimelineEvent::where('source_id', $candidate['source_id'])
                ->orWhere('source_identity', $discovery->identity($candidate))->first();
        };
        $event = $existing();
        if (!$event) {
            try {
                // Persist only the server-side candidate, never submitted content or URLs.
                $event = TimelineEvent::create($candidate);
            } catch (QueryException $e) {
                // Unique indexes protect saves racing across admin sessions/tabs.
                $event = $existing();
                if (!$event) throw $e;
            }
        }
        return response()->json(['id' => $event->id, 'count' => TimelineEvent::count(),
            'html' => view('admin.pages.pieces.timeline.saved', ['event' => $event, 'isLibrary' => true])->render()]);
    }

    public function update(TimelineEventForm $request, TimelineEvent $event)
    {
        $event->update($request->validated());
        return redirect()->route('admin.timeline-events.index')->with('status', 'The timeline event has been updated.');
    }

    public function destroy(Request $request, TimelineEvent $event)
    {
        $event->delete();
        if ($request->expectsJson()) {
            return response()->json(['id' => $event->id, 'source_id' => $event->source_id, 'count' => TimelineEvent::count()]);
        }
        return redirect()->route('admin.timeline-events.index')->with('status', 'The timeline event has been removed.');
    }
}
