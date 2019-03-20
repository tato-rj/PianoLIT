<?php

namespace App\Policies;

use App\Admin;
use App\Membership;
use Illuminate\Auth\Access\HandlesAuthorization;

class MembershipPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view the membership.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Membership  $membership
     * @return mixed
     */
    public function view(Admin $admin, Membership $membership)
    {
        //
    }

    /**
     * Determine whether the admin can create memberships.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function create(Admin $admin)
    {
        //
    }

    /**
     * Determine whether the admin can validate memberships.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function validate(Admin $admin)
    {
        return $admin->isManager();
    }

    /**
     * Determine whether the admin can update the membership.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Membership  $membership
     * @return mixed
     */
    public function update(Admin $admin, Membership $membership)
    {
        //
    }

    /**
     * Determine whether the admin can delete the membership.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Membership  $membership
     * @return mixed
     */
    public function delete(Admin $admin, Membership $membership)
    {
        //
    }

    /**
     * Determine whether the admin can restore the membership.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Membership  $membership
     * @return mixed
     */
    public function restore(Admin $admin, Membership $membership)
    {
        //
    }

    /**
     * Determine whether the admin can permanently delete the membership.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Membership  $membership
     * @return mixed
     */
    public function forceDelete(Admin $admin, Membership $membership)
    {
        //
    }
}
