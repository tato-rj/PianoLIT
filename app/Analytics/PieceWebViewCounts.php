<?php

namespace App\Analytics;

use Illuminate\Support\Facades\DB;

class PieceWebViewCounts
{
    /** @return array<int, int> GA totals keyed by piece ID. Missing totals remain absent. */
    public function forPieces(array $ids): array
    {
        if (!$ids) return [];

        return DB::table('piece_web_view_counts')->whereIntegerInRaw('piece_id', $ids)
            ->pluck('views', 'piece_id')->map(fn ($views) => (int) $views)->all();
    }

    public function save(array $counts, string $through): int
    {
        if (!$counts) return 0;

        return DB::transaction(function () use ($counts, $through) {
            $updated = 0;
            foreach (array_chunk(array_keys($counts), 500) as $ids) {
                // A piece can be deleted while its GA report is running.
                $pieces = DB::table('pieces')->whereIntegerInRaw('id', $ids)->orderBy('id')->lockForUpdate()->pluck('id');
                $rows = $pieces->map(fn ($id) => [
                    'piece_id' => $id,
                    'views' => $counts[$id],
                    'reported_through' => $through,
                ])->all();
                if ($rows) {
                    DB::table('piece_web_view_counts')->upsert($rows, ['piece_id'], ['views', 'reported_through']);
                    $updated += count($rows);
                }
            }
            return $updated;
        }, 3);
    }
}
