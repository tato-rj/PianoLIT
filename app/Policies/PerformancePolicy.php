<?php

namespace App\Policies;

use App\{User, Performance};
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformancePolicy
{
    use HandlesAuthorization;

    public function update(User $user, Performance $performance)
    {
        return $user->id == $performance->user_id;
    }

    public function clap(User $user, Performance $performance)
    {
        return $user->id != $performance->user_id;
    }
}
