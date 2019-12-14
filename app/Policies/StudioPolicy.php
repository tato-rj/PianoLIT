<?php

namespace App\Policies;

use App\User;
use App\StudioPolicy as Policy;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudioPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the studio policy.
     *
     * @param  \App\User  $user
     * @param  \App\StudioPolicy  $studioPolicy
     * @return mixed
     */
    public function view(User $user, Policy $studioPolicy)
    {
        //
    }

    /**
     * Determine whether the user can create studio policies.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the studio policy.
     *
     * @param  \App\User  $user
     * @param  \App\StudioPolicy  $studioPolicy
     * @return mixed
     */
    public function update(User $user, Policy $studioPolicy)
    {
        return $studioPolicy->user_id == $user->id;
    }

    /**
     * Determine whether the user can delete the studio policy.
     *
     * @param  \App\User  $user
     * @param  \App\StudioPolicy  $studioPolicy
     * @return mixed
     */
    public function delete(User $user, Policy $studioPolicy)
    {
        //
    }

    /**
     * Determine whether the user can restore the studio policy.
     *
     * @param  \App\User  $user
     * @param  \App\StudioPolicy  $studioPolicy
     * @return mixed
     */
    public function restore(User $user, Policy $studioPolicy)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the studio policy.
     *
     * @param  \App\User  $user
     * @param  \App\StudioPolicy  $studioPolicy
     * @return mixed
     */
    public function forceDelete(User $user, Policy $studioPolicy)
    {
        //
    }
}
