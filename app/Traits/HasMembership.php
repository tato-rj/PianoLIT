<?php

namespace App\Traits;

trait HasMembership
{
    public function getStatus($callApple = false)
    {
        if (! $this->membership()->exists() && $this->trial_ends_at->gte(now()))
            return 'trial';

        if (! $this->membership()->exists() && $this->trial_ends_at->lt(now()))
            return 'expired';

        if (! $this->membership->expired())
            return 'active';

        if (! $callApple)
            return 'inactive';

        $request = $this->callApple($this->membership->latest_receipt, $this->membership->password);

        return $this->membership->validate($request);
    }
}
