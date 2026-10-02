<?php

namespace App\Http\Controllers\Admin;

use App\{Piece, PieceTimelineEvent};
use App\Http\Controllers\Controller;
use App\Services\Timeline\{TimelineDiscoverySession, WikimediaDiscovery};
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\TimelineEventForm;

class PieceTimelineController extends Controller
{
    public function edit(Piece $piece)
    {
        $events = $piece->timelineEvents()->chronological()->get();
        return view('admin.pages.pieces.timeline.edit', compact('piece', 'events'));
    }

    public function discover(Request $request, Piece $piece, WikimediaDiscovery $discovery, TimelineDiscoverySession $search)
    {
        return $search->discover($request, $discovery,
            $piece->timelineEvents()->get(['source_id', 'wikidata_id', 'event_kind', 'year']), $piece->id);
    }

    public function store(Request $request, Piece $piece, TimelineDiscoverySession $search)
    {
        $candidate = $search->candidate($request, $piece->id);
        // Only save content actually supplied by our discovery service, never submitted URLs/IDs.
        $event = $piece->timelineEvents()->where('source_id', $candidate['source_id'])->first();
        if (!$event) {
            try {
                $event = $piece->timelineEvents()->create($candidate);
            } catch (QueryException $e) {
                // The unique index also protects simultaneous Save requests from separate tabs.
                $event = $piece->timelineEvents()->where('source_id', $candidate['source_id'])->first();
                if (!$event) throw $e;
            }
        }
        return response()->json(['id' => $event->id, 'count' => $piece->timelineEvents()->count(), 'html' => view('admin.pages.pieces.timeline.saved', compact('piece', 'event'))->render()]);
    }

    public function update(TimelineEventForm $request, Piece $piece, PieceTimelineEvent $event)
    {
        abort_unless($event->piece_id === $piece->id, 404);
        $event->update($request->validated());
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
