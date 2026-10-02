<?php

namespace App\Services\Timeline;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

// Shared admin discovery cursors; global and piece editors keep separate namespaces.
class TimelineDiscoverySession
{
    public function discover(Request $request, WikimediaDiscovery $discovery, $saved, ?int $pieceId = null)
    {
        $sessionKey = $this->sessionKey($pieceId);
        $data = $request->validate(['reference_year' => 'required|integer|between:1,9999', 'search_id' => 'nullable|uuid',
            'types' => 'sometimes|required|array|min:1|max:'.count(WikimediaDiscovery::TYPES),
            'types.*' => ['required', 'string', 'distinct', Rule::in(array_keys(WikimediaDiscovery::TYPES))]],
            ['types.required' => 'Choose at least one event type.']);
        $types = array_values(array_intersect(array_keys(WikimediaDiscovery::TYPES), $data['types'] ?? array_keys(WikimediaDiscovery::TYPES)));
        $year = (int) $data['reference_year'];
        $forwardOnly = $pieceId === null;
        $range = $forwardOnly ? config('wikimedia.library_range') : config('wikimedia.ranges')[0];
        $startYear = $forwardOnly ? $year : max(1, $year - $range);
        $endYear = min(9999, $year + $range);
        $sessions = array_filter($request->session()->get($sessionKey, []), function ($state) {
            return $state['expires'] > time();
        });
        $id = $data['search_id'] ?? (string) Str::uuid();
        if (!empty($data['search_id']) && (!isset($sessions[$id]) || $sessions[$id]['piece_id'] !== $pieceId || $sessions[$id]['year'] !== $year)) {
            throw ValidationException::withMessages(['search_id' => 'This search has expired. Start a new search.']);
        }
        $state = $sessions[$id] ?? ['piece_id' => $pieceId, 'year' => $year, 'pool' => [], 'shown' => [], 'expires' => time() + 7200];
        if (!empty($data['search_id']) && ($state['types'] ?? array_keys(WikimediaDiscovery::TYPES)) !== $types) {
            throw ValidationException::withMessages(['types' => 'The event types changed. Start a new search.']);
        }
        if (isset($data['types']) && !isset($state['types'])) {
            // Older cursors have unclassified pools. Retain shown exclusions, rebuild the pool.
            $state = array_merge($state, ['pool' => [], 'batch_index' => 0, 'has_more' => true,
                'periods' => ['before' => !$forwardOnly, 'after' => true]]);
        }
        $state['types'] = $types;
        if ($forwardOnly && ($state['window'] ?? null) !== 'forward-'.$range) {
            // Resume older searches with fresh forward-only pools but retain shown exclusions.
            $state = array_merge($state, ['window' => 'forward-'.$range, 'pool' => [], 'batch_index' => 0,
                'has_more' => true, 'periods' => ['before' => false, 'after' => true]]);
        }
        // Existing open searches retain exclusions and any previously fetched candidates.
        $state += ['batch_index' => 0, 'has_more' => true, 'periods' => ['before' => true, 'after' => true]];
        $excluded = array_merge(array_keys($state['shown']), $saved->pluck('source_id')->all());
        $identities = array_map([$discovery, 'identity'], array_merge(array_values($state['shown']), $saved->toArray()));
        $filterTypes = isset($data['types']);
        $available = function ($pool) use ($excluded, $identities, $discovery, $startYear, $endYear, $types, $filterTypes) {
            return array_values(array_filter($pool, function ($event) use ($excluded, $identities, $discovery, $startYear, $endYear, $types, $filterTypes) {
                return $event['year'] >= $startYear && $event['year'] <= $endYear
                    && (!$filterTypes || count(array_intersect($types, $event['types'] ?? [])) > 0)
                    && !in_array($event['source_id'], $excluded, true) && !in_array($discovery->identity($event), $identities, true);
            }));
        };
        $pool = $available($state['pool']);
        try {
            for ($attempt = 0; $discovery->needsCandidates($pool, $year, $state['periods']) && $state['has_more'] && $attempt < 2; $attempt++) {
                $batch = $discovery->batch($year, $range, $state['batch_index'], $forwardOnly);
                $state['has_more'] = $batch['has_more'];
                $state['periods'] = $batch['periods'];
                $state['batch_index'] = $batch['next_batch'];
                $pool = collect(array_merge($pool, $available($batch['events'])))
                    ->sortBy('rank')->unique(function ($event) use ($discovery) { return $discovery->identity($event); })->values()->all();
                if (!$batch['complete']) break;
            }
            $selected = $discovery->select($pool, 10, $year, $filterTypes ? $types : null);
            $candidates = $discovery->enrich($selected);
        } catch (\Throwable $e) {
            Log::warning('Timeline discovery failed', ['piece_id' => $pieceId, 'reference_year' => $year, 'exception_type' => get_class($e),
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
        $request->session()->put($sessionKey, array_slice($sessions, -6, null, true));
        $more = count($state['pool']) > 0 || $state['has_more'];
        $response = [
            'search_id' => $id, 'count' => count($candidates), 'has_more' => $more,
            'range' => $range,
            'html' => view('admin.pages.pieces.timeline.candidates', compact('candidates'))->render(),
        ];
        if ($forwardOnly) $response += ['start_year' => $startYear, 'end_year' => $endYear];
        return response()->json($response);
    }

    public function candidate(Request $request, ?int $pieceId = null): array
    {
        $data = $request->validate(['search_id' => 'required|uuid', 'source_id' => 'required|string|max:100']);
        $state = $request->session()->get($this->sessionKey($pieceId).'.'.$data['search_id']);
        if (!$state || $state['expires'] <= time() || $state['piece_id'] !== $pieceId || !isset($state['shown'][$data['source_id']])) {
            throw ValidationException::withMessages(['source_id' => 'This candidate has expired. Find events again before saving.']);
        }
        return $state['shown'][$data['source_id']];
    }

    private function sessionKey(?int $pieceId): string
    {
        return $pieceId === null ? 'timeline_event_searches' : 'piece_timeline_searches';
    }
}
