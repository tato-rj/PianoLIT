<?php

namespace App\Policies;

use App\User;
use App\SynthesiaRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class SynthesiaRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the Synthesia request.
     *
     * @param  \App\User  $user
     * @param  \App\SynthesiaRequest  $synthesiaRequest
     * @return mixed
     */
    public function view(User $user, SynthesiaRequest $synthesiaRequest)
    {
        //
    }

    /**
     * Determine whether the user can create Synthesia requests.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        // NOT BEING USED!
        
        if ($user->pendingSynthesiaRequests()->exists())
            return $this->deny('You have a pending request, please wait until we publish it before making a new one!');

        if ($user->publishedSynthesiaRequests()->where('piece_id', request()->piece_id)->exists())
            return $this->deny('Looks like you have already made a request for this piece, please send us an email if you were looking for something else.');

        return true;
    }

    /**
     * Determine whether the user can update the Synthesia request.
     *
     * @param  \App\User  $user
     * @param  \App\SynthesiaRequest  $synthesiaRequest
     * @return mixed
     */
    public function update(User $user, SynthesiaRequest $synthesiaRequest)
    {
        //
    }

    /**
     * Determine whether the user can delete the Synthesia request.
     *
     * @param  \App\User  $user
     * @param  \App\SynthesiaRequest  $synthesiaRequest
     * @return mixed
     */
    public function delete(User $user, SynthesiaRequest $synthesiaRequest)
    {
        //
    }

    /**
     * Determine whether the user can restore the Synthesia request.
     *
     * @param  \App\User  $user
     * @param  \App\SynthesiaRequest  $synthesiaRequest
     * @return mixed
     */
    public function restore(User $user, SynthesiaRequest $synthesiaRequest)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the Synthesia request.
     *
     * @param  \App\User  $user
     * @param  \App\SynthesiaRequest  $synthesiaRequest
     * @return mixed
     */
    public function forceDelete(User $user, SynthesiaRequest $synthesiaRequest)
    {
        //
    }
}
