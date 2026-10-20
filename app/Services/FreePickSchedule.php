<?php

namespace App\Services;

use App\{FreePick, Piece};
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FreePickSchedule
{
    // Request-owned, scalar state: never cache Eloquent models or access grants.
    public function current()
    {
        $at = now('UTC')->toDateTimeString();
        $state = request()->attributes->get('pianolit.freepick');
        if (!$state || $at < $state['at'] || $at >= $state['refresh_at'] || ($state['next_at'] && $at >= $state['next_at'])) {
            // Resolve the current row and next boundary in one statement, including
            // an empty schedule. Ordinary clock ticks must not add queries per row.
            $due = FreePick::current()->select('id', 'piece_id', 'scheduled_at', 'activated_at')->limit(1);
            $next = FreePick::select('scheduled_at')->whereNull('retired_at')->where('scheduled_at', '>', now('UTC'))
                ->orderBy('scheduled_at')->limit(1);
            $pick = DB::query()->fromRaw('(SELECT 1 AS present) AS freepick_clock')
                ->leftJoinSub($due, 'due', function ($join) { $join->whereRaw('1 = 1'); })
                ->select('due.*')->selectSub($next, 'next_at')->first();
            $state = ['at' => $at, 'refresh_at' => now('UTC')->addSeconds(30)->toDateTimeString(), 'next_at' => $pick->next_at,
                'id' => $pick->id ? (int) $pick->id : null, 'piece_id' => $pick->piece_id ? (int) $pick->piece_id : null,
                'scheduled_at' => $pick->scheduled_at, 'activated' => $pick->activated_at !== null];
            request()->attributes->set('pianolit.freepick', $state);
        }
        return $state;
    }

    public function forgetCurrent()
    {
        request()->attributes->remove('pianolit.freepick');
    }

    public function schedule(Piece $piece, ?Carbon $at = null)
    {
        if (!$piece->hasImage()) {
            throw ValidationException::withMessages(['mode' => 'Save a cover image before scheduling this piece as the Freepick.']);
        }
        $future = $at !== null;
        $at = ($at ?: now())->copy()->utc();
        if ($future && $at->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['scheduled_at' => 'Choose a date and time in the future.']);
        }
        try {
            return DB::transaction(function () use ($piece, $at, $future) {
                // Unique reservations prevent two administrators booking the same instant.
                // Immediate selections use their deterministic date/ID order instead.
                $pick = FreePick::create([
                    'piece_id' => $piece->id, 'scheduled_at' => $at,
                    'reserved_at' => $future ? $at : null,
                ]);
                if (!$future) $this->syncHistory();
                return $pick;
            });
        } catch (QueryException $exception) {
            $this->forgetCurrent();
            if ($future && FreePick::where('reserved_at', $at)->exists()) {
                throw ValidationException::withMessages(['scheduled_at' => 'Another Freepick is already scheduled for this date and time. Choose a different time.']);
            }
            throw $exception;
        }
    }

    public function syncHistory()
    {
        // The selection itself is resolved by date on every request. This task only
        // maintains the legacy highlight date consumed by existing mobile clients.
        $count = 0;
        FreePick::whereNull('activated_at')->whereNull('retired_at')->where('scheduled_at', '<=', now('UTC'))
            ->chunkById(100, function ($picks) use (&$count) {
                foreach ($picks as $pick) {
                    DB::transaction(function () use ($pick, &$count) {
                        $locked = FreePick::whereKey($pick->id)->lockForUpdate()->first();
                        if (!$locked || $locked->activated_at) return;
                        DB::table('pieces')->where('id', $locked->piece_id)
                            ->where(function ($query) use ($locked) {
                                $query->whereNull('highlighted_at')->orWhere('highlighted_at', '<', $locked->scheduled_at->copy()->setTimezone(config('app.timezone')));
                            })->update(['highlighted_at' => $locked->scheduled_at->copy()->setTimezone(config('app.timezone'))]);
                        $locked->update(['activated_at' => $locked->scheduled_at]);
                        $count++;
                    });
                }
            });
        return $count;
    }
}
