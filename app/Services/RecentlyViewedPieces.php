<?php

namespace App\Services;

use App\{Piece, User};
use Illuminate\Support\Facades\DB;

class RecentlyViewedPieces
{
    public function record(User $user, Piece $piece)
    {
        // A single row per account/piece also deduplicates concurrent visits.
        DB::table('recently_viewed_pieces')->upsert([
            'user_id' => $user->id,
            'piece_id' => $piece->id,
            'viewed_at' => now()->format('Y-m-d H:i:s.u'),
        ], ['user_id', 'piece_id'], ['viewed_at']);
    }

    public function forUser(User $user)
    {
        return Piece::select('pieces.*')
            ->join('recently_viewed_pieces as recent', 'recent.piece_id', '=', 'pieces.id')
            ->where('recent.user_id', $user->id)
            ->with('tags')
            ->orderByDesc('recent.viewed_at')
            ->orderByDesc('pieces.id')
            ->limit(12)
            ->get();
    }
}
