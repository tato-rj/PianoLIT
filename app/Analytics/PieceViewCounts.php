<?php

namespace App\Analytics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PieceViewCounts
{
    public function counts(string $platform = 'all', ?array $pieceIds = null): Builder
    {
        if ($platform === 'ios') return (new IosPieceViews)->counts($pieceIds);

        $web = DB::table('piece_web_view_counts')->select('piece_id', 'views as total');
        if ($pieceIds !== null) $web->whereIntegerInRaw('piece_id', $pieceIds);
        if ($platform === 'web') return $web;

        return DB::query()->fromSub($web->unionAll((new IosPieceViews)->counts($pieceIds)), 'platform_views')
            ->select('piece_id')->selectRaw('SUM(total) as total')->groupBy('piece_id');
    }

    /** @return array<int, int> Recorded totals keyed by piece ID. */
    public function forPieces(array $pieceIds): array
    {
        if (!$pieceIds) return [];

        return $this->counts('all', $pieceIds)->pluck('total', 'piece_id')->map(fn ($total) => (int) $total)->all();
    }
}
