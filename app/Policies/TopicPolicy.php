<?php

namespace App\Policies;

use App\Admin;
use App\Blog\Topic;
use Illuminate\Auth\Access\HandlesAuthorization;

class TopicPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view the topic.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Blog\Topic  $topic
     * @return mixed
     */
    public function view(Admin $admin, Topic $topic)
    {
        //
    }

    /**
     * Determine whether the admin can create topics.
     *
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function create(Admin $admin)
    {
        return $admin->role == 'manager';
    }

    /**
     * Determine whether the admin can update the topic.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Blog\Topic  $topic
     * @return mixed
     */
    public function update(Admin $admin, Topic $topic)
    {
        //
    }

    /**
     * Determine whether the admin can delete the topic.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Blog\Topic  $topic
     * @return mixed
     */
    public function delete(Admin $admin, Topic $topic)
    {
        //
    }

    /**
     * Determine whether the admin can restore the topic.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Blog\Topic  $topic
     * @return mixed
     */
    public function restore(Admin $admin, Topic $topic)
    {
        //
    }

    /**
     * Determine whether the admin can permanently delete the topic.
     *
     * @param  \App\Admin  $admin
     * @param  \App\Blog\Topic  $topic
     * @return mixed
     */
    public function forceDelete(Admin $admin, Topic $topic)
    {
        //
    }
}
