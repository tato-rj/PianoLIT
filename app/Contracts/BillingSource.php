<?php

namespace App\Contracts;

use App\User;

interface BillingSource
{
	public function scopeSubscribe($query, User $user, $request);
	public function getPlanNameAttribute();
	public function getStatus($args = null);
	public function card();
	public function badge();
	public function hasCard();
	public function willRenew();
	public function isActive();
	public function isPaying();
	public function isOnGracePeriod();
	public function isOnTrial();
	public function isPaused();
	public function isExpired();
	public function isCanceled();
	public function isEnded();
}
