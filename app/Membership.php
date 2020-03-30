<?php

namespace App;

use App\Traits\Apple;

class Membership extends PianoLit
{
	use Apple;
	
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

	public function scopeTrial($query)
	{
		return $query->whereRaw('renews_at >= NOW() AND created_at >= renews_at - INTERVAL 7 DAY');
	}

	public function scopeMember($query)
	{
		return $query->whereRaw('renews_at >= NOW() AND created_at < renews_at - INTERVAL 7 DAY');
	}
}
