<?php

namespace App\Billing;

use App\Billing\Sources\Apple;
use App\{PianoLit, User};
use App\Billing\Sources\Stripe;

class Membership extends PianoLit
{
	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function source()
    {
        return $this->morphTo();
    }

    public function scopeBySource($query, $source)
    {
    	return $query->where(['source_type' => get_class($source), 'source_id' => $source->id])->firstOrFail();
    }

    public function scopeHasSourceFor($query, $source, $user)
    {
    	return $query->where(['source_type' => $source, 'user_id' => $user->id])->exists();
    }

	public function scopeTrial($query)
	{
		return $query->whereHasMorph('source', '*', function($q) {
			$q->whereRaw('renews_at >= NOW() AND created_at >= renews_at - INTERVAL 7 DAY');
		});
	}

	public function scopeMember($query)
	{
		return $query->whereHasMorph('source', '*', function($q) {
			$q->whereRaw('renews_at >= NOW() AND created_at < renews_at - INTERVAL 7 DAY');
		});
	}

	public function scopeExpired($query)
	{
		return $query->has('user')->whereHasMorph('source', '*', function($q) {
			$q->whereRaw('renews_at < NOW()');
		});
	}
}
