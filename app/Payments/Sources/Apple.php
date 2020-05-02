<?php

namespace App\Payments\Sources;

use App\Traits\Apple as AppleTrait;
use App\{PianoLit, User};

class Apple extends PianoLit
{
	use AppleTrait;
	
	protected $table = 'apple_memberships';
	protected $dates = ['renews_at', 'validated_at'];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
	
	public function reactivate($receipt)
	{
		return $this->update([
			'plan' => $receipt->product_id,
			'renews_at' => carbon($receipt->expires_date)->timezone(config('app.timezone')),
			'latest_receipt_info' => json_encode($receipt)
		]);
	}

	public function validate($request)
	{
        $request = json_decode($request);

        if (empty($request->receipt) || empty($request->latest_receipt_info))
        	abort(400, $this->appleError($request->status));

		$latest_receipt = array_key_exists('expires_date', $request->latest_receipt_info) ? 
			$request->latest_receipt_info : 
			end($request->latest_receipt_info);

        $is_valid = carbon($latest_receipt->expires_date)->setTimezone(config('app.timezone')) >= now();

        $this->update(['validated_at' => now()]);

        if (! $is_valid)
            return 'inactive';

        $this->reactivate($latest_receipt);

        if ($latest_receipt->is_trial_period)
        	return 'trial';

        return 'active';
	}

	// public function scopeTrial($query)
	// {
	// 	return $query->whereRaw('renews_at >= NOW() AND created_at >= renews_at - INTERVAL 7 DAY');
	// }

	// public function scopeMember($query)
	// {
	// 	return $query->whereRaw('renews_at >= NOW() AND created_at < renews_at - INTERVAL 7 DAY');
	// }

	// public function scopeExpired($query)
	// {
	// 	return $query->has('user')->whereRaw('renews_at < NOW()');
	// }

	// public function scopeLastRenewed($query, $order = 'ASC')
	// {
	// 	return $query->orderBy('renews_at', $order);
	// }

	public function getNeedsValidationAttribute()
	{
		if (! $this->renews_at)
			return true;

		return now()->gt($this->renews_at) && $this->validated_at->lte($this->renews_at);
	}

	public function getPlanNameAttribute()
	{
		return ucfirst(str_rm(str_end($this->plan, '.'), 'plan') . 'ly');
	}

	public function getRenewalColorAttribute()
	{
		if ($this->planName == 'Monthly') {
			$diff = $this->renews_at->diffInDays(now());

			if ($diff > 10)
				return 'green';

			if ($diff <= 10 && $diff > 3)
				return 'warning';

			return 'danger';
		}

		if ($this->planName == 'Yearly') {
			$diff = $this->renews_at->diffInDays(now());

			if ($diff > 30)
				return 'green';

			if ($diff <= 30 && $diff > 7)
				return 'warning';

			return 'danger';
		}

		return null;
	}
}
