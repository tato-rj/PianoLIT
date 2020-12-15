<?php

namespace App\Billing\Sources;

use App\Traits\Apple as AppleTrait;
use App\{PianoLit, User};
use App\Services\Apple\AppleValidator;
use App\Contracts\BillingSource;

class Apple extends PianoLit implements BillingSource
{
	use AppleTrait;
	
	protected $table = 'apple_memberships';
	protected $dates = ['renews_at', 'validated_at'];

	public function scopeSubscribe($query, User $user, $request)
	{
        $json = (new AppleValidator)->verify($request->receipt_data, $request->password);

        $response = json_decode($json);

        try {
            $latest_receipt = $response->receipt->in_app[0];

            $source = $this->create([
                'plan' => $latest_receipt->product_id,
                'latest_receipt' => $request->receipt_data,
                'latest_receipt_info' => json_encode($latest_receipt),
                'password' => $request->password,
                'renews_at' => carbon($latest_receipt->expires_date)->timezone(config('app.timezone')),
                'validated_at' => now()
            ]);

            $record = $user->membership()->create([
                'source_id' => $source->id,
                'source_type' => get_class($source)
            ]);
        } catch (\Exception $e) {
            $source = $this->create([
                'plan' => null,
                'latest_receipt' => $request->receipt_data,
                'latest_receipt_info' => null,
                'password' => $request->password,
                'renews_at' => null,
                'validated_at' => now()
            ]);
            
            $record = $user->membership()->create([
                'source_id' => $source->id,
                'source_type' => get_class($source)
            ]);
        }

        return $record;
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

		$latest_receipt = isset($request->latest_receipt_info['expires_date']) ? 
			$request->latest_receipt_info : null;

        if (! $latest_receipt) {
            $first = current($request->latest_receipt_info);
            $last = end($request->latest_receipt_info);

            $latest_receipt = ($first->is_trial_period == 'false') ? $first : $last;
        }

        $is_valid = carbon($latest_receipt->expires_date)->setTimezone(config('app.timezone')) >= now();

        $this->update(['validated_at' => now()]);

        if (! $is_valid)
            return 'inactive';

        $this->reactivate($latest_receipt);

        if ($latest_receipt->is_trial_period)
        	return 'trial';

        return 'active';
	}

    public function badge()
    {
        $status = $this->getStatus();

        switch ($status) {
            case 'trial':
                $color = 'warning';
                break;

            case 'active':
                $color = 'green';
                break;

            default:
                $color = 'secondary';
                break;
        }
        
        return '<span class="text-nowrap badge badge-pill alert-'.$color.'">'.strtoupper($status).'</span>';
    }

    public function card()
    {
        //
    }

    public function isOnTrial()
    {
        if ($this->isExpired())
            return false;

        $diff = $this->created_at->diffInDays($this->renews_at);

        return $diff <= 7;
    }

    public function isOnGracePeriod()
    {
        //
    }

    public function willRenew()
    {
        //
    }

    public function isPaused()
    {
        //
    }

    public function isEnded()
    {
        return ! $this->isActive();
    }

    public function isActive()
    {
        return in_array($this->getStatus(), ['active', 'trial']);        
    }

    public function hasCard()
    {
        //
    }

    public function isCanceled()
    {
        //
    }

	public function isExpired()
	{
		if (! $this->renews_at)
			return false;
		
		return ! now()->lte($this->renews_at);
	}

	public function getStatus($callApple = false)
	{
        if (! $this->isExpired())
            return 'active';

        if (! $callApple)
            return 'inactive';

        $request = (new AppleValidator)->verify($this->latest_receipt, $this->password);

        return $this->validate($request);
	}

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
