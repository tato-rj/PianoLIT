<?php

namespace App\Policies;

use App\{Admin, Composer};
use Illuminate\Auth\Access\HandlesAuthorization;

class ComposerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view the composer.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function view(Admin $admin, Composer $composer)
    {
        //
    }

    /**
     * Determine whether the admin can create composers.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function create(Admin $admin)
    {
        //
    }

    /**
     * Determine whether the admin can update the composer.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function update(Admin $admin, Composer $composer)
    {
        return $composer->creator_id == $admin->id;
    }

    /**
     * Determine whether the admin can delete the composer.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function delete(Admin $admin, Composer $composer)
    {
        //
    }

    /**
     * Determine whether the admin can restore the composer.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function restore(Admin $admin, Composer $composer)
    {
        //
    }

    /**
     * Determine whether the admin can permanently delete the composer.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function forceDelete(Admin $admin, Composer $composer)
    {
        //
    }
}
