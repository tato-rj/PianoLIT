<?php

namespace App\Policies;

use App\Admin;
use App\Piece;
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
}
