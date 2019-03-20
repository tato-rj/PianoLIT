<?php

namespace App\Policies;

use App\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the logged in admin can view the admin.
     *
     * @param  \App\Admin  $loggedAdmin
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function view(Admin $loggedAdmin, Admin $admin)
    {
        //
    }

    /**
     * Determine whether the logged in admin can create admins.
     *
     * @param  \App\Admin  $loggedAdmin
     * @return mixed
     */
    public function create(Admin $loggedAdmin)
    {
        return $loggedAdmin->isManager();
    }

    /**
     * Determine whether the logged in admin can update the admin.
     *
     * @param  \App\Admin  $loggedAdmin
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function update(Admin $loggedAdmin, Admin $admin)
    {
        return $loggedAdmin->email == $admin->email && $loggedAdmin->password == $admin->password;
    }

    /**
     * Determine whether the logged in admin can delete the admin.
     *
     * @param  \App\Admin  $loggedAdmin
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function delete(Admin $loggedAdmin, Admin $admin)
    {
        //
    }

    /**
     * Determine whether the logged in admin can restore the admin.
     *
     * @param  \App\Admin  $loggedAdmin
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function restore(Admin $loggedAdmin, Admin $admin)
    {
        //
    }

    /**
     * Determine whether the logged in admin can permanently delete the admin.
     *
     * @param  \App\Admin  $loggedAdmin
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function forceDelete(Admin $loggedAdmin, Admin $admin)
    {
        //
    }
}
