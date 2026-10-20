<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaylistOrder
{
    public function save(array $ids, ?array $original = null, ?string $group = null)
    {
        $ids = array_map('intval', $ids);
        $original = $original === null ? null : array_map('intval', $original);
        if (!$ids || count(array_unique($ids)) !== count($ids)) $this->invalid();
        DB::transaction(function () use ($ids, $original, $group) {
            $selected = DB::table('playlists')->whereIn('id', $ids)->get(['id', 'group']);
            if ($selected->count() !== count($ids) || $selected->pluck('group')->uniqueStrict()->count() !== 1) $this->invalid();
            $storedGroup = $selected->first()->group;
            if ($group !== null && $storedGroup !== ($group === 'journey' ? 'journey' : null)) $this->invalid();
            // Lock the complete group in a fixed order before applying its permutation.
            $rows = DB::table('playlists')->where('group', $storedGroup)->orderBy('id')->lockForUpdate()->get(['id', 'order']);
            $current = $rows->sort(function ($a, $b) { return ($a->order <=> $b->order) ?: ($a->id <=> $b->id); })->pluck('id')->map(function ($id) { return (int) $id; })->values()->all();
            $expected = $current; $submitted = $ids;
            sort($expected); sort($submitted);
            if ($expected !== $submitted || ($original !== null && $original !== $current)) $this->invalid();
            foreach ($ids as $position => $id) {
                DB::table('playlists')->where('id', $id)->update(['order' => $position, 'updated_at' => now()]);
            }
        }, 3);
    }

    private function invalid()
    {
        throw ValidationException::withMessages(['ids' => 'The playlist order has changed. Reload this page and arrange the current list again.'])->errorBag('playlistOrder');
    }
}
