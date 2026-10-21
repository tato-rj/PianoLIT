<?php

namespace App\Services\Admin;

use App\Composer;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class ComposersTable
{
    const SORTS = [
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'pieces_desc' => 'Pieces · most', 'pieces_asc' => 'Pieces · fewest',
        'birth_asc' => 'Born · earliest', 'birth_desc' => 'Born · latest',
        'updated_desc' => 'Updated · newest', 'updated_asc' => 'Updated · oldest',
    ];

    public function filters(Request $request)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'name_asc', [
            'period' => 'nullable|string|max:100',
            'famous' => 'nullable|in:yes,no',
            'focus' => 'nullable|in:pedagogical,other',
        ]);
        return array_merge($filters, ['period' => $filters['period'] ?? '', 'famous' => $filters['famous'] ?? '', 'focus' => $filters['focus'] ?? '']);
    }

    public function query(array $filters)
    {
        $query = Composer::query();
        if (!empty($filters['period'])) $query->whereRaw('LOWER(composers.period) = ?', [mb_strtolower($filters['period'])]);
        if (!empty($filters['famous'])) $query->where('is_famous', $filters['famous'] === 'yes');
        if (!empty($filters['focus'])) $query->where('is_pedagogical', $filters['focus'] === 'pedagogical');
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw("LOWER(composers.name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereHas('country', function ($country) use ($like) {
                        $country->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("LOWER(nationality) LIKE ? ESCAPE '!'", [$like]);
                    });
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['name' => 'composers.name', 'pieces' => 'pieces_count', 'birth' => 'date_of_birth', 'updated' => 'composers.updated_at'][$sort];
        return $query->orderBy($column, $direction)->orderBy('composers.id');
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters, 'name_asc');
        $composers = $this->query($filters)->paginate($filters['per_page'])
            ->appends(array_diff_key($returnFilters, ['page' => true]));
        $periodOptions = Composer::without('country')->select('period')->distinct()->orderBy('period')->pluck('period')
            ->filter()->mapWithKeys(function ($period) { return [mb_strtolower($period) => $period]; })->all();

        return compact('composers', 'filters', 'returnFilters', 'periodOptions');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.composers.index', AdminTableFilters::query($filters, 'name_asc'));
    }
}
