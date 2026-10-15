<?php

namespace App\Billing;

use App\Billing\Sources\Apple;
use App\{PianoLit, User};
use App\Billing\Sources\Stripe;
use Illuminate\Support\Facades\{DB, Schema};

class Membership extends PianoLit
{
    // Accounting lookup only: an archived association never grants access or
    // makes an ended source the account's current subscription again.
    public static function archivedOwnerForSource(string $type, int $sourceId): ?int
    {
        if (!in_array($type, [Stripe::class, Apple::class], true) || !Schema::hasTable('membership_link_archives')) return null;
        $owners = DB::table('membership_link_archives as a')->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.source_type', $type)->where('a.source_id', $sourceId)->distinct()->limit(2)->pluck('a.user_id');
        return $owners->count() === 1 ? (int) $owners->first() : null;
    }

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
