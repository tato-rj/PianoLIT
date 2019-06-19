<?php

namespace App\Policies;

use App\{Admin, Pianist};
use Illuminate\Auth\Access\HandlesAuthorization;

class PianistPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view the pianist.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Pianist  $pianist
     * @return mixed
     */
    public function view(Admin $admin, Pianist $pianist)
    {
        //
    }

    /**
     * Determine whether the admin can create pianists.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function create(Admin $admin)
    {
        //
    }

    /**
     * Determine whether the admin can update the pianist.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Pianist  $pianist
     * @return mixed
     */
    public function update(Admin $admin, Pianist $pianist)
    {
        return $pianist->creator_id == $admin->id;
    }

    /**
     * Determine whether the admin can delete the pianist.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Pianist  $pianist
     * @return mixed
     */
    public function delete(Admin $admin, Pianist $pianist)
    {
        //
    }

    /**
     * Determine whether the admin can restore the pianist.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Pianist  $pianist
     * @return mixed
     */
    public function restore(Admin $admin, Pianist $pianist)
    {
        //
    }

    /**
     * Determine whether the admin can permanently delete the pianist.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Pianist  $pianist
     * @return mixed
     */
    public function forceDelete(Admin $admin, Pianist $pianist)
    {
        //
    }
}
