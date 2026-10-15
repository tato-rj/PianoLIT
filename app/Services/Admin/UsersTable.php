<?php

namespace App\Services\Admin;

use App\User;
use App\Billing\Sources\{Apple, Stripe};
use App\Support\AdminTableFilters;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class UsersTable
{
    const SORTS = [
        'date_desc' => 'Date joined · newest', 'date_asc' => 'Date joined · oldest',
        'id_desc' => 'User ID · highest', 'id_asc' => 'User ID · lowest',
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'origin_asc' => 'Origin · A–Z', 'origin_desc' => 'Origin · Z–A',
    ];

    public function get(Request $request)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS);
        $search = $filters['search'];
        // Explicit selection also removes User's default favorites count. Never
        // serialize these models: User appends query activity for every account.
        $query = User::select(['id', 'first_name', 'last_name', 'created_at', 'origin', 'email', 'email_verified_at', 'super_user']);

        foreach (preg_split('/\s+/u', $search) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                // SQL fields are fixed; submitted names never become query instructions.
                foreach (['created_at', 'id', 'first_name', 'last_name', 'email', 'origin'] as $column) {
                    $query->orWhereRaw("LOWER(users.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $sorts = ['date' => 'created_at', 'id' => 'id', 'name' => 'first_name', 'origin' => 'origin'];
        $users = $query->orderBy($sorts[$sort], $direction)->orderByDesc('id')
            ->with('location:id,user_id,countryCode')
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true])));

        // Super users' status is unconditional; their billing records are unused.
        // Other rows batch-load status fields only, omitting receipts and secrets.
        $users->getCollection()->where('super_user', false)->load([
            'membership:id,user_id,source_type,source_id',
            'membership.source' => function (MorphTo $source) {
                $source->constrain([
                    Stripe::class => function ($query) {
                        $query->select(['id', 'plan', 'status', 'renews_at', 'membership_ends_at', 'paused_at', 'ended_at']);
                    },
                    Apple::class => function ($query) {
                        $query->select(['id', 'plan', 'created_at', 'renews_at']);
                    },
                ]);
            },
        ]);

        return compact('users', 'filters');
    }
}
