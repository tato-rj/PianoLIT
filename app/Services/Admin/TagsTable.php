<?php

namespace App\Services\Admin;

use App\Tag;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagsTable
{
    const SORTS = [
        'type_asc' => 'Type · A–Z', 'type_desc' => 'Type · Z–A',
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'pieces_desc' => 'Pieces · most', 'pieces_asc' => 'Pieces · fewest',
        'ordering_asc' => 'Display order · first to last', 'ordering_desc' => 'Display order · last to first',
    ];

    public function types()
    {
        return collect(Tag::labels())->flatten()->mapWithKeys(function ($type) {
            return [$type => ucfirst($type)];
        })->sort()->all();
    }

    public function filters(Request $request)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'type_asc', [
            'tag_type' => ['nullable', 'string', Rule::in(array_keys($this->types()))],
        ]);
        return array_merge($filters, ['tag_type' => $filters['tag_type'] ?? '']);
    }

    public function query(array $filters)
    {
        $query = Tag::query()->with('creator:id,name')->withCount('pieces');
        if ($filters['tag_type'] !== '') $query->where('tags.type', $filters['tag_type']);
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw("LOWER(tags.name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(tags.type) LIKE ? ESCAPE '!'", [$like]);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        if ($sort === 'ordering') $query->orderByRaw('CASE WHEN tags.ordering IS NULL THEN 1 ELSE 0 END');
        $column = ['type' => 'tags.type', 'name' => 'tags.name', 'pieces' => 'pieces_count', 'ordering' => 'tags.ordering'][$sort];
        return $query->orderBy($column, $direction)->orderBy('tags.name')->orderBy('tags.id');
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters, 'type_asc');
        $tags = $this->query($filters)->paginate($filters['per_page'])
            ->appends(array_diff_key($returnFilters, ['page' => true]));
        $tagTypes = $this->types();
        return compact('filters', 'returnFilters', 'tags', 'tagTypes');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.tags.index', AdminTableFilters::query($filters, 'type_asc'));
    }
}
