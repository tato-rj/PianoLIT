<?php

namespace App\Services\Api\V2;

use App\{Piece, User};

/** Native full-media rule. Deliberately independent of the browser Access Limits. */
class PieceAccess
{
    public function forUser(User $user, Piece $piece): array
    {
        // Local authoritative membership state only; never refresh Apple/Stripe here.
        $member = $user->hasActiveSubscription();
        $freePick = (bool) $piece->is_free;
        return [
            'full_media' => $member || $freePick,
            'basis' => $user->super_user ? 'super_user' : ($member ? 'membership' : ($freePick ? 'free_pick' : 'membership_required')),
        ];
    }
}
