<?php

namespace App\Services\Admin;

use App\SynthesiaRequest;
use App\Support\AdminTableFilters;
use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SynthesiaRequestsTable
{
    const SORTS = [
        'date_desc' => 'Date requested · newest', 'date_asc' => 'Date requested · oldest',
        'piece_asc' => 'Piece · A–Z', 'piece_desc' => 'Piece · Z–A',
        'user_asc' => 'User · A–Z', 'user_desc' => 'User · Z–A',
        'published_desc' => 'Publication date · newest', 'published_asc' => 'Publication date · oldest',
    ];

    public function filters(Request $request)
    {
        return array_merge(['status' => ''], AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => ['nullable', 'string', Rule::in(['pending', 'published'])],
        ]));
    }

    public function query(array $filters)
    {
        $query = SynthesiaRequest::query()->select('synthesia_requests.*')
            ->leftJoin('pieces as p', 'p.id', '=', 'synthesia_requests.piece_id')
            ->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->leftJoin('users as u', 'u.id', '=', 'synthesia_requests.user_id');
        if (($filters['status'] ?? '') === 'pending') $query->whereNull('synthesia_requests.published_at');
        if (($filters['status'] ?? '') === 'published') $query->whereNotNull('synthesia_requests.published_at');
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                foreach (['p.name', 'p.catalogue_name', 'p.catalogue_number', 'c.name', 'u.first_name', 'u.last_name', 'u.email', 'synthesia_requests.created_at', 'synthesia_requests.published_at'] as $column) {
                    $query->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['date' => 'synthesia_requests.created_at', 'piece' => 'p.name', 'user' => 'u.first_name', 'published' => 'synthesia_requests.published_at'][$sort];
        return $query->orderBy($column, $direction)->orderByDesc('synthesia_requests.id');
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        $requests = $this->query($filters)->with([
            'piece.composer', 'user' => function ($query) {
                $query->select(['id', 'first_name', 'last_name', 'super_user'])->with([
                    'membership:id,user_id,source_type,source_id,created_at',
                    'membership.source' => function (MorphTo $source) {
                        $source->constrain([
                            Stripe::class => function ($query) {
                                $query->select(['id', 'plan', 'status', 'created_at', 'renews_at', 'membership_ends_at', 'paused_at', 'ended_at']);
                            },
                            Apple::class => function ($query) {
                                $query->select(['id', 'plan', 'created_at', 'renews_at']);
                            },
                        ]);
                    },
                ]);
            },
        ])->paginate($filters['per_page'])->appends(array_diff_key($returnFilters, ['page' => true]));
        return compact('filters', 'returnFilters', 'requests');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.synthesia-requests.index', AdminTableFilters::query($filters));
    }
}
