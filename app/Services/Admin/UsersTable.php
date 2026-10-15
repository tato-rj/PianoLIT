<?php

namespace App\Services\Admin;

use App\User;
use App\Billing\Sources\{Apple, Stripe};
use App\Support\AdminPagination;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class UsersTable
{
    public function firstPage()
    {
        return $this->page(0, AdminPagination::PER_PAGE, '', [], 0);
    }

    public function response(Request $request)
    {
        $data = $request->validate([
            'draw' => 'sometimes|integer|min:0|max:2147483647',
            'start' => 'sometimes|integer|min:0|max:10000000',
            'length' => 'sometimes|integer|min:1|max:96',
            'search' => 'sometimes|array',
            'search.value' => 'nullable|string|max:255',
            'order' => 'sometimes|array|max:5',
            'order.*.column' => 'required|integer|in:0,1,2,3,5',
            'order.*.dir' => 'required|in:asc,desc',
            'columns' => 'sometimes|array|max:7',
            'columns.*' => 'required|array',
            'columns.*.data' => 'nullable|string|max:100',
            'columns.*.name' => 'nullable|string|max:100',
            'columns.*.search' => 'sometimes|array',
            'columns.*.search.value' => 'nullable|string|max:255',
        ]);

        return response()->json($this->page(
            (int) ($data['start'] ?? 0),
            (int) ($data['length'] ?? AdminPagination::PER_PAGE),
            trim($data['search']['value'] ?? ''),
            $data['order'] ?? [],
            (int) ($data['draw'] ?? 0)
        ));
    }

    protected function page($start, $length, $search, array $orders, $draw)
    {
        // Explicit selection also removes User's default favorites count. Never
        // serialize these models: User appends query activity for every account.
        $query = User::select(['id', 'first_name', 'last_name', 'created_at', 'origin', 'email_verified_at', 'super_user']);
        $total = (clone $query)->count();

        foreach (preg_split('/\s+/u', $search) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                // Retain the existing directory's searchable fields. Client
                // column names, search flags and regexes are never SQL inputs.
                foreach (['created_at', 'id', 'first_name', 'origin'] as $column) {
                    $query->orWhereRaw("LOWER(users.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        $filtered = $search === '' ? $total : (clone $query)->count();
        $sorts = [0 => 'created_at', 1 => 'id', 2 => 'first_name', 3 => 'origin', 5 => 'super_user'];
        foreach ($orders ?: [['column' => 0, 'dir' => 'desc']] as $order) {
            $query->orderBy($sorts[(int) $order['column']], $order['dir']);
        }
        $users = $query->orderByDesc('id')->skip($start)->take($length)
            ->with('location:id,user_id,countryCode')->get();

        // Super users' status is unconditional; their billing records are unused.
        // Other rows batch-load status fields only, omitting receipts and secrets.
        $users->where('super_user', false)->load([
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

        $rows = $users->map(function (User $item) {
            $row = [
                'id' => $item->id,
                'created_at' => $item->created_at
                    ? '<span class="invisible position-absolute">'.$item->created_at->timestamp.'</span>'.e($item->created_at->toFormattedDateString())
                    : '',
            ];
            foreach (['name' => 'name', 'origin_display' => 'origin', 'status' => 'status', 'super_user_display' => 'super-user', 'actions' => 'actions'] as $field => $view) {
                $row[$field] = view('admin.pages.users.table.'.$view, compact('item'))->render();
            }

            return $row;
        })->all();

        return ['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows];
    }
}
