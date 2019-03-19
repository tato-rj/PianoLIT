<?php

namespace App\Policies;

use App\Admin;
use App\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view the tag.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function view(Admin $admin, Tag $tag)
    {
        //
    }

    /**
     * Determine whether the admin can create tags.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function create(Admin $admin)
    {
        return $admin->role == 'manager';
    }

    /**
     * Determine whether the admin can update the tag.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function update(Admin $admin, Tag $tag)
    {
        //
    }

    /**
     * Determine whether the admin can delete the tag.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function delete(Admin $admin, Tag $tag)
    {
        //
    }

    /**
     * Determine whether the admin can restore the tag.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function restore(Admin $admin, Tag $tag)
    {
        //
    }

    /**
     * Determine whether the admin can permanently delete the tag.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function forceDelete(Admin $admin, Tag $tag)
    {
        //
    }
}
