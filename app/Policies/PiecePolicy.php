<?php

namespace App\Policies;

use App\{Piece, User, Admin};
use Illuminate\Auth\Access\HandlesAuthorization;

class PiecePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can update the piece.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function update(Admin $admin, Piece $piece)
    {
        return $piece->creator_id == $admin->id;
    }

    public function perform(User $user, Piece $piece)
    {
        return ! auth()->user()->performances()->of($piece)->exists();// && ! auth()->user()->performances()->last30days()->exists();
    }
}
