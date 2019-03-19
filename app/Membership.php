<?php

namespace App;

use App\Traits\Apple;

class Membership extends PianoLit
{
	use Apple;
	
	protected $dates = ['renews_at', 'validated_at'];

	public function reactivate($receipt)
	{
		return $this->update([
			'plan' => $receipt->product_id,
			'renews_at' => carbon($receipt->expires_date)->timezone(config('app.timezone'))]);
	}

	public function validate($request)
	{
        $request = json_decode($request);

        if (empty($request->receipt))
        	abort(400, $this->appleError($request->status));

        $latest_receipt = end($request->latest_receipt_info);
        
        $is_valid = carbon($latest_receipt->expires_date)->setTimezone(config('app.timezone')) >= now();

        $this->update(['validated_at' => now()]);

        if (! $is_valid)
            return 'inactive';

        $this->reactivate($latest_receipt);

        return 'active';
	}
}
