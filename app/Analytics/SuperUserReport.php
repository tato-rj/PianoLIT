<?php

namespace App\Analytics;

use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class SuperUserReport
{
    public function get(Request $request)
    {
        $filters = $request->validate([
            'super_search' => 'nullable|string|max:160',
            'super_sort' => 'nullable|in:name,granted,registered',
        ]);
        $ready = Schema::hasTable('super_user_access_periods');
        $query = DB::table('users as u')->where('u.super_user', true);
        if ($ready) {
            $query->leftJoin('super_user_access_periods as p', 'p.current_user_id', '=', 'u.id')
                ->leftJoin('admins as a', 'a.id', '=', 'p.granted_by');
        }
        $summary = (clone $query)->selectRaw($ready
            ? 'COUNT(*) as total, SUM(CASE WHEN p.granted_at IS NOT NULL THEN 1 ELSE 0 END) as dated'
            : 'COUNT(*) as total, 0 as dated')->first();
        if (! empty($filters['super_search'])) {
            $value = trim($filters['super_search']);
            $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
            $query->where(function ($q) use ($value, $escaped) {
                $q->whereRaw("u.email LIKE ? ESCAPE '!'", [$escaped])->orWhere(function ($names) use ($value) {
                    foreach (preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $part).'%';
                        $names->where(function ($name) use ($term) {
                            $name->whereRaw("u.first_name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("u.last_name LIKE ? ESCAPE '!'", [$term]);
                        });
                    }
                });
                if (ctype_digit($value)) $q->orWhere('u.id', (int) $value);
            });
        }
        $query->select(['u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.origin', 'u.created_at as registered_at'])
            ->selectRaw($ready ? 'p.granted_at, p.first_observed_at, a.name as granted_by_name' : 'NULL as granted_at, NULL as first_observed_at, NULL as granted_by_name');
        if (($filters['super_sort'] ?? 'name') === 'registered') $query->orderByDesc('u.created_at');
        elseif ($ready && ($filters['super_sort'] ?? 'name') === 'granted') $query->orderByDesc('p.granted_at');
        $query->orderBy('u.first_name')->orderBy('u.last_name')->orderBy('u.id');

        return [
            'superUsers' => $query->paginate(AdminPagination::PER_PAGE, ['*'], 'super_page')->withQueryString(),
            'superUserTotal' => (int) $summary->total, 'superUserDated' => (int) $summary->dated,
            'superUserFilters' => $filters, 'superUserTrackingReady' => $ready,
        ];
    }
}
