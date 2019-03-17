<?php

namespace App\Policies;

use App\Admin;
use App\Piece;
use Illuminate\Auth\Access\HandlesAuthorization;

class PiecePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view the piece.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function view(Admin $admin, Piece $piece)
    {
        //
    }

    /**
     * Determine whether the admin can create pieces.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function create(Admin $admin)
    {
        //
    }

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

    /**
     * Determine whether the admin can delete the piece.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function delete(Admin $admin, Piece $piece)
    {
        //
    }

    /**
     * Determine whether the admin can restore the piece.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function restore(Admin $admin, Piece $piece)
    {
        //
    }

    /**
     * Determine whether the admin can permanently delete the piece.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function forceDelete(Admin $admin, Piece $piece)
    {
        //
    }
}
