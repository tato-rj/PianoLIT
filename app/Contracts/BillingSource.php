<?php

namespace App\Contracts;

use App\User;

interface BillingSource
{
	public function user();
	public function scopeSubscribe($query, User $user, $request);
	public function getPlanNameAttribute();
	public function getStatus($args = null);
	public function card();
	public function badge();
	public function hasCard();
	public function willRenew();
	public function isOnGracePeriod();
	public function isPaused();
	public function isExpired();
	public function isCanceled();
	public function isEnded();
}
