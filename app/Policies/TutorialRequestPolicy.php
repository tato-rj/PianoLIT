<?php

namespace App\Policies;

use App\User;
use App\TutorialRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class TutorialRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the tutorial request.
     *
     * @param  \App\User  $user
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return mixed
     */
    public function view(User $user, TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Determine whether the user can create tutorial requests.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        // NOT BEING USED!
        
        if ($user->pendingTutorialRequests()->exists())
            return $this->deny('You have a pending request, please wait until we publish it before making a new one!');

        if ($user->publishedTutorialRequests()->where('piece_id', request()->piece_id)->exists())
            return $this->deny('Looks like you have already made a request for this piece, please send us an email if you were looking for something else.');

        return true;
    }

    /**
     * Determine whether the user can update the tutorial request.
     *
     * @param  \App\User  $user
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return mixed
     */
    public function update(User $user, TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Determine whether the user can delete the tutorial request.
     *
     * @param  \App\User  $user
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return mixed
     */
    public function delete(User $user, TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Determine whether the user can restore the tutorial request.
     *
     * @param  \App\User  $user
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return mixed
     */
    public function restore(User $user, TutorialRequest $tutorialRequest)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the tutorial request.
     *
     * @param  \App\User  $user
     * @param  \App\TutorialRequest  $tutorialRequest
     * @return mixed
     */
    public function forceDelete(User $user, TutorialRequest $tutorialRequest)
    {
        //
    }
}
