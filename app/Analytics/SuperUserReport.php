<?php

namespace App\Analytics;

use Illuminate\Support\Facades\{DB, Schema};

class SuperUserReport
{
    public function query()
    {
        $ready = Schema::hasTable('super_user_access_periods');
        $query = \App\Activity\ExcludedAccounts::users(DB::table('users as u'), 'u.email')->where('u.super_user', true);
        if ($ready) $query->leftJoin('super_user_access_periods as p', 'p.current_user_id', '=', 'u.id');
        $grantedAt = $ready ? 'p.granted_at' : 'NULL';

        // Keep the same projection as billing rows for the shared SQL directory.
        return $query->selectRaw("NULL as membership_id, {$grantedAt} as membership_started_at")
            ->addSelect(['u.id as user_id', 'u.first_name', 'u.last_name', 'u.email', 'u.super_user'])
            ->selectRaw("'super_user' as provider, 'active' as membership_status, NULL as plan, NULL as access_until")
            ->selectRaw("{$grantedAt} as checked_at, NULL as verified_at, NULL as next_check_at, NULL as error_code, NULL as environment, NULL as check_status")
            ->selectRaw("{$grantedAt} as source_updated_at");
    }
}
