<?php

namespace App\Services\Admin;

use App\EmailCampaignReport;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class EmailReportsTable
{
    const SORTS = [
        'date_desc' => 'Sent · newest', 'date_asc' => 'Sent · oldest',
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'emails_desc' => 'Emails · most', 'emails_asc' => 'Emails · fewest',
        'delivered_desc' => 'Delivered · most', 'delivered_asc' => 'Delivered · fewest',
        'failed_desc' => 'Failed · most', 'failed_asc' => 'Failed · fewest',
        'opened_desc' => 'Opened · most', 'opened_asc' => 'Opened · fewest',
        'clicked_desc' => 'Clicked · most', 'clicked_asc' => 'Clicked · fewest',
    ];

    public function filters(Request $request)
    {
        return AdminTableFilters::fromRequest($request, self::SORTS);
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        $reports = $this->query($filters)->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true])));

        return compact('reports', 'filters', 'returnFilters');
    }

    public function query(array $filters)
    {
        $query = EmailCampaignReport::query();
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(list_id) LIKE ? ESCAPE '!'", [$like]);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $columns = ['date' => 'sent_at', 'name' => 'name', 'emails' => 'emails_count', 'delivered' => 'delivered_count',
            'failed' => 'failed_count', 'opened' => 'opens_count', 'clicked' => 'clicks_count'];

        return $query->orderBy($columns[$sort], $direction)->orderByDesc('id');
    }

    public function redirectAfterDeletion(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);

        return redirect()->route('admin.subscriptions.reports.index', AdminTableFilters::query($filters));
    }
}
