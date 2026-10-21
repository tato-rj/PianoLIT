<?php

namespace App\Services\Admin;

use App\Pianist;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class PianistsTable
{
    const SORTS = [
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'born_asc' => 'Birth date · oldest', 'born_desc' => 'Birth date · youngest',
        'updated_desc' => 'Updated · newest', 'updated_asc' => 'Updated · oldest',
    ];

    public function filters(Request $request)
    {
        return array_merge(['country_id' => ''], AdminTableFilters::fromRequest($request, self::SORTS, 'name_asc', [
            'country_id' => 'nullable|integer|exists:countries,id',
        ]));
    }

    public function query(array $filters)
    {
        $query = Pianist::query();
        if (!empty($filters['country_id'])) $query->where('country_id', $filters['country_id']);
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw("LOWER(pianists.name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereHas('country', function ($country) use ($like) {
                        $country->whereRaw("LOWER(nationality) LIKE ? ESCAPE '!'", [$like]);
                    });
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['name' => 'name', 'born' => 'date_of_birth', 'updated' => 'updated_at'][$sort];
        return $query->orderBy($column, $direction)->orderBy('id');
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters, 'name_asc');
        $pianists = $this->query($filters)->paginate($filters['per_page'])->appends(array_diff_key($returnFilters, ['page' => true]));
        return compact('filters', 'returnFilters', 'pianists');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.pianists.index', AdminTableFilters::query($filters, 'name_asc'));
    }
}
