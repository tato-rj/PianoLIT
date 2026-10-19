<?php

namespace App\Services\Admin;

use App\Subscription;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionsTable
{
    const SORTS = [
        'date_desc' => 'Subscribed · newest', 'date_asc' => 'Subscribed · oldest',
        'id_desc' => 'Subscriber ID · highest', 'id_asc' => 'Subscriber ID · lowest',
        'email_asc' => 'Email · A–Z', 'email_desc' => 'Email · Z–A',
        'origin_asc' => 'Origin · A–Z', 'origin_desc' => 'Origin · Z–A',
        'form_asc' => 'Form ID · A–Z', 'form_desc' => 'Form ID · Z–A',
    ];

    public function filters(Request $request)
    {
        return AdminTableFilters::fromRequest($request, self::SORTS);
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        // Resolve only the account ID for displayed subscribers. User models
        // have unrelated activity appends and a default favorites count.
        $subscriptions = $this->query($filters)->selectSub(
            DB::table('users')->select('id')->whereColumn('users.email', 'subscriptions.email')->orderBy('id')->limit(1),
            'account_id'
        )->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true])));

        return compact('subscriptions', 'filters', 'returnFilters');
    }

    public function query(array $filters)
    {
        $query = Subscription::select(['subscriptions.id', 'subscriptions.created_at', 'subscriptions.email', 'subscriptions.origin_url', 'subscriptions.form_id']);
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                foreach (['id', 'created_at', 'email', 'origin_url', 'form_id'] as $column) {
                    $query->orWhereRaw("LOWER(subscriptions.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $columns = ['date' => 'created_at', 'id' => 'id', 'email' => 'email', 'origin' => 'origin_url', 'form' => 'form_id'];

        return $query->orderBy('subscriptions.'.$columns[$sort], $direction)->orderByDesc('subscriptions.id');
    }

    public function redirectAfterDeletion(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);

        return redirect()->route('admin.subscriptions.index', AdminTableFilters::query($filters));
    }
}
