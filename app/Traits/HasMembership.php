<?php

namespace App\Traits;

use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};

trait HasMembership
{
    public function hasMembershipWith($source)
    {
        return Membership::hasSourceFor($source, $this);    
    }

    public function getStatus($callSource = false)
    {
        if ($this->super_user)
            return 'active';

        if (! $this->membership()->exists())
            return 'expired';

        return $this->membership->source->getStatus($callSource);
    }

    public function isAuthorized()
    {
        $status = $this->getStatus();

        return in_array($status, ['active', 'trial']);
    }
    
    public function scopeExpired($query)
    {
        $users = $query->has('membership')->get();

        foreach ($users as $index => $user) {
            if (! $user->membership->source->isExpired())
                $users->forget($index);
        }

        return $users;
    }

    public function getIsOnTrialAttribute()
    {
        if (! $this->membership()->exists() || $this->membership->source->isExpired())
            return false;

        $diff = $this->membership->source->created_at->diffInDays($this->membership->source->renews_at);

        return $this->membership->source->status == 'trialing' || $diff <= 7;
    }

    public function statusElements()
    {
        if ($this->super_user)
            return ['icon' => 'user-shield', 'color' => 'blue', 'label' => 'Super user'];

        if (! $this->membership)
            return ['icon' => 'ghost', 'color' => 'grey', 'label' => 'Guest'];

        if ($this->membership->source->isExpired())
            return ['icon' => 'credit-card', 'color' => 'grey', 'label' => 'Expired'];

        if ($this->membership->source->isPaused())
            return ['icon' => 'pause', 'color' => 'grey', 'label' => 'Paused'];

        if ($this->membership->source->isOnGracePeriod())
            return ['icon' => 'hourglass-half', 'color' => 'yellow', 'label' => 'Grace period'];

        if ($this->membership->source->isOnTrial())
            return ['icon' => 'credit-card', 'color' => 'yellow', 'label' => 'On trial'];

        return ['icon' => 'credit-card', 'color' => 'green', 'label' => 'Member (' . $this->membership->source->plan_name . ')'];
    }

    public function getRenewalColorAttribute()
    {
        if ($this->planName == 'Monthly') {
            $diff = $this->renews_at ? $this->renews_at->diffInDays(now()) : 0;

            if ($diff > 10)
                return 'green';

            if ($diff <= 10 && $diff > 3)
                return 'warning';

            return 'danger';
        }

        if ($this->planName == 'Yearly') {
            $diff = $this->renews_at ? $this->renews_at->diffInDays(now()) : 0;

            if ($diff > 30)
                return 'green';

            if ($diff <= 30 && $diff > 7)
                return 'warning';

            return 'danger';
        }

        return null;
    }

    public function isSuperUser()
    {
        return $this->super_user;
    }
}
